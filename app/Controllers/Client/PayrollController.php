<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\PayrollModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\PayrollSettingsModel;
use App\Models\Client\PayrollTransactionModel;
use App\Services\Client\PayrollComputationService;
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

    // ================= COMPUTE AND SAVE PAYROLL (FULLY AUTOMATIC) =================
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
            $txData = [
                'employee_id' => $employeeId,
                'period_start' => $start,
                'period_end' => $end,
                'basic_pay' => $computed['basic_pay'],
                'overtime_pay' => $computed['overtime_pay'],
                'night_diff_pay' => $computed['night_diff_pay'],
                'holiday_pay' => $computed['holiday_pay'],
                'de_minimis' => $computed['de_minimis'],
                'other_earnings_taxable' => $computed['other_earnings_taxable'],
                'other_earnings_non_taxable' => $computed['other_earnings_non_taxable'],
                'total_earnings' => $computed['total_earnings'],
                'sss_ee' => $computed['sss_ee'],
                'phic_ee' => $computed['phic_ee'],
                'hdmf_ee' => $computed['hdmf_ee'],
                'withholding_tax' => $computed['withholding_tax'],
                'sss_loan' => $computed['sss_loan'],
                'calamity_loan' => $computed['calamity_loan'],
                'salary_advance' => $computed['salary_advance'],
                'other_deductions' => $computed['other_deductions'],
                'total_deductions' => $computed['total_deductions'],
                'net_pay' => $computed['net_pay'],
                'status' => 'computed',
                'computed_at' => date('Y-m-d H:i:s')
            ];
            $txModel->insert($txData);
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

    // ================= EXPORT PAYROLL REGISTER TO CSV =================
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

    // ================= EXPORT TIMESHEET (RAW SHEET) =================
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
            ->select('date, time_in, time_out, break_duration, total_hours')
            ->where('user_id', $employee['user_id'])
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->orderBy('date', 'ASC')
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

    // ================= IMPORT TIMESHEET (CSV) =================
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
            $breakDur = isset($data['break_duration']) ? (float)$data['break_duration'] : 0;
            $totalHours = $this->calculateTotalHours($data['time_in'], $data['time_out'], $breakDur);
            $attendanceModel->insert([
                'user_id' => $employee['user_id'],
                'date' => $data['date'],
                'time_in' => $data['time_in'],
                'time_out' => $data['time_out'] ?? null,
                'break_duration' => $breakDur,
                'total_hours' => $totalHours
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
                return $this->response->setJSON(['success' => true, 'message' => 'Payslip sent to ' . $user['email']]);
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
        $attendanceModel = new AttendanceModel();
        $data = $attendanceModel
            ->select("date, time_in, time_out, break_duration, total_hours")
            ->where('user_id', function($builder) use ($employeeId) {
                $builder->select('user_id')->from('employees')->where('id', $employeeId);
            })
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->orderBy('date', 'ASC')
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
        foreach ($data as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $realKey = substr($key, 8);
                $settingsModel->setSetting($realKey, $value);
            }
        }
        return redirect()->back()->with('success', 'Settings saved.');
    }

    public function getSettings()
    {
        $settingsModel = new PayrollSettingsModel();
        $keys = ['monthly_hours', 'night_diff_start', 'night_diff_end', 'night_diff_rate', 
                 'ot_regular_rate', 'ot_rest_rate', 'ot_holiday_rate', 'sss_table', 'phic_rate', 'hdmf_rate'];
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

            // Reuse the record created by savePayslipRecord if it exists
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
                // Re-query to get the true inserted ID — avoids CI4 returning 0 on some drivers
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
                // Use raw DB builder to stamp sent_at — bypasses model $allowedFields whitelist
                \Config\Database::connect()
                    ->table('employee_payslips')
                    ->where('id', $payslipRecordId)
                    ->update(['sent_at' => date('Y-m-d H:i:s')]);

                // Activity log — payslip sent
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

    // ================= SAVE PAYSLIP RECORD (on Generate, before Send) =================
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

            // Build the PDF so it can be stored on disk immediately
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

            // Upsert: if a record with same employee + period + net_pay already exists, skip insert
            $existing = $payslipModel
                ->where('employee_id', $employeeId)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->where('net_pay', round((float)$netPay, 2))
                ->first();

            if ($existing) {
                // Update the stored PDF and data in case something changed, but keep sent_at intact
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
                // Re-query to get real ID — avoids CI4 getInsertID() returning 0 on some drivers
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

            // Activity log — payslip generated
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

    // ================= PAYSLIP HISTORY METHODS =================
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

    // JSON endpoint used by the Payslip History tab in payroll_management
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
        <td>
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
                            <tr><td>Basic Pay:</td><td class="right">' . $fmt($data['basic_pay']) . 'NonNull</td></tr>
                            <tr><td>Night Premium:NonNull<td class="right">' . $fmt($data['night_diff_pay']) . 'NonNull</td></tr>
                            <tr><td class="bold">Net Basic Pay:NonNull<td class="right bold">' . $fmt(($data['basic_pay'] ?? 0) + ($data['night_diff_pay'] ?? 0)) . 'NonNull</td></tr>
                            <tr><td class="bold">Reg. OT:NonNull<td class="right">' . $fmt($data['overtime_pay']) . 'NonNull</td></tr>
                        </table>
                    </td>
                    <td><table class="inner-table"><tr><td class="bold">Taxable Other:NonNull<td class="right">' . $fmt($data['other_earnings_taxable']) . 'NonNull</td></tr></table></td>
                    <td><table class="inner-table"><tr><td class="bold">De Minimis:NonNull<td class="right">' . $fmt($data['de_minimis']) . 'NonNull</td></tr></table></td>
                </tr>
                <tr><td colspan="2" class="right"><strong>GROSS PAY</strong>NonNull<td class="right"><strong>' . $fmt($grossPay) . '</strong>NonNull</tr>
            </tbody>
        </table>
        <table>
            <thead><tr><th>REGULAR DEDUCTIONS</th><th>LOANS / ADVANCES</th></tr></thead>
            <tbody>
                <tr>
                    <td>
                        <table class="inner-table">
                            <tr><td class="bold">SSS:NonNull<td class="right">' . $fmt($data['sss_ee']) . 'NonNull</tr>
                            <tr><td class="bold">Philhealth:NonNull<td class="right">' . $fmt($data['phic_ee']) . 'NonNull</tr>
                            <tr><td class="bold">Pag-IBIG:NonNull<td class="right">' . $fmt($data['hdmf_ee']) . 'NonNull</tr>
                            <tr><td class="bold">Withholding Tax:NonNull<td class="right">' . $fmt($data['withholding_tax']) . 'NonNull</tr>
                        </table>
                    </td>
                    <td>
                        <table class="inner-table">
                            <tr><td class="bold">SSS Loan:NonNull<td class="right">' . $fmt($data['sss_loan']) . 'NonNull</tr>
                            <tr><td class="bold">Calamity Loan:NonNull<td class="right">' . $fmt($data['calamity_loan']) . 'NonNull</tr>
                            <tr><td class="bold">Salary Advance:NonNull<td class="right">' . $fmt($data['salary_advance']) . 'NonNull</tr>
                            <tr><td class="bold">Other Deductions:NonNull<td class="right">' . $fmt($data['other_deductions']) . 'NonNull</tr>
                        </table>
                    </td>
                </tr>
                <tr><td class="right"><strong>TOTAL DEDUCTIONS</strong>NonNull<td class="right"><strong>' . $fmt($totalDeductions) . '</strong>NonNull</tr>
            </tbody>
        </table>
        <tr>
            <td style="width:50%"><strong>Employee Signature:</strong>NonNull
            <td style="width:50%">&nbsp;NonNull
        </tr>
        <tr>
            <td class="right"><strong>NET PAY:</strong>NonNull
            <td class="right highlight"><strong>' . $fmt($data['net_pay']) . '</strong>NonNull
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
}