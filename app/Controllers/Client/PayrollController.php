<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\PayrollModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\PayrollSettingsModel;
use App\Models\Client\PayrollTransactionModel;
use App\Services\Client\PayrollComputationService;
use App\Services\Client\AnnouncementService;
use App\Models\Client\AttendanceModel;
use App\Services\Client\LoanService;;
use App\Services\Client\AttendanceComputationService;

use Dompdf\Dompdf;
use Dompdf\Options;

class PayrollController extends BaseController
{
    protected $helpers = ['url', 'form'];

    // ================= PAYROLL STATUS TAB =================

    /**
     * Compute actual period_start / period_end dates for a given month and half,
     * respecting the cross-month Period Definition settings.
     * Returns [periodStart, periodEnd] strings (Y-m-d), or [null, null] for monthly.
     */
    /**
     * A payslip belongs to the cutoff it was actually processed for, not
     * whichever calendar month its period_start happens to fall in — a
     * 26th-to-10th cutoff (like this app's default) starts in the PREVIOUS
     * calendar month, so "Full Month: September" grouping by
     * DATE_FORMAT(period_start,'%Y-%m') wrongly caught (and Export CSV wrongly
     * exported) a Sep 26 – Oct 10 payslip under September instead of October.
     *
     * For '1st_half'/'2nd_half' this returns an exact-date SQL filter (as
     * before). For "Full Month" ('' / 'monthly'), it returns a WIDE SQL
     * window (a cutoff can span at most ~2 weeks across a month boundary) —
     * callers must still filter the fetched rows precisely in PHP with
     * filterRowsBySalaryMonth(), since SQL alone can't apply the cross-month
     * cutoff rule.
     *
     * @return array{0: string, 1: array<int, mixed>, 2: ?string} [$whereSql, $binds, $salaryMonthToFilterInPhp]
     */
    private function periodSqlFilter(string $month, string $period, string $column = 'ps.period_start', string $columnEnd = 'ps.period_end'): array
    {
        if ($period === '1st_half' || $period === '2nd_half') {
            $psModel = new \App\Models\Client\PayrollSettingsModel();
            [$pStart, $pEnd] = $this->computeStatusPeriodDates($month, $period, $psModel);
            return ["{$column} = ? AND {$columnEnd} = ?", [$pStart, $pEnd], null];
        }

        [$y, $m] = array_map('intval', explode('-', $month));
        $windowStart = sprintf('%04d-%02d-01', $m === 1 ? $y - 1 : $y, $m === 1 ? 12 : $m - 1);
        $nm = $m === 12 ? 1 : $m + 1;
        $ny = $m === 12 ? $y + 1 : $y;
        $windowEnd = date('Y-m-t', mktime(0, 0, 0, $nm, 1, $ny));

        return ["{$column} BETWEEN ? AND ?", [$windowStart, $windowEnd], $month];
    }

    /** Filters an array of rows (each with 'period_start'/'period_end' keys) down to the ones whose cutoff belongs to $salaryMonth. */
    private function filterRowsBySalaryMonth(array $rows, ?string $salaryMonth): array
    {
        if ($salaryMonth === null) {
            return $rows;
        }
        return array_values(array_filter($rows, static function ($row) use ($salaryMonth) {
            $start = $row['period_start'] ?? null;
            $end   = $row['period_end'] ?? null;
            if (!$start) {
                return false;
            }
            // A Monthly-type row's own cutoff can be configured independently
            // of half1/half2 (see PayrollCutoff::monthlyRange()), so check it
            // first — forDate() only knows about the two halves and would
            // otherwise misattribute the salary month for it.
            if ($end) {
                $monthly = \App\Libraries\PayrollCutoff::matchMonthlyPeriod((string) $start, (string) $end);
                if ($monthly !== null) {
                    return $monthly['salary_month'] === $salaryMonth;
                }
            }
            return \App\Libraries\PayrollCutoff::forDate((string) $start)['salary_month'] === $salaryMonth;
        }));
    }

    /**
     * Whether a (start,end) payslip period matches a real cutoff — either
     * half of its salary month per current Payroll Settings, or the configured
     * Monthly cutoff (full calendar month by default, or a custom range — see
     * PayrollCutoff::monthlyRange()) — or is "non-standard" (doesn't match any
     * configured cutoff shape, e.g. an ad-hoc final-pay period for someone who
     * left mid-cutoff). end < start is always invalid; it's never something
     * staff can confirm past, since it can't represent a real span of days
     * worked.
     *
     * @return array{valid: bool, standard: bool, reason?: string}
     */
    private function classifyPayslipPeriod(string $start, string $end): array
    {
        if ($start > $end) {
            return ['valid' => false, 'standard' => false, 'reason' => "The end date ({$end}) is before the start date ({$start})."];
        }

        $cutoff = \App\Libraries\PayrollCutoff::forDate($start);
        if ($cutoff['start'] === $start && $cutoff['end'] === $end) {
            return ['valid' => true, 'standard' => true];
        }

        if (\App\Libraries\PayrollCutoff::matchMonthlyPeriod($start, $end) !== null) {
            return ['valid' => true, 'standard' => true];
        }

        return [
            'valid'    => true,
            'standard' => false,
            'reason'   => "This period ({$start} to {$end}) doesn't match a configured cutoff (expected {$cutoff['start']} to {$cutoff['end']} for this start date, or the configured Monthly cutoff). "
                . "If this is intentional — e.g. a final pay for someone who left mid-cutoff — confirm to save it anyway.",
        ];
    }

    private function computeStatusPeriodDates(string $month, string $periodType, \App\Models\Client\PayrollSettingsModel $psModel): array
    {
        if ($periodType === 'monthly') {
            $range = \App\Libraries\PayrollCutoff::monthlyPeriodForSalaryMonth($month);
            return [$range['start'], $range['end']];
        }

        [$y, $m] = array_map('intval', explode('-', $month));

        // Cross-month definition takes priority
        $h1SD = (int)($psModel->getSetting('half1_start_day') ?? 0);
        $h1ED = (int)($psModel->getSetting('half1_end_day')   ?? 0);
        $h2SD = (int)($psModel->getSetting('half2_start_day') ?? 0);
        $h2ED = (int)($psModel->getSetting('half2_end_day')   ?? 0);

        if ($h1SD > 0 && $h1ED > 0 && $h2SD > 0 && $h2ED > 0) {
            $h1SO = (int)($psModel->getSetting('half1_start_offset') ?? 0);
            $h1EO = (int)($psModel->getSetting('half1_end_offset')   ?? 0);
            $h2SO = (int)($psModel->getSetting('half2_start_offset') ?? 0);
            $h2EO = (int)($psModel->getSetting('half2_end_offset')   ?? 0);

            if ($periodType === '1st_half') {
                return [
                    $this->buildPeriodDate($y, $m, $h1SD, $h1SO),
                    $this->buildPeriodDate($y, $m, $h1ED, $h1EO),
                ];
            }
            return [
                $this->buildPeriodDate($y, $m, $h2SD, $h2SO),
                $this->buildPeriodDate($y, $m, $h2ED, $h2EO),
            ];
        }

        // Simple cutoff fallback
        $cutoff  = (int)($psModel->getSetting('first_half_cutoff') ?? 15);
        $lastDay = (int)date('t', mktime(0, 0, 0, $m, 1, $y));

        if ($periodType === '1st_half') {
            return [sprintf('%04d-%02d-01', $y, $m), sprintf('%04d-%02d-%02d', $y, $m, $cutoff)];
        }
        return [sprintf('%04d-%02d-%02d', $y, $m, $cutoff + 1), sprintf('%04d-%02d-%02d', $y, $m, $lastDay)];
    }

    public function payrollView()
    {
        $month      = $this->request->getGet('month')  ?? date('Y-m');
        $periodType = $this->request->getGet('period') ?? 'monthly';
        if (!in_array($periodType, ['monthly', '1st_half', '2nd_half'], true)) {
            $periodType = 'monthly';
        }

        $psModel = new \App\Models\Client\PayrollSettingsModel();
        [$periodStart, $periodEnd] = $this->computeStatusPeriodDates($month, $periodType, $psModel);

        $data['selected_month']   = $month;
        $data['selected_period']  = $periodType;
        $data['period_start_date'] = $periodStart;
        $data['period_end_date']   = $periodEnd;

        $db = \Config\Database::connect();

        // computeStatusPeriodDates() now resolves an exact [start, end] for
        // 'monthly' too (via PayrollCutoff::monthlyPeriodForSalaryMonth()), so
        // every period type can join on the exact dates — matching on just
        // DATE_FORMAT(period_start,'%Y-%m') broke once the Monthly cutoff can
        // start in the previous calendar month (a negative start offset).
        $psJoinCond = "ps.employee_id = e.id AND ps.period_start = ? AND ps.period_end = ?";
        $psBinds    = [$month, $periodType, $periodStart, $periodEnd];

        $rows = $db->query("
            SELECT
                e.id            AS employee_id,
                e.employee_number,
                CONCAT(
                    TRIM(e.first_name), ' ',
                    IF(COALESCE(TRIM(e.middle_name),'')='','',
                       CONCAT(UPPER(LEFT(TRIM(e.middle_name),1)),'. ')),
                    TRIM(e.last_name)
                ) AS employee_name,
                COALESCE(MAX(ea.photo_path), '')   AS photo_path,
                COALESCE(ep.position, '')           AS position,
                COALESCE(ep.monthly_salary, 0)      AS monthly_salary,
                p.id                                AS payroll_id,
                p.status                            AS payroll_status,
                p.daily_rate,
                p.phone,
                p.dst,
                p.currency,
                MAX(ps.sent_at)                     AS payslip_sent_at,
                MAX(ps.id)                          AS payslip_id,
                MAX(ps.send_status)                 AS payslip_status,
                MAX(ps.scheduled_send_date)         AS payslip_scheduled_date,
                MAX(ps.needs_resend)                AS payslip_needs_resend,
                MAX(ps.paid_at)                     AS payslip_paid_at
            FROM employees e
            INNER JOIN users u ON u.id = e.user_id AND u.active = 1
            LEFT JOIN employee_accounts ea  ON ea.employee_id = e.id
            LEFT JOIN employee_positions ep ON ep.employee_id = e.id
            LEFT JOIN payroll p
                ON p.employee_id = e.id AND p.month_year = ? AND p.period_type = ?
            LEFT JOIN employee_payslips ps
                ON $psJoinCond
            GROUP BY e.id, p.id, ep.id
            ORDER BY e.first_name, e.last_name
        ", $psBinds)->getResultArray();

        foreach ($rows as &$row) {
            if (!empty($row['photo_path'])) {
                $row['photo_path'] = base_url((string)$row['photo_path']);
            }
        }
        unset($row);

        $data['payrolls'] = $rows;

        $data += $this->payslipReleasePageData();

        return view('client_portal/payroll_main', $data);
    }

    /**
     * Everything the Payroll Output tab's Payslip tab (Payroll Output) (and the Bank
     * File view, which shares $payslips) needs: every saved payslip with its
     * release status, its default "send on" date (the configured salary day
     * for its cutoff), and whether it's been paid — via the Release page's Mark
     * as Paid or the older preview-modal Mark as Paid on its payroll_transactions
     * row.
     */
    private function payslipReleasePageData(): array
    {
        $payslips = (new \App\Models\Client\EmployeePayslipModel())
            ->select("employee_payslips.*, CONCAT(employees.first_name,' ',employees.last_name) AS employee_name,
                ea.bank, ea.account_number, ea.swift_code, ea.bank_address,
                (SELECT MAX(pt.paid_at) FROM payroll_transactions pt
                  WHERE pt.employee_id = employee_payslips.employee_id
                    AND pt.period_start = employee_payslips.period_start
                    AND pt.period_end   = employee_payslips.period_end
                    AND pt.status = 'paid') AS tx_paid_at", false)
            ->join('employees', 'employees.id = employee_payslips.employee_id')
            ->join('employee_accounts as ea', 'ea.employee_id = employees.id', 'left')
            ->orderBy('employee_payslips.period_start', 'DESC')
            ->orderBy('employee_name', 'ASC')
            ->findAll();

        $salaryDays = [];
        foreach ($payslips as &$ps) {
            $start = (string) $ps['period_start'];
            $end   = (string) $ps['period_end'];
            $key   = $start . '|' . $end;
            $salaryDays[$key] ??= \App\Libraries\PayrollCutoff::salaryDayFor($end, $start);
            $ps['salary_day'] = $salaryDays[$key];
            $ps['is_paid']    = !empty($ps['paid_at']) || !empty($ps['tx_paid_at']);
        }
        unset($ps);

        $settings = new PayrollSettingsModel();
        $salarySetting = [];
        foreach (['half1_salary_day', 'half1_salary_offset', 'half2_salary_day', 'half2_salary_offset', 'monthly_salary_day', 'monthly_salary_offset', 'salary_day_adjust'] as $key) {
            $salarySetting[$key] = $settings->getSetting($key);
        }

        return [
            'payslips'            => $payslips,
            'releaseMode'         => $this->payslipReleaseMode(),
            'releaseSalarySetting'=> $salarySetting,
            'canRelease'          => \App\Libraries\PortalAccess::can('release'),
        ];
    }

    // ================= QUICK STATUS MARK (AJAX) =================
    public function markPayrollStatus()
    {
        try {
            $post       = $this->request->getJSON(true) ?: $this->request->getPost();
            $employeeId = $post['employee_id'] ?? null;
            $monthYear  = $post['month_year']  ?? null;
            $status     = $post['status']      ?? 'Paid';
            $periodType = $post['period_type'] ?? 'monthly';

            if (!$employeeId || !$monthYear) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing employee_id or month_year']);
            }
            if (!in_array($status, ['Paid', 'Pending'], true)) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid status']);
            }
            if (!in_array($periodType, ['monthly', '1st_half', '2nd_half'], true)) {
                $periodType = 'monthly';
            }

            $model    = new PayrollModel();
            $existing = $model->where('employee_id', $employeeId)
                              ->where('month_year', $monthYear)
                              ->where('period_type', $periodType)
                              ->first();

            $oldStatus = $existing['status'] ?? null;

            if ($existing) {
                $model->update($existing['id'], ['status' => $status]);
                $id = $existing['id'];
            } else {
                $model->insert([
                    'employee_id' => $employeeId,
                    'month_year'  => $monthYear,
                    'period_type' => $periodType,
                    'status'      => $status,
                    'currency'    => 'PHP',
                    'dst'         => 0,
                ]);
                $id = $model->getInsertID();
            }

            // Audit trail: who flipped this, and from what. Best-effort — a
            // logging hiccup must not undo or fail the status change itself.
            if ($oldStatus !== $status) {
                try {
                    $emp     = (new \App\Models\Client\EmployeeModel())->find($employeeId);
                    $empName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) ?: ('Employee #' . $employeeId);
                    service('activityLogger')->log(
                        'updated', 'payroll',
                        "Payroll status for {$empName} ({$monthYear}, {$periodType}) set to {$status}",
                        (int) $id,
                        ['status' => $oldStatus],
                        ['status' => $status]
                    );
                } catch (\Throwable $logErr) {
                    log_message('error', 'markPayrollStatus audit log failed: ' . $logErr->getMessage());
                }
            }

            return $this->response->setJSON(['success' => true, 'id' => $id, 'status' => $status]);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to update payroll status. Please try again.');
        }
    }

    /**
     * POST client_portal/payroll/markPaid/(:num)
     *
     * Marks one computed payroll_transactions row as 'paid'. One-way: only a
     * 'computed' row may transition to 'paid' — already-'paid' or still-'draft'
     * rows are rejected, so this can't double-mark a payment or skip
     * computation. Traceable via paid_by/paid_at plus an activity log entry,
     * mirroring the computed_by/computed_at and payslip sent_by/sent_at
     * patterns already used elsewhere in this controller.
     */
    public function markTransactionPaid($id = null)
    {
        try {
            $txModel = new PayrollTransactionModel();
            $tx      = $txModel->find($id);

            if (!$tx) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Payroll transaction not found.']);
            }

            if ($tx['status'] !== 'computed') {
                return $this->response->setStatusCode(409)->setJSON([
                    'error' => $tx['status'] === 'paid'
                        ? 'This payroll transaction is already marked as paid.'
                        : 'Only a computed payroll transaction can be marked as paid.',
                ]);
            }

            $actor = auth()->user();
            $db    = \Config\Database::connect();

            $db->transBegin();

            // Conditional update guards against a concurrent double-click/second
            // request racing past the status check above.
            $affected = $db->table('payroll_transactions')
                ->where('id', $id)
                ->where('status', 'computed')
                ->update([
                    'status'  => 'paid',
                    'paid_by' => $actor ? $actor->id : null,
                    'paid_at' => date('Y-m-d H:i:s'),
                ]);

            if (!$affected || !$db->transStatus()) {
                $db->transRollback();
                return $this->response->setStatusCode(409)->setJSON(['error' => 'This payroll transaction was already updated by another request.']);
            }

            $db->transCommit();

            service('activityLogger')->log(
                'updated', 'payroll',
                'Payroll transaction #' . $id . ' marked as paid',
                null, null,
                [
                    'transaction_id' => (int)$id,
                    'employee_id'    => $tx['employee_id'],
                    'period_start'   => $tx['period_start'],
                    'period_end'     => $tx['period_end'],
                    'net_pay'        => $tx['net_pay'],
                    'paid_by'        => $actor ? ($actor->username ?? $actor->id) : 'system',
                ]
            );

            return $this->response->setJSON(['success' => true, 'id' => (int)$id, 'status' => 'paid']);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to mark payroll transaction as paid. Please try again.');
        }
    }

    public function createPayroll()
    {
        $model = new PayrollModel();
        try {
            $model->save($this->request->getPost());
            return redirect()->back()->with('success', 'Payroll record created successfully');
        } catch (\Exception $e) {
            return $this->flashError($e, 'Unable to create payroll record. Please try again.');
        }
    }

    public function updatePayroll($id)
    {
        $model = new PayrollModel();
        try {
            $model->update($id, $this->request->getPost());
            return redirect()->back()->with('success', 'Payroll record updated successfully');
        } catch (\Exception $e) {
            return $this->flashError($e, 'Unable to update payroll record. Please try again.');
        }
    }

    public function deletePayroll($id)
    {
        $model = new PayrollModel();
        try {
            $model->delete($id);
            return redirect()->back()->with('success', 'Payroll record deleted successfully');
        } catch (\Exception $e) {
            return $this->flashError($e, 'Unable to delete payroll record. Please try again.');
        }
    }

    public function getPayroll($id)
    {
        $model = new PayrollModel();
        return $this->response->setJSON($model->find($id));
    }

    public function getEmployees()
    {
        $employeeModel = new EmployeeModel();
        $rows = $employeeModel
            ->select("employees.id, employees.first_name, employees.last_name, employees.middle_name")
            ->join('users', 'users.id = employees.user_id', 'left')
            ->where('users.active', 1)
            ->orderBy('employees.first_name', 'ASC')
            ->findAll();

        $employees = array_map(function ($row) {
            $first = trim($row['first_name'] ?? '');
            $last  = trim($row['last_name'] ?? '');
            $mid   = trim($row['middle_name'] ?? '');
            $mi    = $mid !== '' ? strtoupper(substr($mid, 0, 1)) . '.' : '';
            $name = trim($first . ' ' . ($mi !== '' ? $mi . ' ' : '') . $last);
            return ['id' => (string)$row['id'], 'name' => $name, 'label' => $name];
        }, $rows);
        return $this->response->setJSON($employees);
    }

    // ================= GET EMPLOYEE DETAILS FOR AUTO-FILL =================
    public function getEmployeeDetails($id)
    {
        try {
            $employeeModel = new EmployeeModel();
            $employee = (array) $employeeModel->find($id);
            if (!$employee) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Employee not found']);
            }

            $month = $this->request->getGet('month');
            if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
                $month = date('Y-m');
            }

            $db = \Config\Database::connect();

            // Employee management is the canonical source for salary and benefits
            $posRow = $db->table('employee_positions')
                ->select('position, monthly_salary, de_minimis_benefit, de_minimis_breakdown, allowances, additional_de_minimis_benefit, additional_de_minimis_breakdown')
                ->where('employee_id', $id)
                ->get()->getRowArray();

            $position      = $posRow['position']          ?? 'Employee';
            $monthlySalary = (float)($posRow['monthly_salary']     ?? 0);
            $deMinimis     = (float)($posRow['de_minimis_benefit'] ?? 0);
            $allowances    = (float)($posRow['allowances']         ?? 0);
            $additionalDeMinimis = (float)($posRow['additional_de_minimis_benefit'] ?? 0);
            $additionalAllocations = json_decode($posRow['additional_de_minimis_breakdown'] ?? '{}', true) ?: [];

            $breakdown             = [];
            $allocations           = [];
            $otherNonTaxableTotal  = 0.0;
            try {
                $breakdown   = json_decode($posRow['de_minimis_breakdown'] ?? '{}', true) ?: [];
                $allocations = $breakdown['allocations'] ?? [];
                foreach (($breakdown['other'] ?? []) as $item) {
                    $otherNonTaxableTotal += (float)($item['amount'] ?? 0);
                }
            } catch (\Throwable $e) {
                $allocations = [];
            }

            $workingDays = $this->getWorkingDaysInMonth($month);

            if ($monthlySalary > 0) {
                // DOLE: OT/NSD base = basic pay only (exclude non-basic allowances).
                // If employee_positions stores total comp in monthly_salary and separates
                // the non-basic portion in the allowances field, subtract it here.
                $basicMonthly = ($allowances > 0 && $allowances < $monthlySalary)
                    ? $monthlySalary - $allowances
                    : $monthlySalary;
                $dailyRate = $monthlySalary * 12 / 261; // full monthly — matches Excel I column
            } else {
                // Fallback: check if payroll status has a daily_rate override
                $payrollModel = new PayrollModel();
                $payroll = $payrollModel
                    ->where('employee_id', $id)
                    ->where('month_year', $month)
                    ->first();

                if ($payroll && (float)$payroll['daily_rate'] > 0) {
                    $dailyRate     = (float)$payroll['daily_rate'];
                    $basicMonthly  = $dailyRate * 261 / 12;
                    $monthlySalary = $basicMonthly;
                } else {
                    return $this->response->setStatusCode(404)->setJSON([
                        'error' => "No salary set for this employee. Please set a Basic Salary in Employee Management.",
                    ]);
                }
                $basicMonthly = $monthlySalary;
            }

            // Display rates use full monthly salary (matches Excel I/J columns).
            // OT/NSD base uses basic monthly only (DOLE excludes non-basic allowances).
            $hourlyRate   = $monthlySalary * 12 / 261 / 8;
            $otHourlyRate = $basicMonthly  * 12 / 261 / 8;

            $first    = trim($employee['first_name']  ?? '');
            $last     = trim($employee['last_name']   ?? '');
            $mid      = trim($employee['middle_name'] ?? '');
            $mi       = $mid !== '' ? strtoupper(substr($mid, 0, 1)) . '.' : '';
            $fullName = trim($first . ' ' . ($mi !== '' ? $mi . ' ' : '') . $last);

            return $this->response->setJSON([
                'id'                   => $employee['id'],
                'name'                 => $fullName,
                'position'             => $position,
                'monthly_salary'       => round($monthlySalary, 2),
                'daily_rate'           => round($dailyRate, 4),
                'hourly_rate'          => round($hourlyRate, 4),
                'ot_hourly_rate'       => round($otHourlyRate, 4),
                'de_minimis_default'   => round($deMinimis, 2),
                'allowances_default'   => round($allowances, 2),
                'rice_subsidy'              => isset($allocations['rice_subsidy'])      ? (float) $allocations['rice_subsidy']      : null,
                'uniform_allowance'         => isset($allocations['uniform_allowance']) ? (float) $allocations['uniform_allowance'] : null,
                'laundry_allowance'         => isset($allocations['laundry_allowance']) ? (float) $allocations['laundry_allowance'] : null,
                'medical_allowance'         => isset($allocations['medical_allowance']) ? (float) $allocations['medical_allowance'] : null,
                'other_non_taxable_default' => round($otherNonTaxableTotal, 2),
                'additional_de_minimis_default' => round($additionalDeMinimis, 2),
                'additional_de_minimis_allocations' => $additionalAllocations,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($e, 'Unable to load employee details.');
        }
    }

    private function getWorkingDaysInMonth($yearMonth)
    {
        $date = \DateTime::createFromFormat('Y-m', $yearMonth);
        if (!$date) return 22;
        $year = (int)$date->format('Y');
        $month = (int)$date->format('m');
        $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $workingDays = 0;
        for ($day = 1; $day <= $totalDays; $day++) {
            $weekday = (int)date('N', strtotime("$year-$month-$day"));
            if ($weekday < 6) $workingDays++;
        }
        return $workingDays;
    }

    // ================= NEW: ATTENDANCE SUMMARY FOR RAW SHEET =================
    public function getAttendanceSummary(int $employeeId, string $startDate, string $endDate)
    {
        try {
            $employeeModel = new EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Employee not found (ID: ' . $employeeId . ')']);
            }

            $userId = $employee['user_id'];
            if (!$userId) {
                return $this->response->setStatusCode(422)->setJSON(['error' => 'Employee has no linked user account']);
            }

            $attendanceModel = new AttendanceModel();
            $attendances = $attendanceModel
                ->where('user_id', $userId)
                ->where('COALESCE(shift_date, DATE(time_in)) >=', $startDate)
                ->where('COALESCE(shift_date, DATE(time_in)) <=', $endDate)
                ->findAll();

            $posModel    = new \App\Models\Client\EmployeePositionModel();
            $position    = $posModel->where('employee_id', $employeeId)->first();
            $workingType = $position['working_day_type'] ?? null;
            $schedStart  = ($workingType !== 'flexible' && !empty($position['start_time']))
                           ? $position['start_time'] : null;
            $schedEnd    = ($workingType !== 'flexible' && !empty($position['end_time']))
                           ? $position['end_time'] : null;

            $summary = $this->aggregateAttendance($attendances, $startDate, $endDate, $schedStart, $schedEnd, (int) $userId);

            // Overlay approved leave requests to adjust absent/paid leave days
            $requestModel = new \App\Models\Client\RequestModel();
            $requests = $requestModel
                ->where('requested_by', $userId)
                ->where('status', 'approved')
                ->where('request_type', 'leave')
                ->where('start_date >=', $startDate)
                ->where('end_date <=', $endDate)
                ->findAll();

            $paidLeaveDays   = 0;
            $unpaidLeaveDays = 0;
            foreach ($requests as $req) {
                if ($req['is_paid_leave'] ?? 1) {
                    $paidLeaveDays   += (float) ($req['duration'] ?? 0);
                } else {
                    $unpaidLeaveDays += (float) ($req['duration'] ?? 0);
                }
            }

            $summary['paid_leave_days']   = $paidLeaveDays;
            $summary['unpaid_leave_days'] = $unpaidLeaveDays;
            $summary['absent_days']       = max(0, $summary['absent_days'] - $paidLeaveDays);

            return $this->response->setJSON($summary);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to load attendance summary.');
        }
    }

    /**
     * PHP mirror of the JS buildPeriodDate() used in payroll_management.php.
     * Resolves a (salary-year, salary-month, day, monthOffset) tuple to YYYY-MM-DD.
     * day=0 means "last day of the target month".
     */
    private function buildPeriodDate(int $y, int $m, int $day, int $offset): string
    {
        $rawM    = $m + $offset;
        $tY      = $y + (int)floor(($rawM - 1) / 12);
        $tM      = (int)((($rawM - 1) % 12 + 12) % 12 + 1);
        $lastDay = (int)date('t', mktime(0, 0, 0, $tM, 1, $tY));
        $d       = ($day === 0) ? $lastDay : min($day, $lastDay);
        return sprintf('%04d-%02d-%02d', $tY, $tM, $d);
    }

    private function aggregateAttendance(array $attendances, string $startDate, string $endDate, ?string $scheduledStart = null, ?string $scheduledEnd = null, ?int $userId = null): array
    {
        $totalWorkingDays = $this->getWorkingDaysCount($startDate, $endDate);

        // Required hours per day = the employee's actual scheduled shift length,
        // not a flat assumption. Falls back to 8 only when no schedule is on file.
        $requiredHours = 8.0;
        if ($scheduledStart && $scheduledEnd) {
            $reqStart = strtotime($scheduledStart);
            $reqEnd   = strtotime($scheduledEnd);
            if ($reqEnd < $reqStart) $reqEnd += 86400;
            $requiredHours = ($reqEnd - $reqStart) / 3600;
        }

        // A day only counts as "OT approved" if an actual approved overtime
        // request covers it — not whatever approved_ot_hours happened to be
        // frozen on the attendance row at clock-out time (same rule as the
        // Timesheet page, so the two stay consistent with each other).
        // Per date: the approved OT requests (a request's time span limits which OT hours count; a span-less request covers the whole day).
        $otRequestsByDate = [];
        if ($userId) {
            $otRequestsByDate = (new AttendanceComputationService())->approvedOtRequestsByDate((int) $userId, $startDate, $endDate);
        }

        // Group records by date — employees can have multiple clock-ins per day
        $byDate = [];
        foreach ($attendances as $att) {
            if (empty($att['time_in']) || empty($att['time_out'])) continue;
            $attDate = $att['shift_date'] ?? date('Y-m-d', strtotime($att['time_in']));
            $byDate[$attDate][] = $att;
        }

        $computationService = new AttendanceComputationService();
        $workedDays      = count($byDate);
        $regularHours    = 0.0;
        $tardyMinutes    = 0;
        $undertimeHours  = 0.0;
        $regOtHours      = 0.0;
        $workdayNsdHours = 0.0;
        $satRegHours     = 0.0;
        $satOtHours      = 0.0;
        $satNsdHours     = 0.0;
        $sunRegHours     = 0.0;
        $sunOtHours      = 0.0;
        $sunNsdHours     = 0.0;
        $holRegHours     = 0.0;
        $holOtHours      = 0.0;
        $holNsdHours     = 0.0;
        $specHolRegHours = 0.0;
        $specHolOtHours  = 0.0;
        $specHolNsdHours = 0.0;
        $totalBreakHours = 0.0;

        // Pre-load holidays for the period (handles recurring via HolidayModel)
        $holidayModel = new \App\Models\Client\HolidayModel();
        $holidayCache = [];
        $getHolidayType = function(string $date) use ($holidayModel, &$holidayCache): ?string {
            if (!array_key_exists($date, $holidayCache)) {
                $h = $holidayModel->isHoliday($date);
                $holidayCache[$date] = $h ? ($h['type'] ?? 'special') : null;
            }
            return $holidayCache[$date];
        };

        foreach ($byDate as $attDate => $recs) {
            usort($recs, fn($a, $b) => strcmp($a['time_in'], $b['time_in']));

            // Each record uses its OWN saved schedule (schedule_snapshot as of
            // clock-in), falling back to the employee's current schedule only when
            // a record has no snapshot — same rule AttendanceWindow::schedule()
            // applies for the Timesheet page. Previously this whole function used
            // only the employee's *current* schedule for every record regardless
            // of when it happened, so an employee whose schedule was edited
            // mid-cutoff would have old shifts silently recomputed against the new
            // schedule here while the Timesheet page kept using the old one.
            $daySchedule = \App\Libraries\AttendanceWindow::schedule(
                $recs[0],
                ['start_time' => $scheduledStart, 'end_time' => $scheduledEnd]
            );
            $dayScheduledStart = $daySchedule['start_time'] ?? null;
            $dayScheduledEnd   = $daySchedule['end_time'] ?? null;
            $dayRequiredHours  = $requiredHours;
            if ($dayScheduledStart && $dayScheduledEnd) {
                $reqStart = strtotime($dayScheduledStart);
                $reqEnd   = strtotime($dayScheduledEnd);
                if ($reqEnd < $reqStart) $reqEnd += 86400;
                $dayRequiredHours = \App\Libraries\AttendanceWindow::requiredHours(($reqEnd - $reqStart) / 3600, $daySchedule);
            }

            // Same shared formula as Timesheet/live clock-in/import — full
            // datetimes rather than bare time-of-day strings, which previously
            // couldn't correctly compare an overnight shift's actual arrival
            // against its scheduled start across the midnight boundary.
            $firstRecDate = date('Y-m-d', strtotime($recs[0]['time_in']));
            $dayWindowStart = $dayScheduledStart ? new \DateTime($firstRecDate . ' ' . $dayScheduledStart) : null;
            $tardyMinutes += AttendanceComputationService::lateMinutes(new \DateTime($recs[0]['time_in']), $dayWindowStart, (int) ($daySchedule['tardiness_grace_minutes'] ?? 0));

            $dayNsd           = 0.0;
            $dayBreak         = 0.0;
            $dayRegularHours  = 0.0;
            $dayExcessHours   = 0.0;
            $otApproval       = AttendanceComputationService::otApprovalForDay($otRequestsByDate[$attDate] ?? [], $attDate);
            $dayApprovedOtHours = 0.0;
            foreach ($recs as $rec) {
                $dayBreak += (float)($rec['break_duration'] ?? 0);

                if (empty($rec['time_in']) || empty($rec['time_out'])) continue;
                $recDate  = date('Y-m-d', strtotime($rec['time_in']));
                $recSchedule = \App\Libraries\AttendanceWindow::schedule(
                    $rec,
                    ['start_time' => $scheduledStart, 'end_time' => $scheduledEnd]
                );

                // Regular/excess math is shared with the Timesheet page (and, via
                // analyzeClockOut(), live clock-out and import) through
                // AttendanceComputationService::splitShiftWindow(), so all of them
                // produce matching numbers for the same shift.
                $split = $computationService->splitShiftWindow(
                    $rec['time_in'],
                    $rec['time_out'],
                    (float) ($rec['break_duration'] ?? 0),
                    $recSchedule['start_time'] ?? null,
                    $recSchedule['end_time'] ?? null,
                    $recDate,
                    (int) ($recSchedule['ot_grace_minutes'] ?? 0)
                );
                $dayRegularHours += $split['regular_hours'];
                $dayExcessHours  += $split['excess_hours'];
                $otSplit = AttendanceComputationService::applyOtApproval($split, $otApproval);
                $dayApprovedOtHours += $otSplit['approved'];

                // Night differential only counts for paid time: the scheduled
                // window, or the full span if this date's overtime was approved.
                // Unapproved excess worked at night isn't recognized as OT, so it
                // isn't paid a night differential either (same rule as Timesheet).
                foreach ($otSplit['paid'] as [$nsdPaidStart, $nsdPaidEnd]) {
                    if ($nsdPaidStart < $nsdPaidEnd) {
                        $dayNsd += $this->calcNightDiffHours(
                            $nsdPaidStart->format('Y-m-d H:i:s'),
                            $nsdPaidEnd->format('Y-m-d H:i:s')
                        );
                    }
                }
            }
            $totalBreakHours += $dayBreak;

            // Break deduction now happens per record (inside splitShiftWindow), not
            // once against the day's total, matching how the Timesheet page and
            // live clock-out apply it.
            $regularCapped = round($dayRegularHours, 2);
            // OT = time actually worked outside the scheduled window, but only
            // paid if an approved overtime request covers this specific date —
            // unapproved excess isn't paid as OT, and doesn't inflate Regular.
            $dayExcessHours = round($dayExcessHours, 2);
            $ot             = round($dayApprovedOtHours, 2);
            // Undertime = shortfall against the scheduled window itself, tracked
            // by the Timesheet page but previously invisible to payroll entirely.
            $dayForgiven    = AttendanceComputationService::graceForgivenHours(new \DateTime($recs[0]['time_in']), $dayWindowStart, (int) ($daySchedule['tardiness_grace_minutes'] ?? 0));
            $dayUndertime   = ($dayScheduledStart && $dayScheduledEnd) ? max(0, round($dayRequiredHours - $regularCapped - $dayForgiven, 2)) : 0.0;
            $undertimeHours += $dayUndertime;
            $dow            = (int)date('N', strtotime($attDate));
            $holidayType   = $getHolidayType($attDate);

            if ($holidayType === 'regular') {
                $holRegHours += $regularCapped;
                $holOtHours  += $ot;
                $holNsdHours += $dayNsd;
            } elseif ($holidayType === 'special') {
                $specHolRegHours += $regularCapped;
                $specHolOtHours  += $ot;
                $specHolNsdHours += $dayNsd;
            } elseif ($dow === 6) {
                $satRegHours += $regularCapped;
                $satOtHours  += $ot;
                $satNsdHours += $dayNsd;
            } elseif ($dow === 7) {
                $sunRegHours += $regularCapped;
                $sunOtHours  += $ot;
                $sunNsdHours += $dayNsd;
            } else {
                $regularHours    += $regularCapped;
                $regOtHours      += $ot;
                $workdayNsdHours += $dayNsd;
            }
        }

        // Only weekday (Mon–Fri) attendance counts against the absent_days denominator.
        // Weekend or holiday shifts inflate $workedDays but should not mask weekday absences.
        $workedWeekdays = 0;
        foreach (array_keys($byDate) as $attDate) {
            if ((int)date('N', strtotime($attDate)) < 6) {
                $workedWeekdays++;
            }
        }

        return [
            'worked_days'        => $workedDays,
            'absent_days'        => max(0, $totalWorkingDays - $workedWeekdays),
            'regular_hours'      => round($regularHours, 2),
            'tardy_minutes'      => $tardyMinutes,
            // Previously not surfaced here at all, even though the Timesheet page
            // has always tracked it for the same shifts — added so payroll has
            // visibility into it too, matching Phase 4's "matching results" goal.
            'undertime_hours'    => round($undertimeHours, 2),
            'break_hours'        => round($totalBreakHours, 2),
            'reg_ot_hours'       => round($regOtHours, 2),
            'workday_nsd_hours'  => round($workdayNsdHours, 2),
            'sat_reg_hours'      => round($satRegHours, 2),
            'sat_ot_hours'       => round($satOtHours, 2),
            'sat_nsd_hours'      => round($satNsdHours, 2),
            'sun_reg_hours'      => round($sunRegHours, 2),
            'sun_ot_hours'       => round($sunOtHours, 2),
            'sun_nsd_hours'      => round($sunNsdHours, 2),
            'hol_reg_hours'      => round($holRegHours, 2),
            'hol_ot_hours'       => round($holOtHours, 2),
            'hol_nsd_hours'      => round($holNsdHours, 2),
            'spec_hol_reg_hours' => round($specHolRegHours, 2),
            'spec_hol_ot_hours'  => round($specHolOtHours, 2),
            'spec_hol_nsd_hours' => round($specHolNsdHours, 2),
            // Legacy aliases kept for any existing code still reading them
            'night_diff_hours'        => round($workdayNsdHours + $satNsdHours + $sunNsdHours + $holNsdHours, 2),
            'rest_day_regular_hours'  => round($satRegHours + $sunRegHours, 2),
            'rest_ot_hours'           => round($satOtHours  + $sunOtHours,  2),
            'holiday_ot_hours'        => round($holOtHours, 2),
        ];
    }

    private function getWorkingDaysCount(string $start, string $end): int
    {
        $begin = new \DateTime($start);
        $end   = (new \DateTime($end))->modify('+1 day');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($begin, $interval, $end);
        $workingDays = 0;
        foreach ($period as $dt) {
            if ($dt->format('N') < 6) $workingDays++;
        }
        return $workingDays;
    }

    // ================= COMPUTE AND SAVE PAYROLL =================
    public function computeAndSave()
    {
        try {

            $employeeId = $this->request->getGet('employee_id');

            $start = $this->request->getGet('start_date');

            $end   = $this->request->getGet('end_date');

            $adjustments = $this->request->getJSON(true) ?: [];

            if (!$employeeId || !$start || !$end) {

                throw new \App\Exceptions\ValidationException('Missing required parameters');

            }

            $service = new PayrollComputationService();

            $computed = $service->computePayroll(
                $employeeId,
                $start,
                $end,
                $adjustments
            );

            $txModel = new PayrollTransactionModel();

            $cleanTxData = [

                'employee_id' => (int) $employeeId,

                'period_start' => $start,

                'period_end' => $end,

                'basic_pay' => (float) ($computed['basic_pay'] ?? 0),

                'overtime_pay' => (float) ($computed['overtime_pay'] ?? 0),

                'night_diff_pay' => (float) ($computed['night_diff_pay'] ?? 0),

                'holiday_pay' => (float) ($computed['holiday_pay'] ?? 0),

                'de_minimis' => (float) ($computed['de_minimis'] ?? 0),

                'other_earnings_taxable' =>
                    (float) ($computed['other_earnings_taxable'] ?? 0),

                'other_earnings_non_taxable' =>
                    (float) ($computed['other_earnings_non_taxable'] ?? 0),

                'total_earnings' =>
                    (float) ($computed['total_earnings'] ?? 0),

                'sss_ee' => (float) ($computed['sss_ee'] ?? 0),

                'phic_ee' => (float) ($computed['phic_ee'] ?? 0),

                'hdmf_ee' => (float) ($computed['hdmf_ee'] ?? 0),

                'withholding_tax' =>
                    (float) ($computed['withholding_tax'] ?? 0),

                // payroll_transactions has no column for an itemized loan
                // breakdown; the loan total is already folded into
                // total_deductions/net_pay below via PayrollComputationService.
                'other_deductions' =>
                    (float) ($computed['other_deductions'] ?? 0),

                'total_deductions' =>
                    (float) ($computed['total_deductions'] ?? 0),

                'net_pay' =>
                    (float) ($computed['net_pay'] ?? 0),

                'status' => 'computed',

                'computed_at' => date('Y-m-d H:i:s')
            ];

            $txModel->insert($cleanTxData);

            $txId = $txModel->getInsertID();

            // Loan installments are locked/marked paid only once the payslip is
            // actually sent to the employee (see deliverPayslip()), not on this
            // computation — this endpoint fires on every "Save Payslip" click.

            return $this->response->setJSON([

                'success' => true,

                'transaction_id' => $txId,

                'computed' => $computed

            ]);

        } catch (\Exception $e) {

            return $this->jsonError($e, 'Unable to compute payroll. Please check the inputs and try again.');
        }
    }

    /**
     * Marks any 'active' loan that has a pending installment due on this
     * payroll's period_end as 'active_ongoing', locking it from further
     * edits now that this cutoff's deduction has been saved. Must run
     * before markInstallmentPaid() flips the schedule row to 'paid'.
     */
    private function markLoansActiveOngoing(int $employeeId, string $periodEnd): void
    {
        $db = \Config\Database::connect();

        $loanIds = $db->table('employee_loan_schedule els')
            ->select('el.id')
            ->join('employee_loans el', 'el.id = els.loan_id')
            ->where('el.employee_id', $employeeId)
            ->where('el.loan_status', 'active')
            ->where('els.schedule_status', 'pending')
            ->where('els.due_date', $periodEnd)
            ->get()
            ->getResultArray();

        if (!$loanIds) {
            return;
        }

        $db->table('employee_loans')
            ->whereIn('id', array_column($loanIds, 'id'))
            ->update(['loan_status' => 'active_ongoing']);
    }

    // ================= PAYROLL REGISTER (JSON for view) =================
    public function getRegisterRows()
    {
        $month  = $this->request->getGet('month')  ?? date('Y-m');
        $period = $this->request->getGet('period') ?? '';   // '1st_half' | '2nd_half' | ''

        $db = \Config\Database::connect();

        // Build period filter — see periodSqlFilter()'s docblock: "Full Month"
        // fetches a wide window here and is narrowed precisely in PHP below,
        // since a cutoff crossing a month boundary can't be matched by SQL
        // alone comparing period_start's own calendar month.
        [$periodWhere, $binds, $salaryMonthFilter] = $this->periodSqlFilter($month, $period);

        $rows = $db->query("
            SELECT
                ps.id                                               AS payslip_id,
                CONCAT(TRIM(e.first_name),' ',TRIM(e.last_name))   AS employee_name,
                COALESCE(ep.monthly_salary, 0)                      AS monthly_salary,
                ps.period_start, ps.period_end,
                ps.net_pay                                          AS db_net_pay,
                ps.generated_data,
                ps.send_status, ps.scheduled_send_date, ps.sent_at, ps.sent_by,
                ps.needs_resend, ps.last_send_error, ps.review_reason, ps.send_attempts,
                ps.paid_at,
                (SELECT MAX(pt.paid_at) FROM payroll_transactions pt
                  WHERE pt.employee_id = ps.employee_id AND pt.period_start = ps.period_start
                    AND pt.period_end = ps.period_end AND pt.status = 'paid') AS tx_paid_at
            FROM employee_payslips ps
            INNER JOIN employees e   ON e.id = ps.employee_id
            LEFT  JOIN users u       ON u.id = e.user_id AND u.active = 1
            LEFT  JOIN employee_positions ep ON ep.employee_id = e.id
            WHERE $periodWhere
            ORDER BY e.first_name, e.last_name
        ", $binds)->getResultArray();

        $rows = $this->filterRowsBySalaryMonth($rows, $salaryMonthFilter);

        $result = [];
        $salaryDays = [];
        $i = 1;
        foreach ($rows as $row) {
            $d  = json_decode((string)($row['generated_data'] ?? ''), true) ?? [];
            $gf = fn($k, $alt = null) => (float)($d[$k] ?? ($alt !== null ? (float)($d[$alt] ?? 0) : 0));

            // Monthly & semi-monthly
            $monthly    = (float)$row['monthly_salary'];
            $semiMonthly = $monthly / 2;

            // De Minimis — individual components (stored separately in generated_data)
            $yearEndGifts    = $gf('year_end_gifts');
            $riceSubsidy     = $gf('rice_subsidy');
            $uniformAllow    = $gf('uniform_allowance');
            $laundryAllow    = $gf('laundry_allowance');
            $mealAllow       = $gf('meal_allowance');
            $medicalAllow    = $gf('medical_allowance');
            $otherNonTax     = $gf('other_earnings_non_taxable');
            $salAdjNonTax    = $gf('salary_adjustment_non_tax');
            $deMinimis       = $yearEndGifts + $riceSubsidy + $uniformAllow + $laundryAllow + $mealAllow + $medicalAllow + $salAdjNonTax;

            // Earnings
            // Bug 1 fix: other_earnings_taxable includes holiday_pay, so we isolate salary adj fields.
            $salAdj  = $gf('salary_adjustment') + $gf('override_amount') + $gf('ned_amount');
            // Excel register "Overtime" = workday OT + Saturday (regular+OT) only.
            // Sun/Hol OT goes into "Holiday Pay" column to match Excel format.
            $regOt      = $gf('reg_ot') + $gf('reg_ot_gt10');
            $satOt      = $gf('sat_ot') + $gf('sat_ot_gt10');
            $sunOtTotal = $gf('sun_ot') + $gf('sun_ot_gt10');
            $holOtTotal = $gf('hol_ot') + $gf('hol_ot_gt10');
            $pureOtSum  = $regOt + $satOt;
            $otRaw      = $gf('overtime_pay', 'total_overtime');
            // If individual fields are missing (legacy record), fall back to overtime_pay
            $otTotal    = ($pureOtSum == 0 && $otRaw > 0) ? $otRaw : $pureOtSum;
            // Bug 2 fix: NSD = all NSD types (workday + sat + sun + hol)
            $nsd     = $gf('night_diff_pay') + $gf('sat_night_prem') + $gf('sun_night_prem') + $gf('hol_night_prem');
            // Bug 3 fix: include undertime in absences
            $absent  = $gf('absent_amt') + $gf('undertime_amt');
            // Holiday Pay = stored holiday_pay (special/reg hol premium) + Sun OT + Hol OT
            $holiday  = ($pureOtSum == 0 && $otRaw > 0)
                      ? $gf('holiday_pay')  // legacy: no split available
                      : $gf('holiday_pay') + $sunOtTotal + $holOtTotal;
            $grossPay = $gf('gross_pay');

            // If gross_pay not stored, compute it (fallback for legacy payslips)
            if ($grossPay <= 0) {
                $netBasic = $gf('net_basic_pay');
                $grossPay = $netBasic + $otRaw + $salAdj + $gf('holiday_pay') + $deMinimis + $otherNonTax;
            }

            // Taxable / non-taxable split
            $nonTaxable   = $deMinimis + $otherNonTax;
            $grossTaxable = $grossPay - $nonTaxable;

            // Government deductions (0 for 1st half per Excel convention)
            $sss  = $gf('sss_ee');
            $phic = $gf('phic_ee');
            $hdmf = $gf('hdmf_ee');

            // Regular Net Taxable = Gross Taxable − SSS − PHIC − HDMF
            $netTaxable = $grossTaxable - $sss - $phic - $hdmf;

            // Withholding Tax (signed: negative = refund due for 2nd-half over-withheld employees)
            $wthRaw    = (float)($d['withholding_tax'] ?? 0);
            $wthDue    = max(0.0, $wthRaw);       // amount deducted from pay (≥0)
            $taxRefund = max(0.0, -$wthRaw);      // refund added to net pay (≥0)

            // Loan deductions
            $sssLoan      = $gf('sss_loan');
            $calamityLoan = $gf('calamity_loan');
            $hdmfLoan     = $gf('hdmf_loan');
            $advances     = $gf('sal_adv', 'salary_advance');

            // Net Pay — use stored value (authoritative); it already incorporates refund since JS fix.
            $netPay = (float)($d['net_pay'] ?? $row['db_net_pay'] ?? 0);

            $result[] = [
                'no'               => $i++,
                'payslip_id'       => (int)$row['payslip_id'],
                'employee_name'    => $row['employee_name'],
                'period_start'     => $row['period_start'],
                'period_end'       => $row['period_end'],
                'monthly_salary'   => round($monthly,       2),
                'semi_monthly'     => round($semiMonthly,   2),
                'year_end_gifts'   => round($yearEndGifts,  2),
                'rice_subsidy'     => round($riceSubsidy,   2),
                'uniform_allow'    => round($uniformAllow,  2),
                'laundry_allow'    => round($laundryAllow,  2),
                'meal_allow'       => round($mealAllow,     2),
                'medical_allow'    => round($medicalAllow,  2),
                'other_non_tax'    => round($otherNonTax,   2),
                'salary_adj'       => round($salAdj,        2),
                'overtime'         => round($otTotal,       2),
                'nsd'              => round($nsd,           2),
                'absences'         => round($absent,        2),
                'holiday_pay'      => round($holiday,       2),
                'gross_comp'       => round($grossPay,      2),
                'gross_taxable'    => round($grossTaxable,  2),
                'non_taxable'      => round($nonTaxable,    2),
                'sss'              => round($sss,           2),
                'phic'             => round($phic,          2),
                'hdmf'             => round($hdmf,          2),
                'net_taxable'      => round($netTaxable,    2),
                'wth'              => round($wthDue,        2),
                'tax_refund'       => round($taxRefund,     2),
                'sss_loan'         => round($sssLoan,       2),
                'calamity_loan'    => round($calamityLoan,  2),
                'hdmf_loan'        => round($hdmfLoan,      2),
                'advances'         => round($advances,      2),
                // Loans-module installments already taken out of net_pay (listed
                // separately on the payslip) — so the Payslip card's Deductions add up.
                'loans_module'     => round(array_sum(array_column((array) ($d['loan_deductions'] ?? []), 'amount')), 2),
                'net_pay'          => round($netPay,        2),

                // Release state, for the Payslip view's cards (Send / Schedule / Paid).
                'send_status'         => (string) ($row['send_status'] ?? 'draft'),
                'scheduled_send_date' => $row['scheduled_send_date'],
                'sent_at'             => $row['sent_at'],
                'sent_by'             => $row['sent_by'],
                'needs_resend'        => (bool) $row['needs_resend'],
                'last_send_error'     => $row['last_send_error'],
                'review_reason'       => $row['review_reason'],
                'send_attempts'       => (int) $row['send_attempts'],
                'is_paid'             => !empty($row['paid_at']) || !empty($row['tx_paid_at']),
                'paid_at'             => $row['paid_at'] ?: $row['tx_paid_at'],
                'salary_day'          => $salaryDays[$row['period_start'] . '|' . $row['period_end']]
                    ??= \App\Libraries\PayrollCutoff::salaryDayFor((string) $row['period_end'], (string) $row['period_start']),
            ];
        }

        return $this->response->setJSON($result);
    }

    /**
     * Same period/employee set as getRegisterRows(), but with the raw
     * adjustable fields named exactly as savePayslipEditData() expects them
     * (uniform_allowance, overtime_pay, etc. — not the aggregated Excel-column
     * names getRegisterRows() uses) so the Raw Sheet tab's inline-editable
     * cells map 1:1 onto what gets saved, with no field-name translation.
     */
    public function getRegisterRawSheet()
    {
        $month  = $this->request->getGet('month')  ?? date('Y-m');
        $period = $this->request->getGet('period') ?? '';

        $db = \Config\Database::connect();

        [$periodWhere, $binds, $salaryMonthFilter] = $this->periodSqlFilter($month, $period);

        $rows = $db->query("
            SELECT
                ps.id                                               AS payslip_id,
                CONCAT(TRIM(e.first_name),' ',TRIM(e.last_name))   AS employee_name,
                COALESCE(ep.monthly_salary, 0)                      AS monthly_salary,
                ps.period_start,
                ps.period_end,
                ps.net_pay                                          AS db_net_pay,
                ps.generated_data
            FROM employee_payslips ps
            INNER JOIN employees e   ON e.id = ps.employee_id
            LEFT  JOIN employee_positions ep ON ep.employee_id = e.id
            WHERE $periodWhere
            ORDER BY e.first_name, e.last_name
        ", $binds)->getResultArray();

        $rows = $this->filterRowsBySalaryMonth($rows, $salaryMonthFilter);

        $result = [];
        foreach ($rows as $row) {
            $d  = json_decode((string)($row['generated_data'] ?? ''), true) ?? [];
            $gf = fn($k, $alt = null) => (float)($d[$k] ?? ($alt !== null ? (float)($d[$alt] ?? 0) : 0));

            $result[] = [
                'payslip_id'      => (int)$row['payslip_id'],
                'employee_name'   => $row['employee_name'],
                'monthly_salary'  => (float)$row['monthly_salary'],
                'net_basic_pay'   => $gf('net_basic_pay'),
                'salary_adjustment'          => $gf('salary_adjustment'),
                'override_amount'            => $gf('override_amount'),
                'ned_amount'                 => $gf('ned_amount'),
                'overtime_pay'               => $gf('overtime_pay', 'total_overtime'),
                'night_diff_pay'             => $gf('night_diff_pay') + $gf('sat_night_prem') + $gf('sun_night_prem') + $gf('hol_night_prem'),
                'holiday_pay'                => $gf('holiday_pay'),
                'year_end_gifts'             => $gf('year_end_gifts'),
                'rice_subsidy'               => $gf('rice_subsidy'),
                'uniform_allowance'          => $gf('uniform_allowance'),
                'laundry_allowance'          => $gf('laundry_allowance'),
                'meal_allowance'             => $gf('meal_allowance'),
                'medical_allowance'          => $gf('medical_allowance'),
                'salary_adjustment_non_tax'  => $gf('salary_adjustment_non_tax'),
                'other_earnings_non_taxable' => $gf('other_earnings_non_taxable'),
                'sss_ee'          => $gf('sss_ee'),
                'phic_ee'         => $gf('phic_ee'),
                'hdmf_ee'         => $gf('hdmf_ee'),
                'withholding_tax' => $gf('withholding_tax'),
                'sss_loan'        => $gf('sss_loan'),
                'calamity_loan'   => $gf('calamity_loan'),
                'hdmf_loan'       => $gf('hdmf_loan'),
                'sal_adv'         => $gf('sal_adv', 'salary_advance'),
                'gross_pay'       => $gf('gross_pay'),
                'net_pay'         => $gf('net_pay') ?: (float)($row['db_net_pay'] ?? 0),
            ];
        }

        return $this->response->setJSON($result);
    }

    // ================= REGISTER INLINE EDIT =================

    public function getPayslipEditData()
    {
        $user = auth()->user();
        $isAdmin = \App\Libraries\PortalAccess::can('payroll', $user);
        if (!$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $id = (int)$this->request->getGet('id');
        if (!$id) return $this->response->setJSON(['error' => 'Missing id']);

        try {
            $db  = \Config\Database::connect();
            $row = $db->query("
                SELECT ps.*, CONCAT(e.first_name,' ',e.last_name) AS employee_name,
                       COALESCE(ep.monthly_salary, 0) AS monthly_salary
                FROM employee_payslips ps
                INNER JOIN employees e ON e.id = ps.employee_id
                LEFT  JOIN employee_positions ep ON ep.employee_id = e.id
                WHERE ps.id = ?
            ", [$id])->getRowArray();

            if (!$row) return $this->response->setJSON(['error' => 'Not found']);

            $d  = json_decode((string)($row['generated_data'] ?? ''), true) ?? [];
            $gf = fn($k, $alt = null) => (float)($d[$k] ?? ($alt !== null ? (float)($d[$alt] ?? 0) : 0));

            return $this->response->setJSON([
                'id'              => $id,
                'employee_name'   => $row['employee_name'],
                'period_start'    => $row['period_start'],
                'period_end'      => $row['period_end'],
                'monthly_salary'  => (float)$row['monthly_salary'],
                // Income (read-only base)
                'net_basic_pay'   => $gf('net_basic_pay'),
                // Adjustable income
                'salary_adjustment'       => $gf('salary_adjustment'),
                'override_amount'         => $gf('override_amount'),
                'ned_amount'              => $gf('ned_amount'),
                'overtime_pay'            => $gf('overtime_pay', 'total_overtime'),
                'night_diff_pay'          => $gf('night_diff_pay') + $gf('sat_night_prem') + $gf('sun_night_prem') + $gf('hol_night_prem'),
                'holiday_pay'             => $gf('holiday_pay'),
                // De minimis
                'year_end_gifts'          => $gf('year_end_gifts'),
                'rice_subsidy'            => $gf('rice_subsidy'),
                'uniform_allowance'       => $gf('uniform_allowance'),
                'laundry_allowance'       => $gf('laundry_allowance'),
                'meal_allowance'          => $gf('meal_allowance'),
                'medical_allowance'       => $gf('medical_allowance'),
                'salary_adjustment_non_tax' => $gf('salary_adjustment_non_tax'),
                'other_earnings_non_taxable' => $gf('other_earnings_non_taxable'),
                // Government contributions
                'sss_ee'          => $gf('sss_ee'),
                'phic_ee'         => $gf('phic_ee'),
                'hdmf_ee'         => $gf('hdmf_ee'),
                // Tax (signed: negative = refund)
                'withholding_tax' => $gf('withholding_tax'),
                // Loans
                'sss_loan'        => $gf('sss_loan'),
                'calamity_loan'   => $gf('calamity_loan'),
                'hdmf_loan'       => $gf('hdmf_loan'),
                'sal_adv'         => $gf('sal_adv', 'salary_advance'),
                // Stored computed values (for display reference)
                'gross_pay'       => $gf('gross_pay'),
                'net_pay'         => $gf('net_pay') ?: (float)($row['net_pay'] ?? 0),
                // Audit trail
                'edit_history'    => $d['_edit_history'] ?? [],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to load payslip data. Please try again.');
        }
    }

    public function savePayslipEditData()
    {
        $user = auth()->user();
        $isAdmin = \App\Libraries\PortalAccess::can('payroll', $user);
        if (!$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $input = $this->request->getJSON(true) ?? [];
        $id    = (int)($input['id'] ?? 0);
        if (!$id) return $this->response->setJSON(['error' => 'Missing id']);

        try {
            $db  = \Config\Database::connect();
            $row = $db->query("SELECT * FROM employee_payslips WHERE id = ?", [$id])->getRowArray();
            if (!$row) return $this->response->setJSON(['error' => 'Not found']);

            $d = json_decode((string)($row['generated_data'] ?? ''), true) ?? [];

            // net_basic_pay is stored as a subtotal that already has the *original*
            // night_diff_pay baked in (basic - absences - undertime + night diff).
            // night_diff_pay is also exposed as its own editable field here, so before
            // applying the incoming edits we split it back out into a "pure" basic
            // (no night diff) — otherwise recomputing gross below would add night diff
            // on top of a net_basic_pay that already contains it, double-counting it.
            $pureBasicPay = (float)($d['net_basic_pay'] ?? 0) - (float)($d['night_diff_pay'] ?? 0);

            // Adjustable fields (salary_adjustment allows negative values)
            $floatFields = [
                'salary_adjustment', 'override_amount', 'ned_amount',
                'overtime_pay', 'night_diff_pay', 'holiday_pay',
                'year_end_gifts', 'rice_subsidy', 'uniform_allowance', 'laundry_allowance',
                'meal_allowance', 'medical_allowance', 'salary_adjustment_non_tax',
                'other_earnings_non_taxable',
                'sss_ee', 'phic_ee', 'hdmf_ee',
                'withholding_tax',
                'sss_loan', 'calamity_loan', 'hdmf_loan', 'sal_adv',
            ];

            // Audit: record which fields actually changed before updating
            $changes = [];
            foreach ($floatFields as $f) {
                if (array_key_exists($f, $input)) {
                    $oldVal = round((float)($d[$f] ?? 0), 4);
                    $newVal = round((float)$input[$f], 4);
                    if (abs($oldVal - $newVal) > 0.001) {
                        $changes[] = [
                            'field'     => $f,
                            'old_value' => round($oldVal, 2),
                            'new_value' => round($newVal, 2),
                        ];
                    }
                }
            }

            // Apply new values
            foreach ($floatFields as $f) {
                if (array_key_exists($f, $input)) {
                    $d[$f] = (float)$input[$f];
                }
            }

            // Append to audit trail regardless (records every save, even no-change saves)
            if (!isset($d['_edit_history'])) $d['_edit_history'] = [];
            $session = \Config\Services::session();
            $editorName = $session->get('name') ?? $session->get('email') ?? $session->get('username') ?? 'admin';
            $d['_edit_history'][] = [
                'timestamp'  => date('Y-m-d H:i:s'),
                'edited_by'  => $editorName,
                'changes'    => $changes,
            ];

            // Recompute derived values
            $nsd       = (float)($d['night_diff_pay'] ?? 0);   // possibly just-edited value
            $netBasic  = $pureBasicPay + $nsd;                 // rebuild the subtotal from the (possibly new) night diff
            $d['net_basic_pay'] = round($netBasic, 2);         // keep stored subtotal in sync for future edits
            $salAdj    = (float)($d['salary_adjustment'] ?? 0)
                       + (float)($d['override_amount'] ?? 0)
                       + (float)($d['ned_amount'] ?? 0);
            $ot        = (float)($d['overtime_pay'] ?? $d['total_overtime'] ?? 0);
            $holiday   = (float)($d['holiday_pay'] ?? 0);
            $deMin     = (float)($d['year_end_gifts'] ?? 0)
                       + (float)($d['rice_subsidy'] ?? 0)
                       + (float)($d['uniform_allowance'] ?? 0)
                       + (float)($d['laundry_allowance'] ?? 0)
                       + (float)($d['meal_allowance'] ?? 0)
                       + (float)($d['medical_allowance'] ?? 0)
                       + (float)($d['salary_adjustment_non_tax'] ?? 0);
            $otherNT   = (float)($d['other_earnings_non_taxable'] ?? 0);
            $vlCash    = (float)($d['vl_cash_conversion'] ?? 0);
            $nonTax    = $deMin + $otherNT + $vlCash;

            $gross     = $netBasic + $ot + $salAdj + $holiday + $nonTax;
            $grossTax  = $gross - $nonTax;

            $sss       = (float)($d['sss_ee'] ?? 0);
            $phic      = (float)($d['phic_ee'] ?? 0);
            $hdmf      = (float)($d['hdmf_ee'] ?? 0);
            $wt        = (float)($d['withholding_tax'] ?? 0);   // signed; negative = refund
            $sssLoan   = (float)($d['sss_loan'] ?? 0);
            $calamity  = (float)($d['calamity_loan'] ?? 0);
            $hdmfLoan  = (float)($d['hdmf_loan'] ?? 0);
            $adv       = (float)($d['sal_adv'] ?? 0);

            $netTax    = $grossTax - $sss - $phic - $hdmf;
            $netPay    = $gross - $sss - $phic - $hdmf
                       - max(0.0, $wt) + max(0.0, -$wt)
                       - $sssLoan - $calamity - $hdmfLoan - $adv;

            $d['gross_pay']       = round($gross,   2);
            $d['net_pay']         = round($netPay,  2);
            $d['total_deductions'] = round(
                $sss + $phic + $hdmf + max(0.0, $wt) + $sssLoan + $calamity + $hdmfLoan + $adv,
                2
            );

            // $netPay above is the figure BEFORE the Loans-module deduction (this
            // formula never included Loans-module installments). Store it as
            // such, then take the installments out once — the same two-part
            // shape savePayslipRecord() stores, so a later send never has to
            // guess whether the stored net pay already had them removed.
            // A sent payslip's installments were already locked when it went
            // out, so its stored list is kept; a draft's is refreshed.
            $alreadySent = !empty($row['sent_at']);
            $loans = $alreadySent
                ? (array) ($d['loan_deductions'] ?? [])
                : $this->fetchLoanScheduleBreakdown((int) $row['employee_id'], (string) $row['period_end']);
            $netPayBeforeLoans = round($netPay, 2);
            $netPay = round($netPayBeforeLoans - array_sum(array_column($loans, 'amount')), 2);
            $d['net_pay_before_loans'] = $netPayBeforeLoans;
            $d['loan_deductions']      = $loans;
            $d['net_pay']              = $netPay;

            if (($row['send_status'] ?? '') === \App\Models\Client\EmployeePayslipModel::STATUS_SENDING) {
                return $this->response->setStatusCode(409)->setJSON(['error' => 'This payslip is being sent right now. Wait a moment, then save again.']);
            }

            $db->query(
                "UPDATE employee_payslips SET generated_data = ?, net_pay = ?, net_pay_before_loans = ?, needs_resend = ? WHERE id = ?",
                [json_encode($d), $netPay, $netPayBeforeLoans, $alreadySent ? 1 : (int) ($row['needs_resend'] ?? 0), $id]
            );

            $empName = $d['employee_name'] ?? trim((string) $db->query(
                "SELECT CONCAT(first_name,' ',last_name) AS n FROM employees WHERE id = ?",
                [$row['employee_id']]
            )->getRowArray()['n'] ?? '');
            service('activityLogger')->payslipEdited(
                $id, $empName ?: 'Unknown', $row['period_start'], $row['period_end'], $changes
            );

            return $this->response->setJSON([
                'success'    => true,
                'gross_pay'  => round($gross,  2),
                'net_taxable'=> round($netTax, 2),
                'net_pay'    => round($netPay, 2),
                'needs_resend' => $alreadySent,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to save payslip changes. Please try again.');
        }
    }

    // ================= EXPORT / IMPORT =================
    public function exportRegister()
    {
        $month  = $this->request->getGet('month') ?? date('Y-m');
        // Was never read — this export always dumped the whole calendar month
        // regardless of which half (1st/2nd) was loaded on screen. Now it
        // exports exactly what's loaded, same as getRegisterRows().
        $period = $this->request->getGet('period') ?? '';

        [$periodWhere, $binds, $salaryMonthFilter] = $this->periodSqlFilter(
            $month, $period, 'employee_payslips.period_start', 'employee_payslips.period_end'
        );

        $records = \Config\Database::connect()->query("
            SELECT employee_payslips.*, CONCAT(employees.first_name,' ',employees.last_name) as employee_name
            FROM employee_payslips
            JOIN employees ON employees.id = employee_payslips.employee_id
            WHERE $periodWhere
            ORDER BY employee_name ASC
        ", $binds)->getResultArray();

        $records = $this->filterRowsBySalaryMonth($records, $salaryMonthFilter);

        $filename = "payroll_register_{$month}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Employee', 'Period Start', 'Period End',
            'Basic Pay', 'Night Premium',
            'Less Absences', 'Less Undertime',
            'Net Basic Pay',
            'Reg OT', 'Sat OT', 'Sun OT', 'Hol OT',
            'Salary Adjustment', 'De Minimis',
            'Gross Pay',
            'SSS', 'PhilHealth', 'Pag-IBIG', 'Withholding Tax',
            'Loan Deductions',
            'Total Deductions', 'Net Pay',
        ]);

        foreach ($records as $row) {
            $d = json_decode((string)($row['generated_data'] ?? ''), true) ?? [];
            $f = fn($k) => number_format((float)($d[$k] ?? 0), 2, '.', '');

            $sss     = (float)($d['sss_ee']          ?? 0);
            $phic    = (float)($d['phic_ee']          ?? 0);
            $hdmf    = (float)($d['hdmf_ee']          ?? 0);
            $wtax    = (float)($d['withholding_tax']  ?? 0);
            $loanDeductions = $d['loan_deductions'] ?? [];
                if (is_string($loanDeductions)) {
                    $loanDeductions = json_decode($loanDeductions, true) ?? [];
                }
            $loanSummary = [];

            foreach ($loanDeductions as $loan) {
                $loanSummary[] =
                    ($loan['loan_type'] ?? 'Loan')
                    . ': '
                    . number_format($loan['amount'] ?? 0, 2);
            }

            $loanText = implode(' | ', $loanSummary);
            $totalDed = (float)($d['total_deductions'] ?? 0);
            $netPay  = (float)($row['net_pay'] ?? 0);

            fputcsv($output, [
                $row['employee_name'],
                $row['period_start'],
                $row['period_end'],
                $f('basic_pay'),
                $f('night_diff_pay'),
                $f('absent_amt'),
                $f('undertime_amt'),
                $f('net_basic_pay'),
                $f('reg_ot'),
                $f('sat_ot'),
                $f('sun_ot'),
                $f('hol_ot'),
                $f('other_earnings_taxable'),
                $f('de_minimis'),
                number_format((float)($d['gross_pay'] ?? 0), 2, '.', ''),
                number_format($sss,  2, '.', ''),
                number_format($phic, 2, '.', ''),
                number_format($hdmf, 2, '.', ''),
                number_format($wtax, 2, '.', ''),
                $loanText,
                number_format($totalDed, 2, '.', ''),
                number_format($netPay,   2, '.', ''),
            ]);
        }

        fclose($output);
        exit();
    }

    public function exportTimesheet()
    {
        $employeeId = $this->request->getGet('employee_id');
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');

        if (!$employeeId || !$startDate || !$endDate) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing parameters']);
        }

        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->find($employeeId);
        if (!$employee) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Employee not found']);
        }

        return $this->buildTimesheetWorkbook($employee, (string) $startDate, (string) $endDate);
    }

    /**
     * The actual "system timesheet" XLSX — Timesheet + Timesheet Totals +
     * Payroll Summary sheets. Shared by this controller's own exportTimesheet()
     * AND by TimesheetController's "Past Timesheets" / "Review Employee
     * Timesheets" export buttons, so every export of a cutoff's timesheet in
     * the app produces the exact same file, not three near-identical ones
     * that can quietly drift apart.
     */
    public function buildTimesheetWorkbook(array $employee, string $startDate, string $endDate)
    {
        $employeeId = (int) $employee['id'];

        foreach ([$startDate, $endDate] as $date) {
            $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',(string)$date);
            if(!$parsed||$parsed->format('Y-m-d')!==$date)return $this->response->setStatusCode(400)->setJSON(['error'=>'Invalid cutoff date']);
        }
        if($startDate>$endDate)return $this->response->setStatusCode(400)->setJSON(['error'=>'Invalid cutoff range']);

        // Read the same calculations as the system timesheet and payroll screen.
        // Exporting never recalculates or saves payroll transactions.
        $detailed=(new \App\Services\Client\TimesheetsService())->getDetailedTimesheet((int)$employee['user_id'],$startDate,$endDate);
        $summaryResponse=$this->getAttendanceSummary((int)$employeeId,$startDate,$endDate);
        if($summaryResponse->getStatusCode()!==200)return $summaryResponse;
        $summary=json_decode($summaryResponse->getJSON(),true,512,JSON_THROW_ON_ERROR);
        $book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        try {
            $sheet=$book->getActiveSheet()->setTitle('Timesheet');
            // Client and Task Performed are intentionally left off this sheet —
            // they now live in the "Task Log" sheet below, one row per task
            // instead of a single "; "-joined summary column.
            $headers=['employee_number','date','time_in','time_out','break_duration','Regular Hours','OT Hours','Unapproved OT Hours','RDOT Hours','Tardy Minutes','Undertime Hours','Night Diff','Remarks','Edited'];
            $sheet->fromArray($headers,null,'A1');
            $ids=array_values(array_filter(array_column($detailed['daily'],'attendance_id')));$breaks=[];
            if($ids)foreach((new AttendanceModel())->select('id,break_duration')->whereIn('id',$ids)->findAll() as $row)$breaks[$row['id']]=(float)$row['break_duration'];

            // Attendance rows a staff member has manually corrected (vs. the
            // normal clock-in/clock-out/break flow) — TimesheetController::
            // updateAttendance() logs these as action=updated, module=attendance,
            // description="Attendance corrected by {username}" (not the unused
            // ActivityLogger::attendanceManualEdit() wrapper), so match on that.
            $editedIds=[];
            if($ids)foreach((new \App\Models\ActivityLogModel())
                ->select('record_id')
                ->where('module','attendance')
                ->where('action','updated')
                ->like('description','Attendance corrected by ')
                ->whereIn('record_id',$ids)
                ->findAll() as $log)$editedIds[(int)$log['record_id']]=true;

            $redFill=[
                'fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor'=>['rgb'=>'FFC7CE'],
            ];
            $greenFill=[
                'fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor'=>['rgb'=>'C6EFCE'],
            ];
            $editedFill=[
                'fillType'=>\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor'=>['rgb'=>'FFD966'],
            ];

            $line=2;
            foreach($detailed['daily'] as $row){
                $cells=[$employee['employee_number'],$row['date'],$row['time_in_datetime']??'',$row['time_out_datetime']??'',$breaks[$row['attendance_id']]??0];
                foreach(['regular_hours','ot_hours','unapproved_ot_hours','rdot_hours','tardy_minutes','undertime_hours','night_diff','remarks'] as $key)$cells[]=$row[$key]??'';
                $wasEdited=!empty($row['attendance_id'])&&isset($editedIds[(int)$row['attendance_id']]);
                $cells[]=$wasEdited?'Yes':'';
                foreach($cells as $index=>$value){
                    $type=is_int($value)||is_float($value)?\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC:\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
                    $sheet->setCellValueExplicit([$index+1,$line],$value,$type);
                }
                // Whole row: red if late, green if on time. The Edited cell
                // (column N) gets its own orange fill on top so both signals
                // stay visible — a row can't show two different fills at once,
                // but a single cell within it can differ from the rest.
                $isLate=(float)($row['tardy_minutes']??0)>0;
                $sheet->getStyle('A'.$line.':N'.$line)->applyFromArray(['fill'=>$isLate?$redFill:$greenFill]);
                if($wasEdited)$sheet->getStyle('N'.$line)->applyFromArray(['fill'=>$editedFill]);
                $line++;
            }
            $sheet->freezePane('A2');$sheet->setAutoFilter('A1:N'.max(1,$line-1));

            // One row per logged task (not just the "; "-joined summary above)
            // so the exported file carries the same per-task time/duration/client
            // detail the Timesheet page and review modal show on screen.
            $taskLog=$book->createSheet()->setTitle('Task Log');
            $taskLog->fromArray(['Date','Time Start','Time End','Duration','Client','Task Performed'],null,'A1');
            $taskLine=2;
            foreach($detailed['daily'] as $row){
                foreach(($row['tasks']??[]) as $task){
                    $start=\DateTimeImmutable::createFromFormat('!H:i:s',substr((string)$task['start_time'],0,8))
                        ?:\DateTimeImmutable::createFromFormat('!H:i',substr((string)$task['start_time'],0,5));
                    $end=\DateTimeImmutable::createFromFormat('!H:i:s',substr((string)$task['end_time'],0,8))
                        ?:\DateTimeImmutable::createFromFormat('!H:i',substr((string)$task['end_time'],0,5));
                    $durationLabel='';
                    if($start&&$end){
                        if($end<=$start)$end=$end->modify('+1 day');
                        $minutes=(int)round(($end->getTimestamp()-$start->getTimestamp())/60);
                        $durationLabel=sprintf('%dh %02dm',intdiv($minutes,60),$minutes%60);
                    }
                    $taskCells=[$row['date'],$task['start_time']??'',$task['end_time']??'',$durationLabel,$task['client']??'',$task['task_performed']??''];
                    foreach($taskCells as $index=>$value)$taskLog->setCellValueExplicit([$index+1,$taskLine],$value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $taskLine++;
                }
            }
            $taskLog->freezePane('A2');
            if($taskLine>2)$taskLog->setAutoFilter('A1:F'.($taskLine-1));

            $totals=$book->createSheet()->setTitle('Timesheet Totals');
            $totals->fromArray([['System timesheet totals','Value'],['Employee',$employee['employee_number']],['Cutoff start',$startDate],['Cutoff end',$endDate]]);
            $line=5;foreach($detailed['totals'] as $key=>$value)$totals->fromArray([$key,$value],null,'A'.$line++,true);
            $payroll=$book->createSheet()->setTitle('Payroll Summary');
            $payroll->fromArray([['Payroll attendance summary','Value'],['Employee',$employee['employee_number']],['Cutoff start',$startDate],['Cutoff end',$endDate],['Source','Same attendance summary used by Payroll Management'],['Scope','Attendance inputs; excludes unsaved manual payroll adjustments']]);
            foreach([$totals,$payroll] as $tab)$tab->setCellValueExplicit('B2',$employee['employee_number'],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $line=7;foreach($summary as $key=>$value)if(is_scalar($value)||$value===null)$payroll->fromArray([$key,$value],null,'A'.$line++,true);
            foreach($book->getAllSheets() as $tab){
                $tab->getStyle('1:1')->getFont()->setBold(true);
                foreach($tab->getColumnIterator() as $column)$tab->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }
            $book->setActiveSheetIndex(0);
            ob_start();
            try{(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');$content=ob_get_contents();}finally{ob_end_clean();}
            $slug=preg_replace('/[^A-Za-z0-9_-]/','_',$employee['employee_number']);
            return $this->response->download("timesheet_{$slug}_{$startDate}_to_{$endDate}.xlsx",$content)->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } finally {$book->disconnectWorksheets();}
    }

    /** Decodes one of the import form's JSON-encoded column/employee mapping fields. */
    private function importMap(string $key): array
    {
        $raw=$this->request->getPost($key);
        if($raw===null||$raw==='')return [];
        if(!is_string($raw))throw new \DomainException('Invalid import mapping.');
        $map=json_decode($raw,true);
        if(!is_array($map)||json_last_error()!==JSON_ERROR_NONE)throw new \DomainException('Invalid import mapping.');
        foreach($map as $value)if(!is_scalar($value))throw new \DomainException('Invalid import mapping value.');
        ksort($map);
        return $map;
    }

    public function previewTimesheetImport()
    {
        session()->remove('timesheet_import_preview');
        try {
            $file=$this->request->getFile('timesheet_file');
            if(!$file||!$file->isValid())throw new \DomainException('Choose a valid CSV or XLSX file.');
            $columns=$this->importMap('column_map');$employees=$this->importMap('employee_map');
            $order=(string)($this->request->getPost('date_order')??'dmy');
            $unit=(string)($this->request->getPost('break_unit')??'auto');
            $periodStart=$this->request->getPost('period_start');$periodEnd=$this->request->getPost('period_end');
            $periodStart=($periodStart===null||$periodStart==='')?null:(string)$periodStart;
            $periodEnd=($periodEnd===null||$periodEnd==='')?null:(string)$periodEnd;
            $forcedEmployee=filter_var($this->request->getPost('employee_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;
            $extension=strtolower(pathinfo($file->getClientName(),PATHINFO_EXTENSION));
            $plan=(new \App\Services\Client\TimesheetImportService())->preview($file->getTempName(),$extension,$columns,$employees,$order,$unit,$periodStart,$periodEnd,$forcedEmployee);
            if($plan['can_import']) {
                $token=bin2hex(random_bytes(32));
                session()->set('timesheet_import_preview',['token'=>$token,'user_id'=>(int)auth()->id(),'created'=>time(),
                    'columns'=>$plan['column_map'],'employees'=>$employees,'order'=>$order,'unit'=>$unit,'extension'=>$extension,
                    'period_start'=>$periodStart,'period_end'=>$periodEnd,'forced_employee'=>$forcedEmployee,
                    'file_hash'=>$plan['file_hash'],'hash'=>$plan['plan_hash']]);
                $plan['preview_token']=$token;
            }
            unset($plan['records'],$plan['plan_hash'],$plan['file_hash']);
            return $this->response->setJSON($plan);
        } catch(\DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON(['success'=>false,'error'=>$e->getMessage()]);
        } catch(\Throwable $e) {
            log_message('error','Timesheet preview failed: {message}',['message'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['success'=>false,'error'=>'Unable to preview this file. Check its format and try again.']);
        }
    }

    public function importTimesheet()
    {
        try {
            $token=$this->request->getPost('preview_token');
            $plan=session()->get('timesheet_import_preview');
            if(!is_string($token)||!is_array($plan)||!hash_equals($plan['token'],$token)||$plan['user_id']!==(int)auth()->id()||time()-$plan['created']>900)
                throw new \DomainException('Preview the file before importing. Previews expire after 15 minutes.');
            $file=$this->request->getFile('timesheet_file');
            if(!$file||!$file->isValid()||!hash_equals($plan['file_hash'],hash_file('sha256',$file->getTempName())))
                throw new \DomainException('The file changed. Preview it again.');
            $result=(new \App\Services\Client\TimesheetImportService())->commit($file->getTempName(),$plan['extension'],$plan['columns'],$plan['employees'],$plan['order'],$plan['unit'],$plan['hash'],$plan['period_start']??null,$plan['period_end']??null,$file->getClientName(),(int)auth()->id(),$plan['forced_employee']??null);
            session()->remove('timesheet_import_preview');
            return $this->response->setJSON($result);
        } catch(\DomainException $e) {
            return $this->response->setStatusCode(409)->setJSON(['success'=>false,'error'=>$e->getMessage()]);
        } catch(\Throwable $e) {
            log_message('error','Timesheet import failed: {message}',['message'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['success'=>false,'error'=>'Import failed; no rows were saved. Preview again before retrying.']);
        }
    }
    private function calcNightDiffHours(string $timeIn, string $timeOut): float
    {
        $start   = new \DateTime($timeIn);
        $end     = new \DateTime($timeOut);
        $date    = $start->format('Y-m-d');
        $ndStart = new \DateTime($date . ' 22:00:00');
        $ndEnd   = (new \DateTime($date . ' 06:00:00'))->modify('+1 day');
        $ovStart = max($start, $ndStart);
        $ovEnd   = min($end,   $ndEnd);
        if ($ovStart >= $ovEnd) return 0.0;
        $diff = $ovStart->diff($ovEnd);
        return ($diff->h * 60 + $diff->i) / 60.0;
    }

    // ================= GENERATE PDF PAYSLIP =================
    public function generatePayslip($transactionId)
    {
        try {
            $txModel = new PayrollTransactionModel();
            $payroll = $txModel->find($transactionId);
            if (!$payroll) throw new \App\Exceptions\ValidationException('Payroll transaction not found');

            $employeeModel = new EmployeeModel();
            $employee = $employeeModel->find($payroll['employee_id']);

            $data = [
                'payroll' => $payroll,
                'employee' => $employee,
                'company' => 'Your Company Name'
            ];

            $html = view('client_portal/payslip_pdf', $data);
            $options = new Options();
            $options->set('defaultFont', 'Courier');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream("payslip_{$employee['employee_number']}_{$payroll['period_start']}.pdf", ['Attachment' => false]);
            exit;
        } catch (\Exception $e) {
            return $this->flashError($e, 'Unable to generate payslip PDF.');
        }
    }

    // sendPayslip() (route payroll/send/{transactionId}) was removed: an old,
    // unused send path that emailed an unencrypted placeholder PDF, never
    // recorded sent_at, and never locked the loan installment — it bypassed the
    // payslip release flow entirely. Payslips are sent only via deliverPayslip().

    // ================= OTHER METHODS =================
    public function getTimesheet($employeeId, $startDate, $endDate)
    {
        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->find($employeeId);
        if (! $employee) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Employee not found']);
        }

        $attendanceModel = new AttendanceModel();
        $data = $attendanceModel
            ->select('DATE(time_in) as date, time_in, time_out, break_duration, total_hours')
            ->where('user_id', $employee['user_id'])
            ->where('DATE(time_in) >=', $startDate)
            ->where('DATE(time_in) <=', $endDate)
            ->orderBy('time_in', 'ASC')
            ->findAll();
        return $this->response->setJSON($data);
    }

    public function importCSV()
    {
        $file = $this->request->getFile('csv_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Invalid CSV file.');
        }
        $csvData = array_map('str_getcsv', file($file->getTempName()));
        array_shift($csvData);
        session()->set('imported_payroll_data', $csvData);
        return redirect()->back()->with('success', 'CSV imported.');
    }

    public function exportCSV($month = null)
    {
        $month = $month ?? date('Y-m');
        $payrollModel = new PayrollModel();
        $payrolls = $payrollModel
            ->select("payroll.*, CONCAT(employees.first_name,' ',employees.last_name) as employee_name")
            ->join('employees', 'employees.id = payroll.employee_id')
            ->where('month_year', $month)
            ->findAll();

        $filename = "payroll_export_{$month}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Employee', 'Phone', 'Daily Rate', 'DST', 'Currency', 'Status', 'Month']);
        foreach ($payrolls as $row) {
            fputcsv($output, [
                $row['id'], $row['employee_name'], $row['phone'],
                $row['daily_rate'], $row['dst'], $row['currency'],
                $row['status'], $row['month_year']
            ]);
        }
        fclose($output);
        exit();
    }

    public function saveSettings()
    {
        $user = auth()->user();
        $isAdmin = $user->inGroup('client_payroll_staff') || $user->inGroup('superadmin') || $user->inGroup('admin');
        if (!$isAdmin) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        $settingsModel = new PayrollSettingsModel();
        $data = $this->request->getPost();

        // Track which contribution-related settings actually changed
        $contributionKeys = ['phic_rate', 'phic_cap', 'hdmf_rate', 'hdmf_max'];
        $changed = [];

        // Reject a bad contribution value up front (nothing is saved) — e.g.
        // typing 5 instead of 0.05 as a rate would multiply everyone's
        // deduction by 100 on the next payslip. Rates are fractions (0-1),
        // caps are non-negative amounts; other settings are untouched.
        // The toast also rides on the redirect URL (toast.js reads and strips
        // it) so it isn't lost to the one-shot flash-data race.
        $rejectSettings = function (string $message) {
            $target = (string) previous_url();
            $target .= (str_contains($target, '?') ? '&' : '?') . 'toast_error=' . rawurlencode($message);
            return redirect()->to($target)->with('error', $message);
        };
        foreach (['phic_rate', 'hdmf_rate'] as $rateKey) {
            if (array_key_exists('setting_' . $rateKey, $data)) {
                $v = trim((string) $data['setting_' . $rateKey]);
                if (! is_numeric($v) || (float) $v < 0 || (float) $v > 1) {
                    return $rejectSettings("Invalid value for {$rateKey}: enter a rate between 0 and 1 (e.g. 0.05 for 5%). Nothing was saved.");
                }
            }
        }
        foreach (['phic_cap', 'hdmf_max'] as $capKey) {
            if (array_key_exists('setting_' . $capKey, $data)) {
                $v = trim((string) $data['setting_' . $capKey]);
                if (! is_numeric($v) || (float) $v < 0) {
                    return $rejectSettings("Invalid value for {$capKey}: enter a non-negative amount. Nothing was saved.");
                }
            }
        }

        foreach ($data as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $realKey = substr($key, 8);
                if (in_array($realKey, $contributionKeys, true)) {
                    $old = $settingsModel->getSetting($realKey);
                    if ((string) $old !== (string) $value) {
                        $changed[$realKey] = ['from' => $old, 'to' => $value];
                    }
                }
                $settingsModel->setSetting($realKey, $value);
            }
        }

        if (! empty($changed)) {
            $lines = [];
            $labels = [
                'phic_rate' => 'PhilHealth Rate',
                'phic_cap'  => 'PhilHealth Monthly Income Cap (₱)',
                'hdmf_rate' => 'Pag-IBIG Rate',
                'hdmf_max'  => 'Pag-IBIG Max Contribution (₱)',
            ];
            foreach ($changed as $key => $diff) {
                $label = $labels[$key] ?? $key;
                $lines[] = "• {$label}: {$diff['from']} → {$diff['to']}";
            }
            $this->postContributionAnnouncement(
                'Government Contribution Settings Updated',
                "Payroll contribution settings have been updated:\n\n" . implode("\n", $lines)
                    . "\n\nThese changes will apply to the next payroll computation."
            );
            service('activityLogger')->payrollSettingsUpdated($changed);
        }

        return redirect()->back()->with('success', 'Settings saved.');
    }

    public function getSettings()
    {
        $settingsModel = new PayrollSettingsModel();
        $keys = [
            'monthly_hours',
            'night_diff_start', 'night_diff_end', 'night_diff_rate',
            'rest_day_rate', 'reg_holiday_rate', 'spec_holiday_rate',
            'ot_regular_rate', 'ot_rest_rate', 'ot_holiday_rate',
            'phic_rate', 'phic_cap',
            'hdmf_rate', 'hdmf_max',
            'default_period_type', 'first_half_cutoff',
            // Cross-month period definition
            'half1_start_day', 'half1_start_offset', 'half1_end_day', 'half1_end_offset',
            'half2_start_day', 'half2_start_offset', 'half2_end_day', 'half2_end_offset',
            // Monthly cutoff period (own start/end day, independent of half1/half2 —
            // see PayrollCutoff::monthlyRange()). Defaults to the full calendar
            // month when left blank.
            'monthly_start_day', 'monthly_start_offset', 'monthly_end_day', 'monthly_end_offset',
            // Salary day per half (day of the salary month + month offset,
            // 0 = last day) and the weekend/holiday rule — the default date on
            // the Payslip tab (Payroll Output)'s Schedule, and the date Automatic
            // release mode schedules to (see PayrollCutoff::salaryDayFor()).
            'half1_salary_day', 'half1_salary_offset', 'half2_salary_day', 'half2_salary_offset',
            'monthly_salary_day', 'monthly_salary_offset',
            'salary_day_adjust',
        ];
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $settingsModel->getSetting($key);
        }

        // Bundle the currently-active SSS bracket table so the client can compute
        // SSS from the same data an admin edits in Payroll Settings, instead of a
        // hardcoded table that Settings has no way to affect.
        $db = \Config\Database::connect();
        $settings['sss_brackets'] = $db->query(
            'SELECT range_start, range_end, ee_regular, ee_wisp
             FROM   sss_brackets
             WHERE  is_active = 1
             ORDER  BY range_start ASC'
        )->getResultArray();

        return $this->response->setJSON($settings);
    }

    // ================= PAYSLIP RELEASE (Payroll Output → Payslip) =================
    //
    // A saved payslip is a draft the employee can't see. It's delivered only
    // from the Payslip tab (Payroll Output) (Send Now / Resend / Retry, one request per
    // payslip so bulk sends show progress) or by the payroll:send-scheduled-payslips
    // console command on its scheduled date. Both go through deliverPayslip().

    /** POST client_portal/payroll/release/send  {id, force?, method?: 'individual'|'bulk'} */
    public function releaseSend()
    {
        $input = $this->request->getJSON(true) ?? [];
        $id    = (int) ($input['id'] ?? 0);
        if ($id < 1) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Missing payslip id.']);
        }

        $user   = auth()->user();
        $method = ($input['method'] ?? '') === 'bulk' ? 'bulk' : 'individual';
        $result = $this->deliverPayslip($id, $user ? (string) ($user->username ?? 'Unknown') : 'System', $method, !empty($input['force']));
        $http   = $result['http'] ?? ($result['success'] ? 200 : 422);
        unset($result['http']);

        return $this->response->setStatusCode($http)->setJSON($result);
    }

    /**
     * The one place a payslip is actually delivered: builds the
     * password-protected PDF from the SAVED figures, emails it, marks it sent,
     * and (first send only) locks the Loans-module installment. Never
     * recalculates money — the saved net_pay already has the loan taken out
     * exactly once (see savePayslipRecord()).
     *
     * Not a route (auto-routing is off); called by releaseSend() and by the
     * scheduled-send console command, so it never touches $this->request /
     * $this->response.
     *
     * First send is refused (payslip held as Needs Review) when:
     *  - it was saved before the release update (no before-loans figure),
     *  - the Loans-module installments due now differ from the saved ones,
     *  - attendance / approved requests changed since saving — unless $force
     *    (a person deliberately chose to send the saved figures anyway).
     *
     * @return array{success:bool, status?:string, error?:string, warning?:string, needs_confirm?:bool, http?:int}
     */
    public function deliverPayslip(int $payslipId, string $actor, string $method = 'individual', bool $force = false): array
    {
        $P            = \App\Models\Client\EmployeePayslipModel::class;
        $db           = \Config\Database::connect();
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();

        $row = $payslipModel->find($payslipId);
        if (!$row) {
            return ['success' => false, 'error' => 'Payslip not found.', 'http' => 404];
        }

        $isResend  = !empty($row['sent_at']);
        $claimable = $isResend
            ? [$P::STATUS_SENT]
            : [$P::STATUS_DRAFT, $P::STATUS_SCHEDULED, $P::STATUS_FAILED, $P::STATUS_NEEDS_REVIEW];
        if (!in_array($row['send_status'], $claimable, true)) {
            return [
                'success' => false,
                'error'   => $row['send_status'] === $P::STATUS_SENDING
                    ? 'This payslip is already being sent.'
                    : 'This payslip can\'t be sent in its current state (' . $row['send_status'] . ').',
                'http'    => 409,
            ];
        }

        $data     = json_decode((string) ($row['generated_data'] ?? ''), true);
        $employee = (new \App\Models\Client\EmployeeModel())->find($row['employee_id']);
        $empName  = (is_array($data) ? ($data['employee_name'] ?? '') : '')
            ?: trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''))
            ?: ('Employee #' . $row['employee_id']);

        if (!$isResend) {
            if ($row['net_pay_before_loans'] === null || !is_array($data) || $data === []) {
                return $this->holdPayslip($row, $empName, $P::STATUS_NEEDS_REVIEW,
                    'Saved before the release update - open it in Generate Payslip and save it again before sending.', false);
            }

            $savedLoans   = $this->loanSignature((array) ($data['loan_deductions'] ?? []));
            $currentLoans = $this->loanSignature($this->fetchLoanScheduleBreakdown((int) $row['employee_id'], (string) $row['period_end']));
            if ($savedLoans !== $currentLoans) {
                return $this->holdPayslip($row, $empName, $P::STATUS_NEEDS_REVIEW,
                    'Loan installments due this cutoff changed since it was saved - save it again from Generate Payslip.', false);
            }

            if (!$force && $row['source_fingerprint'] !== $this->payslipSourceFingerprint((int) $row['employee_id'], (string) $row['period_start'], (string) $row['period_end'])) {
                return $this->holdPayslip($row, $empName, $P::STATUS_NEEDS_REVIEW,
                    'Attendance or approved requests changed since it was saved - save it again from Generate Payslip, or send the saved figures anyway.', true);
            }
        }

        if (!$employee) {
            return $this->holdPayslip($row, $empName, $P::STATUS_FAILED, 'Employee record not found.', false);
        }
        $account = $db->table('users')->select('active')->where('id', $employee['user_id'])->get()->getRowArray();
        if ($account && !(bool) $account['active']) {
            return $this->holdPayslip($row, $empName, $P::STATUS_FAILED, 'Employee account is inactive.', false);
        }
        $emailAddress = $this->getUserEmail($employee['user_id']);
        if (!$emailAddress) {
            return $this->holdPayslip($row, $empName, $P::STATUS_FAILED, 'No email address on file for this employee.', false);
        }

        // Claim it: only the request that flips it to 'sending' goes on, so a
        // Send Now racing the scheduled job (or two job runs) can't both email it.
        $db->table('employee_payslips')
            ->where('id', $payslipId)
            ->where('send_status', $row['send_status'])
            ->update([
                'send_status'     => $P::STATUS_SENDING,
                'last_attempt_at' => date('Y-m-d H:i:s'),
                'send_attempts'   => $isResend ? (int) $row['send_attempts'] : (int) $row['send_attempts'] + 1,
            ]);
        if ($db->affectedRows() < 1) {
            return ['success' => false, 'error' => 'This payslip was just updated by someone else. Refresh and try again.', 'http' => 409];
        }

        // Re-read now that the row is ours: a Save that landed between the
        // checks above and the claim must not leave us emailing stale figures.
        // (Saves are blocked from here on - see savePayslipRecord().)
        $row  = $payslipModel->find($payslipId) ?: $row;
        $data = json_decode((string) ($row['generated_data'] ?? ''), true) ?: $data;

        $employeeId      = (int) $row['employee_id'];
        $payslipRecordId = (int) $payslipId;
        $data['period_start'] = $row['period_start'];
        $data['period_end']   = $row['period_end'];
        $emailed  = false;
        $tempFile = null;

        try {
            // --- Step 2: Generate PDF ---
            ini_set('memory_limit', '1G');
            $html = $this->buildPayslipHtmlForPdf($data, $employee);
            $options = new Options();
            $options->set('defaultFont', 'Courier');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $this->encryptPayslipPdf($dompdf, $employee);
            $pdfOutput = $dompdf->output();

            $permanentDir = WRITEPATH . 'uploads/payslips/permanent/';
            if (!is_dir($permanentDir)) mkdir($permanentDir, 0777, true);
            $filename      = "payslip_{$employee['employee_number']}_{$data['period_start']}.pdf";
            $permanentPath = $permanentDir . $filename;
            file_put_contents($permanentPath, $pdfOutput);
            $payslipModel->update($payslipRecordId, ['file_path' => $permanentPath]);

            // --- Step 3: Send email with PDF attachment ---
            $tempPath = WRITEPATH . 'uploads/payslips/temp/';
            if (!is_dir($tempPath)) mkdir($tempPath, 0777, true);
            $tempFile = $tempPath . $filename;
            file_put_contents($tempFile, $pdfOutput);

            $empName = $data['employee_name'] ?? trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));

            $email = service('email');
            // Shared instance: the scheduled job sends many payslips in one
            // process, so clear the previous recipient/attachment first.
            $email->clear(true);
            $email->setMailType('html');
            $email->setTo($emailAddress);
            $email->setSubject("Your Payslip for {$data['period_start']} to {$data['period_end']}");
            $email->setMessage("
                Dear " . esc((string) $empName) . ",<br><br>
                Please find your payslip attached as a PDF. The file is password-protected.<br><br>
                <strong>PDF Password:</strong> Your payslip password as set in your account profile.
                If you have not changed it, the default is your Employee Number followed by your Birthdate (YYYYMMDD).<br><br>
                Regards,<br>
                Payroll Team
            ");
            $email->attach($tempFile);

            if (!$email->send()) {
                log_message('error', 'deliverPayslip email failed for payslip {id}: {debug}', [
                    'id' => $payslipRecordId, 'debug' => $email->printDebugger(['headers', 'subject']),
                ]);
                throw new \RuntimeException('Email delivery failed. Check the mail server (SMTP) settings, then retry.');
            }

            $emailed = true;
            if (is_file($tempFile)) unlink($tempFile);

            $payslipModel->update($payslipRecordId, [
                'sent_at'         => date('Y-m-d H:i:s'),
                'sent_by'         => mb_substr($actor, 0, 100),
                'send_status'     => $P::STATUS_SENT,
                'needs_resend'    => 0,
                'last_send_error' => null,
                'review_reason'   => null,
            ]);

            $loanWarning = null;
            if (!$isResend) {
                // Only now — the payslip has actually been emailed to the
                // employee — do we lock the loan and consume the installment.
                // Locking on Generate/Save instead would create a permanent
                // payment record for a payslip that might never be sent.
                // payroll_id references employee_payslips (this flow has no
                // payroll_transactions row) so payments stay traceable to the
                // payslip that generated them.
                //
                // markInstallmentPaid() can update several loans' schedules
                // for one employee; without a transaction, a failure partway
                // through left some installments marked paid and others not,
                // with no way to tell which reflect this send. Wrapped so
                // it's all-or-nothing — a failure here doesn't undo the email
                // that already went out (can't recall it), so it's caught
                // and logged rather than surfaced as a failed send; the
                // payslip record itself still reflects sent_at/sent_by above.
                $db->transBegin();
                try {
                    $this->markLoansActiveOngoing((int) $employeeId, (string) $data['period_end']);
                    (new \App\Services\Client\LoanService())->markInstallmentPaid(
                        (int) $employeeId,
                        (string) $data['period_start'],
                        (string) $data['period_end'],
                        (int) $payslipRecordId
                    );
                    if (!$db->transStatus()) throw new \RuntimeException('Loan installment update failed to save.');
                    $db->transCommit();
                } catch (\Throwable $e) {
                    $db->transRollback();
                    log_message('error', 'deliverPayslip: loan installment marking failed for employee {id}, payslip {payslip}: {message}', [
                        'id' => $employeeId, 'payslip' => $payslipRecordId, 'message' => $e->getMessage(),
                    ]);
                    $loanWarning = 'Payslip sent, but the loan installment could not be recorded — please review this employee\'s loans manually.';
                }
            }

            service('activityLogger')->payslipSent(
                $payslipRecordId,
                $empName,
                (string) $data['period_start'],
                (string) $data['period_end'],
                $emailAddress,
                $isResend ? 'resend' : $method
            );

            return ['success' => true, 'status' => $P::STATUS_SENT] + ($loanWarning ? ['warning' => $loanWarning] : []);
        } catch (\Throwable $e) {
            if ($tempFile && is_file($tempFile)) @unlink($tempFile);
            log_message('error', 'deliverPayslip failed for payslip {id}: {message}', ['id' => $payslipRecordId, 'message' => $e->getMessage()]);

            if ($emailed) {
                // The email already went out and can't be recalled — keep it
                // recorded as sent rather than inviting a duplicate send.
                $db->table('employee_payslips')->where('id', $payslipRecordId)->update([
                    'send_status' => $P::STATUS_SENT,
                    'sent_at'     => $row['sent_at'] ?: date('Y-m-d H:i:s'),
                ]);
                return ['success' => true, 'status' => $P::STATUS_SENT, 'warning' => 'Payslip emailed, but a follow-up step failed: ' . $e->getMessage()];
            }

            $reason = $e instanceof \RuntimeException ? $e->getMessage() : ('Unexpected error while sending: ' . $e->getMessage());
            $db->table('employee_payslips')->where('id', $payslipRecordId)->update([
                'send_status'     => $isResend ? $P::STATUS_SENT : $P::STATUS_FAILED,
                'last_send_error' => mb_substr($reason, 0, 500),
            ]);
            if (!$isResend) {
                service('activityLogger')->payslipSendProblem($payslipRecordId, $empName, (string) $row['period_start'], (string) $row['period_end'], $P::STATUS_FAILED, $reason);
            }

            return ['success' => false, 'status' => $isResend ? $P::STATUS_SENT : $P::STATUS_FAILED, 'error' => $reason, 'http' => 500];
        }
    }

    /**
     * Records why a payslip wasn't sent (before it was claimed) and returns the
     * failure result. An already-sent payslip keeps its 'sent' status — only the
     * error is noted.
     */
    private function holdPayslip(array $row, string $empName, string $status, string $reason, bool $canForce): array
    {
        $P  = \App\Models\Client\EmployeePayslipModel::class;
        $db = \Config\Database::connect();

        if (!empty($row['sent_at'])) {
            $db->table('employee_payslips')->where('id', $row['id'])->update([
                'last_send_error' => mb_substr($reason, 0, 500),
                'last_attempt_at' => date('Y-m-d H:i:s'),
            ]);
            return ['success' => false, 'status' => $P::STATUS_SENT, 'error' => $reason, 'http' => 422];
        }

        $fields = $status === $P::STATUS_NEEDS_REVIEW
            ? ['send_status' => $status, 'review_reason' => mb_substr($reason, 0, 255)]
            : ['send_status' => $status, 'last_send_error' => mb_substr($reason, 0, 500),
               'send_attempts' => (int) $row['send_attempts'] + 1, 'last_attempt_at' => date('Y-m-d H:i:s')];

        $db->table('employee_payslips')->where('id', $row['id'])->where('send_status <>', $P::STATUS_SENDING)->update($fields);

        if (($row['send_status'] ?? '') !== $status || ($status === $P::STATUS_NEEDS_REVIEW && ($row['review_reason'] ?? '') !== $reason)) {
            service('activityLogger')->payslipSendProblem((int) $row['id'], $empName, (string) $row['period_start'], (string) $row['period_end'], $status, $reason);
        }

        return ['success' => false, 'status' => $status, 'error' => $reason, 'needs_confirm' => $canForce, 'http' => 422];
    }

    /** @return int[] positive unique payslip ids from a JSON {ids: [...]} body */
    private function releaseIdsFromInput(array $input): array
    {
        $ids = array_map('intval', (array) ($input['ids'] ?? []));
        return array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));
    }

    private function releaseActorName(): string
    {
        $user = auth()->user();
        return $user ? mb_substr((string) ($user->username ?? 'Unknown'), 0, 100) : 'System';
    }

    /**
     * Schedule or reschedule payslips to send on a date.
     * POST client_portal/payroll/release/schedule  {ids: [...], date: 'Y-m-d'}
     * Sent / currently-sending payslips are skipped (reported back).
     */
    public function releaseSchedule()
    {
        $P     = \App\Models\Client\EmployeePayslipModel::class;
        $input = $this->request->getJSON(true) ?? [];
        $ids   = $this->releaseIdsFromInput($input);
        $date  = (string) ($input['date'] ?? '');

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$ids || !$parsed || $parsed->format('Y-m-d') !== $date) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Choose at least one payslip and a valid date.']);
        }
        if ($date < date('Y-m-d')) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'The send date can\'t be in the past.']);
        }

        $model   = new \App\Models\Client\EmployeePayslipModel();
        $actor   = $this->releaseActorName();
        $done    = 0;
        $skipped = [];

        foreach ($model->whereIn('id', $ids)->findAll() as $row) {
            if (!empty($row['sent_at']) || $row['send_status'] === $P::STATUS_SENDING) {
                $skipped[] = (int) $row['id'];
                continue;
            }
            $model->update($row['id'], [
                'send_status'         => $P::STATUS_SCHEDULED,
                'scheduled_send_date' => $date,
                'scheduled_by'        => $actor,
                'scheduled_at'        => date('Y-m-d H:i:s'),
                'send_attempts'       => 0,
                'last_send_error'     => null,
            ]);
            $d = json_decode((string) $row['generated_data'], true) ?: [];
            service('activityLogger')->payslipScheduled((int) $row['id'], (string) ($d['employee_name'] ?? ('Employee #' . $row['employee_id'])),
                (string) $row['period_start'], (string) $row['period_end'], $row['scheduled_send_date'], $date);
            $done++;
        }

        return $this->response->setJSON(['success' => true, 'scheduled' => $done, 'skipped' => $skipped]);
    }

    /**
     * Cancel scheduled sends — back to draft.
     * POST client_portal/payroll/release/cancel  {ids: [...]}
     */
    public function releaseCancel()
    {
        $P     = \App\Models\Client\EmployeePayslipModel::class;
        $ids   = $this->releaseIdsFromInput($this->request->getJSON(true) ?? []);
        if (!$ids) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Choose at least one payslip.']);
        }

        $model = new \App\Models\Client\EmployeePayslipModel();
        $done  = 0;
        foreach ($model->whereIn('id', $ids)->findAll() as $row) {
            if (!empty($row['sent_at']) || !in_array($row['send_status'], [$P::STATUS_SCHEDULED, $P::STATUS_FAILED, $P::STATUS_NEEDS_REVIEW], true) || empty($row['scheduled_send_date'])) {
                continue;
            }
            $model->update($row['id'], [
                'send_status'         => $row['send_status'] === $P::STATUS_NEEDS_REVIEW ? $P::STATUS_NEEDS_REVIEW : $P::STATUS_DRAFT,
                'scheduled_send_date' => null,
                'scheduled_by'        => null,
                'scheduled_at'        => null,
                'send_attempts'       => 0,
                'last_send_error'     => null,
            ]);
            $d = json_decode((string) $row['generated_data'], true) ?: [];
            service('activityLogger')->payslipScheduleCancelled((int) $row['id'], (string) ($d['employee_name'] ?? ('Employee #' . $row['employee_id'])),
                (string) $row['period_start'], (string) $row['period_end'], $row['scheduled_send_date']);
            $done++;
        }

        return $this->response->setJSON(['success' => true, 'cancelled' => $done]);
    }

    /**
     * Manual / Automatic release mode (toggle on the Payslip tab (Payroll Output)).
     * POST client_portal/payroll/release/mode  {mode: 'manual'|'automatic'}
     * Only affects payslips saved AFTER the switch.
     */
    public function releaseMode()
    {
        $mode = (string) (($this->request->getJSON(true) ?? [])['mode'] ?? '');
        if (!in_array($mode, ['manual', 'automatic'], true)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid release mode.']);
        }

        $old = $this->payslipReleaseMode();
        if ($old !== $mode) {
            (new PayrollSettingsModel())->setSetting('payslip_release_mode', $mode);
            service('activityLogger')->payslipReleaseModeChanged($old, $mode);
        }

        return $this->response->setJSON(['success' => true, 'mode' => $mode]);
    }

    /**
     * Mark payslips as paid (one-way). Also marks the matching computed
     * payroll_transactions row paid when one exists — the Bulk panel never
     * created one, so the payslip itself is the record of payment.
     * POST client_portal/payroll/release/markPaid  {ids: [...]}
     */
    public function releaseMarkPaid()
    {
        $ids = $this->releaseIdsFromInput($this->request->getJSON(true) ?? []);
        if (!$ids) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Choose at least one payslip.']);
        }

        $db    = \Config\Database::connect();
        $model = new \App\Models\Client\EmployeePayslipModel();
        $user  = auth()->user();
        $actor = $this->releaseActorName();
        $done  = 0;

        foreach ($model->whereIn('id', $ids)->findAll() as $row) {
            if (!empty($row['paid_at'])) continue;

            $db->transBegin();
            $db->table('employee_payslips')->where('id', $row['id'])->where('paid_at', null)
                ->update(['paid_at' => date('Y-m-d H:i:s'), 'paid_by' => $actor]);
            if ($db->affectedRows() < 1) { $db->transRollback(); continue; }

            $tx = $db->table('payroll_transactions')
                ->where('employee_id', $row['employee_id'])
                ->where('period_start', $row['period_start'])
                ->where('period_end', $row['period_end'])
                ->where('status', 'computed')
                ->orderBy('id', 'DESC')->limit(1)
                ->get()->getRowArray();
            if ($tx) {
                $db->table('payroll_transactions')->where('id', $tx['id'])->where('status', 'computed')
                    ->update(['status' => 'paid', 'paid_by' => $user ? $user->id : null, 'paid_at' => date('Y-m-d H:i:s')]);
            }

            if (!$db->transStatus()) { $db->transRollback(); continue; }
            $db->transCommit();

            $d = json_decode((string) $row['generated_data'], true) ?: [];
            service('activityLogger')->payslipMarkedPaid((int) $row['id'], (string) ($d['employee_name'] ?? ('Employee #' . $row['employee_id'])),
                (string) $row['period_start'], (string) $row['period_end'], $tx ? (int) $tx['id'] : null);
            $done++;
        }

        return $this->response->setJSON(['success' => true, 'marked' => $done]);
    }

    public function savePayslipRecord()
    {
        try {
            $data = $this->request->getJSON(true);
            $employeeId  = $data['employee_id']  ?? null;
            $periodStart = $data['period_start']  ?? null;
            $periodEnd   = $data['period_end']    ?? null;
            $netPay      = $data['net_pay']       ?? null;

            if (!$employeeId || !$periodStart || !$periodEnd || $netPay === null) {
                throw new \App\Exceptions\ValidationException('Missing required fields.');
            }

            // A payroll should fall inside its own cutoff, not wherever its
            // start date happens to land — see classifyPayslipPeriod(). A
            // malformed period (end before start) is always rejected; a
            // well-formed but non-standard one (e.g. final pay for someone
            // who left mid-cutoff) is allowed only once staff explicitly
            // confirm it's intentional.
            $periodCheck = $this->classifyPayslipPeriod((string) $periodStart, (string) $periodEnd);
            if (!$periodCheck['valid']) {
                throw new \App\Exceptions\ValidationException($periodCheck['reason']);
            }
            if (!$periodCheck['standard'] && empty($data['confirm_non_standard'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'needs_confirm' => true,
                    'error' => $periodCheck['reason'],
                ]);
            }

            $employeeModel = new \App\Models\Client\EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) throw new \App\Exceptions\ValidationException('Employee not found');

            // Net pay is stored in two unambiguous parts: the figure BEFORE the
            // Loans-module deduction (what the Generate Payslip page computed —
            // it never includes Loans-module installments), and the installments
            // themselves. Final net pay = before − loans, worked out here, once.
            // Sending later reuses these stored figures as-is and never
            // subtracts a loan again (the old sendPdf() did, which took a due
            // installment out twice whenever a stored payslip was sent).
            $netPayBeforeLoans = round((float) ($data['net_pay_before_loans'] ?? $netPay), 2);
            $loanBreakdown = $this->fetchLoanScheduleBreakdown((int) $employeeId, (string) $periodEnd);
            $loanDeductionTotal = array_sum(array_column($loanBreakdown, 'amount'));
            $netPay = round($netPayBeforeLoans - $loanDeductionTotal, 2);
            $data['net_pay_before_loans'] = $netPayBeforeLoans;
            $data['loan_deductions']      = $loanBreakdown;
            $data['net_pay']              = $netPay;
            unset($data['send_method']);

            $payslipModel = new \App\Models\Client\EmployeePayslipModel();

            $existing = $payslipModel
                ->where('employee_id', $employeeId)
                ->where('period_start', $periodStart)
                ->where('period_end',   $periodEnd)
                ->first();

            $db = \Config\Database::connect();
            $db->transBegin();
            if ($existing) {
                // Lock the row for this save: a send claims it with a single
                // UPDATE, which waits on this lock, so a send can't start
                // between the "is it sending?" check and the write below.
                $locked = $db->query('SELECT send_status FROM employee_payslips WHERE id = ? FOR UPDATE', [$existing['id']])->getRowArray();
                if (($locked['send_status'] ?? '') === \App\Models\Client\EmployeePayslipModel::STATUS_SENDING) {
                    $db->transRollback();
                    return $this->response->setStatusCode(409)->setJSON([
                        'success' => false,
                        'error'   => 'This payslip is being sent right now. Wait a moment, then save again.',
                    ]);
                }
                // Release state must come from the locked, current row.
                $existing = $payslipModel->find($existing['id']) ?: $existing;
            }

            $fields = [
                'net_pay'              => $netPay,
                'net_pay_before_loans' => $netPayBeforeLoans,
                'generated_data'       => json_encode($data),
                'source_fingerprint'   => $this->payslipSourceFingerprint((int) $employeeId, (string) $periodStart, (string) $periodEnd),
            ];
            $release = $this->releaseStateForSave($existing, (string) $periodEnd, (string) $periodStart);
            $fields += $release['fields'];

            if ($existing) {
                $payslipModel->update($existing['id'], $fields);
                $recordId = $existing['id'];
            } else {
                $payslipModel->insert($fields + [
                    'employee_id'  => $employeeId,
                    'period_start' => $periodStart,
                    'period_end'   => $periodEnd,
                    'sent_at'      => null,
                ]);
                $inserted = $payslipModel
                    ->where('employee_id', $employeeId)
                    ->where('period_start', $periodStart)
                    ->where('period_end',   $periodEnd)
                    ->first();
                $recordId = $inserted['id'] ?? null;
            }

            if (!$recordId) {
                throw new \App\Exceptions\ValidationException('Failed to resolve payslip record after save.');
            }
            if (!$db->transCommit()) {
                throw new \RuntimeException('Payslip could not be saved. Please try again.');
            }

            // No PDF is written here on purpose: deliverPayslip() builds the
            // encrypted PDF at send time and downloadPayslip() regenerates from
            // the saved figures. A file written on save was unencrypted and
            // overwrote the copy the employee had already been emailed.

            $empName = $data['employee_name'] ?? trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
            service('activityLogger')->payslipGenerated(
                (int) $recordId,
                $empName,
                $periodStart,
                $periodEnd,
                round((float)$netPay, 2)
            );
            if ($release['auto_scheduled']) {
                service('activityLogger')->payslipScheduled(
                    (int) $recordId, $empName, $periodStart, $periodEnd, null,
                    (string) $release['fields']['scheduled_send_date'], true
                );
            }

            return $this->response->setJSON([
                'success'             => true,
                'payslip_id'          => $recordId,
                'send_status'         => $release['fields']['send_status'] ?? ($existing['send_status'] ?? 'draft'),
                'scheduled_send_date' => $release['fields']['scheduled_send_date'] ?? ($existing['scheduled_send_date'] ?? null),
                'message'             => $release['message'],
            ]);
        } catch (\Exception $e) {
            if (isset($db) && $db->transDepth > 0) $db->transRollback();
            return $this->jsonError($e, 'Unable to save payslip record.');
        }
    }

    /**
     * What a Save does to a payslip's release state (see Payroll Output →
     * Payslip). Only the release columns are returned — money fields are set by
     * the caller.
     *  - already sent       → stays sent, flagged "resend needed" (the
     *                          employee's copy is now out of date)
     *  - already scheduled  → keeps its scheduled date, back to 'scheduled'
     *  - anything else/new  → 'draft', or 'scheduled' for the salary day when
     *                          release mode is Automatic and a salary day is set
     *
     * @return array{fields: array<string,mixed>, message: string, auto_scheduled: bool}
     */
    private function releaseStateForSave(?array $existing, string $periodEnd, ?string $periodStart = null): array
    {
        $P = \App\Models\Client\EmployeePayslipModel::class;
        $clear = ['send_attempts' => 0, 'last_send_error' => null, 'review_reason' => null];

        if ($existing && !empty($existing['sent_at'])) {
            return [
                'fields'  => ['needs_resend' => 1, 'review_reason' => null],
                'message' => 'Saved. This payslip was already sent — resend it from Payroll Output → Payslip so the employee gets the updated copy.',
                'auto_scheduled' => false,
            ];
        }

        // A scheduled payslip keeps its date when re-saved — including one that
        // failed or was held for review, since re-saving is how those get fixed.
        $keptStatuses = [$P::STATUS_SCHEDULED, $P::STATUS_FAILED, $P::STATUS_NEEDS_REVIEW];
        if ($existing && in_array($existing['send_status'] ?? '', $keptStatuses, true) && !empty($existing['scheduled_send_date'])) {
            $sendOn = max((string) $existing['scheduled_send_date'], date('Y-m-d'));
            return [
                'fields'  => $clear + ['send_status' => $P::STATUS_SCHEDULED, 'scheduled_send_date' => $sendOn],
                'message' => 'Saved. Scheduled to send on ' . date('M j, Y', strtotime($sendOn)) . '.',
                'auto_scheduled' => false,
            ];
        }

        if ($this->payslipReleaseMode() === 'automatic') {
            $salaryDay = \App\Libraries\PayrollCutoff::salaryDayFor($periodEnd, $periodStart);
            if ($salaryDay !== null) {
                $sendOn = max($salaryDay, date('Y-m-d'));
                return [
                    'fields'  => $clear + [
                        'send_status'         => $P::STATUS_SCHEDULED,
                        'scheduled_send_date' => $sendOn,
                        'scheduled_by'        => 'Automatic (salary day)',
                        'scheduled_at'        => date('Y-m-d H:i:s'),
                    ],
                    'message' => 'Saved and scheduled to send on ' . date('M j, Y', strtotime($sendOn)) . ' (salary day).',
                    'auto_scheduled' => true,
                ];
            }

            return [
                'fields'  => $clear + ['send_status' => $P::STATUS_DRAFT, 'scheduled_send_date' => null],
                'message' => 'Saved as draft. Release mode is Automatic, but no salary day is set for this half in Payroll Settings — schedule or send it from Payroll Output → Payslip.',
                'auto_scheduled' => false,
            ];
        }

        return [
            'fields'  => $clear + ['send_status' => $P::STATUS_DRAFT, 'scheduled_send_date' => null],
            'message' => 'Saved as draft. Send or schedule it from Payroll Output → Payslip.',
            'auto_scheduled' => false,
        ];
    }

    private function payslipReleaseMode(): string
    {
        $mode = (new PayrollSettingsModel())->getSetting('payslip_release_mode');
        return $mode === 'automatic' ? 'automatic' : 'manual';
    }

    /**
     * Hash of everything a payslip's figures are built from that can change
     * after it's saved: the employee's attendance rows for the period, their
     * approved requests overlapping it, and the Loans-module installments due
     * on its period end. A mismatch at send time means the saved figures may
     * be out of date (see deliverPayslip()).
     */
    private function payslipSourceFingerprint(int $employeeId, string $periodStart, string $periodEnd): string
    {
        $db = \Config\Database::connect();
        $employee = $db->table('employees')->select('user_id')->where('id', $employeeId)->get()->getRowArray();
        $userId = (int) ($employee['user_id'] ?? 0);

        $attendance = $db->table('attendance')
            ->select('id, time_in, time_out, break_duration, shift_date, state_version, approved_ot_hours, unapproved_ot_hours, approved_ut_hours, unapproved_ut_hours, attendance_status')
            ->where('user_id', $userId)
            ->where('COALESCE(shift_date, DATE(time_in)) >=', $periodStart)
            ->where('COALESCE(shift_date, DATE(time_in)) <=', $periodEnd)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $requests = $db->table('requests')
            ->select('id, request_type, leave_category, start_date, end_date, duration, is_paid_leave, status')
            ->where('requested_by', $userId)
            ->where('status', 'approved')
            ->where('start_date <=', $periodEnd)
            ->groupStart()
                ->where('end_date >=', $periodStart)
                ->orWhere('end_date', null)
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return hash('sha256', json_encode([
            'attendance' => $attendance,
            'requests'   => $requests,
            'loans'      => $this->loanSignature($this->fetchLoanScheduleBreakdown($employeeId, $periodEnd)),
        ]));
    }

    /** Order-independent schedule_id → amount map, for comparing loan breakdowns. */
    private function loanSignature(array $breakdown): array
    {
        $sig = [];
        foreach ($breakdown as $loan) {
            $sig[(int) ($loan['schedule_id'] ?? 0)] = round((float) ($loan['amount'] ?? 0), 2);
        }
        ksort($sig);
        return $sig;
    }

    /**
     * Self-service only (see getMyPayslips()). A saved payslip is a draft until
     * it's sent from the Payslip tab (Payroll Output), so only sent ones are returned —
     * before the release workflow every saved draft (including unfinished or
     * wrong figures) was listed here. Only the fields the employee's payslip
     * modal needs are returned.
     */
    public function getEmployeePayslips($employeeId)
    {
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $payslips = $payslipModel
            ->select('id, employee_id, period_start, period_end, net_pay, sent_at')
            ->where('employee_id', $employeeId)
            ->where('sent_at IS NOT NULL', null, false)
            ->orderBy('period_start', 'DESC')
            ->findAll();

        foreach ($payslips as &$ps) {
            $ps['download_url'] = site_url('client_portal/payroll/downloadPayslip/' . $ps['id']);
        }
        return $this->response->setJSON($payslips);
    }

    /**
     * An employee may only open their own payslip once it has been sent.
     * Admins (payroll permission) can open any payslip, sent or not.
     */
    private function payslipVisibleToEmployee(array $payslip, bool $isOwner, bool $isAdmin): bool
    {
        if ($isAdmin) return true;
        return $isOwner && !empty($payslip['sent_at']);
    }

    /**
     * Return the 1st-half net taxable and WT for a given employee, used by 2nd-half WT.
     *
     * GET client_portal/payroll/getPriorHalfWT/{empId}/{priorStart}/{priorEnd}
     *   priorStart / priorEnd are the explicit 1st-half period dates supplied by JS.
     *   Two-argument legacy form still works (derives dates from same-month cutoff).
     */
    public function getPriorHalfWT(int $employeeId, string $priorStart, string $priorEnd = ''): \CodeIgniter\HTTP\ResponseInterface
    {
        if ($priorEnd === '') {
            // Legacy two-argument call: $priorStart is the current 2nd-half period start.
            // Derive prior-period dates from the configured cutoff setting.
            $psModel = new \App\Models\Client\PayrollSettingsModel();
            $cutoff  = (int)($psModel->getSetting('first_half_cutoff') ?? 0);
            if ($cutoff <= 0) {
                return $this->response->setJSON(['found' => false, 'net_taxable' => 0, 'wt' => 0]);
            }
            $dt  = new \DateTime($priorStart);
            $day = (int)$dt->format('d');
            if ($day <= $cutoff) {
                // Caller is generating a 1st-half payslip — no prior half exists
                return $this->response->setJSON(['found' => false, 'net_taxable' => 0, 'wt' => 0]);
            }
            $year       = $dt->format('Y');
            $month      = $dt->format('m');
            $priorStart = "{$year}-{$month}-01";
            $priorEnd   = "{$year}-{$month}-" . str_pad($cutoff, 2, '0', STR_PAD_LEFT);
        }

        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $record = $payslipModel
            ->where('employee_id', $employeeId)
            ->where('period_start', $priorStart)
            ->where('period_end',   $priorEnd)
            ->first();

        // Primary lookup failed — build alternative candidate date ranges from the
        // configured period settings and try each one.  This handles records that were
        // saved under a different (or earlier) period configuration without assuming any
        // specific cutoff day or start day.
        if (!$record) {
            $psModel  = new \App\Models\Client\PayrollSettingsModel();
            $dtEnd    = new \DateTime($priorEnd);
            $salaryYm = $dtEnd->format('Y-m');   // salary month derived from prior end date
            $salaryY  = (int)$dtEnd->format('Y');
            $salaryM  = (int)$dtEnd->format('m');

            $candidates = [];

            // Option A: simple cutoff — {salaryMonth}-01 to {salaryMonth}-{cutoff}
            $cutoff = (int)($psModel->getSetting('first_half_cutoff') ?? 0);
            if ($cutoff > 0) {
                $candidates[] = [
                    $salaryYm . '-01',
                    $salaryYm . '-' . str_pad($cutoff, 2, '0', STR_PAD_LEFT),
                ];
            }

            // Option B: cross-month settings — compute 1st-half dates from half1_* keys
            $h1SD = (int)($psModel->getSetting('half1_start_day') ?? 0);
            $h1ED = (int)($psModel->getSetting('half1_end_day')   ?? 0);
            if ($h1SD > 0 && $h1ED > 0) {
                $h1SO = (int)($psModel->getSetting('half1_start_offset') ?? 0);
                $h1EO = (int)($psModel->getSetting('half1_end_offset')   ?? 0);
                $candidates[] = [
                    $this->buildPeriodDate($salaryY, $salaryM, $h1SD, $h1SO),
                    $this->buildPeriodDate($salaryY, $salaryM, $h1ED, $h1EO),
                ];
            }

            foreach ($candidates as [$altS, $altE]) {
                if ($altS === $priorStart && $altE === $priorEnd) continue; // already tried
                $try = $payslipModel
                    ->where('employee_id', $employeeId)
                    ->where('period_start', $altS)
                    ->where('period_end',   $altE)
                    ->first();
                if ($try) {
                    $record     = $try;
                    $priorStart = $altS;
                    $priorEnd   = $altE;
                    break;
                }
            }
        }

        if (!$record) {
            return $this->response->setJSON(['found' => false, 'net_taxable' => 0, 'wt' => 0,
                'period_start' => $priorStart, 'period_end' => $priorEnd]);
        }

        $d = json_decode((string)($record['generated_data'] ?? ''), true) ?? [];

        // Net taxable for 1st half = net basic pay + all OT + other taxable earnings
        // (No SSS/PHIC/HDMF deduction — matches Excel 1st-half register column 26)
        $netBasicPay = (float)($d['net_basic_pay'] ?? (
            (float)($d['basic_pay']      ?? 0)
          + (float)($d['night_diff_pay'] ?? 0)
          - (float)($d['absent_amt']     ?? 0)
          - (float)($d['undertime_amt']  ?? 0)
        ));

        // Prefer total_overtime (NSD-corrected aggregate saved by the JS) over re-summing
        // individual fields, which can give a 0.01 discrepancy due to per-type rounding.
        if (isset($d['total_overtime'])) {
            $totalOt = (float)$d['total_overtime'];
        } else {
            $hasIndividual = isset($d['reg_ot']) || isset($d['sat_ot']) || isset($d['sun_ot']);
            $totalOt = $hasIndividual
                ? (float)($d['reg_ot'] ?? 0) + (float)($d['reg_ot_gt10'] ?? 0) + (float)($d['reg_ot_night'] ?? 0)
                  + (float)($d['sat_ot'] ?? 0) + (float)($d['sat_ot_gt10'] ?? 0) + (float)($d['sat_night_prem'] ?? 0)
                  + (float)($d['sun_ot'] ?? 0) + (float)($d['sun_ot_gt10'] ?? 0) + (float)($d['sun_night_prem'] ?? 0)
                  + (float)($d['hol_ot'] ?? 0) + (float)($d['hol_ot_gt10'] ?? 0) + (float)($d['hol_night_prem'] ?? 0)
                : (float)($d['overtime_pay'] ?? 0);
        }

        $otherTaxable = (float)($d['other_earnings_taxable'] ?? ($d['other_taxable'] ?? 0));
        $netTaxable   = $netBasicPay + $totalOt + $otherTaxable;
        $wt           = (float)($d['withholding_tax'] ?? 0);

        // prior_gross = total 1st-half gross pay (taxable + non-taxable).
        // Used by the 2nd-half SSS computation to derive the monthly MSC from total compensation.
        $priorGross  = (float)($d['gross_pay']  ?? 0);
        // prior_de_minimis = 1st-half de minimis amount.
        // The 2nd-half payslip may display the full monthly de minimis for the employee's reference,
        // so the SSS formula uses the 1st-half amount as the consistent semi-monthly basis.
        $priorDeMin  = (float)($d['de_minimis'] ?? 0);

        return $this->response->setJSON([
            'found'            => true,
            'net_taxable'      => round($netTaxable,  2),
            'wt'               => round($wt,          2),
            'prior_gross'      => round($priorGross,  2),
            'prior_de_minimis' => round($priorDeMin,  2),
            'period_start'     => $priorStart,
            'period_end'       => $priorEnd,
        ]);
    }

    public function getMyPayslips()
    {
        $user = auth()->user();
        $employeeModel = new \App\Models\Client\EmployeeModel();
        $employee = $employeeModel->where('user_id', $user->id)->first();
        if (!$employee) {
            return $this->response->setJSON([]);
        }
        return $this->getEmployeePayslips($employee['id']);
    }

    public function downloadPayslip($id)
    {
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $payslip = (array) $payslipModel->find($id);
        if (!$payslip) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Payslip not found');
        }

        $user = auth()->user();
        $employeeModel = new \App\Models\Client\EmployeeModel();
        $employee = (array) $employeeModel->find($payslip['employee_id']);
        $isOwner = ($employee && (string)$employee['user_id'] === (string)$user->id);
        $isAdmin = \App\Libraries\PortalAccess::can('payroll', $user);

        if (!$isOwner && !$isAdmin) {
            return $this->response->setStatusCode(403)->setBody('Unauthorized');
        }
        if (!$this->payslipVisibleToEmployee($payslip, $isOwner, $isAdmin)) {
            return $this->response->setStatusCode(403)->setBody('This payslip has not been released yet.');
        }

        // A sent payslip that was re-saved afterwards (needs_resend) no longer
        // matches what the employee was emailed. Until it's resent, the
        // employee gets the copy they actually received. Only used when that
        // file exists and is password-protected (older saves wrote unencrypted
        // drafts there); staff always get the current saved figures.
        if ($isOwner && !$isAdmin && !empty($payslip['sent_at']) && !empty($payslip['needs_resend'])) {
            $storedPath = (string) ($payslip['file_path'] ?? '');
            $storedPdf  = ($storedPath !== '' && is_file($storedPath)) ? (string) file_get_contents($storedPath) : '';
            if ($storedPdf !== '' && str_contains($storedPdf, '/Encrypt')) {
                $disposition = $this->request->getGet('mode') === 'inline' ? 'inline' : 'attachment';
                return $this->response
                    ->setHeader('Content-Type', 'application/pdf')
                    ->setHeader('Content-Disposition', $disposition . '; filename="payslip_' . ($payslip['period_start'] ?? 'unknown') . '.pdf"')
                    ->setBody($storedPdf);
            }
        }

        // Regenerate the PDF on-the-fly so it always reflects the current layout + logo.
        $data = json_decode((string)($payslip['generated_data'] ?? ''), true) ?? [];
        if (empty($data)) {
            // Fallback: serve the stored file if no generated_data
            $filePath = (string)($payslip['file_path'] ?? '');
            if (!is_file($filePath)) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException('Payslip file not found');
            }
            return $this->response->download($filePath, null);
        }

        ini_set('memory_limit', '1G');

        $html = $this->buildPayslipHtmlForPdf($data, $employee);

        $options = new Options();
        $options->set('defaultFont', 'Courier');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $this->encryptPayslipPdf($dompdf, $employee);
        $pdfOutput = $dompdf->output();

        $filename = 'payslip_' . ($payslip['period_start'] ?? 'unknown') . '.pdf';

        // Same password-protected file either way — only how the browser
        // hands it to the user differs. "Print Payslip" opens it inline in
        // the browser's own PDF viewer so the toolbar's print button/Ctrl+P
        // works; "Download PDF" forces a save-to-disk. Without this, both
        // buttons pointed at the same URL with a forced "attachment"
        // disposition, so Print silently just downloaded the file too.
        $disposition = $this->request->getGet('mode') === 'inline' ? 'inline' : 'attachment';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"')
            ->setBody($pdfOutput);
    }

    public function getAllPayslips()
    {
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $payslips = $payslipModel
            ->select("employee_payslips.*, CONCAT(employees.first_name, ' ', employees.last_name) as employee_name")
            ->join('employees', 'employees.id = employee_payslips.employee_id')
            ->orderBy('employee_payslips.created_at', 'DESC')
            ->findAll();
        return $this->response->setJSON($payslips);
    }

    public function payslipHistory()
    {
        return view('client_portal/payroll_payslip_history', $this->payslipReleasePageData());
    }

    /**
     * Full activity timeline for one payslip — generated, every send (individual
     * or bulk, not just the most recent), and every edit with the field-level
     * before/after values. Pulled from the append-only activity_logs table plus
     * the _edit_history kept inside generated_data for edits made before this
     * endpoint existed.
     */
    public function getPayslipActivity($id)
    {
        $user = auth()->user();
        $isAdmin = \App\Libraries\PortalAccess::can('payroll', $user);
        if (!$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $id = (int) $id;
        if (!$id) return $this->response->setJSON(['error' => 'Missing id']);

        $db = \Config\Database::connect();

        $logs = $db->table('activity_logs')
            ->where('module', 'employee_payslips')
            ->where('record_id', $id)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        $timeline = [];
        foreach ($logs as $row) {
            $timeline[] = [
                'source'      => 'activity_log',
                'action'      => $row['action'],
                'description' => $row['description'],
                'username'    => $row['username'],
                'old_values'  => json_decode((string) ($row['old_values'] ?? ''), true),
                'new_values'  => json_decode((string) ($row['new_values'] ?? ''), true),
                'created_at'  => $row['created_at'],
            ];
        }

        // Older edits (made before payslipEdited() started logging to activity_logs)
        // only exist in generated_data._edit_history — merge those in too.
        $row = $db->query("SELECT generated_data FROM employee_payslips WHERE id = ?", [$id])->getRowArray();
        $data = json_decode((string) ($row['generated_data'] ?? ''), true) ?: [];
        foreach ($data['_edit_history'] ?? [] as $edit) {
            // Skip if an activity_log row for this same edit already covers it
            // (best-effort de-dupe by timestamp — new edits log to both places).
            $already = false;
            foreach ($timeline as $t) {
                if ($t['action'] === 'updated' && $t['created_at'] === $edit['timestamp']) { $already = true; break; }
            }
            if ($already) continue;

            $timeline[] = [
                'source'      => 'edit_history',
                'action'      => 'updated',
                'description' => empty($edit['changes']) ? 'Payslip reviewed — no changes' : 'Payslip edited',
                'username'    => $edit['edited_by'] ?? 'admin',
                'old_values'  => $edit['changes'] ? array_combine(array_column($edit['changes'], 'field'), array_column($edit['changes'], 'old_value')) : null,
                'new_values'  => $edit['changes'] ? array_combine(array_column($edit['changes'], 'field'), array_column($edit['changes'], 'new_value')) : null,
                'created_at'  => $edit['timestamp'] ?? null,
            ];
        }

        usort($timeline, fn($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));

        return $this->response->setJSON(['success' => true, 'timeline' => $timeline]);
    }

    /**
     * System-wide audit log viewer — filterable, paginated read of activity_logs.
     * Backs the Audit Log tab in the Payroll module.
     */
    /** Recent timesheet import batches — filename, when, who, and row outcomes. */
    public function getImportHistory()
    {
        $user = auth()->user();
        if (!\App\Libraries\PortalAccess::can('payroll', $user)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $rows = (new \App\Models\Client\ImportBatchModel())->recent('timesheet', 50);
        return $this->response->setJSON(['success' => true, 'batches' => $rows]);
    }

    public function getAuditLog()
    {
        $user = auth()->user();
        $isAdmin = \App\Libraries\PortalAccess::can('payroll', $user);
        if (!$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $model  = new \App\Models\ActivityLogModel();

        $module = trim((string) ($this->request->getGet('module') ?? ''));
        $action = trim((string) ($this->request->getGet('action') ?? ''));
        $search = trim((string) ($this->request->getGet('search') ?? ''));
        $from   = trim((string) ($this->request->getGet('from') ?? ''));
        $to     = trim((string) ($this->request->getGet('to') ?? ''));
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 25;

        if ($module !== '') {
            $model->byModule($module);
        }
        if (in_array($action, ['created', 'updated', 'deleted'], true)) {
            $model->byAction($action);
        }
        if ($from !== '' && $to !== '') {
            $model->betweenDates($from . ' 00:00:00', $to . ' 23:59:59');
        }
        if ($search !== '') {
            $model->groupStart()
                ->like('description', $search)
                ->orLike('username', $search)
                ->groupEnd();
        }

        $total = $model->countAllResults(false);
        $rows  = $model->orderBy('created_at', 'DESC')->findAll($perPage, ($page - 1) * $perPage);

        foreach ($rows as &$row) {
            $row['old_values'] = json_decode((string) ($row['old_values'] ?? ''), true);
            $row['new_values'] = json_decode((string) ($row['new_values'] ?? ''), true);
        }
        unset($row);

        $db      = \Config\Database::connect();
        $modules = $db->table('activity_logs')
            ->distinct()
            ->select('module')
            ->orderBy('module', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'rows'    => $rows,
            'total'   => (int) $total,
            'page'    => $page,
            'perPage' => $perPage,
            'modules' => array_column($modules, 'module'),
        ]);
    }

    public function getPayslipData($id)
    {
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $payslip = (array) $payslipModel->find($id);
        if (!$payslip) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Payslip not found']);
        }
        
        $user = auth()->user();
        $employeeModel = new \App\Models\Client\EmployeeModel();
        $employee = (array) $employeeModel->find($payslip['employee_id']);
        $isOwner = ($employee && (string)$employee['user_id'] === (string)$user->id);
        $isAdmin = \App\Libraries\PortalAccess::can('payroll', $user);

        if (!$isOwner && !$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        if (!$this->payslipVisibleToEmployee($payslip, $isOwner, $isAdmin)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'This payslip has not been released yet.']);
        }

        $generatedData = json_decode((string) ($payslip['generated_data'] ?? ''), true);
        if (!$generatedData) {
            $generatedData = [];
        }
        
        return $this->response->setJSON([
            'id' => $payslip['id'],
            'period_start' => $payslip['period_start'],
            'period_end' => $payslip['period_end'],
            'generated_data' => $generatedData
        ]);
    }

    private function buildPayslipHtmlForPdf($data, $employee)
    {
        $fmt = fn($n) => number_format((float)($n ?? 0), 2);

        $fmtDate = fn($d) => $d ? date('d-M-Y', strtotime($d)) : '';

        $netBasicPay      = (float)($data['net_basic_pay']     ?? 0);
        $regOt            = (float)($data['reg_ot']            ?? 0);
        $regOtGt10        = (float)($data['reg_ot_gt10']       ?? 0);
        $regOtNight       = (float)($data['reg_ot_night']      ?? 0);
        $satOt            = (float)($data['sat_ot']            ?? 0);
        $satOtGt10        = (float)($data['sat_ot_gt10']       ?? 0);
        $satNightPrem     = (float)($data['sat_night_prem']    ?? 0);
        $sunOt            = (float)($data['sun_ot']            ?? 0);
        $sunOtGt10        = (float)($data['sun_ot_gt10']       ?? 0);
        $sunNightPrem     = (float)($data['sun_night_prem']    ?? 0);
        $holOt            = (float)($data['hol_ot']            ?? 0);
        $holOtGt10        = (float)($data['hol_ot_gt10']       ?? 0);
        $holNightPrem     = (float)($data['hol_night_prem']    ?? 0);
        $vlCashConversion = (float)($data['vl_cash_conversion'] ?? 0);
        $totalOvertime    = $regOt + $regOtGt10 + $regOtNight
                          + $satOt + $satOtGt10 + $satNightPrem
                          + $sunOt + $sunOtGt10 + $sunNightPrem
                          + $holOt + $holOtGt10 + $holNightPrem;
        $taxableOther     = (float)($data['other_earnings_taxable'] ?? 0);
        $nonTaxable       = (float)($data['de_minimis']        ?? 0);
        $grossPay         = $netBasicPay + $totalOvertime + $taxableOther + $nonTaxable + $vlCashConversion;

        $govDeductions = (float)($data['sss_ee']          ?? 0)
                       + (float)($data['phic_ee']         ?? 0)
                       + (float)($data['hdmf_ee']         ?? 0)
                       + (float)($data['withholding_tax'] ?? 0);

        $totalDeductionsDisplay = $govDeductions
                       + (float)($data['sss_loan']            ?? 0)
                       + (float)($data['calamity_loan']       ?? 0)
                       + (float)($data['hdmf_loan']            ?? 0)
                       + (float)($data['hdmf_mpl_loan']        ?? 0)
                       + (float)($data['hdmf_calamity_loan']   ?? 0)
                       + (float)($data['salary_advance']       ?? ($data['sal_adv'] ?? 0))
                       + (float)($data['cash_advance']         ?? 0)
                       + (float)($data['emergency_advance']    ?? 0)
                       + (float)($data['other_deductions']     ?? 0);

        // =========================================================
        // GOVERNMENT DEDUCTIONS
        // =========================================================

        $govDeductions =
            (float)($data['sss_ee'] ?? 0)
            + (float)($data['phic_ee'] ?? 0)
            + (float)($data['hdmf_ee'] ?? 0)
            + (float)($data['withholding_tax'] ?? 0);

        $loanDeductions = $data['loan_deductions'] ?? [];

        if (is_string($loanDeductions)) {
            $loanDeductions = json_decode($loanDeductions, true) ?? [];
        }

        $totalLoanDeductions = 0;

        foreach ($loanDeductions as $loan) {
            $totalLoanDeductions += (float)($loan['amount'] ?? 0);
        }

        // Loans-module deductions aren't part of the manual raw-sheet fields
        // summed above, so fold them into the printed total separately.
        $totalDeductionsDisplay += $totalLoanDeductions;

        // =========================================================
        // TOTAL DEDUCTIONS
        // =========================================================

        $totalDeductions =
            $govDeductions
            + $totalLoanDeductions
            + (float)($data['other_deductions'] ?? 0);

        // =========================================================
        // LOGO
        // =========================================================

        $logoDataUri = $this->getLogoDataUri();

        $logoHtml = $logoDataUri
            ? '<div style="text-align:center;margin-bottom:20px;">
                    <img src="' . $logoDataUri . '" style="max-height:80px;">
            </div>'
            : '';

        $period =
            $fmtDate($data['period_start'] ?? '')
            . ' &ndash; '
            . $fmtDate($data['period_end'] ?? '');

        // =========================================================
        // INNER TABLE STYLES
        // =========================================================

        $ic = 'style="border:none;padding:2px 4px;"';
        $ir = 'style="border:none;padding:2px 4px;text-align:right;"';

        $fmtAmt = fn($v) => ($v > 0) ? number_format((float)$v, 2) : '-';
        $ratePerHour      = (float)($data['rate_per_hour'] ?? 0);
        $hoursWorked      = (int)round((float)($data['hours_worked'] ?? 0));
        $nightDiffPay     = (float)($data['night_diff_pay'] ?? 0);
        $absentAmt        = (float)($data['absent_amt']  ?? 0);
        $undertimeAmt     = (float)($data['undertime_amt'] ?? 0);
        $regOtGt10        = (float)($data['reg_ot_gt10']  ?? 0);
        $regOtNight       = (float)($data['reg_ot_night'] ?? 0);
        $satOtGt10        = (float)($data['sat_ot_gt10']  ?? 0);
        $satNightPrem     = (float)($data['sat_night_prem'] ?? 0);
        $sunOtGt10        = (float)($data['sun_ot_gt10']   ?? 0);
        $sunNightPrem     = (float)($data['sun_night_prem'] ?? 0);
        $holOtGt10        = (float)($data['hol_ot_gt10']   ?? 0);
        $holNightPrem     = (float)($data['hol_night_prem'] ?? 0);
        $vlCashConversion = (float)($data['vl_cash_conversion'] ?? 0);

        // [label, amount, OT multiplier for back-calculating displayed hours from pay amount]
        // PH labor law: reg OT=1.25×; rest-day work ≤8 hrs=1.30×, rest OT >8 hrs=1.69×;
        // regular holiday ≤8 hrs=2.00×, hol OT >8 hrs=2.60×; NSD=0.10×.
        $otRows = [
            ['Reg. OT&le;10 hrs', $regOt,       1.25],
            ['Reg. OT&gt;10 hrs', $regOtGt10,   1.25],
            ['Night premium',      $regOtNight,  0.10],
            ['Sat OT&le;10 hrs',  $satOt,        1.30],
            ['Sat OT&gt;10 hrs',  $satOtGt10,   1.69],
            ['Sat Night premium',  $satNightPrem, 0.10],
            ['Sun OT&le;10 hrs',  $sunOt,        1.30],
            ['Sun OT&gt;10 hrs',  $sunOtGt10,   1.69],
            ['Sun Night premium',  $sunNightPrem, 0.10],
            ['Hol OT&le;10 hrs',  $holOt,        2.00],
            ['Hol OT&gt;10 hrs',  $holOtGt10,   2.60],
            ['Hol Night premium',  $holNightPrem, 0.10],
        ];
        $otRowsHtml = '';
        foreach ($otRows as [$label, $val, $mult]) {
            $hrs = ($ratePerHour > 0 && $mult > 0 && $val > 0)
                ? (int)round($val / ($ratePerHour * $mult))
                : 0;
            $otRowsHtml .= '<tr><td style="border:1px solid #000;padding:3px 5px;">' . $label . '</td>'
                . '<td style="border:1px solid #000;padding:3px 5px;text-align:center;">' . ($val > 0 ? $hrs : '0') . '</td>'
                . '<td style="border:1px solid #000;padding:3px 5px;text-align:right;">' . $fmtAmt($val) . '</td></tr>';
        }
        $nightHrs = ($ratePerHour > 0 && $nightDiffPay > 0) ? (int)round($nightDiffPay / ($ratePerHour * 0.10)) : 0;
        $absDays  = ($ratePerHour > 0 && $absentAmt > 0) ? (int)round($absentAmt / ($ratePerHour * 8)) : 0;
        $utHrs    = ($ratePerHour > 0 && $undertimeAmt > 0) ? (int)round($undertimeAmt / $ratePerHour) : 0;
        $rowspan  = 14; // basic section rows + OT header

        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body  { font-family: Arial, sans-serif; font-size: 12px; margin: 0; }
    .wrap { background:#fff; border:1px solid #000; padding:16px; width:100%; box-sizing:border-box; }
    h2    { text-align:center; margin:4px 0 8px; }
    table { width:100%; border-collapse:collapse; font-size:12px; }
    td, th { border:1px solid #000; padding:3px 5px; vertical-align:top; }
    th { background:#eee; text-align:center; font-weight:bold; }
</style>
</head>
<body>
<div class="wrap">
' . $logoHtml . '
<h2>PAY SLIP</h2>
<table>
    <tr>
        <td><strong>Employee:</strong></td>
        <td colspan="3">' . htmlspecialchars($data['employee_name'] ?? '') . '</td>
        <td><strong>Payroll period:</strong></td>
        <td colspan="2">' . $period . '</td>
    </tr>
    <tr>
        <td><strong>Position:</strong></td>
        <td colspan="6">' . htmlspecialchars($data['position'] ?? 'N/A') . '</td>
    </tr>
</table>
<table style="margin-top:6px;">
    <colgroup>
        <col style="width:36%">
        <col style="width:9%">
        <col style="width:13%">
        <col style="width:21%">
        <col style="width:21%">
    </colgroup>
    <tr>
        <th colspan="3">REGULAR EARNINGS</th>
        <th>TAXABLE OTHER EARNINGS</th>
        <th>NON-TAXABLE OTHER EARNINGS</th>
    </tr>
    <tr>
        <th style="background:#f5f5f5;"></th>
        <th style="background:#f5f5f5;text-align:center;">Days/Hours</th>
        <th style="background:#f5f5f5;text-align:right;">Amount</th>
        <th style="background:#f5f5f5;"></th>
        <th style="background:#f5f5f5;"></th>
    </tr>
    <tr>
        <td>Basic pay:</td><td></td><td style="text-align:right;">' . $fmt($data['basic_pay'] ?? 0) . '</td>
        <td rowspan="' . $rowspan . '" style="vertical-align:top;padding:5px;">'
            . ($taxableOther > 0 ? '<strong>Salary adjustment</strong><br>' . $fmt($taxableOther) : '') . '</td>
        <td rowspan="' . $rowspan . '" style="vertical-align:top;padding:5px;">
            <strong>De Minimis Benefit</strong>&nbsp;&nbsp;' . $fmt($nonTaxable)
            . ($vlCashConversion > 0 ? '<br><strong>VL Cash Conversion</strong>&nbsp;&nbsp;' . $fmt($vlCashConversion) : '') . '
        </td>
    </tr>
    <tr><td>Rate per hour</td><td style="text-align:center;">' . $fmt($ratePerHour) . '</td><td></td></tr>
    <tr><td>No. of hours Worked:</td><td style="text-align:center;">' . $hoursWorked . '</td><td></td></tr>
    <tr><td>Night premium</td><td style="text-align:center;">' . $nightHrs . '</td><td style="text-align:right;">' . $fmtAmt($nightDiffPay) . '</td></tr>
    <tr><td>Less: Absences</td><td style="text-align:center;">' . $absDays . '</td><td style="text-align:right;">' . $fmtAmt($absentAmt) . '</td></tr>
    <tr><td>&nbsp;&nbsp;Late/Undertime</td><td style="text-align:center;">' . $utHrs . '</td><td style="text-align:right;">' . $fmtAmt($undertimeAmt) . '</td></tr>
    <tr><td><strong>Net basic pay</strong></td><td></td><td style="text-align:right;"><strong>' . $fmt($netBasicPay) . '</strong></td></tr>
    ' . $otRowsHtml . '
</table>
<table>
    <tr>
        <td colspan="3" style="text-align:right;"><strong>GROSS PAY</strong></td>
        <td style="text-align:right;"><strong>' . $fmt($grossPay) . '</strong></td>
    </tr>
</table>
' . (function() use ($data, $fmt, $totalDeductionsDisplay) {
    $regDeds = [
        ['SSS',             (float)($data['sss_ee']          ?? 0)],
        ['Philhealth',      (float)($data['phic_ee']         ?? 0)],
        ['HDMF',            (float)($data['hdmf_ee']         ?? 0)],
        ['Withholding tax', (float)($data['withholding_tax'] ?? 0)],
    ];
    $allLoans = [
        ['Salary Loan',              (float)($data['sss_loan']            ?? 0)],
        ['Calamity Loan',            (float)($data['calamity_loan']       ?? 0)],
        ['HDMF Loan',                (float)($data['hdmf_loan']           ?? 0)],
        ['HDMF Multi-Purpose Loan',  (float)($data['hdmf_mpl_loan']       ?? 0)],
        ['HDMF Calamity Loan',       (float)($data['hdmf_calamity_loan']  ?? 0)],
        ['Salary Advance',           (float)($data['salary_advance']      ?? ($data['sal_adv'] ?? 0))],
        ['Cash Advance',             (float)($data['cash_advance']        ?? 0)],
        ['Emergency Advance',        (float)($data['emergency_advance']   ?? 0)],
        ['Cal. Advance',             (float)($data['cal_adv']             ?? 0)],
    ];
    // Loans-module deductions (employee_loans, via the Loans screen) — kept
    // separate from the manual raw-sheet fields above so both sources show
    // up on the payslip instead of the Loans-module ones being silently
    // dropped.
    $loanDeductionsRaw = $data['loan_deductions'] ?? [];
    if (is_string($loanDeductionsRaw)) {
        $loanDeductionsRaw = json_decode($loanDeductionsRaw, true) ?? [];
    }
    foreach ($loanDeductionsRaw as $loan) {
        $amount = (float)($loan['amount'] ?? 0);
        if ($amount > 0) {
            $allLoans[] = [trim((string)($loan['loan_type'] ?? 'Loan')), $amount];
        }
    }
    $loans = array_values(array_filter($allLoans, fn($r) => $r[1] > 0));
    $maxRows = max(count($regDeds), count($loans));
    $loanHeader = '<th colspan="2">LOANS/ADVANCES</th>';
    $out = '<table style="margin-top:6px;"><tr><th colspan="2">REGULAR DEDUCTIONS</th>' . $loanHeader . '</tr>';
    for ($i = 0; $i < $maxRows; $i++) {
        $l = $regDeds[$i] ?? null;
        $r = $loans[$i]   ?? null;
        $out .= '<tr>';
        $out .= $l ? '<td>' . $l[0] . '</td><td style="text-align:right;">' . $fmt($l[1]) . '</td>'
                   : '<td colspan="2"></td>';
        $out .= $r ? '<td>' . $r[0] . '</td><td style="text-align:right;">' . $fmt($r[1]) . '</td>'
                   : '<td colspan="2"></td>';
        $out .= '</tr>';
    }
    $out .= '<tr><td colspan="2"></td><td colspan="2" style="text-align:right;"><strong>TOTAL DEDUCTIONS</strong>&nbsp;&nbsp;' . $fmt($totalDeductionsDisplay) . '</td></tr>';
    $out .= '</table>';
    return $out;
})() . '
<table style="margin-top:6px;">
    <tr>
        <td style="width:30%;">Employee signature:</td>
        <td style="width:30%;height:35px;"></td>
        <td style="width:20%;text-align:right;"><strong>NET PAY:</strong></td>
        <td style="width:20%;text-align:right;background:#f8d7da;"><strong>' . $fmt($data['net_pay'] ?? 0) . '</strong></td>
    </tr>
</table>
</div>
</body>
</html>';
    }

    private function getUserEmail($userId)
    {
        $db = \Config\Database::connect();
        $row = $db->table('auth_identities')
            ->select('secret')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get()
            ->getRowArray();
        return $row['secret'] ?? null;
    }

    /**
     * Password-protects a rendered payslip PDF: the employee's payslip password
     * opens it; the owner password is random per file (nobody needs it) so the
     * print-only restriction can't be lifted with a well-known constant.
     */
    private function encryptPayslipPdf(Dompdf $dompdf, array $employee): void
    {
        $dompdf->getCanvas()->get_cpdf()->setEncryption($this->resolvePayslipPassword($employee), bin2hex(random_bytes(16)), ['print']);
    }

    private function resolvePayslipPassword(array $employee): string
    {
        $db = \Config\Database::connect();
        $settingsRow = $db->table('user_settings')
            ->where('user_id', $employee['user_id'])
            ->get()
            ->getRowArray();

        if ($settingsRow) {
            $settings = json_decode($settingsRow['settings'], true);
            if (! empty($settings['payslip_password_enc'])) {
                $encrypter = \Config\Services::encrypter();
                try {
                    return $encrypter->decrypt(base64_decode($settings['payslip_password_enc']));
                } catch (\Exception $e) {
                    log_message('warning', 'Failed to decrypt payslip password for user ' . $employee['user_id']);
                }
            }
        }
        return $employee['employee_number'] . date('Ymd', strtotime($employee['birthdate']));
    }

    private function getCompanyLogoUrl(): ?string
    {
        try {
            $companyId = $this->resolveActiveCompanyId();
            $branding  = (new \App\Models\Client\CompanyBrandingModel())
                ->where('company_id', $companyId)->first();
            return (!empty($branding['logo_path']))
                ? base_url($branding['logo_path'])
                : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Returns a base64 data URI for the company logo (resized to thumbnail)
     * so DomPDF can embed it without HTTP requests or memory exhaustion from
     * processing a large full-resolution image.
     */
    private function getLogoDataUri(): ?string
    {
        try {
            $companyId = $this->resolveActiveCompanyId();
            $branding  = (new \App\Models\Client\CompanyBrandingModel())
                ->where('company_id', $companyId)->first();
            if (empty($branding['logo_path'])) return null;

            $rel      = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$branding['logo_path']), DIRECTORY_SEPARATOR);
            $filePath = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . $rel;
            if (!is_file($filePath)) return null;

            // Resize to at most 300×90 px via GD to keep base64 tiny and
            // avoid DomPDF exhausting memory when decoding a large PNG.
            $src = @imagecreatefromstring(file_get_contents($filePath));
            if ($src === false) return null;

            $origW = imagesx($src);
            $origH = imagesy($src);
            $maxW  = 300;
            $maxH  = 90;
            $scale = min($maxW / max($origW, 1), $maxH / max($origH, 1), 1.0);
            $newW  = max(1, (int)round($origW * $scale));
            $newH  = max(1, (int)round($origH * $scale));

            $dst = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW - 1, $newH - 1, $trans);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);

            ob_start();
            imagepng($dst, null, 6);
            $pngData = ob_get_clean();
            imagedestroy($dst);

            return 'data:image/png;base64,' . base64_encode($pngData);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getCompanyLogo()
    {
        $companyId = $this->resolveActiveCompanyId();
        $branding = (new \App\Models\Client\CompanyBrandingModel())->where('company_id', $companyId)->first();
        $logoUrl = !empty($branding['logo_path']) ? base_url($branding['logo_path']) : null;
        return $this->response->setJSON(['logo_url' => $logoUrl]);
    }

    // =========================================================================
    // SSS BRACKETS
    // =========================================================================

    /** GET  client_portal/payroll/sss/years  → [{year, is_active}] */
    public function sssYears()
    {
        $db   = \Config\Database::connect();
        $rows = $db->query(
            'SELECT effective_year AS `year`, MAX(is_active) AS is_active
             FROM sss_brackets
             GROUP BY effective_year
             ORDER BY effective_year DESC'
        )->getResultArray();
        return $this->response->setJSON($rows);
    }

    /** GET  client_portal/payroll/sss/brackets?year=2025  → bracket rows */
    public function sssBrackets()
    {
        $year = (int) ($this->request->getGet('year') ?? date('Y'));
        $db   = \Config\Database::connect();
        $rows = $db->query(
            'SELECT id, range_start, range_end, monthly_salary_credit,
                    ee_regular, ee_wisp, er_regular, er_wisp
             FROM   sss_brackets
             WHERE  effective_year = ?
             ORDER  BY range_start ASC',
            [$year]
        )->getResultArray();
        return $this->response->setJSON($rows);
    }

    /** POST client_portal/payroll/sss/saveBrackets  { year, brackets: [...] } */
    public function sssSaveBrackets()
    {
        $body     = $this->request->getJSON(true);
        $year     = (int) ($body['year'] ?? 0);
        $brackets = $body['brackets'] ?? [];

        if (!$year || !is_array($brackets)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Invalid payload.']);
        }

        $db = \Config\Database::connect();

        // Preserve the active flag before wiping rows so a save never clears it
        $isActive = (int) $db->query(
            'SELECT MAX(is_active) AS a FROM sss_brackets WHERE effective_year = ?', [$year]
        )->getRow()->a;

        $db->transStart();

        $db->query('DELETE FROM sss_brackets WHERE effective_year = ?', [$year]);

        foreach ($brackets as $b) {
            $db->table('sss_brackets')->insert([
                'effective_year'        => $year,
                'range_start'           => $b['range_start']           ?? 0,
                'range_end'             => isset($b['range_end']) && $b['range_end'] !== '' ? $b['range_end'] : null,
                'monthly_salary_credit' => $b['monthly_salary_credit'] ?? 0,
                'ee_regular'            => $b['ee_regular']            ?? 0,
                'ee_wisp'               => $b['ee_wisp']               ?? 0,
                'er_regular'            => $b['er_regular']            ?? 0,
                'er_wisp'               => $b['er_wisp']               ?? 0,
                'is_active'             => $isActive,
            ]);
        }

        $db->transComplete();
        $success = $db->transStatus();

        if ($success) {
            service('activityLogger')->sssBracketsYearSaved($year, count($brackets));

            // If this year is already the active one, announce the update
            if ($isActive) {
                $this->postContributionAnnouncement(
                    "SSS Contribution Table Updated ({$year})",
                    "The active SSS contribution bracket table ({$year}) has been updated "
                        . "and will be applied to all upcoming payroll computations.\n\n"
                        . "Please review your payslip details with your payroll officer if you have any questions."
                );
            }
        }

        return $this->response->setJSON(['success' => $success]);
    }

    /** POST client_portal/payroll/sss/setActive  { year } */
    public function sssSetActive()
    {
        $year = (int) ($this->request->getJSON(true)['year'] ?? 0);
        if (!$year) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }

        $db = \Config\Database::connect();
        $db->query('UPDATE sss_brackets SET is_active = 0');
        $db->query('UPDATE sss_brackets SET is_active = 1 WHERE effective_year = ?', [$year]);

        service('activityLogger')->sssBracketsYearActivated($year);

        $this->postContributionAnnouncement(
            "SSS Contribution Table Updated to {$year}",
            "The SSS contribution bracket table has been updated. The {$year} schedule is now active "
                . "and will be used for all upcoming payroll computations.\n\n"
                . "Please review your payslip details with your payroll officer if you have any questions."
        );

        return $this->response->setJSON(['success' => true]);
    }

    /** POST client_portal/payroll/sss/createYear  { year, copy_from? } */
    public function sssCreateYear()
    {
        $body     = $this->request->getJSON(true);
        $year     = (int) ($body['year']      ?? 0);
        $copyFrom = (int) ($body['copy_from'] ?? 0);

        if (!$year) {
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Invalid year.']);
        }

        $db     = \Config\Database::connect();
        $exists = $db->query(
            'SELECT COUNT(*) AS n FROM sss_brackets WHERE effective_year = ?', [$year]
        )->getRow()->n;

        if ($exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Year {$year} already exists.",
            ]);
        }

        if ($copyFrom) {
            $db->query(
                'INSERT INTO sss_brackets
                    (effective_year, range_start, range_end, monthly_salary_credit,
                     ee_regular, ee_wisp, er_regular, er_wisp, is_active)
                 SELECT ?, range_start, range_end, monthly_salary_credit,
                        ee_regular, ee_wisp, er_regular, er_wisp, 0
                 FROM   sss_brackets
                 WHERE  effective_year = ?',
                [$year, $copyFrom]
            );
        }

        service('activityLogger')->sssBracketsYearCreated($year, $copyFrom ?: null);

        return $this->response->setJSON(['success' => true]);
    }

    // =========================================================================
    // WITHHOLDING TAX BRACKETS
    // =========================================================================

    /** GET  client_portal/payroll/wtax/years  → [{year, is_active}] */
    public function wtaxYears()
    {
        $db   = \Config\Database::connect();
        $rows = $db->query(
            'SELECT effective_year AS `year`, MAX(is_active) AS is_active
             FROM   wtax_brackets_monthly
             GROUP  BY effective_year
             ORDER  BY effective_year DESC'
        )->getResultArray();
        return $this->response->setJSON($rows);
    }

    /** GET  client_portal/payroll/wtax/brackets?year=2025  → bracket rows */
    public function wtaxBrackets()
    {
        $year = (int) ($this->request->getGet('year') ?? date('Y'));
        $db   = \Config\Database::connect();
        $rows = $db->query(
            'SELECT id, min_amount, max_amount, fixed_tax, percent_over
             FROM   wtax_brackets_monthly
             WHERE  effective_year = ?
             ORDER  BY min_amount ASC',
            [$year]
        )->getResultArray();
        return $this->response->setJSON($rows);
    }

    /** POST client_portal/payroll/wtax/saveBrackets  { year, brackets: [...] } */
    public function wtaxSaveBrackets()
    {
        $body     = $this->request->getJSON(true);
        $year     = (int) ($body['year'] ?? 0);
        $brackets = $body['brackets'] ?? [];

        if (!$year || !is_array($brackets)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Invalid payload.']);
        }

        $db = \Config\Database::connect();

        // Preserve the active flag before wiping rows so a save never clears it
        $isActive = (int) $db->query(
            'SELECT MAX(is_active) AS a FROM wtax_brackets_monthly WHERE effective_year = ?', [$year]
        )->getRow()->a;

        $db->transStart();

        $db->query('DELETE FROM wtax_brackets_monthly WHERE effective_year = ?', [$year]);

        foreach ($brackets as $b) {
            $db->table('wtax_brackets_monthly')->insert([
                'effective_year' => $year,
                'min_amount'     => $b['min_amount']   ?? 0,
                'max_amount'     => isset($b['max_amount']) && $b['max_amount'] !== '' ? $b['max_amount'] : null,
                'fixed_tax'      => $b['fixed_tax']    ?? 0,
                'percent_over'   => $b['percent_over'] ?? 0,
                'is_active'      => $isActive,
            ]);
        }

        $db->transComplete();
        $success = $db->transStatus();

        if ($success) {
            service('activityLogger')->wtaxBracketsYearSaved($year, count($brackets));

            // If this year is already the active one, announce the update
            if ($isActive) {
                $this->postContributionAnnouncement(
                    "Withholding Tax Table Updated ({$year})",
                    "The active withholding tax bracket table ({$year}) has been updated "
                        . "and will be applied to all upcoming payroll computations.\n\n"
                        . "Please review your payslip details with your payroll officer if you have any questions."
                );
            }
        }

        return $this->response->setJSON(['success' => $success]);
    }

    /** POST client_portal/payroll/wtax/setActive  { year } */
    public function wtaxSetActive()
    {
        $year = (int) ($this->request->getJSON(true)['year'] ?? 0);
        if (!$year) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }

        $db = \Config\Database::connect();
        $db->query('UPDATE wtax_brackets_monthly SET is_active = 0');
        $db->query('UPDATE wtax_brackets_monthly SET is_active = 1 WHERE effective_year = ?', [$year]);

        service('activityLogger')->wtaxBracketsYearActivated($year);

        $this->postContributionAnnouncement(
            "Withholding Tax Table Updated to {$year}",
            "The withholding tax bracket table has been updated. The {$year} schedule is now active "
                . "and will be applied to all upcoming payroll computations.\n\n"
                . "Please review your payslip details with your payroll officer if you have any questions."
        );

        return $this->response->setJSON(['success' => true]);
    }

    /** POST client_portal/payroll/wtax/createYear  { year, copy_from? } */
    public function wtaxCreateYear()
    {
        $body     = $this->request->getJSON(true);
        $year     = (int) ($body['year']      ?? 0);
        $copyFrom = (int) ($body['copy_from'] ?? 0);

        if (!$year) {
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Invalid year.']);
        }

        $db     = \Config\Database::connect();
        $exists = $db->query(
            'SELECT COUNT(*) AS n FROM wtax_brackets_monthly WHERE effective_year = ?', [$year]
        )->getRow()->n;

        if ($exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Year {$year} already exists.",
            ]);
        }

        if ($copyFrom) {
            $db->query(
                'INSERT INTO wtax_brackets_monthly
                    (effective_year, min_amount, max_amount, fixed_tax, percent_over, is_active)
                 SELECT ?, min_amount, max_amount, fixed_tax, percent_over, 0
                 FROM   wtax_brackets_monthly
                 WHERE  effective_year = ?',
                [$year, $copyFrom]
            );
        }

        service('activityLogger')->wtaxBracketsYearCreated($year, $copyFrom ?: null);

        return $this->response->setJSON(['success' => true]);
    }

    // =========================================================================
    // ADDITIONAL DE MINIMIS CATEGORIES (BIR) — separate from the existing
    // fixed rice/uniform/laundry/medical fields on employee_positions, which
    // this never touches. Caps live here, editable, not hardcoded, since BIR
    // revises them periodically.
    // =========================================================================

    /** GET client_portal/payroll/deminimis/categories */
    public function getDeMinimisCategories()
    {
        $model = new \App\Models\Client\DeMinimisCategoryModel();
        return $this->response->setJSON(
            $model->orderBy('label', 'ASC')->findAll()
        );
    }

    /** POST client_portal/payroll/deminimis/saveCategories  { categories: [...] } */
    public function saveDeMinimisCategories()
    {
        $body       = $this->request->getJSON(true);
        $categories = $body['categories'] ?? [];

        if (!is_array($categories)) {
            return $this->response->setStatusCode(400)
                ->setJSON(['success' => false, 'message' => 'Invalid payload.']);
        }

        $model = new \App\Models\Client\DeMinimisCategoryModel();

        foreach ($categories as $c) {
            $id = (int) ($c['id'] ?? 0);
            if (!$id) {
                continue;
            }

            $model->update($id, [
                'label'      => $c['label'] ?? '',
                'note'       => $c['note'] ?? null,
                'cap_amount' => (float) ($c['cap_amount'] ?? 0),
                'cap_period' => in_array($c['cap_period'] ?? '', ['monthly', 'annual', 'per_payroll'], true)
                    ? $c['cap_period']
                    : 'annual',
                'is_active'  => !empty($c['is_active']) ? 1 : 0,
            ]);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /** POST client_portal/payroll/deminimis/restoreDefaults */
    public function restoreDeMinimisDefaults()
    {
        $model = new \App\Models\Client\DeMinimisCategoryModel();
        $model->seedBirDefaults();

        return $this->response->setJSON([
            'success'    => true,
            'categories' => $model->orderBy('label', 'ASC')->findAll(),
        ]);
    }

    // =========================================================================
    // 13TH MONTH PAY & ANNUALIZATION
    // =========================================================================

    /**
     * POST client_portal/payroll/computeYearEnd  { year: 2025 }
     *
     * Computes 13th month pay (PD 851) and year-end withholding tax annualization
     * (BIR) for every employee that has payroll transactions in the requested year.
     * Results are upserted into thirteenth_month_records and annualization_records.
     */
    public function computeYearEnd()
    {
        $body = $this->request->getJSON(true) ?: [];
        $year = (int)($body['year'] ?? date('Y'));

        if ($year < 2000 || $year > 2100) {
            return $this->response->setStatusCode(400)
                ->setJSON(['error' => 'Invalid year.']);
        }

        $db      = \Config\Database::connect();
        $service = new PayrollComputationService();

        // employee_payslips is the primary payroll record store (generated via Generate Payslip tab).
        // We fetch every payslip for the year and parse the generated_data JSON blob in PHP.
        $rows = $db->table('employee_payslips ep')
            ->select([
                'ep.employee_id',
                'ep.generated_data',
                "CONCAT(
                    TRIM(e.first_name), ' ',
                    IF(COALESCE(TRIM(e.middle_name),'')='','',
                       CONCAT(UPPER(LEFT(TRIM(e.middle_name),1)),'. ')
                    ),
                    TRIM(e.last_name)
                ) AS employee_name",
            ], false)
            ->join('employees e', 'e.id = ep.employee_id', 'left')
            ->where('YEAR(ep.period_start)', $year)
            ->orderBy('ep.employee_id, ep.period_start', 'ASC')
            ->get()->getResultArray();

        if (empty($rows)) {
            return $this->response->setJSON([
                'success'          => true,
                'year'             => $year,
                'thirteenth_month' => [],
                'annualization'    => [],
                'message'          => "No payslip records found for {$year}. Generate payslips first.",
            ]);
        }

        // Aggregate by employee from JSON blobs
        $byEmployee = [];
        foreach ($rows as $row) {
            $empId = (int)$row['employee_id'];
            $gd    = json_decode($row['generated_data'] ?? '{}', true) ?: [];

            if (!isset($byEmployee[$empId])) {
                $byEmployee[$empId] = [
                    'employee_name'          => $row['employee_name'],
                    'total_gross_basic'      => 0,  // sum of semi-monthly basic_pay (gross)
                    'total_absent_undertime' => 0,  // sum of absent_amt + undertime_amt
                    'total_pure_basic'       => 0,  // gross − deductions (13th month base)
                    'total_net_basic'        => 0,  // net_basic_pay (includes night diff, for tax)
                    'total_overtime'         => 0,
                    'total_other_taxable'    => 0,
                    'total_sss'              => 0,
                    'total_phic'             => 0,
                    'total_hdmf'             => 0,
                    'total_wtax'             => 0,
                    'periods_count'          => 0,
                ];
            }

            // Pure basic for 13th month (DOLE PD 851: excludes ND, OT, allowances)
            $basicPay     = (float)($gd['basic_pay']     ?? 0);
            $absentAmt    = (float)($gd['absent_amt']    ?? 0);
            $undertimeAmt = (float)($gd['undertime_amt'] ?? 0);
            $deductions   = $absentAmt + $undertimeAmt;
            $pureBasic    = max(0, $basicPay - $deductions);

            // net_basic_pay from JSON already includes night diff
            $netBasic = (float)($gd['net_basic_pay'] ?? $pureBasic);

            $byEmployee[$empId]['total_gross_basic']      += $basicPay;
            $byEmployee[$empId]['total_absent_undertime'] += $deductions;
            $byEmployee[$empId]['total_pure_basic']       += $pureBasic;
            $byEmployee[$empId]['total_net_basic']         += $netBasic;
            $byEmployee[$empId]['total_overtime']       += (float)($gd['overtime_pay']            ?? 0);
            $byEmployee[$empId]['total_other_taxable']  += (float)($gd['other_earnings_taxable']  ?? 0);
            $byEmployee[$empId]['total_sss']            += (float)($gd['sss_ee']                  ?? 0);
            $byEmployee[$empId]['total_phic']           += (float)($gd['phic_ee']                 ?? 0);
            $byEmployee[$empId]['total_hdmf']            += (float)($gd['hdmf_ee']                ?? 0);
            $byEmployee[$empId]['total_wtax']           += (float)($gd['withholding_tax']         ?? 0);
            $byEmployee[$empId]['periods_count']++;
        }

        // TRAIN Law: 13th month exempt up to ₱90,000 (configurable via payroll_settings)
        $settings    = new PayrollSettingsModel();
        $exemptLimit = (float)($settings->getSetting('thirteenth_month_exempt_limit') ?? 90000);

        $thirteenthModel    = new \App\Models\Client\ThirteenthMonthModel();
        $annualizationModel = new \App\Models\Client\AnnualizationModel();
        $thirteenthData     = [];
        $annualizationData  = [];

        // Every employee's 13th-month/annualization upsert used to run as its
        // own unguarded write — a failure partway through a large roster left
        // some employees recomputed against this run and others still holding
        // whatever a previous run (or nothing) had saved, with no way to tell
        // which employees actually reflect this computation. Wrapping the
        // whole pass keeps it all-or-nothing without changing a single
        // computed value or the response shape for the success case.
        $db->transBegin();
        foreach ($byEmployee as $empId => $agg) {
            // ── 13th Month ───────────────────────────────────────────────────
            $totalPureBasic = $agg['total_pure_basic'];
            $thirteenth     = round($totalPureBasic / 12, 2);
            $taxExempt      = round(min($thirteenth, $exemptLimit), 2);
            $taxable13th    = round(max(0, $thirteenth - $exemptLimit), 2);

            // ── Annualization ─────────────────────────────────────────────────
            // Taxable compensation = net_basic (includes ND) + OT + other taxable,
            // less SSS/PHIC/HDMF — the per-period withholding tax (total_wtax,
            // see updateWithholdingTax() in payroll_management.php) is computed
            // on gross minus those contributions, so the annual figure has to
            // net them out too or this double-counts them and overstates both
            // annual_taxable_income and the resulting year_end_adjustment.
            $totalTaxable  = max(0, $agg['total_net_basic']
                           + $agg['total_overtime']
                           + $agg['total_other_taxable']
                           - $agg['total_sss']
                           - $agg['total_phic']
                           - $agg['total_hdmf']);
            $annualTaxable = round($totalTaxable + $taxable13th, 2);
            $annualTaxDue  = $service->calculateAnnualWithholdingTax($annualTaxable);
            $totalWtax     = round($agg['total_wtax'], 2);
            $adjustment    = round($annualTaxDue - $totalWtax, 2);

            $now = date('Y-m-d H:i:s');

            // Upsert 13th month record
            $rec13  = $thirteenthModel->where('employee_id', $empId)->where('year', $year)->first();
            $data13 = [
                'employee_id'             => $empId,
                'year'                    => $year,
                'total_gross_basic'       => round($agg['total_gross_basic'], 2),
                'total_absent_undertime'  => round($agg['total_absent_undertime'], 2),
                'total_basic_earned'      => round($totalPureBasic, 2),
                'periods_count'           => $agg['periods_count'],
                'thirteenth_month_amount' => $thirteenth,
                'tax_exempt_amount'       => $taxExempt,
                'taxable_amount'          => $taxable13th,
                'status'                  => 'computed',
                'computed_at'             => $now,
            ];
            $rec13 ? $thirteenthModel->update($rec13['id'], $data13)
                   : $thirteenthModel->insert($data13);

            // Upsert annualization record
            $recAnn  = $annualizationModel->where('employee_id', $empId)->where('year', $year)->first();
            $dataAnn = [
                'employee_id'                => $empId,
                'year'                       => $year,
                'total_taxable_compensation' => round($totalTaxable, 2),
                'thirteenth_month_taxable'   => $taxable13th,
                'annual_taxable_income'      => $annualTaxable,
                'annual_tax_due'             => $annualTaxDue,
                'total_tax_withheld'         => $totalWtax,
                'year_end_adjustment'        => $adjustment,
                'status'                     => 'computed',
                'computed_at'                => $now,
            ];
            $recAnn ? $annualizationModel->update($recAnn['id'], $dataAnn)
                    : $annualizationModel->insert($dataAnn);

            $thirteenthData[] = [
                'employee_id'             => $empId,
                'employee_name'           => $agg['employee_name'],
                'total_gross_basic'       => round($agg['total_gross_basic'], 2),
                'total_absent_undertime'  => round($agg['total_absent_undertime'], 2),
                'total_basic_earned'      => round($totalPureBasic, 2),
                'periods_count'           => $agg['periods_count'],
                'thirteenth_month_amount' => $thirteenth,
                'tax_exempt_amount'       => $taxExempt,
                'taxable_amount'          => $taxable13th,
            ];

            $annualizationData[] = [
                'employee_id'                => $empId,
                'employee_name'              => $agg['employee_name'],
                'total_taxable_compensation' => round($totalTaxable, 2),
                'thirteenth_month_taxable'   => $taxable13th,
                'annual_taxable_income'      => $annualTaxable,
                'annual_tax_due'             => $annualTaxDue,
                'total_tax_withheld'         => $totalWtax,
                'year_end_adjustment'        => $adjustment,
            ];
        }

        if (!$db->transStatus()) {
            $db->transRollback();
            log_message('error', 'Year-end computation failed to save for {count} employee(s); rolled back.', ['count' => count($byEmployee)]);
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Year-end computation could not be saved. Please try again.']);
        }
        $db->transCommit();

        $actor = auth()->user();
        service('activityLogger')->log(
            'updated', 'payroll',
            'Year-end 13th-month/annualization computed for ' . $year . ' (' . count($byEmployee) . ' employee(s))',
            null, null,
            ['year' => $year, 'employee_count' => count($byEmployee), 'run_by' => $actor ? ($actor->username ?? $actor->id) : 'system']
        );

        return $this->response->setJSON([
            'success'          => true,
            'year'             => $year,
            'exempt_limit'     => $exemptLimit,
            'thirteenth_month' => $thirteenthData,
            'annualization'    => $annualizationData,
        ]);
    }

    /**
     * GET client_portal/payroll/getYearEndRecords?year=2025
     *
     * Returns previously saved 13th month and annualization records for the year.
     */
    public function getYearEndRecords()
    {
        $year = (int)($this->request->getGet('year') ?? date('Y'));
        $db   = \Config\Database::connect();

        $nameExpr = "CONCAT(
            TRIM(e.first_name), ' ',
            IF(COALESCE(TRIM(e.middle_name),'')='','',
               CONCAT(UPPER(LEFT(TRIM(e.middle_name),1)),'. ')
            ),
            TRIM(e.last_name)
        ) AS employee_name";

        $thirteenth = $db->table('thirteenth_month_records t')
            ->select("t.*, {$nameExpr}", false)
            ->join('employees e', 'e.id = t.employee_id', 'left')
            ->where('t.year', $year)
            ->orderBy('e.last_name', 'ASC')
            ->get()->getResultArray();

        $annualization = $db->table('annualization_records a')
            ->select("a.*, {$nameExpr}", false)
            ->join('employees e', 'e.id = a.employee_id', 'left')
            ->where('a.year', $year)
            ->orderBy('e.last_name', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'year'             => $year,
            'thirteenth_month' => $thirteenth,
            'annualization'    => $annualization,
        ]);
    }

    // =========================================================================
    // CONTRIBUTION CHANGE ANNOUNCEMENTS
    // =========================================================================

    /**
     * Post a system-wide announcement whenever a contribution rule changes.
     * Targets audience = 'all' so every employee sees it in their notification bell.
     * Category is 'payroll' so it can be filtered/styled distinctly on the frontend.
     */
    private function postContributionAnnouncement(string $title, string $details): void
    {
        try {
            $user = auth()->user();
            if (! $user) return;

            (new AnnouncementService())->createAnnouncement((int) $user->id, [
                'title'                  => $title,
                'category'               => 'payroll',
                'audience'               => 'all',
                'department'             => '',
                'priority'               => 'normal',
                'publish_at'             => date('Y-m-d H:i:s'),
                'expires_at'             => '',
                'details'                => $details,
                'require_acknowledgement'=> false,
            ]);
        } catch (\Throwable $e) {
            // Announcement failure must never break the actual save operation
            log_message('error', 'postContributionAnnouncement failed: ' . $e->getMessage());
        }
    }

    // ================= LOAN BREAKDOWN =================

    /**
     * Pending Loans-module installments due on the exact cutoff date, for one
     * employee. Shared by the public getLoanBreakdown() endpoint below and by
     * the payslip save / release paths (savePayslipRecord, savePayslipEditData,
     * deliverPayslip's loan check), which need the same
     * figure server-side to fold into net pay — the client never sends it.
     *
     * Matches due_date == $periodEnd exactly (not a range) so a payslip saved
     * more than once for the same period always recomputes the same amount
     * instead of accumulating — see "IMPORTANT FIX" note below.
     */
    private function fetchLoanScheduleBreakdown(int $employeeId, string $periodEnd): array
    {
        $db = \Config\Database::connect();

        /*
        |--------------------------------------------------------------------------
        | DETERMINE CURRENT PAYROLL CUTOFF
        |--------------------------------------------------------------------------
        |
        | Example:
        | 2026-06-01 → 2026-06-15
        | fetch ONLY June 15 deduction
        |
        | 2026-06-16 → 2026-06-30
        | fetch ONLY June 30 deduction
        |
        */

        $cutoffDate = $periodEnd;

        /*
        |--------------------------------------------------------------------------
        | FETCH PENDING LOAN DEDUCTIONS
        |--------------------------------------------------------------------------
        */

        $schedules = $db->table('employee_loan_schedule els')

            ->select("
                els.id,
                els.loan_id,
                els.amount_due,
                els.due_date,
                els.remaining_balance,
                el.loan_category,
                el.loan_type,
                el.employee_id
            ")

            ->join(
                'employee_loans el',
                'el.id = els.loan_id'
            )

            ->where('el.employee_id', $employeeId)

            ->whereIn('el.loan_status', ['active', 'active_ongoing'])

            ->where('els.schedule_status', 'pending')

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT FIX
            |--------------------------------------------------------------------------
            |
            | Instead of:
            | due_date >= periodStart
            | due_date <= periodEnd
            |
            | We fetch ONLY the cutoff date.
            |
            */

            ->where('els.due_date', $cutoffDate)

            ->orderBy('els.due_date', 'ASC')

            ->get()

            ->getResultArray();

        /*
        |--------------------------------------------------------------------------
        | FORMAT RESPONSE
        |--------------------------------------------------------------------------
        */

        $breakdown = [];

        foreach ($schedules as $row) {

            $breakdown[] = [

                'schedule_id' => (int) $row['id'],

                'loan_id'     => (int) $row['loan_id'],

                'loan_category' => $row['loan_category'],

                'loan_type'   => $row['loan_type'],

                'amount'      => (float) $row['amount_due'],

                'due_date'    => $row['due_date'],

                'remaining_balance' =>
                    (float) $row['remaining_balance'],
            ];
        }

        return $breakdown;
    }

    public function getLoanBreakdown(
        int $employeeId,
        string $periodStart,
        string $periodEnd
    ) {
        try {
            return $this->response->setJSON(
                $this->fetchLoanScheduleBreakdown($employeeId, $periodEnd)
            );
        } catch (\Throwable $e) {

            log_message(
                'error',
                'getLoanBreakdown Error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([

                    'success' => false,

                    'message' => $this->safeErrorMessage($e, 'Unable to load loan breakdown.')

                ]);
        }
    }

    // ================= HOLIDAY MANAGEMENT =================

    public function holidayList()
    {
        $year  = (int) ($this->request->getGet('year') ?: date('Y'));
        $model = new \App\Models\Client\HolidayModel();
        $rows  = $model->forYear($year);
        return $this->response->setJSON(['success' => true, 'data' => $rows]);
    }

    public function holidaySave()
    {
        $post  = $this->request->getJSON(true) ?: $this->request->getPost();
        $model = new \App\Models\Client\HolidayModel();

        $id          = !empty($post['id']) ? (int) $post['id'] : null;
        $holidayDate = trim($post['holiday_date'] ?? '');
        $name        = trim($post['name']         ?? '');
        $type        = in_array($post['type'] ?? '', ['regular', 'special']) ? $post['type'] : 'regular';
        $isRecurring = !empty($post['is_recurring']) ? 1 : 0;

        if (!$holidayDate || !$name) {
            return $this->response->setJSON(['success' => false, 'message' => 'Date and name are required.']);
        }

        $payload = [
            'holiday_date' => $holidayDate,
            'name'         => $name,
            'type'         => $type,
            'is_recurring' => $isRecurring,
        ];

        if ($id) {
            $model->update($id, $payload);
        } else {
            $existing = $model->where('holiday_date', $holidayDate)->first();
            if ($existing) {
                $id = $existing['id'];
            } else {
                $id = $model->insert($payload);
            }
        }

        return $this->response->setJSON(['success' => true, 'id' => $id]);
    }

    public function holidayDelete()
    {
        $post = $this->request->getJSON(true) ?: $this->request->getPost();
        $id   = (int) ($post['id'] ?? 0);

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid ID.']);
        }

        $model = new \App\Models\Client\HolidayModel();
        if (!$model->find($id)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Not found.']);
        }

        $model->delete($id);
        return $this->response->setJSON(['success' => true]);
    }
}
