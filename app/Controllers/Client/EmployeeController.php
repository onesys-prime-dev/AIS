<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\EmployeeModel;
use App\Services\Client\EmployeeService;
use CodeIgniter\Exceptions\PageNotFoundException;

class EmployeeController extends BaseController 
{
    private function ensurePayrollStaff(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->inGroup('client_payroll_staff')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    public function employeeView()
    {
        $this->ensurePayrollStaff();

        $employeeModel = new EmployeeModel();

        $data['employees'] = $employeeModel
            ->select('employees.*, employee_positions.department, employee_positions.position, employee_positions.employment_type, users.active as status')
            ->join('employee_positions', 'employees.id = employee_positions.employee_id', 'left')
            ->join('users', 'employees.user_id = users.id', 'left')
            ->findAll();

        return view('client_portal/employee_management', $data);
    }

    public function createEmployee()
    {
        $this->ensurePayrollStaff();

        $service = new EmployeeService();

        try {

            $service->createEmployee($this->request->getPost());

            return redirect()->back()
                ->with('success', 'Employee created successfully');

        } catch (\Throwable $e) {

            log_message('error', $e->getMessage());

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function updateEmployee($id)
    {
        $this->ensurePayrollStaff();

        $service = new EmployeeService();
        try {
            $service->updateEmployee($id, $this->request->getPost());
            return redirect()->back()
                ->with('success', 'Employee updated successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function deleteEmployee($id)
    {
        $this->ensurePayrollStaff();

        $service = new EmployeeService();
        try {
            $service->deleteEmployee($id);
            return redirect()->back()
                ->with('success', 'Employee deleted successfully');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function getEmployee($id)
    {
        $this->ensurePayrollStaff();

        $employeeModel = new EmployeeModel();
        $employee = $employeeModel
            ->select('employees.*, employee_positions.department, employee_positions.position, employee_positions.employment_type, users.active as status')
            ->join('employee_positions', 'employees.id = employee_positions.employee_id', 'left')
            ->join('users', 'employees.user_id = users.id', 'left')
            ->where('employees.id', $id)
            ->first();
        return $this->response->setJSON($employee);
    }

}