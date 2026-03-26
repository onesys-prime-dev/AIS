<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\PayrollModel;
use App\Models\Client\EmployeeModel;
use App\Services\Client\PayrollService;

class PayrollController extends BaseController
{
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
}