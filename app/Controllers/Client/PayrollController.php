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

        // Get selected month from query string, default to current month
        $month = $this->request->getGet('month') ?? date('Y-m');
        $data['selected_month'] = $month;

        // Fetch payroll records for the selected month with employee name
        $data['payrolls'] = $payrollModel
            ->select('payroll.*, 
                    CONCAT(employees.last_name, ", ", employees.first_name, " ", LEFT(employees.middle_name, 1), ".") as employee_name,
                    employees.employee_number')
            ->join('employees', 'employees.id = payroll.employee_id', 'left')
            ->where('payroll.month_year', $month)
            ->findAll();

        // Fetch active employees for dropdown
        $data['employees'] = $employeeModel
            ->select('employees.id, CONCAT(first_name, " ", last_name) as full_name')
            ->join('users', 'users.id = employees.user_id', 'left')
            ->where('users.active', 1)
            ->findAll();

        return view('client_portal/payroll', $data);
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
}