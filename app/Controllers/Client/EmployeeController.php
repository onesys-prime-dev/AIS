<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\EmployeeModel;
use App\Models\Client\LeaveBalanceModel;
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
        ->select("
            employees.id,
            employees.user_id,
            employees.first_name,
            employees.middle_name,
            employees.last_name,
            employees.birthdate,
            employees.employee_number,

            employee_positions.department,
            employee_positions.position,
            employee_positions.employment_type,
            employee_positions.working_day_type,
            employee_positions.working_days,
            employee_positions.start_time,
            employee_positions.end_time,

            employee_government_ids.sss,
            employee_government_ids.tin,
            employee_government_ids.pagibig,
            employee_government_ids.philhealth,

            ai.secret as email,
            users.active as status,
            users.online_status,

            COALESCE(
                MAX(
                    CASE
                        WHEN agu.group = 'client_payroll_staff' THEN 'client_payroll_staff'
                        WHEN agu.group = 'employee' THEN 'employee'
                        ELSE NULL
                    END
                ),
                'employee'
            ) as account_role,

            MAX(ea.photo_path) as photo_path
        ", false)

        ->join('employee_government_ids', 'employees.id = employee_government_ids.employee_id', 'left')
        ->join('employee_positions', 'employees.id = employee_positions.employee_id', 'left')
        ->join('users', 'employees.user_id = users.id', 'left')
        ->join('auth_identities as ai', "users.id = ai.user_id AND ai.type = 'email_password'",'left')
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

        // Merge leave balance for the current year into each employee row
        $leaveBalanceModel = new LeaveBalanceModel();
        $currentYear       = (int) date('Y');

        foreach ($employees as &$employee) {
            $balance = $leaveBalanceModel->syncFromApprovedRequests((int) $employee['id'], $currentYear, 'leave');
            $employee['leave_total']     = $balance['total_entitlement'] ?? null;
            $employee['leave_remaining'] = $balance['remaining']         ?? null;
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

    public function getEmployee($id)
    {
        $this->ensurePayrollStaff();

        $employeeModel = new EmployeeModel();
        $employee = $employeeModel
            ->select("employees.id, employees.user_id, employees.first_name, employees.middle_name, employees.last_name, employees.birthdate, employees.employee_number, employee_positions.department, employee_positions.position, employee_positions.employment_type, users.active as status, users.online_status, COALESCE(MAX(CASE WHEN agu.group = 'client_payroll_staff' THEN 'client_payroll_staff' WHEN agu.group = 'employee' THEN 'employee' ELSE NULL END), 'employee') as account_role", false)
            ->join('employee_positions', 'employees.id = employee_positions.employee_id', 'left')
            ->join('users', 'employees.user_id = users.id', 'left')
            ->join('auth_groups_users as agu', 'employees.user_id = agu.user_id', 'left')
            ->where('employees.id', $id)
            ->groupBy('employees.id')
            ->first();
        return $this->response->setJSON($employee);
    }

    public function importEmployees()
    {
        $this->ensurePayrollStaff();

        try {

            $file = $this->request->getFile('file');

            if (! $file || ! $file->isValid()) {

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid file.'
                ]);
            }

            $handle = fopen($file->getTempName(), 'r');

            if (! $handle) {

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Unable to read file.'
                ]);
            }

            $header = fgetcsv($handle);

            $employeeModel = new EmployeeModel();

            while (($row = fgetcsv($handle)) !== false) {

                $employeeModel->insert([
                    'first_name' => $row[0] ?? '',
                    'middle_name' => $row[1] ?? '',
                    'last_name' => $row[2] ?? '',
                    'birthdate' => $row[3] ?? null,
                    'employee_number' => $row[4] ?? '',
                ]);
            }

            fclose($handle);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Employees imported successfully.'
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function exportEmployees()
    {
        $this->ensurePayrollStaff();

        $employeeModel = new EmployeeModel();

        $employees = $employeeModel

            ->select("
                employees.employee_number,
                employees.first_name,
                employees.middle_name,
                employees.last_name,
                employees.birthdate,

                employee_positions.department,
                employee_positions.position,
                employee_positions.employment_type,
                employee_positions.working_day_type,
                employee_positions.working_days,
                employee_positions.start_time,
                employee_positions.end_time,

                employee_government_ids.sss,
                employee_government_ids.tin,
                employee_government_ids.pagibig,
                employee_government_ids.philhealth,

                ai.secret as email,

                users.active as status
            ", false)

            ->join(
                'employee_positions',
                'employees.id = employee_positions.employee_id',
                'left'
            )

            ->join(
                'employee_government_ids',
                'employees.id = employee_government_ids.employee_id',
                'left'
            )

            ->join(
                'users',
                'employees.user_id = users.id',
                'left'
            )

            ->join(
                'auth_identities as ai',
                "users.id = ai.user_id AND ai.type = 'email_password'",
                'left'
            )

            ->findAll();

        $filename = 'employees_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $output = fopen('php://output', 'w');

        /*
        |--------------------------------------------------------------------------
        | CSV HEADERS
        |--------------------------------------------------------------------------
        */

        fputcsv($output, [

            'Employee Number',

            'First Name',
            'Middle Name',
            'Last Name',
            'Birthdate',

            'Email',

            'Department',
            'Position',
            'Employment Type',

            'Working Day Type',
            'Working Days',

            'Start Time',
            'End Time',

            'SSS',
            'TIN',
            'Pagibig',
            'PhilHealth',

            'Status'

        ]);

        /*
        |--------------------------------------------------------------------------
        | CSV ROWS
        |--------------------------------------------------------------------------
        */

        foreach ($employees as $employee) {

            /*
            |--------------------------------------------------------------------------
            | Working Days Format
            |--------------------------------------------------------------------------
            */

            $workingDays = [];

            try {

                $workingDays = json_decode(
                    $employee['working_days'] ?? '[]',
                    true
                );

                if (! is_array($workingDays)) {
                    $workingDays = [];
                }

            } catch (\Throwable $e) {

                $workingDays = [];
            }

            fputcsv($output, [

                $employee['employee_number'],

                $employee['first_name'],
                $employee['middle_name'],
                $employee['last_name'],
                $employee['birthdate'],

                $employee['email'],

                $employee['department'],
                $employee['position'],
                $employee['employment_type'],

                $employee['working_day_type'],
                implode(', ', $workingDays),

                $employee['start_time'],
                $employee['end_time'],

                $employee['sss'],
                $employee['tin'],
                $employee['pagibig'],
                $employee['philhealth'],

                ((int) ($employee['status'] ?? 0) === 1)
                    ? 'Active'
                    : 'Inactive'

            ]);
        }

        fclose($output);

        exit;
    }

}