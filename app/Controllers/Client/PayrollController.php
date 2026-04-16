<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\PayrollModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\PayrollSettingsModel;
use App\Models\Client\PayrollTransactionModel;
use App\Services\Client\PayrollComputationService;
use App\Models\AttendanceModel;
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
                employees.employee_number
            ")
            ->join('employees', 'employees.id = payroll.employee_id', 'left')
            ->where('payroll.month_year', $month)
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
            $employee = $employeeModel->find($id);
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

            $first = trim($employee['first_name'] ?? '');
            $last  = trim($employee['last_name'] ?? '');
            $mid   = trim($employee['middle_name'] ?? '');
            $mi    = $mid !== '' ? strtoupper(substr($mid, 0, 1)) . '.' : '';
            $fullName = trim($first . ' ' . ($mi !== '' ? $mi . ' ' : '') . $last);

            return $this->response->setJSON([
                'id'              => $employee['id'],
                'name'            => $fullName,
                'position'        => 'Employee',
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

    // ================= COMPUTE AND SAVE PAYROLL =================
    public function computeAndSave()
    {
        try {
            $employeeId = $this->request->getGet('employee_id');
            $start = $this->request->getGet('start_date');
            $end   = $this->request->getGet('end_date');
            $adjustments = $this->request->getJSON(true);

            if (!$employeeId || !$start || !$end) {
                throw new \Exception('Missing required parameters');
            }

            // Get employee and payroll data
            $employeeModel = new EmployeeModel();
            $employee = $employeeModel->find($employeeId);
            if (!$employee) throw new \Exception('Employee not found');

            $periodMonth = date('Y-m', strtotime($start));
            $payrollModel = new PayrollModel();
            $payrollRecord = $payrollModel
                ->where('employee_id', $employeeId)
                ->where('month_year', $periodMonth)
                ->first();

            if (!$payrollRecord || $payrollRecord['daily_rate'] <= 0) {
                throw new \Exception("No daily rate set for $periodMonth. Please create payroll entry first.");
            }

            $dailyRate = (float)$payrollRecord['daily_rate'];
            
            // Get attendance for the period
            $attendanceModel = new AttendanceModel();
            $attendance = $attendanceModel
                ->where('user_id', $employee['user_id'])
                ->where('date >=', $start)
                ->where('date <=', $end)
                ->findAll();

            // Calculate present days
            $presentDays = 0;
            $dates = [];
            foreach ($attendance as $rec) {
                if (!in_array($rec['date'], $dates) && !empty($rec['time_in'])) {
                    $dates[] = $rec['date'];
                    $presentDays++;
                }
            }
            
            $basicPay = $presentDays * $dailyRate;
            
            // Calculate overtime (simple: total_hours > 8)
            $otPay = 0;
            $settingsModel = new PayrollSettingsModel();
            $otRate = (float)$settingsModel->getSetting('ot_regular_rate') ?: 1.25;
            $monthlySalary = $dailyRate * 22; // approximate
            $hourlyRate = $monthlySalary / 160;
            
            foreach ($attendance as $day) {
                $totalHours = (float)($day['total_hours'] ?? 0);
                if ($totalHours > 8) {
                    $otHours = $totalHours - 8;
                    $otPay += $otHours * $hourlyRate * $otRate;
                }
            }
            
            // Simple deductions (SSS, PhilHealth, Pag-IBIG approximations)
            $sss = min($monthlySalary * 0.045, 1350);
            $phic = min($monthlySalary * 0.03, 1200);
            $hdmf = min($monthlySalary * 0.02, 100);
            
            // Withholding tax (simplified)
            $withholdingTax = 0;
            $taxableIncome = $basicPay + $otPay;
            if ($monthlySalary > 20833) $withholdingTax = ($monthlySalary - 20833) * 0.20 / 2;
            
            // Adjustments from frontend
            $deMinimis = $adjustments['de_minimis'] ?? 0;
            $otherTaxable = $adjustments['other_taxable'] ?? 0;
            $sssLoan = $adjustments['sss_loan'] ?? 0;
            $calamityLoan = $adjustments['calamity_loan'] ?? 0;
            $salaryAdvance = $adjustments['salary_advance'] ?? 0;
            $otherDeductions = $adjustments['other_deductions'] ?? 0;
            
            $totalEarnings = $basicPay + $otPay + $deMinimis + $otherTaxable;
            $totalDeductions = $sss + $phic + $hdmf + $withholdingTax + $sssLoan + $calamityLoan + $salaryAdvance + $otherDeductions;
            $netPay = $totalEarnings - $totalDeductions;
            
            // Save to transactions
            $txModel = new PayrollTransactionModel();
            $txData = [
                'employee_id' => $employeeId,
                'period_start' => $start,
                'period_end' => $end,
                'basic_pay' => round($basicPay, 2),
                'overtime_pay' => round($otPay, 2),
                'night_diff_pay' => 0,
                'holiday_pay' => 0,
                'de_minimis' => round($deMinimis, 2),
                'other_earnings_taxable' => round($otherTaxable, 2),
                'other_earnings_non_taxable' => 0,
                'total_earnings' => round($totalEarnings, 2),
                'sss_ee' => round($sss, 2),
                'phic_ee' => round($phic, 2),
                'hdmf_ee' => round($hdmf, 2),
                'withholding_tax' => round($withholdingTax, 2),
                'sss_loan' => round($sssLoan, 2),
                'calamity_loan' => round($calamityLoan, 2),
                'salary_advance' => round($salaryAdvance, 2),
                'other_deductions' => round($otherDeductions, 2),
                'total_deductions' => round($totalDeductions, 2),
                'net_pay' => round($netPay, 2),
                'status' => 'computed',
                'computed_at' => date('Y-m-d H:i:s')
            ];
            $txModel->insert($txData);
            $txId = $txModel->getInsertID();
            
            return $this->response->setJSON([
                'success' => true,
                'transaction_id' => $txId,
                'net_pay' => round($netPay, 2)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'computeAndSave: ' . $e->getMessage());
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
        }
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
        
        // Generate a simple HTML email with the payslip data
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
        $password = $employee['employee_number'] . date('Ymd', strtotime($employee['birthdate']));

        // Build HTML for PDF
        $html = $this->buildPayslipHtmlForPdf($data, $employee);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Courier');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Password = EMPNO + YYYYMMDD
        $password = $employee['employee_number'] . date('Ymd', strtotime($employee['birthdate']));

        // Apply PDF encryption
        $dompdf->getCanvas()
            ->get_cpdf()
            ->setEncryption($password, "owner-password", ['print']);

        $pdfOutput = $dompdf->output();

        $filename = "payslip_{$employee['employee_number']}_{$data['period_start']}.pdf";
        $tempPath = WRITEPATH . 'uploads/payslips/';
        if (!is_dir($tempPath)) mkdir($tempPath, 0777, true);
        file_put_contents($tempPath . $filename, $pdfOutput);

        $email = service('email');
        // ✅ Set mail type FIRST before anything else
        $email->setMailType('html');
        $email->setTo($emailAddress);
        $email->setSubject("Payslip for period {$data['period_start']} to {$data['period_end']}");
        $email->setMessage("
            Dear {$data['employee_name']},<br><br>
            Please find attached your payslip.<br><br>

            <strong>PDF Password:</strong><br>
            Your Employee Number + Birthdate (YYYYMMDD)<br>
            Example: EMP-XXXX-ZZZZYYYYMMDD<br><br>

            Regards,<br>
            Payroll Team
            ");
        $email->attach($tempPath . $filename);

        if ($email->send()) {
            unlink($tempPath . $filename);
            return $this->response->setJSON(['success' => true]);
        } else {
            // ✅ Capture the actual mailer error for debugging
            $debugInfo = $email->printDebugger(['headers', 'subject', 'body']);
            log_message('error', 'sendPdf email failed: ' . $debugInfo);
            throw new \Exception('Failed to send email. Check logs for details.');
        }
    } catch (\Exception $e) {
        log_message('error', 'sendPdf exception: ' . $e->getMessage());
        return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
    }
}

private function buildPayslipHtmlForPdf($data, $employee)
{
    $formatDate = function($date) {
        if (!$date) return '';
        return date('d-M-Y', strtotime($date));
    };
    
    $html = '
    <div style="font-family: Arial, sans-serif; border: 1px solid #000; padding: 20px; max-width: 900px; margin: auto;">
        <h2 style="text-align: center;">PAY SLIP</h2>
        <table style="width:100%; border-collapse: collapse; margin-bottom:10px;">
            <tr><td style="border:1px solid #000; padding:5px;"><strong>Employee:</strong></td><td style="border:1px solid #000; padding:5px;">' . $data['employee_name'] . '</td>
                <td style="border:1px solid #000; padding:5px;"></td>
                <td style="border:1px solid #000; padding:5px;"><strong>Payroll period:</strong></td>
                <td style="border:1px solid #000; padding:5px;">' . $formatDate($data['period_start']) . '</td>
                <td style="border:1px solid #000; padding:5px;">' . $formatDate($data['period_end']) . '</td></tr>
            <tr><td style="border:1px solid #000; padding:5px;"><strong>Position:</strong></td><td colspan="5" style="border:1px solid #000; padding:5px;">' . ($data['position'] ?? 'N/A') . '</td></tr>
        </table>
        
        <table style="width:100%; border-collapse: collapse; margin-top:10px;">
            <tr><th style="border:1px solid #000; background:#eee;">REGULAR EARNINGS</th><th style="border:1px solid #000; background:#eee;">TAXABLE OTHER EARNINGS</th><th style="border:1px solid #000; background:#eee;">NON-TAXABLE OTHER EARNINGS</th></tr>
            <tr>
                <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                    <table style="width:100%;">' .
                        '<tr><td>Basic pay:</td><td style="text-align:right;">' . number_format($data['basic_pay'], 2) . '</td></tr>' .
                        '<tr><td>Night premium:</td><td style="text-align:right;">' . number_format($data['night_diff_pay'], 2) . '</td></tr>' .
                        '<tr><td><strong>Net basic pay</strong></td><td style="text-align:right;"><strong>' . number_format($data['basic_pay'] + $data['night_diff_pay'], 2) . '</strong></td></tr>' .
                        '<tr><td>Reg. OT:</td><td style="text-align:right;">' . number_format($data['overtime_pay'], 2) . '</td></tr>' .
                    '</table>
                </td>
                <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                    <table style="width:100%;">' .
                        '<tr><td>Taxable Other:</td><td style="text-align:right;">' . number_format($data['other_earnings_taxable'], 2) . '</td></tr>' .
                    '</table>
                </td>
                <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                    <table style="width:100%;">' .
                        '<tr><td>De Minimis:</td><td style="text-align:right;">' . number_format($data['de_minimis'], 2) . '</td></tr>' .
                    '</table>
                </td>
            </tr>
            <tr><td colspan="2" style="text-align:right;"><strong>GROSS PAY</strong></td><td style="text-align:right;"><strong>' . number_format($data['basic_pay'] + $data['night_diff_pay'] + $data['overtime_pay'] + $data['other_earnings_taxable'] + $data['de_minimis'], 2) . '</strong></td></tr>
        </table>
        
        <table style="width:100%; border-collapse: collapse; margin-top:10px;">
            <tr><th style="border:1px solid #000; background:#eee;">REGULAR DEDUCTIONS</th><th style="border:1px solid #000; background:#eee;">LOANS/ADVANCES</th></tr>
            <tr>
                <td style="border:1px solid #000; padding:5px;">
                    <table style="width:100%;">' .
                        '<tr><td>SSS</td><td style="text-align:right;">' . number_format($data['sss_ee'], 2) . '</td></tr>' .
                        '<tr><td>Philhealth</td><td style="text-align:right;">' . number_format($data['phic_ee'], 2) . '</td></tr>' .
                        '<tr><td>Pag-IBIG</td><td style="text-align:right;">' . number_format($data['hdmf_ee'], 2) . '</td></tr>' .
                        '<tr><td>Withholding Tax</td><td style="text-align:right;">' . number_format($data['withholding_tax'], 2) . '</td></tr>' .
                    '</table>
                </td>
                <td style="border:1px solid #000; padding:5px;">
                    <table style="width:100%;">' .
                        '<tr><td>SSS Loan</td><td style="text-align:right;">' . number_format($data['sss_loan'], 2) . '</td></tr>' .
                        '<tr><td>Calamity Loan</td><td style="text-align:right;">' . number_format($data['calamity_loan'], 2) . '</td></tr>' .
                        '<tr><td>Salary Advance</td><td style="text-align:right;">' . number_format($data['salary_advance'], 2) . '</td></tr>' .
                        '<tr><td>Other Deductions</td><td style="text-align:right;">' . number_format($data['other_deductions'], 2) . '</td></tr>' .
                    '</table>
                </td>
            </tr>
            <tr><td colspan="2" style="text-align:right;"><strong>TOTAL DEDUCTIONS</strong></td><td style="text-align:right;"><strong>' . number_format($data['sss_ee'] + $data['phic_ee'] + $data['hdmf_ee'] + $data['withholding_tax'] + $data['sss_loan'] + $data['calamity_loan'] + $data['salary_advance'] + $data['other_deductions'], 2) . '</strong></td></tr>
        </table>
        
        <table style="width:100%; border-collapse: collapse; margin-top:10px;">
            <tr><td style="border:1px solid #000; padding:5px;">Employee signature:</td><td style="border:1px solid #000; padding:5px;"></td>
                <td style="text-align:right;"><strong>NET PAY:</strong></td>
                <td style="background:#f8d7da; text-align:right;"><strong>' . number_format($data['net_pay'], 2) . '</strong></td></tr>
        </table>
    </div>';
    
    return $html;
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

}