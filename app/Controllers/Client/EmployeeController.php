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

        if (! $user || ! \App\Libraries\PortalAccess::can('employees', $user)) {
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
            employees.gender,
            employees.civil_status,

            MAX(employee_positions.department) as department,
            MAX(employee_positions.position) as position,
            MAX(employee_positions.employment_type) as employment_type,
            MAX(employee_positions.working_day_type) as working_day_type,
            MAX(employee_positions.working_days) as working_days,
            MAX(employee_positions.start_time) as start_time,
            MAX(employee_positions.end_time) as end_time,
            MAX(employee_positions.break_schedule) as break_schedule,
            MAX(employee_positions.monthly_salary) as monthly_salary,
            MAX(employee_positions.de_minimis_benefit) as de_minimis_benefit,
            MAX(employee_positions.allowances) as allowances,

            MAX(employee_positions.de_minimis_breakdown) as de_minimis_breakdown,

                MAX(employee_government_ids.sss) as sss,
                MAX(employee_government_ids.tin) as tin,
                MAX(employee_government_ids.pagibig) as pagibig,
                MAX(employee_government_ids.philhealth) as philhealth,

                MAX(ai.secret) as email,
                MAX(users.active) as status,
                MAX(users.online_status) as online_status,

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

            MAX(ea.photo_path) as photo_path,
            MAX(ea.mobile_number) as mobile_number,
            MAX(ea.address) as address,
            MAX(ea.bank) as bank,
            MAX(ea.bank_address) as bank_address,
            MAX(ea.account_number) as account_number,
            MAX(ea.swift_code) as swift_code
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
            $eid = (int) $employee['id'];

            $vacRow  = $leaveBalanceModel->getBalance($eid, 'vacation',  $currentYear);
            $sickRow = $leaveBalanceModel->getBalance($eid, 'sick',      $currentYear);
            $emeRow  = $leaveBalanceModel->getBalance($eid, 'emergency', $currentYear);

            $employee['vacation_leave']  = $vacRow  ? (float) $vacRow['total_entitlement']  : 0.0;
            $employee['sick_leave']      = $sickRow ? (float) $sickRow['total_entitlement'] : 0.0;
            $employee['emergency_leave'] = $emeRow  ? (float) $emeRow['total_entitlement']  : 0.0;

            $employee['leave_total']     = $employee['vacation_leave'] + $employee['sick_leave'] + $employee['emergency_leave'];
            $employee['leave_remaining'] =
                ($vacRow  ? (float) $vacRow['remaining']  : 0.0) +
                ($sickRow ? (float) $sickRow['remaining'] : 0.0) +
                ($emeRow  ? (float) $emeRow['remaining']  : 0.0);

            // Show null (—) instead of 0 when no leave rows exist yet for this employee
            if ($vacRow === null && $sickRow === null && $emeRow === null) {
                $employee['leave_total']     = null;
                $employee['leave_remaining'] = null;
            }
        }

        unset($employee);

        /*
        |--------------------------------------------------------------------------
        | LOANS
        |--------------------------------------------------------------------------
        */

        $employees = array_map([\App\Libraries\PortalAccess::class, 'hideCompensation'], $employees);
        if (!\App\Libraries\PortalAccess::can('loans')) {
            return view('client_portal/employee_main', ['employees' => $employees, 'loans' => [], 'settings' => [], 'currentUserId' => (int) $currentUser->id]);
        }
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

                'loan_category' =>
                    $loan['loan_category'],

                'loan_type' =>
                    $loan['loan_type'],

                'principal_amount' =>
                    $loan['principal_amount'],

                'interest_rate' =>
                    $loan['interest_rate'],

                'total_interest' =>
                    $loan['total_interest'],

                'total_payable' =>
                    $loan['total_payable'],

                'remaining_balance' =>
                    $loan['remaining_balance'],

                'deduction_per_payroll' =>
                    $loan['deduction_per_payroll'],

                'deduction_frequency' =>
                    $loan['deduction_frequency'],

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
            $postData = \App\Libraries\PortalAccess::employeeInput($this->request->getPost());
            
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

            log_message('error', static::class . ': ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

            return redirect()->back()
                ->withInput()
                ->with('error', $this->safeErrorMessage($e, 'Unable to create employee. Please check the inputs and try again.'));
        }
    }

    public function updateEmployee($id)
    {
        $this->ensurePayrollStaff();

        $service = new EmployeeService();
        try {
            $service->updateEmployee($id, \App\Libraries\PortalAccess::employeeInput($this->request->getPost(), (int) $id));
            return redirect()->back()
                ->with('success', 'Employee updated successfully');
        } catch (\Throwable $e) {
            return $this->flashError($e, 'Unable to update employee. Please check the inputs and try again.');
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
                employee_positions.monthly_salary, employee_positions.de_minimis_benefit, employee_positions.de_minimis_breakdown, employee_positions.allowances,
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

        return $this->response->setJSON($employee ? \App\Libraries\PortalAccess::hideCompensation($employee) : null);
    }

    /**
     * Maps one CSV row (keyed by the same headers exportEmployees() writes) to the
     * shape EmployeeService::createEmployee() expects. Each imported row goes
     * through the same account/position/government-ID/leave creation as the "Add
     * Employee" modal — this importer used to insert a bare `employees` row with
     * no linked user account, position, or compensation, which silently produced
     * employees who couldn't log in, clock in, or be paid.
     */
    private function mapImportRow(array $row, string $tempPassword): array
    {
        $get = static fn (string $key) => trim((string) ($row[$key] ?? ''));
        return [
            'email'              => $get('Email'),
            'password'           => $tempPassword,
            'first_name'         => $get('First Name'),
            'middle_name'        => $get('Middle Name'),
            'last_name'          => $get('Last Name'),
            'dob'                => $get('Birthdate') ?: null,
            'gender'             => $get('Gender') ?: null,
            'civil_status'       => $get('Civil Status') ?: null,
            'sss'                => $get('SSS') ?: null,
            'tin'                => $get('TIN') ?: null,
            'pagibig'            => $get('Pagibig') ?: null,
            'philhealth'         => $get('PhilHealth') ?: null,
            'department'         => $get('Department') ?: null,
            'position'           => $get('Position') ?: null,
            'employment_type'    => $get('Employment Type') ?: null,
            'working_day_type'   => $get('Working Day Type') ?: 'full_weekdays',
            'working_days'       => array_values(array_filter(array_map('trim', explode(',', $get('Working Days'))))),
            'start_time'         => $get('Start Time'),
            'end_time'           => $get('End Time'),
            'monthly_salary'     => $get('Basic Salary'),
            'de_minimis_benefit' => $get('De Minimis Benefit'),
            'allowances'         => $get('Allowances'),
            'mobile_number'      => $get('Contact No.') ?: null,
            'address'            => $get('Address') ?: null,
            'bank'               => $get('Bank') ?: null,
            'bank_address'       => $get('Bank Address') ?: null,
            'account_number'     => $get('Account Number') ?: null,
            'swift_code'         => $get('Swift Code') ?: null,
            'vacation_leave'     => $get('Vacation Leave'),
            'sick_leave'         => $get('Sick Leave'),
            'emergency_leave'    => $get('Emergency Leave'),
        ];
    }

    public function importEmployees()
    {
        $this->ensurePayrollStaff();

        try {
            $file = $this->request->getFile('file');

            if (! $file || ! $file->isValid()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid file.']);
            }

            $handle = fopen($file->getTempName(), 'r');

            if (! $handle) {
                return $this->response->setJSON(['success' => false, 'message' => 'Unable to read file.']);
            }

            $header = fgetcsv($handle);
            if (! $header) {
                fclose($handle);
                return $this->response->setJSON(['success' => false, 'message' => 'The file has no header row.']);
            }
            if (! in_array('Email', $header, true)) {
                fclose($handle);
                return $this->response->setJSON(['success' => false, 'message' => 'The file needs an "Email" column — use the exported template as a starting point.']);
            }

            $service = new EmployeeService();
            $imported = [];
            $errors = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (count($row) !== count($header)) {
                    $errors[] = "Row {$rowNumber}: column count doesn't match the header row.";
                    continue;
                }
                $data = array_combine($header, $row);
                if (trim((string) ($data['Email'] ?? '')) === '') continue; // blank trailing row

                // A random temporary password per row — the admin communicates it
                // (or resets it) to the employee, who should change it on first login.
                $tempPassword = bin2hex(random_bytes(6));

                try {
                    $mapped = \App\Libraries\PortalAccess::employeeInput($this->mapImportRow($data, $tempPassword));
                    $service->createEmployee($mapped);
                    $imported[] = ['row' => $rowNumber, 'email' => $mapped['email'], 'temp_password' => $tempPassword];
                } catch (\Throwable $e) {
                    $errors[] = "Row {$rowNumber} ({$data['Email']}): " . $this->safeErrorMessage($e, 'Could not be imported.');
                }
            }

            fclose($handle);

            // Previously this importer left no trace anywhere — no activity
            // log entry, no filename, nothing — unlike every other write path
            // in the app. Recorded after the loop, non-critical: a history
            // write failing here must not turn an otherwise-successful import
            // into a reported failure.
            try {
                service('activityLogger')->log(
                    'created', 'employees',
                    count($imported) . ' employee(s) imported from CSV; ' . count($errors) . ' row(s) skipped',
                    null, null,
                    ['filename' => $file->getClientName(), 'imported_count' => count($imported), 'error_count' => count($errors)]
                );
                (new \App\Models\Client\ImportBatchModel())->record(
                    'employees', $file->getClientName(), hash_file('sha256', $file->getTempName()),
                    (int) (auth()->id() ?? 0), $rowNumber - 1, count($imported), 0, count($errors), 0,
                    ['errors' => array_slice($errors, 0, 20)]
                );
            } catch (\Throwable $e) {
                log_message('error', 'Employee import history record failed (import itself succeeded): {message}', ['message' => $e->getMessage()]);
            }

            return $this->response->setJSON([
                'success'  => true,
                'message'  => count($imported) . ' employee(s) imported; ' . count($errors) . ' skipped.',
                'imported' => $imported,
                'errors'   => $errors,
            ]);

        } catch (\Throwable $e) {

            log_message('error', static::class . ': ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

            return $this->response->setJSON([
                'success' => false,
                'message' => $this->safeErrorMessage($e, 'Unable to import employees from this file.')
            ]);
        }
    }

    /** Recent employee CSV import batches — filename, when, who, and row outcomes. */
    public function importHistory()
    {
        $this->ensurePayrollStaff();
        $rows = (new \App\Models\Client\ImportBatchModel())->recent('employees', 50);
        return $this->response->setJSON(['success' => true, 'batches' => $rows]);
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
                employee_positions.de_minimis_breakdown,
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
            'Basic Salary', 'De Minimis Benefit',
            'Rice Subsidy', 'Uniform/Clothing Allowance', 'Laundry Allowance', 'Medical Allowance',
            'Other Non-Taxable Compensation', 'Other Non-Taxable Compensation Breakdown',
            'Allowances',
            'Vacation Leave', 'Sick Leave', 'Emergency Leave',
            'Status',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CSV ROWS
        |--------------------------------------------------------------------------
        */

        foreach ($employees as $employee) {
            $employee = \App\Libraries\PortalAccess::hideCompensation($employee);

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

            /*
            |--------------------------------------------------------------------------
            | De Minimis Breakdown Format
            |--------------------------------------------------------------------------
            */

            $deMinimisBreakdown = [];
            $allocations = [];
            $otherCompBreakdown = [];

            try {

                $deMinimisBreakdown = json_decode(
                    $employee['de_minimis_breakdown'] ?? '{}',
                    true
                );

                if (! is_array($deMinimisBreakdown)) {
                    $deMinimisBreakdown = [];
                }

                $allocations = $deMinimisBreakdown['allocations'] ?? [];

                if (! is_array($allocations)) {
                    $allocations = [];
                }

                $otherCompBreakdown = $deMinimisBreakdown['other'] ?? [];

                if (! is_array($otherCompBreakdown)) {
                    $otherCompBreakdown = [];
                }

            } catch (\Throwable $e) {

                $deMinimisBreakdown = [];
                $allocations = [];
                $otherCompBreakdown = [];
            }

            $otherCompTotal = array_sum(array_map(
                static fn ($item) => (float) ($item['amount'] ?? 0),
                $otherCompBreakdown
            ));

            $otherCompSummary = implode('; ', array_map(
                static fn ($item) => trim((string) ($item['purpose'] ?? '')) . ': ' . ($item['amount'] ?? 0),
                $otherCompBreakdown
            ));

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
                $employee['monthly_salary'] ?? '',
                $employee['de_minimis_benefit'] ?? '',
                $allocations['rice_subsidy'] ?? '',
                $allocations['uniform_allowance'] ?? '',
                $allocations['laundry_allowance'] ?? '',
                $allocations['medical_allowance'] ?? '',
                $otherCompTotal,
                $otherCompSummary,
                $employee['allowances'] ?? '',
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
