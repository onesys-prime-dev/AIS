<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\PayrollModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\PayrollSettingsModel;
use App\Services\Client\PayrollService;
use App\Models\AttendanceModel;

class PayrollController extends BaseController
{
    // ================= EXISTING METHODS (keep as is) =================
    
    public function payrollView()
    {
        $payrollModel = new PayrollModel();
        $employeeModel = new EmployeeModel();

        $month = $this->request->getGet('month') ?? date('Y-m');
        $data['selected_month'] = $month;

        // FirstName M. LastName
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

        // FirstName M. LastName
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
        $service = new PayrollService();

        try {
            $service->createPayroll($this->request->getPost());
            return redirect()->back()
                ->with('success', 'Payroll record created successfully');
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function updatePayroll($id)
    {
        $service = new PayrollService();

        try {
            $service->updatePayroll($id, $this->request->getPost());
            return redirect()->back()
                ->with('success', 'Payroll record updated successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function deletePayroll($id)
    {
        $service = new PayrollService();

        try {
            $service->deletePayroll($id);
            return redirect()->back()
                ->with('success', 'Payroll record deleted successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function getPayroll($id)
    {
        $payrollModel = new PayrollModel();
        $payroll = $payrollModel->find($id);
        return $this->response->setJSON($payroll);
    }

    public function getEmployees()
    {
        $employeeModel = new EmployeeModel();

        $rows = $employeeModel
            ->select("
                employees.id,
                TRIM(employees.first_name) AS first_name,
                TRIM(employees.last_name) AS last_name,
                TRIM(COALESCE(employees.middle_name, '')) AS middle_name
            ")
            ->join('users', 'users.id = employees.user_id', 'left')
            ->where('users.active', 1)
            ->orderBy('employees.first_name', 'ASC')
            ->orderBy('employees.last_name', 'ASC')
            ->findAll();

        $employees = array_map(static function ($row) {
            $first = $row['first_name'] ?? '';
            $last  = $row['last_name'] ?? '';
            $mid   = $row['middle_name'] ?? '';
            $mi    = $mid !== '' ? strtoupper(substr($mid, 0, 1)) . '.' : '';

            $name = trim($first . ' ' . ($mi !== '' ? $mi . ' ' : '') . $last);

            return [
                'id'    => (string)($row['id'] ?? ''),
                'name'  => $name,
                'label' => $name,
            ];
        }, $rows);

        return $this->response->setJSON($employees);
    }

    // ================= NEW METHODS =================

    /**
     * Get timesheet data for an employee for a given period
     */
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

    /**
     * Import CSV for payroll data (e.g., overtime, adjustments)
     */
    public function importCSV()
    {
        $file = $this->request->getFile('csv_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Invalid CSV file.');
        }
        $csvData = array_map('str_getcsv', file($file->getTempName()));
        $headers = array_shift($csvData);
        // Store in session for use in payslip generation
        session()->set('imported_payroll_data', $csvData);
        return redirect()->back()->with('success', 'CSV imported. Use data in Payslip tab.');
    }

    /**
     * Export current payroll register to CSV
     */
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
                $row['id'],
                $row['employee_name'],
                $row['phone'],
                $row['daily_rate'],
                $row['dst'],
                $row['currency'],
                $row['status'],
                $row['month_year']
            ]);
        }
        fclose($output);
        exit();
    }

    /**
     * Save payroll settings
     */
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
        return redirect()->back()->with('success', 'Payroll settings saved.');
    }

    /**
     * Get current payroll settings as JSON
     */
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
}