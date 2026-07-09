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
    private function computeStatusPeriodDates(string $month, string $periodType, \App\Models\Client\PayrollSettingsModel $psModel): array
    {
        if ($periodType === 'monthly') {
            return [null, null];
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

        // Build payslip join condition — monthly uses month match, half-periods use exact dates
        if ($periodType === 'monthly') {
            $psJoinCond = "ps.employee_id = e.id AND DATE_FORMAT(ps.period_start,'%Y-%m') = ?";
            $psBinds    = [$month, $periodType, $month];
        } else {
            $psJoinCond = "ps.employee_id = e.id AND ps.period_start = ? AND ps.period_end = ?";
            $psBinds    = [$month, $periodType, $periodStart, $periodEnd];
        }

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
                MAX(ps.sent_at)                     AS payslip_sent_at
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

        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $data['payslips'] = $payslipModel
            ->select("employee_payslips.*, CONCAT(employees.first_name,' ',employees.last_name) AS employee_name")
            ->join('employees', 'employees.id = employee_payslips.employee_id')
            ->orderBy('employee_payslips.created_at', 'DESC')
            ->findAll();

        return view('client_portal/payroll_main', $data);
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

            return $this->response->setJSON(['success' => true, 'id' => $id, 'status' => $status]);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to update payroll status. Please try again.');
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
                ->select('position, monthly_salary, de_minimis_benefit, de_minimis_breakdown, allowances')
                ->where('employee_id', $id)
                ->get()->getRowArray();

            $position      = $posRow['position']          ?? 'Employee';
            $monthlySalary = (float)($posRow['monthly_salary']     ?? 0);
            $deMinimis     = (float)($posRow['de_minimis_benefit'] ?? 0);
            $allowances    = (float)($posRow['allowances']         ?? 0);

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
                ->where('DATE(time_in) >=', $startDate)
                ->where('DATE(time_in) <=', $endDate)
                ->findAll();

            $posModel    = new \App\Models\Client\EmployeePositionModel();
            $position    = $posModel->where('employee_id', $employeeId)->first();
            $workingType = $position['working_day_type'] ?? null;
            $schedStart  = ($workingType !== 'flexible' && !empty($position['start_time']))
                           ? $position['start_time'] : null;

            $summary = $this->aggregateAttendance($attendances, $startDate, $endDate, $schedStart);

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

    private function aggregateAttendance(array $attendances, string $startDate, string $endDate, ?string $scheduledStart = null): array
    {
        $totalWorkingDays = $this->getWorkingDaysCount($startDate, $endDate);

        // Group records by date — employees can have multiple clock-ins per day
        $byDate = [];
        foreach ($attendances as $att) {
            if (empty($att['time_in']) || empty($att['time_out'])) continue;
            $attDate = date('Y-m-d', strtotime($att['time_in']));
            $byDate[$attDate][] = $att;
        }

        $workedDays      = count($byDate);
        $regularHours    = 0.0;
        $tardyMinutes    = 0;
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
            $firstIn = date('H:i:s', strtotime($recs[0]['time_in']));
            if ($scheduledStart && $firstIn > $scheduledStart) {
                $tardyMinutes += (int)round((strtotime($firstIn) - strtotime($scheduledStart)) / 60);
            }

            $dayHours = 0.0;
            $dayNsd   = 0.0;
            foreach ($recs as $rec) {
                $dayHours += (float)($rec['total_hours']      ?? 0);
                $dayNsd   += (float)($rec['night_diff_hours'] ?? 0);
            }

            $ot          = max(0, $dayHours - 8);
            $dow         = (int)date('N', strtotime($attDate));
            $holidayType = $getHolidayType($attDate);

            if ($holidayType === 'regular') {
                $holRegHours += min($dayHours, 8);
                $holOtHours  += $ot;
                $holNsdHours += $dayNsd;
            } elseif ($holidayType === 'special') {
                $specHolRegHours += min($dayHours, 8);
                $specHolOtHours  += $ot;
                $specHolNsdHours += $dayNsd;
            } elseif ($dow === 6) {
                $satRegHours += min($dayHours, 8);
                $satOtHours  += $ot;
                $satNsdHours += $dayNsd;
            } elseif ($dow === 7) {
                $sunRegHours += min($dayHours, 8);
                $sunOtHours  += $ot;
                $sunNsdHours += $dayNsd;
            } else {
                $regularHours    += min($dayHours, 8);
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

            /*
            |--------------------------------------------------------------------------
            | DEBUG COMPUTED
            |--------------------------------------------------------------------------
            */

            log_message(
                'error',
                'COMPUTED DATA: ' . print_r($computed, true)
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

                'loan_deductions' =>
                    json_encode($computed['loan_deductions'] ?? []),

                'other_deductions' =>
                    (float) ($computed['other_deductions'] ?? 0),

                'total_deductions' =>
                    (float) ($computed['total_deductions'] ?? 0),

                'net_pay' =>
                    (float) ($computed['net_pay'] ?? 0),

                'status' => 'computed',

                'computed_at' => date('Y-m-d H:i:s')
            ];

            /*
            |--------------------------------------------------------------------------
            | DEBUG CLEAN TX
            |--------------------------------------------------------------------------
            */

            log_message(
                'error',
                'CLEAN TX DATA: ' . print_r($cleanTxData, true)
            );

            $txModel->insert($cleanTxData);

            $txId = $txModel->getInsertID();

            $loanService = new \App\Services\Client\LoanService();

            $loanService->markInstallmentPaid(
                (int) $employeeId,
                $start,
                $end,
                $txId
            );

            return $this->response->setJSON([

                'success' => true,

                'transaction_id' => $txId,

                'computed' => $computed

            ]);

        } catch (\Exception $e) {

            return $this->jsonError($e, 'Unable to compute payroll. Please check the inputs and try again.');
        }
    }

    // ================= PAYROLL REGISTER (JSON for view) =================
    public function getRegisterRows()
    {
        $month  = $this->request->getGet('month')  ?? date('Y-m');
        $period = $this->request->getGet('period') ?? '';   // '1st_half' | '2nd_half' | ''

        $db = \Config\Database::connect();

        // Build period filter
        if ($period === '1st_half' || $period === '2nd_half') {
            $psModel = new \App\Models\Client\PayrollSettingsModel();
            [$pStart, $pEnd] = $this->computeStatusPeriodDates($month, $period, $psModel);
            $periodWhere = "ps.period_start = ? AND ps.period_end = ?";
            $binds = [$pStart, $pEnd];
        } else {
            $periodWhere = "DATE_FORMAT(ps.period_start,'%Y-%m') = ?";
            $binds = [$month];
        }

        $rows = $db->query("
            SELECT
                CONCAT(TRIM(e.first_name),' ',TRIM(e.last_name))   AS employee_name,
                COALESCE(ep.monthly_salary, 0)                      AS monthly_salary,
                ps.period_start, ps.period_end,
                ps.net_pay                                          AS db_net_pay,
                ps.generated_data
            FROM employee_payslips ps
            INNER JOIN employees e   ON e.id = ps.employee_id
            LEFT  JOIN users u       ON u.id = e.user_id AND u.active = 1
            LEFT  JOIN employee_positions ep ON ep.employee_id = e.id
            WHERE $periodWhere
            ORDER BY e.first_name, e.last_name
        ", $binds)->getResultArray();

        $result = [];
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
                'employee_name'    => $row['employee_name'],
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
                'net_pay'          => round($netPay,        2),
            ];
        }

        return $this->response->setJSON($result);
    }

    // ================= EXPORT / IMPORT =================
    public function exportRegister()
    {
        $month = $this->request->getGet('month') ?? date('Y-m');

        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $records = $payslipModel
            ->select("employee_payslips.*, CONCAT(employees.first_name,' ',employees.last_name) as employee_name")
            ->join('employees', 'employees.id = employee_payslips.employee_id')
            ->where("DATE_FORMAT(employee_payslips.period_start, '%Y-%m')", $month)
            ->orderBy('employee_name', 'ASC')
            ->findAll();

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

        $attendanceModel = new AttendanceModel();
        $records = $attendanceModel
            ->select('DATE(time_in) as date, time_in, time_out, break_duration, total_hours, night_diff_hours')
            ->where('user_id', $employee['user_id'])
            ->where('DATE(time_in) >=', $startDate)
            ->where('DATE(time_in) <=', $endDate)
            ->orderBy('time_in', 'ASC')
            ->findAll();

        $filename = "timesheet_{$employee['employee_number']}_{$startDate}_to_{$endDate}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['employee_number', 'date', 'time_in', 'time_out', 'break_duration', 'total_hours', 'night_diff_hours']);
        foreach ($records as $row) {
            fputcsv($output, [
                $employee['employee_number'],
                $row['date'],
                $row['time_in'],
                $row['time_out'],
                $row['break_duration'],
                $row['total_hours'],
                $row['night_diff_hours'] ?? 0,
            ]);
        }
        fclose($output);
        exit();
    }

    public function importTimesheet()
    {
        $file = $this->request->getFile('timesheet_file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid file.']);
        }

        $handle = fopen($file->getTempName(), 'r');
        $headers = fgetcsv($handle);
        $successCount = 0;
        $employeeModel = new EmployeeModel();
        $attendanceModel = new AttendanceModel();
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) continue;
            $data = array_combine($headers, $row);
            $employee = $employeeModel->where('employee_number', $data['employee_number'])->first();
            if (!$employee) continue;
            $breakDur = (float)($data['break_duration'] ?? 0);
            // time_in / time_out from our own export are full datetimes already;
            // handle both "HH:MM:SS" and "YYYY-MM-DD HH:MM:SS" formats.
            $rawIn  = trim($data['time_in']  ?? '');
            $rawOut = trim($data['time_out'] ?? '');
            $date   = trim($data['date']     ?? '');
            $timeInDt  = (strpos($rawIn,  ' ') !== false) ? $rawIn  : "{$date} {$rawIn}";
            $timeOutDt = $rawOut !== '' ? ((strpos($rawOut, ' ') !== false) ? $rawOut : "{$date} {$rawOut}") : null;
            $totalHours    = isset($data['total_hours']) && $data['total_hours'] !== ''
                           ? (float)$data['total_hours']
                           : $this->calculateTotalHours($timeInDt, $timeOutDt, $breakDur);
            // Use stored night_diff_hours if present in CSV; otherwise compute from timestamps
            $nightDiffHours = isset($data['night_diff_hours']) && $data['night_diff_hours'] !== ''
                            ? (float)$data['night_diff_hours']
                            : (($timeInDt && $timeOutDt) ? $this->calcNightDiffHours($timeInDt, $timeOutDt) : 0);
            $attendanceModel->insert([
                'user_id'          => $employee['user_id'],
                'time_in'          => $timeInDt,
                'time_out'         => $timeOutDt,
                'break_duration'   => $breakDur,
                'total_hours'      => $totalHours,
                'night_diff_hours' => round($nightDiffHours, 2),
            ]);
            $successCount++;
        }
        fclose($handle);
        return $this->response->setJSON(['success' => true, 'imported' => $successCount, 'message' => "{$successCount} record(s) imported successfully."]);
    }

    private function calculateTotalHours($timeIn, $timeOut, $breakDuration)
    {
        if (!$timeIn || !$timeOut) return 0;
        $start = new \DateTime($timeIn);
        $end = new \DateTime($timeOut);
        $diff = $end->diff($start);
        $hours = $diff->h + $diff->i / 60;
        return max(0, $hours - $breakDuration);
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

    public function sendPayslip($transactionId)
    {
        try {
            $txModel = new PayrollTransactionModel();
            $payroll = $txModel->find($transactionId);
            if (!$payroll) throw new \App\Exceptions\ValidationException('Transaction not found');

            $employeeModel = new EmployeeModel();
            $employee = $employeeModel->find($payroll['employee_id']);
            $emailAddress = $this->getUserEmail($employee['user_id']);

            if (!$emailAddress) {
                throw new \App\Exceptions\ValidationException('Employee email not found');
            }

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
            $pdfOutput = $dompdf->output();

            $filename = "payslip_{$employee['employee_number']}_{$payroll['period_start']}.pdf";
            $tempPath = WRITEPATH . 'uploads/payslips/';
            if (!is_dir($tempPath)) mkdir($tempPath, 0777, true);
            file_put_contents($tempPath . $filename, $pdfOutput);

            $email = service('email');
            $email->setTo($emailAddress);
            $email->setSubject("Payslip for period {$payroll['period_start']} to {$payroll['period_end']}");
            $email->setMessage("Dear {$employee['first_name']},<br><br>Please find attached your payslip.<br><br>Regards,<br>Payroll Team");
            $email->attach($tempPath . $filename);

            if ($email->send()) {
                unlink($tempPath . $filename);
                return $this->response->setJSON(['success' => true, 'message' => 'Payslip sent to ' . $emailAddress]);
            } else {
                throw new \App\Exceptions\ValidationException('Failed to send email');
            }
        } catch (\Exception $e) {
            return $this->jsonError($e, 'Unable to send payslip email.');
        }
    }

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
        $settingsModel = new PayrollSettingsModel();
        $data = $this->request->getPost();

        // Track which contribution-related settings actually changed
        $contributionKeys = ['phic_rate', 'phic_cap', 'hdmf_rate', 'hdmf_max'];
        $changed = [];

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
        ];
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $settingsModel->getSetting($key);
        }
        return $this->response->setJSON($settings);
    }

    public function sendStatic()
    {
        try {
            $data = $this->request->getJSON(true);
            $employeeId = $data['employee_id'];
            
            $employeeModel = new EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) throw new \App\Exceptions\ValidationException('Employee not found');

            $emailAddress = $this->getUserEmail($employee['user_id']);

            if (!$emailAddress) {
                throw new \App\Exceptions\ValidationException('Employee email not found');
            }

            $html = view('client_portal/email_payslip', ['data' => $data, 'employee' => $employee]);

            $email = service('email');
            $email->setTo($emailAddress);
            $email->setSubject("Payslip for period {$data['period_start']} to {$data['period_end']}");
            $email->setMessage($html);

            if ($email->send()) {
                return $this->response->setJSON(['success' => true]);
            } else {
                throw new \App\Exceptions\ValidationException('Failed to send email');
            }
        } catch (\Exception $e) {
            return $this->jsonError($e, 'Unable to send payslip email.');
        }
    }

    public function sendPdf()
    {
        try {
            $data       = $this->request->getJSON(true);
            $employeeId = $data['employee_id'] ?? null;
            if (!$employeeId) throw new \App\Exceptions\ValidationException('Missing employee_id');

            $employeeModel = new \App\Models\Client\EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) throw new \App\Exceptions\ValidationException('Employee not found');

            $emailAddress = $this->getUserEmail($employee['user_id']);
            if (!$emailAddress) throw new \App\Exceptions\ValidationException('Employee email not found');

            $payslipModel = new \App\Models\Client\EmployeePayslipModel();

            // --- Step 1: Upsert DB record with generated_data FIRST ---
            // Match by employee + period only (no net_pay) so we don't create duplicates.
            $existing = $payslipModel
                ->where('employee_id', $employeeId)
                ->where('period_start', $data['period_start'])
                ->where('period_end',   $data['period_end'])
                ->first();

            if ($existing) {
                $payslipModel->update($existing['id'], [
                    'net_pay'        => round((float)($data['net_pay'] ?? 0), 2),
                    'generated_data' => json_encode($data),
                ]);
                $payslipRecordId = $existing['id'];
            } else {
                $payslipModel->insert([
                    'employee_id'    => $employeeId,
                    'period_start'   => $data['period_start'],
                    'period_end'     => $data['period_end'],
                    'net_pay'        => round((float)($data['net_pay'] ?? 0), 2),
                    'generated_data' => json_encode($data),
                    'sent_at'        => null,
                ]);
                $inserted = $payslipModel
                    ->where('employee_id', $employeeId)
                    ->where('period_start', $data['period_start'])
                    ->where('period_end',   $data['period_end'])
                    ->first();
                $payslipRecordId = $inserted['id'] ?? null;
            }

            if (!$payslipRecordId) {
                throw new \App\Exceptions\ValidationException('Could not resolve payslip record ID after save.');
            }

            // --- Step 2: Generate PDF ---
            ini_set('memory_limit', '1G');
            $html = $this->buildPayslipHtmlForPdf($data, $employee);
            $options = new Options();
            $options->set('defaultFont', 'Courier');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $password = $this->resolvePayslipPassword($employee);
            $dompdf->getCanvas()->get_cpdf()->setEncryption($password, 'owner-password', ['print']);
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
            $email->setMailType('html');
            $email->setTo($emailAddress);
            $email->setSubject("Your Payslip for {$data['period_start']} to {$data['period_end']}");
            $email->setMessage("
                Dear {$empName},<br><br>
                Please find your payslip attached as a PDF. The file is password-protected.<br><br>
                <strong>PDF Password:</strong> Your payslip password as set in your account profile.
                If you have not changed it, the default is your Employee Number followed by your Birthdate (YYYYMMDD).<br><br>
                Regards,<br>
                Payroll Team
            ");
            $email->attach($tempFile);

            if ($email->send()) {
                if (is_file($tempFile)) unlink($tempFile);

                $sender     = auth()->user();
                $senderName = $sender ? ($sender->username ?? 'Unknown') : 'System';

                \Config\Database::connect()
                    ->table('employee_payslips')
                    ->where('id', $payslipRecordId)
                    ->update([
                        'sent_at' => date('Y-m-d H:i:s'),
                        'sent_by' => $senderName,
                    ]);

                service('activityLogger')->payslipSent(
                    $payslipRecordId,
                    $empName,
                    $data['period_start'],
                    $data['period_end'],
                    $emailAddress
                );

                return $this->response->setJSON(['success' => true]);
            } else {
                $debugInfo = $email->printDebugger(['headers', 'subject', 'body']);
                log_message('error', 'sendPdf email failed: ' . $debugInfo);
                // Record is already saved — return partial success so portal is updated.
                return $this->response->setJSON([
                    'success' => false,
                    'error'   => 'Payslip saved to portal but email delivery failed. Check spam or SMTP settings.',
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            return $this->jsonError($e, 'Unable to generate and send the payslip PDF.');
        }
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

            $employeeModel = new \App\Models\Client\EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) throw new \App\Exceptions\ValidationException('Employee not found');

            $payslipModel = new \App\Models\Client\EmployeePayslipModel();

            // --- Step 1: Save/update the record with generated_data immediately ---
            // This ensures the portal can display the payslip even if PDF generation fails.
            $existing = $payslipModel
                ->where('employee_id', $employeeId)
                ->where('period_start', $periodStart)
                ->where('period_end',   $periodEnd)
                ->first();

            if ($existing) {
                $payslipModel->update($existing['id'], [
                    'net_pay'        => round((float)$netPay, 2),
                    'generated_data' => json_encode($data),
                ]);
                $recordId = $existing['id'];
            } else {
                $payslipModel->insert([
                    'employee_id'    => $employeeId,
                    'period_start'   => $periodStart,
                    'period_end'     => $periodEnd,
                    'net_pay'        => round((float)$netPay, 2),
                    'generated_data' => json_encode($data),
                    'sent_at'        => null,
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

            // --- Step 2: Generate PDF and update file_path (best-effort, non-fatal) ---
            try {
                ini_set('memory_limit', '1G');
                $html = $this->buildPayslipHtmlForPdf($data, $employee);
                $options = new Options();
                $options->set('defaultFont', 'Courier');
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfOutput = $dompdf->output();

                $permanentDir = WRITEPATH . 'uploads/payslips/permanent/';
                if (!is_dir($permanentDir)) mkdir($permanentDir, 0777, true);
                $filename      = "payslip_{$employee['employee_number']}_{$periodStart}.pdf";
                $permanentPath = $permanentDir . $filename;
                file_put_contents($permanentPath, $pdfOutput);

                $payslipModel->update($recordId, ['file_path' => $permanentPath]);
            } catch (\Throwable $pdfErr) {
                log_message('error', 'savePayslipRecord PDF generation failed: ' . $pdfErr->getMessage());
            }

            $empName = $data['employee_name'] ?? trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
            service('activityLogger')->payslipGenerated(
                $recordId,
                $empName,
                $periodStart,
                $periodEnd,
                round((float)$netPay, 2)
            );

            return $this->response->setJSON(['success' => true, 'payslip_id' => $recordId]);
        } catch (\Exception $e) {
            return $this->jsonError($e, 'Unable to save payslip record.');
        }
    }

    public function getEmployeePayslips($employeeId)
    {
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $payslips = $payslipModel
            ->where('employee_id', $employeeId)
            ->orderBy('period_start', 'DESC')
            ->findAll();

        foreach ($payslips as &$ps) {
            $ps['download_url'] = site_url('client_portal/payroll/downloadPayslip/' . $ps['id']);
        }
        return $this->response->setJSON($payslips);
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
        $priorGross = (float)($d['gross_pay'] ?? 0);

        return $this->response->setJSON([
            'found'        => true,
            'net_taxable'  => round($netTaxable, 2),
            'wt'           => round($wt, 2),
            'prior_gross'  => round($priorGross, 2),
            'period_start' => $priorStart,
            'period_end'   => $priorEnd,
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
        $isAdmin = $user->inGroup('client_payroll_staff')
                || $user->inGroup('superadmin')
                || $user->inGroup('admin');

        if (!$isOwner && !$isAdmin) {
            return $this->response->setStatusCode(403)->setBody('Unauthorized');
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

        $password = $this->resolvePayslipPassword($employee);
        $dompdf->getCanvas()->get_cpdf()->setEncryption($password, 'owner-password', ['print']);
        $pdfOutput = $dompdf->output();

        $filename = 'payslip_' . ($payslip['period_start'] ?? 'unknown') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
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
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $payslips = $payslipModel
            ->select('employee_payslips.*, CONCAT(employees.first_name, " ", employees.last_name) as employee_name')
            ->join('employees', 'employees.id = employee_payslips.employee_id')
            ->orderBy('employee_payslips.created_at', 'DESC')
            ->findAll();

        return view('client_portal/payroll_payslip_history', ['payslips' => $payslips]);
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
        $isAdmin = $user->inGroup('client_payroll_staff') || $user->inGroup('superadmin') || $user->inGroup('admin');

        if (!$isOwner && !$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
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
                       + (float)($data['sss_loan']        ?? 0)
                       + (float)($data['calamity_loan']   ?? 0)
                       + (float)($data['salary_advance']  ?? 0)
                       + (float)($data['other_deductions'] ?? 0);

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

        $loanRowsHtml = '';

        foreach (($data['loan_deductions'] ?? []) as $loan) {

            $loanType = htmlspecialchars(
                $loan['loan_type'] ?? 'Loan'
            );

            $loanAmount = $fmt(
                $loan['amount'] ?? 0
            );

            $loanRowsHtml .= "
                <tr>
                    <td {$ic}>{$loanType}</td>
                    <td {$ir}>{$loanAmount}</td>
                </tr>
            ";
        }

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
        ['Salary Loan',    (float)($data['sss_loan']       ?? 0)],
        ['Calamity Loan',  (float)($data['calamity_loan']  ?? 0)],
        ['HDMF Loan',      (float)($data['hdmf_loan']      ?? 0)],
        ['Salary Advance', (float)($data['salary_advance'] ?? 0)],
        ['Cal. Advance',   (float)($data['cal_adv']        ?? 0)],
    ];
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

        foreach ($byEmployee as $empId => $agg) {
            // ── 13th Month ───────────────────────────────────────────────────
            $totalPureBasic = $agg['total_pure_basic'];
            $thirteenth     = round($totalPureBasic / 12, 2);
            $taxExempt      = round(min($thirteenth, $exemptLimit), 2);
            $taxable13th    = round(max(0, $thirteenth - $exemptLimit), 2);

            // ── Annualization ─────────────────────────────────────────────────
            // Taxable compensation = net_basic (includes ND) + OT + other taxable
            $totalTaxable  = $agg['total_net_basic']
                           + $agg['total_overtime']
                           + $agg['total_other_taxable'];
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
    public function getLoanBreakdown(
        int $employeeId,
        string $periodStart,
        string $periodEnd
    ) {

        try {

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

                ->where('el.loan_status', 'active')

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

            return $this->response->setJSON($breakdown);

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