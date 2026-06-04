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
use Dompdf\Dompdf;
use Dompdf\Options;

class PayrollController extends BaseController
{
    protected $helpers = ['url', 'form'];

    // ================= PAYROLL STATUS TAB =================
    public function payrollView()
    {
        $payrollModel = new PayrollModel();
        $employeeModel = new EmployeeModel();

        $month = $this->request->getGet('month') ?? date('Y-m');
        $data['selected_month'] = $month;

        $data['payrolls'] = $payrollModel
            ->select("
                payroll.*,
                CONCAT(
                    TRIM(employees.first_name), ' ',
                    IF(
                        COALESCE(TRIM(employees.middle_name), '') = '',
                        '',
                        CONCAT(UPPER(LEFT(TRIM(employees.middle_name), 1)), '. ')
                    ),
                    TRIM(employees.last_name)
                ) AS employee_name,
                employees.employee_number,
                MAX(ea.photo_path) as photo_path
            ", false)
            ->join('employees', 'employees.id = payroll.employee_id', 'left')
            ->join('employee_accounts as ea', 'employees.id = ea.employee_id', 'left')
            ->where('payroll.month_year', $month)
            ->groupBy('payroll.id')
            ->findAll();

        $data['employees'] = $employeeModel
            ->select("
                employees.id,
                CONCAT(
                    TRIM(employees.first_name), ' ',
                    IF(
                        COALESCE(TRIM(employees.middle_name), '') = '',
                        '',
                        CONCAT(UPPER(LEFT(TRIM(employees.middle_name), 1)), '. ')
                    ),
                    TRIM(employees.last_name)
                ) AS full_name
            ")
            ->join('users', 'users.id = employees.user_id', 'left')
            ->where('users.active', 1)
            ->findAll();

        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $data['payslips'] = $payslipModel
            ->select("employee_payslips.*, CONCAT(employees.first_name, ' ', employees.last_name) as employee_name")
            ->join('employees', 'employees.id = employee_payslips.employee_id')
            ->orderBy('employee_payslips.created_at', 'DESC')
            ->findAll();

        // convert photo_path to absolute URL where available
        foreach ($data['payrolls'] as &$pr) {
            if (! empty($pr['photo_path'])) {
                $pr['photo_path'] = base_url((string) $pr['photo_path']);
            }
        }
        unset($pr);

        return view('client_portal/payroll_main', $data);
    }

    public function createPayroll()
    {
        $model = new PayrollModel();
        try {
            $model->save($this->request->getPost());
            return redirect()->back()->with('success', 'Payroll record created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function updatePayroll($id)
    {
        $model = new PayrollModel();
        try {
            $model->update($id, $this->request->getPost());
            return redirect()->back()->with('success', 'Payroll record updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function deletePayroll($id)
    {
        $model = new PayrollModel();
        try {
            $model->delete($id);
            return redirect()->back()->with('success', 'Payroll record deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
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

            $payrollModel = new PayrollModel();
            $payroll = $payrollModel
                ->where('employee_id', $id)
                ->where('month_year', $month)
                ->first();

            if (!$payroll || $payroll['daily_rate'] <= 0) {
                return $this->response->setStatusCode(404)->setJSON([
                    'error' => "No payroll record or daily rate not set for $month. Please create a payroll entry first in Payroll Status tab."
                ]);
            }

            $dailyRate = (float)$payroll['daily_rate'];
            $workingDays = $this->getWorkingDaysInMonth($month);
            $monthlySalary = $dailyRate * $workingDays;
            $hourlyRate = $monthlySalary / 160;

            // Get position from employee_positions table
            $position = 'Employee';
            $db = \Config\Database::connect();
            $posRow = $db->table('employee_positions')->select('position')->where('employee_id', $id)->get()->getRowArray();
            if ($posRow) $position = $posRow['position'];

            $first = trim($employee['first_name'] ?? '');
            $last  = trim($employee['last_name'] ?? '');
            $mid   = trim($employee['middle_name'] ?? '');
            $mi    = $mid !== '' ? strtoupper(substr($mid, 0, 1)) . '.' : '';
            $fullName = trim($first . ' ' . ($mi !== '' ? $mi . ' ' : '') . $last);

            return $this->response->setJSON([
                'id'              => $employee['id'],
                'name'            => $fullName,
                'position'        => $position,
                'monthly_salary'  => round($monthlySalary, 2),
                'daily_rate'      => round($dailyRate, 2),
                'hourly_rate'     => round($hourlyRate, 2),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'getEmployeeDetails: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Server error: ' . $e->getMessage()]);
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
        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->find($employeeId);
        if (!$employee) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Employee not found']);
        }

        $userId = $employee['user_id'];
        $attendanceModel = new AttendanceModel();
        $attendances = $attendanceModel
            ->where('user_id', $userId)
            ->where('DATE(time_in) >=', $startDate)
            ->where('DATE(time_in) <=', $endDate)
            ->findAll();

        $summary = $this->aggregateAttendance($attendances, $startDate, $endDate);

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
            if ($req['is_paid_leave']) {
                $paidLeaveDays   += (float) $req['duration'];
            } else {
                $unpaidLeaveDays += (float) $req['duration'];
            }
        }

        // Paid leaves reduce the absent count; unpaid leaves are already captured as absent
        $summary['paid_leave_days']   = $paidLeaveDays;
        $summary['unpaid_leave_days'] = $unpaidLeaveDays;
        $summary['absent_days']       = max(0, $summary['absent_days'] - $paidLeaveDays);

        return $this->response->setJSON($summary);
    }

    private function aggregateAttendance(array $attendances, string $startDate, string $endDate): array
    {
        $totalWorkingDays = $this->getWorkingDaysCount($startDate, $endDate);
        $workedDays = 0;
        $regularHours = 0;
        $nightDiffHours = 0;
        $tardyMinutes = 0;
        $regOtHours = 0;
        $restOtHours = 0;
        $holidayOtHours = 0;

        foreach ($attendances as $att) {
            if (empty($att['time_in']) || empty($att['time_out'])) continue;

            // Derive date and time-only from the datetime column
            $attDate  = date('Y-m-d', strtotime($att['time_in']));
            $timeOnly = date('H:i:s', strtotime($att['time_in']));

            $workedDays++;
            $totalHours = (float)($att['total_hours'] ?? 0);
            // Regular hours = min(8, total hours)
            $regularHours += min($totalHours, 8);
            $nightDiffHours += (float)($att['night_diff_hours'] ?? 0);

            // Tardy minutes (scheduled start 08:00)
            $scheduledStart = '08:00:00';
            if ($timeOnly > $scheduledStart) {
                $tardyMinutes += round((strtotime($timeOnly) - strtotime($scheduledStart)) / 60);
            }

            // Overtime: any hours beyond 8 per day
            $ot = max(0, $totalHours - 8);
            $isRestDay = (date('N', strtotime($attDate)) == 7); // Sunday
            $isHoliday = false; // implement holiday check if needed

            if ($isHoliday) {
                $holidayOtHours += $ot;
            } elseif ($isRestDay) {
                $restOtHours += $ot;
            } else {
                $regOtHours += $ot;
            }
        }

        return [
            'worked_days'      => $workedDays,
            'absent_days'      => max(0, $totalWorkingDays - $workedDays),
            'regular_hours'    => round($regularHours, 2),
            'night_diff_hours' => round($nightDiffHours, 2),
            'tardy_minutes'    => (int)$tardyMinutes,
            'reg_ot_hours'     => round($regOtHours, 2),
            'rest_ot_hours'    => round($restOtHours, 2),
            'holiday_ot_hours' => round($holidayOtHours, 2),
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
                throw new \Exception('Missing required parameters');
            }

            $service = new PayrollComputationService();
            $computed = $service->computePayroll($employeeId, $start, $end, $adjustments);

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
                'other_earnings_taxable' => (float) ($computed['other_earnings_taxable'] ?? 0),
                'other_earnings_non_taxable' => (float) ($computed['other_earnings_non_taxable'] ?? 0),
                'total_earnings' => (float) ($computed['total_earnings'] ?? 0),
                'sss_ee' => (float) ($computed['sss_ee'] ?? 0),
                'phic_ee' => (float) ($computed['phic_ee'] ?? 0),
                'hdmf_ee' => (float) ($computed['hdmf_ee'] ?? 0),
                'withholding_tax' => (float) ($computed['withholding_tax'] ?? 0),
                'sss_loan' => (float) ($computed['sss_loan'] ?? 0),
                'calamity_loan' => (float) ($computed['calamity_loan'] ?? 0),
                'salary_advance' => (float) ($computed['salary_advance'] ?? 0),
                'other_deductions' => (float) ($computed['other_deductions'] ?? 0),
                'total_deductions' => (float) ($computed['total_deductions'] ?? 0),
                'net_pay' => (float) ($computed['net_pay'] ?? 0),
                'status' => 'computed',
                'computed_at' => date('Y-m-d H:i:s')
            ];

            $txModel->insert($cleanTxData);
            $txId = $txModel->getInsertID();

            return $this->response->setJSON([
                'success' => true,
                'transaction_id' => $txId,
                'computed' => $computed
            ]);
        } catch (\Exception $e) {
            log_message('error', 'computeAndSave: ' . $e->getMessage());
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
        }
    }

    // ================= EXPORT / IMPORT =================
    public function exportRegister()
    {
        $month = $this->request->getGet('month') ?? date('Y-m');
        $payrollModel = new PayrollModel();
        $records = $payrollModel
            ->select("payroll.*, CONCAT(employees.first_name,' ',employees.last_name) as employee_name")
            ->join('employees', 'employees.id = payroll.employee_id')
            ->where('month_year', $month)
            ->findAll();

        $filename = "payroll_register_{$month}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Employee', 'Phone', 'Daily Rate', 'Currency', 'Status']);
        foreach ($records as $row) {
            fputcsv($output, [
                $row['employee_name'], $row['phone'], $row['daily_rate'], $row['currency'], $row['status']
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
            ->select('DATE(time_in) as date, time_in, time_out, break_duration, total_hours')
            ->where('user_id', $employee['user_id'])
            ->where('DATE(time_in) >=', $startDate)
            ->where('DATE(time_in) <=', $endDate)
            ->orderBy('time_in', 'ASC')
            ->findAll();

        $filename = "timesheet_{$employee['employee_number']}_{$startDate}_to_{$endDate}.csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['employee_number', 'date', 'time_in', 'time_out', 'break_duration', 'total_hours']);
        foreach ($records as $row) {
            fputcsv($output, [
                $employee['employee_number'],
                $row['date'],
                $row['time_in'],
                $row['time_out'],
                $row['break_duration'],
                $row['total_hours']
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
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $employeeModel = new EmployeeModel();
            $employee = $employeeModel->where('employee_number', $data['employee_number'])->first();
            if (!$employee) continue;
            $attendanceModel = new AttendanceModel();
            $breakDur   = isset($data['break_duration']) ? (float)$data['break_duration'] : 0;
            $timeInDt   = $data['date'] . ' ' . $data['time_in'];
            $timeOutDt  = ! empty($data['time_out']) ? $data['date'] . ' ' . $data['time_out'] : null;
            $totalHours = $this->calculateTotalHours($timeInDt, $timeOutDt, $breakDur);
            $attendanceModel->insert([
                'user_id'        => $employee['user_id'],
                'time_in'        => $timeInDt,
                'time_out'       => $timeOutDt,
                'break_duration' => $breakDur,
                'total_hours'    => $totalHours,
            ]);
            $successCount++;
        }
        fclose($handle);
        return $this->response->setJSON(['success' => true, 'imported' => $successCount]);
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

    // ================= GENERATE PDF PAYSLIP =================
    public function generatePayslip($transactionId)
    {
        try {
            $txModel = new PayrollTransactionModel();
            $payroll = $txModel->find($transactionId);
            if (!$payroll) throw new \Exception('Payroll transaction not found');

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
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function sendPayslip($transactionId)
    {
        try {
            $txModel = new PayrollTransactionModel();
            $payroll = $txModel->find($transactionId);
            if (!$payroll) throw new \Exception('Transaction not found');

            $employeeModel = new EmployeeModel();
            $employee = $employeeModel->find($payroll['employee_id']);
            $emailAddress = $this->getUserEmail($employee['user_id']);

            if (!$emailAddress) {
                throw new \Exception('Employee email not found');
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
                throw new \Exception('Failed to send email');
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
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
            'ot_regular_rate',  'ot_rest_rate',   'ot_holiday_rate',
            'phic_rate',        'phic_cap',
            'hdmf_rate',        'hdmf_max',
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
            if (!$employee) throw new \Exception('Employee not found');
            
            $emailAddress = $this->getUserEmail($employee['user_id']);

            if (!$emailAddress) {
                throw new \Exception('Employee email not found');
            }
            
            $html = view('client_portal/email_payslip', ['data' => $data, 'employee' => $employee]);
            
            $email = service('email');
            $email->setTo($emailAddress);
            $email->setSubject("Payslip for period {$data['period_start']} to {$data['period_end']}");
            $email->setMessage($html);
            
            if ($email->send()) {
                return $this->response->setJSON(['success' => true]);
            } else {
                throw new \Exception('Failed to send email');
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
        }
    }

    public function sendPdf()
    {
        try {
            $data = $this->request->getJSON(true);
            $employeeId = $data['employee_id'];

            $employeeModel = new \App\Models\Client\EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) throw new \Exception('Employee not found');

            $emailAddress = $this->getUserEmail($employee['user_id']);
            if (!$emailAddress) {
                throw new \Exception('Employee email not found');
            }

            $html = $this->buildPayslipHtmlForPdf($data, $employee);

            $options = new \Dompdf\Options();
            $options->set('defaultFont', 'Courier');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $password = $this->resolvePayslipPassword($employee);
            $dompdf->getCanvas()->get_cpdf()->setEncryption($password, "owner-password", ['print']);
            $pdfOutput = $dompdf->output();

            $permanentDir = WRITEPATH . 'uploads/payslips/permanent/';
            if (!is_dir($permanentDir)) mkdir($permanentDir, 0777, true);
            $filename = "payslip_{$employee['employee_number']}_{$data['period_start']}.pdf";
            $permanentPath = $permanentDir . $filename;
            file_put_contents($permanentPath, $pdfOutput);

            $payslipModel = new \App\Models\Client\EmployeePayslipModel();

            $existing = $payslipModel
                ->where('employee_id', $employeeId)
                ->where('period_start', $data['period_start'])
                ->where('period_end', $data['period_end'])
                ->where('net_pay', round((float)$data['net_pay'], 2))
                ->first();

            if ($existing) {
                $payslipModel->update($existing['id'], [
                    'file_path'      => $permanentPath,
                    'generated_data' => json_encode($data),
                ]);
                $payslipRecordId = $existing['id'];
            } else {
                $payslipModel->insert([
                    'employee_id'    => $employeeId,
                    'period_start'   => $data['period_start'],
                    'period_end'     => $data['period_end'],
                    'net_pay'        => round((float)$data['net_pay'], 2),
                    'file_path'      => $permanentPath,
                    'generated_data' => json_encode($data),
                    'sent_at'        => null,
                ]);
                $inserted = $payslipModel
                    ->where('employee_id', $employeeId)
                    ->where('period_start', $data['period_start'])
                    ->where('period_end', $data['period_end'])
                    ->where('net_pay', round((float)$data['net_pay'], 2))
                    ->first();
                $payslipRecordId = $inserted['id'] ?? null;
            }

            if (!$payslipRecordId) {
                throw new \Exception('Could not resolve payslip record ID after save.');
            }

            $tempPath = WRITEPATH . 'uploads/payslips/temp/';
            if (!is_dir($tempPath)) mkdir($tempPath, 0777, true);
            $tempFile = $tempPath . $filename;
            file_put_contents($tempFile, $pdfOutput);

            $email = service('email');
            $email->setMailType('html');
            $email->setTo($emailAddress);
            $email->setSubject("Payslip for period {$data['period_start']} to {$data['period_end']}");
            $email->setMessage("
                Dear {$data['employee_name']},<br><br>
                Please find attached your payslip. It is password-protected.<br><br>
                <strong>PDF Password:</strong> Use the payslip password set in your account profile.
                If you have not changed it, the default is your Employee Number + Birthdate (YYYYMMDD).<br><br>
                Regards,<br>
                Payroll Team
            ");
            $email->attach($tempFile);

            if ($email->send()) {
                unlink($tempFile);
                \Config\Database::connect()
                    ->table('employee_payslips')
                    ->where('id', $payslipRecordId)
                    ->update(['sent_at' => date('Y-m-d H:i:s')]);

                service('activityLogger')->payslipSent(
                    $payslipRecordId,
                    $data['employee_name'] ?? trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')),
                    $data['period_start'],
                    $data['period_end'],
                    $emailAddress
                );

                return $this->response->setJSON(['success' => true]);
            } else {
                $debugInfo = $email->printDebugger(['headers', 'subject', 'body']);
                log_message('error', 'sendPdf email failed: ' . $debugInfo);
                throw new \Exception('Failed to send email. Check logs for details.');
            }
        } catch (\Exception $e) {
            log_message('error', 'sendPdf exception: ' . $e->getMessage());
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
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
                throw new \Exception('Missing required fields.');
            }

            $employeeModel = new \App\Models\Client\EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) throw new \Exception('Employee not found');

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
            $filename     = "payslip_{$employee['employee_number']}_{$periodStart}.pdf";
            $permanentPath = $permanentDir . $filename;
            file_put_contents($permanentPath, $pdfOutput);

            $payslipModel = new \App\Models\Client\EmployeePayslipModel();

            $existing = $payslipModel
                ->where('employee_id', $employeeId)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->where('net_pay', round((float)$netPay, 2))
                ->first();

            if ($existing) {
                $payslipModel->update($existing['id'], [
                    'file_path'      => $permanentPath,
                    'generated_data' => json_encode($data),
                ]);
                $recordId = $existing['id'];
            } else {
                $payslipModel->insert([
                    'employee_id'    => $employeeId,
                    'period_start'   => $periodStart,
                    'period_end'     => $periodEnd,
                    'net_pay'        => round((float)$netPay, 2),
                    'file_path'      => $permanentPath,
                    'generated_data' => json_encode($data),
                    'sent_at'        => null,
                ]);
                $inserted = $payslipModel
                    ->where('employee_id', $employeeId)
                    ->where('period_start', $periodStart)
                    ->where('period_end', $periodEnd)
                    ->where('net_pay', round((float)$netPay, 2))
                    ->first();
                $recordId = $inserted['id'] ?? null;
            }

            if (!$recordId) {
                throw new \Exception('Failed to resolve payslip record after save.');
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
            log_message('error', 'savePayslipRecord: ' . $e->getMessage());
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
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
            throw new \Exception('Payslip not found');
        }

        $user = auth()->user();
        $employeeModel = new \App\Models\Client\EmployeeModel();
        $employee = (array) $employeeModel->find($payslip['employee_id']);
        $isOwner = ($employee && $employee['user_id'] == $user->id);
        $isAdmin = $user->inGroup('client_payroll_staff');

        if (!$isOwner && !$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $filePath = $payslip['file_path'];
        if (! file_exists((string) $filePath)) {
            throw new \Exception('File not found');
        }

        return $this->response->download((string) $filePath, null);
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
        $isOwner = ($employee && $employee['user_id'] == $user->id);
        $isAdmin = $user->inGroup('client_payroll_staff');
        
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

        $grossPay = ($data['basic_pay'] ?? 0)
                + ($data['night_diff_pay'] ?? 0)
                + ($data['overtime_pay'] ?? 0)
                + ($data['other_earnings_taxable'] ?? 0)
                + ($data['de_minimis'] ?? 0);

        $totalDeductions = ($data['sss_ee'] ?? 0)
                        + ($data['phic_ee'] ?? 0)
                        + ($data['hdmf_ee'] ?? 0)
                        + ($data['withholding_tax'] ?? 0)
                        + ($data['sss_loan'] ?? 0)
                        + ($data['calamity_loan'] ?? 0)
                        + ($data['salary_advance'] ?? 0)
                        + ($data['other_deductions'] ?? 0);

        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    .wrap { border: 1px solid #000; padding: 20px; max-width: 900px; margin: auto; }
    h2 { text-align: center; margin: 0 0 10px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    td, th { border: 1px solid #000; padding: 5px; }
    th { background: #eee; }
    .inner-table { width: 100%; border-collapse: collapse; }
    .inner-table td { border: none; padding: 2px 4px; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .highlight { background: #f8d7da; }
</style>
</head>
<body>
<div class="wrap">
    <h2>PAY SLIP</h2>
    <tr>
        <tr>
            <td><strong>Employee:</strong></td>
            <td>' . htmlspecialchars($data['employee_name'] ?? '') . '</td>
            <td><strong>Period Start:</strong></td>
            <td>' . $fmtDate($data['period_start'] ?? '') . '</td>
            <td><strong>Period End:</strong></td>
            <td>' . $fmtDate($data['period_end'] ?? '') . '</td>
        </tr>
        <tr>
            <td><strong>Position:</strong></td>
            <td colspan="5">' . htmlspecialchars($data['position'] ?? 'N/A') . '</td>
        </tr>
    </table>
    <table>
        <thead><tr><th>REGULAR EARNINGS</th><th>TAXABLE OTHER EARNINGS</th><th>NON-TAXABLE OTHER EARNINGS</th></tr></thead>
        <tbody>
            <tr>
                <td>
                    <table class="inner-table">
                        <tr><td>Basic Pay:</td><td class="right">' . $fmt($data['basic_pay']) . '</td></tr>
                        <tr><td class="bold">Night Premium:</td><td class="right">' . $fmt($data['night_diff_pay']) . '</td></tr>
                        <tr><td class="bold">Net Basic Pay:</td><td class="right bold">' . $fmt(($data['basic_pay'] ?? 0) + ($data['night_diff_pay'] ?? 0)) . '</strong></td></tr>
                        <tr><td class="bold">Reg. OT:</td><td class="right">' . $fmt($data['overtime_pay']) . '</strong></td></tr>
                    </table>
                </td>
                <td>
                    <table class="inner-table">
                        <tr><td class="bold">Taxable Other:</td><td class="right">' . $fmt($data['other_earnings_taxable']) . '</strong></td></tr>
                    </table>
                </td>
                <td>
                    <table class="inner-table">
                        <tr><td class="bold">De Minimis:</td><td class="right">' . $fmt($data['de_minimis']) . '</strong></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="right"><strong>GROSS PAY</strong></td>
                <td class="right"><strong>' . $fmt($grossPay) . '</strong></td>
            </tr>
        </tbody>
    </table>
    </table>
        <thead><tr><th>REGULAR DEDUCTIONS</th><th>LOANS / ADVANCES</th></tr></thead>
        <tbody>
            <tr>
                <td>
                    <table class="inner-table">
                        <tr><td class="bold">SSS:</td><td class="right">' . $fmt($data['sss_ee']) . '</strong></td></tr>
                        <tr><td class="bold">Philhealth:</td><td class="right">' . $fmt($data['phic_ee']) . '</strong></td></tr>
                        <tr><td class="bold">Pag-IBIG:</td><td class="right">' . $fmt($data['hdmf_ee']) . '</strong></td></tr>
                        <tr><td class="bold">Withholding Tax:</td><td class="right">' . $fmt($data['withholding_tax']) . '</strong></td></tr>
                    </table>
                </td>
                <td>
                    <table class="inner-table">
                        <tr><td class="bold">SSS Loan:</td><td class="right">' . $fmt($data['sss_loan']) . '</strong></td></tr>
                        <tr><td class="bold">Calamity Loan:</td><td class="right">' . $fmt($data['calamity_loan']) . '</strong></td></tr>
                        <tr><td class="bold">Salary Advance:</td><td class="right">' . $fmt($data['salary_advance']) . '</strong></td></tr>
                        <tr><td class="bold">Other Deductions:</td><td class="right">' . $fmt($data['other_deductions']) . '</strong></td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="right"><strong>TOTAL DEDUCTIONS</strong></td>
                <td class="right"><strong>' . $fmt($totalDeductions) . '</strong></td>
            </tr>
        </tbody>
    </table>
    <table>
        <tr>
            <td style="width:50%"><strong>Employee Signature:</strong></td>
            <td style="width:50%; height: 40px;">&nbsp;</td>
        </tr>
        <tr>
            <td class="right"><strong>NET PAY:</strong></td>
            <td class="right highlight"><strong>' . $fmt($data['net_pay']) . '</strong></td>
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
}