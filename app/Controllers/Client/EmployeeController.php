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
        $currentUser = auth()->user();

        $employees = $employeeModel
            ->select("employees.id, employees.user_id, employees.first_name, employees.middle_name, employees.last_name, employees.birthdate, employees.employee_number, employee_positions.department, employee_positions.position, employee_positions.employment_type, users.active as status, COALESCE(MAX(CASE WHEN agu.group = 'client_payroll_staff' THEN 'client_payroll_staff' WHEN agu.group = 'employee' THEN 'employee' ELSE NULL END), 'employee') as account_role, MAX(ea.photo_path) as photo_path", false)
            ->join('employee_positions', 'employees.id = employee_positions.employee_id', 'left')
            ->join('users', 'employees.user_id = users.id', 'left')
            ->join('employee_accounts as ea', 'employees.id = ea.employee_id', 'left')
            ->join('auth_groups_users as agu', 'employees.user_id = agu.user_id', 'left')
            ->groupBy('employees.id')
            ->findAll();

        foreach ($employees as &$employee) {
            if (! empty($employee['photo_path'])) {
                $employee['photo_path'] = base_url((string) $employee['photo_path']);
            }
        }
        unset($employee);

        $data['employees'] = $employees;

        $data['currentUserId'] = (int) ($currentUser->id ?? 0);

        return view('client_portal/employee_management', $data);
    }

    public function createEmployee()
    {
        $this->ensurePayrollStaff();

        $service = new EmployeeService();

        try {
            $postData = $this->request->getPost();
            
            // Controller-level validation - fail fast before service processing
            $email = trim((string) ($postData['email'] ?? ''));
            $password = trim((string) ($postData['password'] ?? ''));
            
            if (empty($email)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Email address is required. Please enter a valid email.');
            }
            
            if (empty($password)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Password is required. Please enter a password (minimum 8 characters).');
            }

            $service->createEmployee($postData);

            return redirect()->back()
                ->with('success', 'Employee created successfully');

        } catch (\Throwable $e) {

            log_message('error', $e->getMessage());

            return redirect()->back()
                ->withInput()
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
            ->select("employees.id, employees.user_id, employees.first_name, employees.middle_name, employees.last_name, employees.birthdate, employees.employee_number, employee_positions.department, employee_positions.position, employee_positions.employment_type, users.active as status, COALESCE(MAX(CASE WHEN agu.group = 'client_payroll_staff' THEN 'client_payroll_staff' WHEN agu.group = 'employee' THEN 'employee' ELSE NULL END), 'employee') as account_role", false)
            ->join('employee_positions', 'employees.id = employee_positions.employee_id', 'left')
            ->join('users', 'employees.user_id = users.id', 'left')
            ->join('auth_groups_users as agu', 'employees.user_id = agu.user_id', 'left')
            ->where('employees.id', $id)
            ->groupBy('employees.id')
            ->first();
        return $this->response->setJSON($employee);
    }

}