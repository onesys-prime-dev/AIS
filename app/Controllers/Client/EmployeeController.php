<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\EmployeeModel;
use App\Models\Client\LoanSettingModel;
use App\Models\Client\LeaveBalanceModel;
use App\Models\Client\LoanModel;
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

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES
        |--------------------------------------------------------------------------
        */

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
                            WHEN agu.group = 'client_payroll_staff'
                                THEN 'client_payroll_staff'

                            WHEN agu.group = 'employee'
                                THEN 'employee'

                            ELSE NULL
                        END
                    ),
                    'employee'
                ) as account_role,

            MAX(ea.photo_path) as photo_path
        ", false)

            ->join(
                'employee_government_ids',
                'employees.id = employee_government_ids.employee_id',
                'left'
            )

            ->join(
                'employee_positions',
                'employees.id = employee_positions.employee_id',
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

            ->join(
                'employee_accounts as ea',
                'employees.id = ea.employee_id',
                'left'
            )

            ->join(
                'auth_groups_users as agu',
                'employees.user_id = agu.user_id',
                'left'
            )

            ->groupBy('employees.id')

            ->findAll();

        /*
        |--------------------------------------------------------------------------
        | PHOTO URL
        |--------------------------------------------------------------------------
        */

        foreach ($employees as &$employee) {

            if (!empty($employee['photo_path'])) {

                $employee['photo_path'] =
                    base_url((string) $employee['photo_path']);
            }
        }

        unset($employee);

        /*
        |--------------------------------------------------------------------------
        | LEAVE BALANCE
        |--------------------------------------------------------------------------
        */

        $leaveBalanceModel = new LeaveBalanceModel();

        $currentYear = (int) date('Y');

        foreach ($employees as &$employee) {
            $balance = $leaveBalanceModel->syncFromApprovedRequests((int) $employee['id'], $currentYear, 'leave');
            $employee['leave_total']     = $balance['total_entitlement'] ?? null;
            $employee['leave_remaining'] = $balance['remaining']         ?? null;
        }

        unset($employee);

        /*
        |--------------------------------------------------------------------------
        | LOANS
        |--------------------------------------------------------------------------
        */

        $loanModel = new LoanModel();

        $loans = $loanModel

            ->select("
                employee_loans.*,

                employees.first_name,
                employees.middle_name,
                employees.last_name
            ")

            ->join(
                'employees',
                'employees.id = employee_loans.employee_id',
                'left'
            )

            ->orderBy(
                'employee_loans.created_at',
                'DESC'
            )

            ->findAll();

        $formattedLoans = array_map(function ($loan) {

            $first = trim($loan['first_name'] ?? '');

            $middle = trim($loan['middle_name'] ?? '');

            $last = trim($loan['last_name'] ?? '');

            $mi = $middle !== ''
                ? strtoupper(substr($middle, 0, 1)) . '.'
                : '';

            $employeeName = trim(
                $first . ' ' .
                ($mi !== '' ? $mi . ' ' : '') .
                $last
            );

            return [

                'id' => $loan['id'],

                'employee_name' => $employeeName,

                'loan_type' => $loan['loan_type'],

                'principal_amount' =>
                    $loan['principal_amount'],

                'remaining_balance' =>
                    $loan['remaining_balance'],

                'deduction_per_payroll' =>
                    $loan['deduction_per_payroll'],

                'installment_count' =>
                    $loan['installment_count'],

                'start_date' =>
                    $loan['start_date'],

                'estimated_end_date' =>
                    $loan['estimated_end_date'],

                'loan_status' =>
                    $loan['loan_status'],
            ];

        }, $loans);

        /*
        |--------------------------------------------------------------------------
        | LOAN SETTINGS
        |--------------------------------------------------------------------------
        */

        $loanSettingModel = new LoanSettingModel();

        $loanSettings = $loanSettingModel
            ->orderBy('loan_type', 'ASC')
            ->findAll();

        /*
        |--------------------------------------------------------------------------
        | VIEW DATA
        |--------------------------------------------------------------------------
        */

        $data['employees'] = $employees;

        $data['loans'] = $formattedLoans;

        $data['settings'] = $loanSettings;

        $data['currentUserId'] =
            (int) ($currentUser->id ?? 0);

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'client_portal/employee_main',
            $data
        );
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
            ->select("
                employees.id, employees.user_id,
                employees.first_name, employees.middle_name, employees.last_name,
                employees.birthdate, employees.employee_number,
                employees.gender, employees.civil_status,
                employee_positions.department, employee_positions.position,
                employee_positions.employment_type, employee_positions.working_day_type,
                employee_positions.working_days, employee_positions.start_time, employee_positions.end_time,
                employee_positions.monthly_salary, employee_positions.de_minimis_benefit, employee_positions.allowances,
                employee_government_ids.sss, employee_government_ids.tin,
                employee_government_ids.pagibig, employee_government_ids.philhealth,
                ai.secret as email,
                users.active as status, users.online_status,
                COALESCE(MAX(CASE WHEN agu.group = 'client_payroll_staff' THEN 'client_payroll_staff' WHEN agu.group = 'employee' THEN 'employee' ELSE NULL END), 'employee') as account_role,
                MAX(ea.mobile_number) as mobile_number,
                MAX(ea.address) as address,
                MAX(ea.bank) as bank,
                MAX(ea.bank_address) as bank_address,
                MAX(ea.account_number) as account_number,
                MAX(ea.swift_code) as swift_code
            ", false)
            ->join('employee_positions', 'employees.id = employee_positions.employee_id', 'left')
            ->join('employee_government_ids', 'employees.id = employee_government_ids.employee_id', 'left')
            ->join('users', 'employees.user_id = users.id', 'left')
            ->join('auth_identities as ai', "users.id = ai.user_id AND ai.type = 'email_password'", 'left')
            ->join('employee_accounts as ea', 'employees.id = ea.employee_id', 'left')
            ->join('auth_groups_users as agu', 'employees.user_id = agu.user_id', 'left')
            ->where('employees.id', $id)
            ->groupBy('employees.id')
            ->first();

        // Attach leave type entitlements
        if ($employee) {
            $leaveBalanceModel = new LeaveBalanceModel();
            $year = (int) date('Y');
            $employee['vacation_leave']  = $leaveBalanceModel->getTypeEntitlement((int) $employee['id'], 'vacation',  $year);
            $employee['sick_leave']      = $leaveBalanceModel->getTypeEntitlement((int) $employee['id'], 'sick',      $year);
            $employee['emergency_leave'] = $leaveBalanceModel->getTypeEntitlement((int) $employee['id'], 'emergency', $year);
        }

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
            $imported = 0;

            while (($row = fgetcsv($handle)) !== false) {

                if (count($row) !== count($header)) continue;
                $data = array_combine($header, $row);

                // Use header-based mapping so the file round-trips with exportEmployees.
                $employeeModel->insert([
                    'employee_number' => $data['Employee Number'] ?? ($data['employee_number'] ?? ''),
                    'first_name'      => $data['First Name']      ?? ($data['first_name']      ?? ''),
                    'middle_name'     => $data['Middle Name']     ?? ($data['middle_name']     ?? ''),
                    'last_name'       => $data['Last Name']       ?? ($data['last_name']       ?? ''),
                    'birthdate'       => !empty($data['Birthdate'] ?? $data['birthdate'] ?? '')
                                            ? ($data['Birthdate'] ?? $data['birthdate'])
                                            : null,
                ]);
                $imported++;
            }

            fclose($handle);

            return $this->response->setJSON([
                'success' => true,
                'message' => "{$imported} employee(s) imported successfully.",
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
                employees.id,
                employees.employee_number,
                employees.first_name,
                employees.middle_name,
                employees.last_name,
                employees.birthdate,
                employees.gender,
                employees.civil_status,

                employee_positions.department,
                employee_positions.position,
                employee_positions.employment_type,
                employee_positions.working_day_type,
                employee_positions.working_days,
                employee_positions.start_time,
                employee_positions.end_time,
                employee_positions.monthly_salary,
                employee_positions.de_minimis_benefit,
                employee_positions.allowances,

                employee_government_ids.sss,
                employee_government_ids.tin,
                employee_government_ids.pagibig,
                employee_government_ids.philhealth,

                ai.secret as email,

                MAX(ea.mobile_number) as mobile_number,
                MAX(ea.address) as address,
                MAX(ea.bank) as bank,
                MAX(ea.bank_address) as bank_address,
                MAX(ea.account_number) as account_number,
                MAX(ea.swift_code) as swift_code,

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

            ->join(
                'employee_accounts as ea',
                'employees.id = ea.employee_id',
                'left'
            )

            ->groupBy('employees.id')

            ->findAll();

        $leaveBalanceModel = new LeaveBalanceModel();
        $currentYear = (int) date('Y');

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
            'First Name', 'Middle Name', 'Last Name',
            'Gender', 'Civil Status', 'Birthdate',
            'Email', 'Contact No.', 'Address',
            'Department', 'Position', 'Employment Type',
            'Working Day Type', 'Working Days', 'Start Time', 'End Time',
            'SSS', 'TIN', 'Pagibig', 'PhilHealth',
            'Bank', 'Bank Address', 'Account Number', 'Swift Code',
            'Basic Salary', 'De Minimis Benefit', 'Allowances',
            'Vacation Leave', 'Sick Leave', 'Emergency Leave',
            'Status',
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
                $employee['gender'],
                $employee['civil_status'],
                $employee['birthdate'],
                $employee['email'],
                $employee['mobile_number'],
                $employee['address'],
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
                $employee['bank'],
                $employee['bank_address'],
                $employee['account_number'],
                $employee['swift_code'],
                $employee['monthly_salary'],
                $employee['de_minimis_benefit'],
                $employee['allowances'],
                $leaveBalanceModel->getTypeEntitlement((int) $employee['id'], 'vacation',  $currentYear),
                $leaveBalanceModel->getTypeEntitlement((int) $employee['id'], 'sick',      $currentYear),
                $leaveBalanceModel->getTypeEntitlement((int) $employee['id'], 'emergency', $currentYear),
                ((int) ($employee['status'] ?? 0) === 1) ? 'Active' : 'Inactive',
            ]);
        }

        fclose($output);

        exit;
    }
}