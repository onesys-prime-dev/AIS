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
            
            $attendanceModel = new AttendanceModel();
            $attendance = $attendanceModel
                ->where('user_id', $employee['user_id'])
                ->where('date >=', $start)
                ->where('date <=', $end)
                ->findAll();

            $presentDays = 0;
            $dates = [];
            foreach ($attendance as $rec) {
                if (!in_array($rec['date'], $dates) && !empty($rec['time_in'])) {
                    $dates[] = $rec['date'];
                    $presentDays++;
                }
            }
            
            $basicPay = $presentDays * $dailyRate;
            
            $otPay = 0;
            $settingsModel = new PayrollSettingsModel();
            $otRate = (float)$settingsModel->getSetting('ot_regular_rate') ?: 1.25;
            $monthlySalary = $dailyRate * 22;
            $hourlyRate = $monthlySalary / 160;
            
            foreach ($attendance as $day) {
                $totalHours = (float)($day['total_hours'] ?? 0);
                if ($totalHours > 8) {
                    $otHours = $totalHours - 8;
                    $otPay += $otHours * $hourlyRate * $otRate;
                }
            }
            
            $sss = min($monthlySalary * 0.045, 1350);
            $phic = min($monthlySalary * 0.03, 1200);
            $hdmf = min($monthlySalary * 0.02, 100);
            
            $withholdingTax = 0;
            if ($monthlySalary > 20833) $withholdingTax = ($monthlySalary - 20833) * 0.20 / 2;
            
            $adjustments = $this->request->getJSON(true);
            $deMinimis = $adjustments['de_minimis'] ?? 0;
            $otherTaxable = $adjustments['other_taxable'] ?? 0;
            $sssLoan = $adjustments['sss_loan'] ?? 0;
            $calamityLoan = $adjustments['calamity_loan'] ?? 0;
            $salaryAdvance = $adjustments['salary_advance'] ?? 0;
            $otherDeductions = $adjustments['other_deductions'] ?? 0;
            
            $totalEarnings = $basicPay + $otPay + $deMinimis + $otherTaxable;
            $totalDeductions = $sss + $phic + $hdmf + $withholdingTax + $sssLoan + $calamityLoan + $salaryAdvance + $otherDeductions;
            $netPay = $totalEarnings - $totalDeductions;
            
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
            $payslipModel->insert([
                'employee_id'    => $employeeId,
                'period_start'   => $data['period_start'],
                'period_end'     => $data['period_end'],
                'net_pay'        => $data['net_pay'],
                'file_path'      => $permanentPath,
                'generated_data' => json_encode($data)
            ]);

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
        $payslip = $payslipModel->find($id);
        if (!$payslip) {
            throw new \Exception('Payslip not found');
        }

        $user = auth()->user();
        $employeeModel = new \App\Models\Client\EmployeeModel();
        $employee = $employeeModel->find($payslip['employee_id']);
        $isOwner = ($employee && $employee['user_id'] == $user->id);
        // Fix: use inGroup() instead of can()
        $isAdmin = $user->inGroup('client_payroll_staff');

        if (!$isOwner && !$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $filePath = $payslip['file_path'];
        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        return $this->response->download($filePath, null);
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

    // NEW: Get payslip data for preview
    public function getPayslipData($id)
    {
        $payslipModel = new \App\Models\Client\EmployeePayslipModel();
        $payslip = $payslipModel->find($id);
        if (!$payslip) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Payslip not found']);
        }
        
        $user = auth()->user();
        $employeeModel = new \App\Models\Client\EmployeeModel();
        $employee = $employeeModel->find($payslip['employee_id']);
        $isOwner = ($employee && $employee['user_id'] == $user->id);
        // Fix: use inGroup() instead of can()
        $isAdmin = $user->inGroup('client_payroll_staff');
        
        if (!$isOwner && !$isAdmin) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        
        $generatedData = json_decode($payslip['generated_data'], true);
        if (!$generatedData) {
            $generatedData = []; // fallback
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

        <!-- Header Info -->
        <table>
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

        <!-- Earnings -->
        <table>
            <tr>
                <th>REGULAR EARNINGS</th>
                <th>TAXABLE OTHER EARNINGS</th>
                <th>NON-TAXABLE OTHER EARNINGS</th>
            </tr>
            <tr>
                <td>
                    <table class="inner-table">
                        <tr><td>Basic Pay:</td><td class="right">' . $fmt($data['basic_pay']) . '</td></tr>
                        <tr><td>Night Premium:</td><td class="right">' . $fmt($data['night_diff_pay']) . '</td></tr>
                        <tr><td class="bold">Net Basic Pay:</td><td class="right bold">' . $fmt(($data['basic_pay'] ?? 0) + ($data['night_diff_pay'] ?? 0)) . '</td></tr>
                        <tr><td>Reg. OT:</td><td class="right">' . $fmt($data['overtime_pay']) . '</td></tr>
                    </table>
                </td>
                <td>
                    <table class="inner-table">
                        <tr><td>Taxable Other:</td><td class="right">' . $fmt($data['other_earnings_taxable']) . '</td></tr>
                    </table>
                </td>
                <td>
                    <table class="inner-table">
                        <tr><td>De Minimis:</td><td class="right">' . $fmt($data['de_minimis']) . '</td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="right"><strong>GROSS PAY</strong></td>
                <td class="right"><strong>' . $fmt($grossPay) . '</strong></td>
            </tr>
        </table>

        <!-- Deductions -->
        <table>
            <tr>
                <th>REGULAR DEDUCTIONS</th>
                <th>LOANS / ADVANCES</th>
            </tr>
            <tr>
                <td>
                    <table class="inner-table">
                        <tr><td>SSS:</td><td class="right">' . $fmt($data['sss_ee']) . '</td></tr>
                        <tr><td>Philhealth:</td><td class="right">' . $fmt($data['phic_ee']) . '</td></tr>
                        <tr><td>Pag-IBIG:</td><td class="right">' . $fmt($data['hdmf_ee']) . '</td></tr>
                        <tr><td>Withholding Tax:</td><td class="right">' . $fmt($data['withholding_tax']) . '</td></tr>
                    </table>
                </td>
                <td>
                    <table class="inner-table">
                        <tr><td>SSS Loan:</td><td class="right">' . $fmt($data['sss_loan']) . '</td></tr>
                        <tr><td>Calamity Loan:</td><td class="right">' . $fmt($data['calamity_loan']) . '</td></tr>
                        <tr><td>Salary Advance:</td><td class="right">' . $fmt($data['salary_advance']) . '</td></tr>
                        <tr><td>Other Deductions:</td><td class="right">' . $fmt($data['other_deductions']) . '</td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="right"><strong>TOTAL DEDUCTIONS</strong></td>
                <td class="right"><strong>' . $fmt($totalDeductions) . '</strong></td>
            </tr>
        </table>

        <!-- Net Pay / Signature -->
        <table>
            <tr>
                <td style="width:50%"><strong>Employee Signature:</strong></td>
                <td style="width:50%">&nbsp;</td>
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

    /* ALL columns included version for email
    private function buildPayslipHtmlForPdf($data, $employee)
    {
        $formatDate = function($date) {
            if (!$date) return '';
            return date('d-M-Y', strtotime($date));
        };

        $grossPay      = ($data['net_basic_pay'] ?? 0) 
                    + ($data['overtime_pay'] ?? 0) 
                    + ($data['other_earnings_taxable'] ?? 0) 
                    + ($data['de_minimis'] ?? 0);

        $totalDeductions = ($data['sss_ee'] ?? 0) + ($data['phic_ee'] ?? 0) + ($data['hdmf_ee'] ?? 0)
                        + ($data['withholding_tax'] ?? 0) + ($data['sss_loan'] ?? 0)
                        + ($data['calamity_loan'] ?? 0) + ($data['salary_advance'] ?? 0)
                        + ($data['calamity_advance'] ?? 0) + ($data['other_deductions'] ?? 0);

        $html = '
        <div style="font-family:Arial,sans-serif; border:1px solid #000; padding:20px; max-width:900px; margin:auto;">
            <h2 style="text-align:center;">PAY SLIP</h2>

            <table style="width:100%; border-collapse:collapse; margin-bottom:10px; font-size:13px;">
                <tr>
                    <td style="border:1px solid #000; padding:5px;"><strong>Employee:</strong></td>
                    <td style="border:1px solid #000; padding:5px;">' . ($data['employee_name'] ?? '') . '</td>
                    <td style="border:1px solid #000; padding:5px;"></td>
                    <td style="border:1px solid #000; padding:5px;"><strong>Payroll period:</strong></td>
                    <td style="border:1px solid #000; padding:5px;">' . $formatDate($data['period_start']) . '</td>
                    <td style="border:1px solid #000; padding:5px;">' . $formatDate($data['period_end']) . '</td>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px;"><strong>Position:</strong></td>
                    <td colspan="5" style="border:1px solid #000; padding:5px;">' . ($data['position'] ?? 'N/A') . '</td>
                </tr>
            </table>

            <table style="width:100%; border-collapse:collapse; font-size:13px; margin-top:10px;">
                <tr>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">REGULAR EARNINGS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">TAXABLE OTHER EARNINGS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">NON-TAXABLE OTHER EARNINGS</th>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>Basic pay:</td><td style="text-align:right;">' . number_format($data['basic_pay'] ?? 0, 2) . '</td></tr>
                            <tr><td>Rate per hour:</td><td style="text-align:right;">' . number_format($data['rate_per_hour'] ?? 0, 2) . '</td></tr>
                            <tr><td>No. of hours Worked:</td><td style="text-align:right;">' . number_format($data['hours_worked'] ?? 0, 2) . '</td></tr>
                            <tr><td>Night premium:</td><td style="text-align:right;">' . number_format($data['night_diff_pay'] ?? 0, 2) . '</td></tr>
                            <tr><td>Less: Absences</td><td style="text-align:right;">' . number_format($data['absent_amt'] ?? 0, 2) . '</td></tr>
                            <tr><td>Late/Undertime</td><td style="text-align:right;">' . number_format($data['undertime_amt'] ?? 0, 2) . '</td></tr>
                            <tr><td><strong>Net basic pay</strong></td><td style="text-align:right;"><strong>' . number_format($data['net_basic_pay'] ?? 0, 2) . '</strong></td></tr>
                            <tr><td>Reg. OT</td><td style="text-align:right;">' . number_format($data['reg_ot'] ?? 0, 2) . '</td></tr>
                            <tr><td>Sat OT</td><td style="text-align:right;">' . number_format($data['sat_ot'] ?? 0, 2) . '</td></tr>
                            <tr><td>Sun OT</td><td style="text-align:right;">' . number_format($data['sun_ot'] ?? 0, 2) . '</td></tr>
                            <tr><td>Holiday OT</td><td style="text-align:right;">' . number_format($data['hol_ot'] ?? 0, 2) . '</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>Salary Adjustment</td><td style="text-align:right;">' . number_format($data['salary_adjustment'] ?? 0, 2) . '</td></tr>
                            <tr><td>Override</td><td style="text-align:right;">' . number_format($data['override_amount'] ?? 0, 2) . '</td></tr>
                            <tr><td>NED</td><td style="text-align:right;">' . number_format($data['ned_amount'] ?? 0, 2) . '</td></tr>
                            <tr><td>Holiday Pay</td><td style="text-align:right;">' . number_format($data['holiday_pay'] ?? 0, 2) . '</td></tr>
                            <tr><td>Gross Compensation</td><td style="text-align:right;">' . number_format($data['gross_comp'] ?? 0, 2) . '</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>Year End Gifts</td><td style="text-align:right;">' . number_format($data['year_end_gifts'] ?? 0, 2) . '</td></tr>
                            <tr><td>Rice Subsidy</td><td style="text-align:right;">' . number_format($data['rice_subsidy'] ?? 0, 2) . '</td></tr>
                            <tr><td>Uniform Allowance</td><td style="text-align:right;">' . number_format($data['uniform_allowance'] ?? 0, 2) . '</td></tr>
                            <tr><td>Laundry Allowance</td><td style="text-align:right;">' . number_format($data['laundry_allowance'] ?? 0, 2) . '</td></tr>
                            <tr><td>Medical Allowance</td><td style="text-align:right;">' . number_format($data['medical_allowance'] ?? 0, 2) . '</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border:1px solid #000; padding:5px; text-align:right;"><strong>GROSS PAY</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>' . number_format($grossPay, 2) . '</strong></td>
                </tr>
            </table>

            <table style="width:100%; border-collapse:collapse; font-size:13px; margin-top:10px;">
                <tr>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">REGULAR DEDUCTIONS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">LOANS/ADVANCES</th>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>SSS</td><td style="text-align:right;">' . number_format($data['sss_ee'] ?? 0, 2) . '</td></tr>
                            <tr><td>Philhealth</td><td style="text-align:right;">' . number_format($data['phic_ee'] ?? 0, 2) . '</td></tr>
                            <tr><td>Pag-IBIG</td><td style="text-align:right;">' . number_format($data['hdmf_ee'] ?? 0, 2) . '</td></tr>
                            <tr><td>Withholding tax</td><td style="text-align:right;">' . number_format($data['withholding_tax'] ?? 0, 2) . '</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>Salary Loan</td><td style="text-align:right;">' . number_format($data['sss_loan'] ?? 0, 2) . '</td></tr>
                            <tr><td>Calamity Loan</td><td style="text-align:right;">' . number_format($data['calamity_loan'] ?? 0, 2) . '</td></tr>
                            <tr><td>Salary Advance</td><td style="text-align:right;">' . number_format($data['salary_advance'] ?? 0, 2) . '</td></tr>
                            <tr><td>Calamity Advance</td><td style="text-align:right;">' . number_format($data['calamity_advance'] ?? 0, 2) . '</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border:1px solid #000; padding:5px; text-align:right;">
                        <strong>TOTAL DEDUCTIONS: ' . number_format($totalDeductions, 2) . '</strong>
                    </td>
                </tr>
            </table>

            <table style="width:100%; border-collapse:collapse; font-size:13px; margin-top:10px;">
                <tr>
                    <td style="border:1px solid #000; padding:5px;">Employee signature:</td>
                    <td style="border:1px solid #000; padding:5px; height:40px;"></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>NET PAY:</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right; background:#f8d7da;"><strong>' . number_format($data['net_pay'] ?? 0, 2) . '</strong></td>
                </tr>
            </table>
        </div>';

        return $html;
    }
    */

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
            // The stored value is a bcrypt hash — we cannot decrypt it.
            // So we store the plaintext separately under a different key,
            // encrypted with the app's encryption key.
            if (! empty($settings['payslip_password_enc'])) {
                $encrypter = \Config\Services::encrypter();
                try {
                    return $encrypter->decrypt(base64_decode($settings['payslip_password_enc']));
                } catch (\Exception $e) {
                    log_message('warning', 'Failed to decrypt payslip password for user ' . $employee['user_id']);
                }
            }
        }

        // Fallback to preset
        return $employee['employee_number'] . date('Ymd', strtotime($employee['birthdate']));
    }
}