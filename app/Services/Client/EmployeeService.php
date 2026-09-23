<?php

namespace App\Services\Client;

use App\Models\Client\EmployeeGovernmentIdModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\EmployeePositionModel;
use App\Models\Client\LeaveBalanceModel;
use CodeIgniter\Shield\Models\UserModel;

class EmployeeService
{
    protected $employeeModel;
    protected $govModel;
    protected $posModel;
    protected $userModel;
    protected $leaveBalanceModel;

    public function __construct()
    {
        $this->employeeModel     = new EmployeeModel();
        $this->govModel          = new EmployeeGovernmentIdModel();
        $this->posModel          = new EmployeePositionModel();
        $this->userModel         = new UserModel();
        $this->leaveBalanceModel = new LeaveBalanceModel();
    }

    /** The only working_day_type values the rest of the app actually knows
     *  about — 'flexible' in particular disables scheduled start/end time in
     *  payroll and attendance aggregation (PayrollController::getAttendanceSummary(),
     *  PayrollAggregationService), so an unrecognized value here is never
     *  allowed to slip through and silently behave as "fixed". */
    private const KNOWN_WORKING_DAY_TYPES = ['full_weekdays', 'weekday_with_rest', 'weekend_shift', 'custom', 'flexible'];

    private function resolveWorkingDayType(array $data, string $fallback = 'full_weekdays'): string
    {
        $submitted = trim((string) ($data['working_day_type'] ?? ''));
        return in_array($submitted, self::KNOWN_WORKING_DAY_TYPES, true) ? $submitted : $fallback;
    }

    /**
     * Resolves a submitted department_id into the ['department_id', 'department']
     * pair actually written to employee_positions — the free-text 'department'
     * column is kept in sync from the department's real name so existing
     * reads of it (reports, exports, filters) keep working unchanged while
     * assignment itself now always goes through the curated department list.
     */
    private function resolveDepartment(array $data, ?string $fallbackName = null, ?int $fallbackId = null): array
    {
        $departmentId = isset($data['department_id']) && $data['department_id'] !== '' ? (int) $data['department_id'] : null;
        if ($departmentId === null) {
            return ['department_id' => $fallbackId, 'department' => trim((string) ($data['department'] ?? '')) ?: $fallbackName];
        }

        $dept = \Config\Database::connect()->table('departments')->where('id', $departmentId)->where('deleted_at', null)->get()->getRowArray();
        if (! $dept) {
            throw new \Exception('Selected department was not found.');
        }

        return ['department_id' => $departmentId, 'department' => $dept['name']];
    }

    /** Registration has no department picker — it falls back to a fixed
     *  name ('General'/'Payroll'), so that name is found-or-created here
     *  rather than left with no department_id at all. */
    private function findOrCreateDepartmentByName(string $name): int
    {
        $db = \Config\Database::connect();
        $existing = $db->table('departments')->where('name', $name)->where('deleted_at', null)->first();
        if ($existing) {
            return (int) $existing['id'];
        }
        $db->table('departments')->insert(['name' => $name, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return (int) $db->insertID();
    }

    public function createEmployee(array $data)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $email = trim((string) ($data['email'] ?? ''));
            $password = trim((string) ($data['password'] ?? ''));

            // Validate email and password are provided and not empty
            if (empty($email)) {
                throw new \Exception('Email is required and cannot be empty.');
            }

            if (empty($password)) {
                throw new \Exception('Password is required and cannot be empty.');
            }

            // Validate email format
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Please provide a valid email address.');
            }

            // Check minimum password length
            if (strlen($password) < 8) {
                throw new \Exception('Password must be at least 8 characters long.');
            }

            $startTime = trim((string) ($data['start_time'] ?? ''));
            $endTime = trim((string) ($data['end_time'] ?? ''));
            $workingDayType = $this->resolveWorkingDayType($data);
            $workingDays = array_values(array_filter(array_map(
                static fn ($day) => strtolower(trim((string) $day)),
                (array) ($data['working_days'] ?? [])
            )));
            $breakSchedule = $this->parseBreakSchedule($data['break_schedule'] ?? null);
            $breakWindows = $this->parseBreakWindows($data['break_windows'] ?? null, count($breakSchedule));
            $tardinessGraceMinutes = $this->parseGraceMinutes($data['tardiness_grace_minutes'] ?? null);
            $otGraceMinutes = $this->parseGraceMinutes($data['ot_grace_minutes'] ?? null);

            if ($startTime === '' || $endTime === '') {
                throw new \Exception('Start time and end time are required.');
            }

            if ($startTime === $endTime) {
                throw new \Exception('Start and end time must differ. An earlier end time means the following day.');
            }

            if ($workingDays === []) {
                throw new \Exception('Please select at least one working day.');
            }

            $existingUser = $this->userModel
                ->where('username', $email)
                ->first();

            if ($existingUser) {
                throw new \Exception('This email is already registered. Please use a different email.');
            }

            // Use Shield's provider flow to guarantee proper email identity creation.
            $users = auth()->getProvider();
            $user = $users->createNewUser([
                'username' => $email,
                'email' => $email,
                'password' => $password,
            ]);

            if (trim((string) ($user->username ?? '')) === '') {
                $user->username = $email;
            }

            if (! $users->save($user)) {
                throw new \Exception(json_encode($users->errors()));
            }

            $userId = (int) ($user->id ?? $users->getInsertID());

            if ($userId <= 0) {
                throw new \Exception('Unable to determine created user.');
            }

            // Keep username aligned to email for existing app conventions.
            $this->userModel->update($userId, [
                'username' => $email,
                'active' => 1,
                'status' => 'active',
            ]);

            $savedUser = $this->userModel->find($userId);

            if (! $savedUser) {
                throw new \Exception('Unable to load created user for group assignment.');
            }

            // Ensure normal employees created by payroll staff are in employee group.
            $savedUser->removeGroup('user');
            $savedUser->addGroup('employee');

            if (! $savedUser->inGroup('employee')) {
                throw new \Exception('Failed to assign employee group.');
            }

            $employeeNumber = $this->generateEmployeeNumber();

            $employeeId = $this->employeeModel->insert([
                'user_id' => $userId,
                'employee_number' => $employeeNumber,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'],
                'last_name' => $data['last_name'],
                'birthdate' => $data['dob'],
                'gender' => $data['gender'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
            ]);

            if (! $employeeId) {
                throw new \Exception(json_encode($this->employeeModel->errors()));
            }

            $this->govModel->insert([
                'employee_id' => $employeeId,
                'sss' => $data['sss'],
                'tin' => $data['tin'],
                'pagibig' => $data['pagibig'],
                'philhealth' => $data['philhealth'],
            ]);

            if ($this->govModel->errors()) {
                throw new \Exception(json_encode($this->govModel->errors()));
            }

            $resolvedDepartment = $this->resolveDepartment($data);

            $this->posModel->insert([
                'employee_id' => $employeeId,
                'department' => $resolvedDepartment['department'],
                'department_id' => $resolvedDepartment['department_id'],
                'position' => $data['position'],
                'employment_type' => $data['employment_type'],
                'working_day_type' => $workingDayType,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'working_days' => json_encode($workingDays),
                'break_schedule' => json_encode($breakSchedule),
                'break_windows' => json_encode($breakWindows),
                'tardiness_grace_minutes' => $tardinessGraceMinutes,
                'ot_grace_minutes' => $otGraceMinutes,
                'monthly_salary' => isset($data['monthly_salary']) && $data['monthly_salary'] !== '' ? (float) $data['monthly_salary'] : null,
                'de_minimis_benefit' => isset($data['de_minimis_benefit']) && $data['de_minimis_benefit'] !== '' ? (float) $data['de_minimis_benefit'] : 0,
                'de_minimis_breakdown' => isset($data['de_minimis_breakdown']) && $data['de_minimis_breakdown'] !== '' ? $data['de_minimis_breakdown'] : null,
                // Separate, additive set of BIR categories — never merged
                // into de_minimis_benefit/_breakdown above.
                'additional_de_minimis_benefit' => isset($data['additional_de_minimis_benefit']) && $data['additional_de_minimis_benefit'] !== '' ? (float) $data['additional_de_minimis_benefit'] : 0,
                'additional_de_minimis_breakdown' => isset($data['additional_de_minimis_breakdown']) && $data['additional_de_minimis_breakdown'] !== '' ? $data['additional_de_minimis_breakdown'] : null,
                'allowances' => isset($data['allowances']) && $data['allowances'] !== '' ? (float) $data['allowances'] : 0,
            ]);

            if ($this->posModel->errors()) {
                throw new \Exception(json_encode($this->posModel->errors()));
            }

            // Save employee_accounts (contact + bank info)
            $db->table('employee_accounts')->insert([
                'employee_id'    => $employeeId,
                'mobile_number'  => $data['mobile_number'] ?? null,
                'address'        => $data['address'] ?? null,
                'bank'           => $data['bank'] ?? null,
                'bank_address'   => $data['bank_address'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'swift_code'     => $data['swift_code'] ?? null,
            ]);

            // Vacation / Sick / Emergency leave entitlements
            foreach (['vacation', 'sick', 'emergency'] as $lt) {
                $key = $lt . '_leave';
                if (isset($data[$key]) && $data[$key] !== '') {
                    $this->leaveBalanceModel->setLeaveTypeEntitlement($employeeId, $lt, (float) $data[$key]);
                }
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function createEmployeeProfileForExistingUser(int $userId, string $role, array $details): void
    {
        $existingEmployee = $this->employeeModel->where('user_id', $userId)->first();
        if ($existingEmployee) {
            return;
        }

        [$firstNameFallback, $middleNameFallback, $lastNameFallback] = $this->extractNameParts(
            (string) ($details['username'] ?? ''),
            (string) ($details['email'] ?? '')
        );

        $firstName = trim((string) ($details['first_name'] ?? '')) ?: $firstNameFallback;
        $middleName = trim((string) ($details['middle_name'] ?? '')) ?: $middleNameFallback;
        $lastName = trim((string) ($details['last_name'] ?? '')) ?: $lastNameFallback;
        $birthdate = trim((string) ($details['birthdate'] ?? '')) ?: '1900-01-01';

        $sss = trim((string) ($details['sss'] ?? '')) ?: 'PENDING';
        $tin = trim((string) ($details['tin'] ?? '')) ?: 'PENDING';
        $pagibig = trim((string) ($details['pagibig'] ?? '')) ?: 'PENDING';
        $philhealth = trim((string) ($details['philhealth'] ?? '')) ?: 'PENDING';

        $department = trim((string) ($details['department'] ?? '')) ?: ($role === 'client_payroll_staff' ? 'Payroll' : 'General');
        $position = trim((string) ($details['position'] ?? '')) ?: ($role === 'client_payroll_staff' ? 'Client Payroll Staff' : 'Employee');
        $employmentType = trim((string) ($details['employment_type'] ?? '')) ?: 'Regular';
        $workingDayType = $this->resolveWorkingDayType($details);
        $workingDays = array_values(array_filter(array_map(
            static fn ($day) => strtolower(trim((string) $day)),
            (array) ($details['working_days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'])
        )));
        $startTime = trim((string) ($details['start_time'] ?? '08:00')) ?: '08:00';
        $endTime = trim((string) ($details['end_time'] ?? '17:00')) ?: '17:00';

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $employeeId = $this->employeeModel->insert([
                'employee_number' => $this->generateEmployeeNumber(),
                'user_id' => $userId,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'birthdate' => $birthdate,
            ]);

            if (! $employeeId) {
                throw new \RuntimeException('Failed to create employee record: ' . json_encode($this->employeeModel->errors()));
            }

            $this->govModel->insert([
                'employee_id' => $employeeId,
                'sss' => $sss,
                'tin' => $tin,
                'pagibig' => $pagibig,
                'philhealth' => $philhealth,
            ]);

            if ($this->govModel->errors()) {
                throw new \RuntimeException('Failed to create employee government IDs: ' . json_encode($this->govModel->errors()));
            }

            $this->posModel->insert([
                'employee_id' => $employeeId,
                'department' => $department,
                'department_id' => $this->findOrCreateDepartmentByName($department),
                'position' => $position,
                'employment_type' => $employmentType,
                'working_day_type' => $workingDayType,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'working_days' => json_encode($workingDays),
            ]);

            if ($this->posModel->errors()) {
                throw new \RuntimeException('Failed to create employee position: ' . json_encode($this->posModel->errors()));
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Parses the break_schedule form field (a JSON array of per-break minutes,
     * e.g. "[15,60,15]") into a clean array of positive integers. Falls back to
     * a single 60-minute break — matching the previous flat-1-hour rule — when
     * nothing valid was submitted.
     */
    private function parseBreakSchedule($raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $minutes = array_values(array_filter(
                    array_map('intval', $decoded),
                    static fn ($m) => $m > 0
                ));
                if ($minutes !== []) {
                    return $minutes;
                }
            }
        }
        return [60];
    }

    /**
     * Parses the break_windows form field (a JSON array parallel to
     * break_schedule, e.g. '[{"mode":"strict","start":"12:00","end":"13:00"},null]')
     * into a clean array the same length as $breakCount. Any entry that isn't
     * a valid window object becomes null (Flexible — no restriction, today's
     * behavior), so a malformed submission degrades safely instead of
     * blocking the whole save.
     */
    private function parseBreakWindows($raw, int $breakCount): array
    {
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        $items = is_array($decoded) ? array_values($decoded) : [];

        $windows = [];
        for ($i = 0; $i < $breakCount; $i++) {
            $item = $items[$i] ?? null;
            $windows[] = $this->validateBreakWindow($item);
        }

        return $windows;
    }

    private function validateBreakWindow($item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $mode = (string) ($item['mode'] ?? 'flexible');
        if (!in_array($mode, ['strict', 'slightly_strict'], true)) {
            return null;
        }

        $start = trim((string) ($item['start'] ?? ''));
        $end = trim((string) ($item['end'] ?? ''));
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end) || $start === $end) {
            return null;
        }

        return ['mode' => $mode, 'start' => $start, 'end' => $end];
    }

    /**
     * A grace-period minutes field (tardiness or OT) — clamped to a sane
     * 0-120 range and defaulted to 0 (no grace, today's exact-minute
     * behavior) for anything blank, negative, or malformed, so a bad
     * submission degrades safely instead of blocking the whole save.
     */
    private function parseGraceMinutes($raw): int
    {
        $minutes = filter_var($raw, FILTER_VALIDATE_INT);
        if ($minutes === false || $minutes < 0) {
            return 0;
        }
        return min(120, $minutes);
    }

    private function generateEmployeeNumber()
    {
        $year = date('Y');
        $prefix = 'EMP-' . $year . '-';

        $latestEmployee = $this->employeeModel
            ->select('employee_number')
            ->like('employee_number', $prefix, 'after')
            ->orderBy('employee_number', 'DESC')
            ->first();

        $lastSequence = 0;
        if (! empty($latestEmployee['employee_number'])) {
            $parts = explode('-', (string) $latestEmployee['employee_number']);
            $lastChunk = end($parts);
            $lastSequence = (int) $lastChunk;
        }

        $nextSequence = $lastSequence + 1;

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    private function extractNameParts(string $username, string $email): array
    {
        $base = trim($username);

        if ($base === '') {
            $base = trim(strtok($email, '@') ?: 'Employee');
        }

        $parts = preg_split('/\s+/', $base) ?: [];

        if (count($parts) === 0) {
            return ['Employee', null, 'User'];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, 'User'];
        }

        if (count($parts) === 2) {
            return [$parts[0], null, $parts[1]];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);
        $middle = implode(' ', $parts);

        return [$first ?: 'Employee', $middle ?: null, $last ?: 'User'];
    }

    public function updateEmployee($employeeId, array $data)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {

            $employeeModel = new EmployeeModel();
            $positionModel = new EmployeePositionModel();
            $govModel      = new EmployeeGovernmentIdModel();

            $status = isset($data['status'])
                ? (int) $data['status']
                : 1;

            /*
            |--------------------------------------------------------------------------
            | Schedule Data
            |--------------------------------------------------------------------------
            */

            $scheduleData = [];

            if (isset($data['start_time']) && trim((string) $data['start_time']) !== '') {
                $scheduleData['start_time'] = trim((string) $data['start_time']);
            }

            if (isset($data['end_time']) && trim((string) $data['end_time']) !== '') {
                $scheduleData['end_time'] = trim((string) $data['end_time']);
            }

            if (isset($data['tardiness_grace_minutes'])) {
                $scheduleData['tardiness_grace_minutes'] = $this->parseGraceMinutes($data['tardiness_grace_minutes']);
            }

            if (isset($data['ot_grace_minutes'])) {
                $scheduleData['ot_grace_minutes'] = $this->parseGraceMinutes($data['ot_grace_minutes']);
            }

            if (isset($data['working_day_type']) && trim((string) $data['working_day_type']) !== '') {
                $submittedType = trim((string) $data['working_day_type']);
                if (! in_array($submittedType, self::KNOWN_WORKING_DAY_TYPES, true)) {
                    throw new \Exception('Invalid working day type.');
                }
                $scheduleData['working_day_type'] = $submittedType;
            }

            if (isset($data['working_days'])) {

                $workingDays = array_values(array_filter(array_map(
                    static fn ($day) => strtolower(trim((string) $day)),
                    (array) $data['working_days']
                )));

                if ($workingDays !== []) {
                    $scheduleData['working_days'] = json_encode($workingDays);
                }
            }

            if (isset($data['break_schedule']) && trim((string) $data['break_schedule']) !== '') {
                $parsedBreaks = $this->parseBreakSchedule($data['break_schedule']);
                $scheduleData['break_schedule'] = json_encode($parsedBreaks);
                // break_windows is only ever submitted alongside break_schedule
                // (same form, same JS sync), so it's parsed here too, aligned
                // to the same slot count. Absent field = leave every slot
                // Flexible (an empty array is still valid JSON for "none").
                $scheduleData['break_windows'] = json_encode($this->parseBreakWindows($data['break_windows'] ?? null, count($parsedBreaks)));
            }

            /*
            |--------------------------------------------------------------------------
            | Schedule Validation
            |--------------------------------------------------------------------------
            */

            if (
                isset($scheduleData['start_time'], $scheduleData['end_time'])
                && $scheduleData['start_time'] === $scheduleData['end_time']
            ) {
                throw new \InvalidArgumentException(
                    'Start and end time must differ. An earlier end time means the following day.'
                );
            }

            if (! in_array($status, [0, 1], true)) {
                throw new \InvalidArgumentException(
                    'Invalid account status.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Find Employee
            |--------------------------------------------------------------------------
            */

            $employee = $employeeModel->find($employeeId);

            if (! $employee) {
                throw new \RuntimeException('Employee not found.');
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Self Disable
            |--------------------------------------------------------------------------
            */

            $currentUser   = auth()->user();
            $currentUserId = (int) ($currentUser->id ?? 0);

            if (
                $status === 0
                && ! empty($employee['user_id'])
                && (int) $employee['user_id'] === $currentUserId
            ) {
                throw new \RuntimeException(
                    'You cannot deactivate your own account while logged in.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Update Employee Basic Info
            |--------------------------------------------------------------------------
            */

            $employeeModel->update($employeeId, [
                'first_name'   => $data['first_name']   ?? null,
                'middle_name'  => $data['middle_name']  ?? null,
                'last_name'    => $data['last_name']    ?? null,
                'birthdate'    => $data['dob']          ?? null,
                'gender'       => $data['gender']       ?? null,
                'civil_status' => $data['civil_status'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update User Status
            |--------------------------------------------------------------------------
            */

            if (! empty($employee['user_id'])) {

                $this->userModel->update($employee['user_id'], [
                    'active' => $status,
                    'status' => $status === 1
                        ? 'active'
                        : 'inactive',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Shield Email
                |--------------------------------------------------------------------------
                */

                if (! empty($data['email'])) {

                    $db->table('auth_identities')
                        ->where('user_id', $employee['user_id'])
                        ->where('type', 'email_password')
                        ->update([
                            'secret' => trim((string) $data['email'])
                        ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Government IDs
            |--------------------------------------------------------------------------
            */

            $existingGov = $govModel
                ->where('employee_id', $employeeId)
                ->first();

            $govData = [
                'sss' => $data['sss'] ?? null,
                'tin' => $data['tin'] ?? null,
                'pagibig' => $data['pagibig'] ?? null,
                'philhealth' => $data['philhealth'] ?? null,
            ];

            if ($existingGov) {

                $govModel
                    ->where('employee_id', $employeeId)
                    ->set($govData)
                    ->update();

            } else {

                $govModel->insert([
                    'employee_id' => $employeeId,
                    ...$govData
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Update Position / Schedule
            |--------------------------------------------------------------------------
            */

            $existingPosition = $positionModel
                ->where('employee_id', $employeeId)
                ->first();

            $resolvedDepartment = $this->resolveDepartment(
                $data,
                $existingPosition['department'] ?? null,
                isset($existingPosition['department_id']) ? (int) $existingPosition['department_id'] : null
            );

            $positionData = [
                'department'         => $resolvedDepartment['department'],
                'department_id'      => $resolvedDepartment['department_id'],
                'position'           => $data['position']        ?? null,
                'employment_type'    => $data['employment_type'] ?? null,
                'monthly_salary'     => isset($data['monthly_salary'])     && $data['monthly_salary']     !== '' ? (float) $data['monthly_salary']     : null,
                'de_minimis_benefit' => isset($data['de_minimis_benefit']) && $data['de_minimis_benefit'] !== '' ? (float) $data['de_minimis_benefit'] : 0,
                'de_minimis_breakdown' => isset($data['de_minimis_breakdown']) && $data['de_minimis_breakdown'] !== '' ? $data['de_minimis_breakdown'] : null,
                // Separate, additive set of BIR categories — never merged
                // into de_minimis_benefit/_breakdown above.
                'additional_de_minimis_benefit' => isset($data['additional_de_minimis_benefit']) && $data['additional_de_minimis_benefit'] !== '' ? (float) $data['additional_de_minimis_benefit'] : 0,
                'additional_de_minimis_breakdown' => isset($data['additional_de_minimis_breakdown']) && $data['additional_de_minimis_breakdown'] !== '' ? $data['additional_de_minimis_breakdown'] : null,
                'allowances'         => isset($data['allowances'])         && $data['allowances']         !== '' ? (float) $data['allowances']         : 0,
            ] + $scheduleData;

            // Compensation fields are already scrubbed to their existing values by
            // PortalAccess::employeeInput() before $data reaches this method (its
            // only caller, EmployeeController::updateEmployee()) — this is the
            // single place that guard is enforced, so it isn't repeated here.

            if ($existingPosition) {

                $positionModel
                    ->where('employee_id', $employeeId)
                    ->set($positionData)
                    ->update();

            } else {

                $positionModel->insert([
                    'employee_id' => $employeeId,
                    ...$positionData
                ]);

                if ($positionModel->errors()) {
                    throw new \Exception(
                        json_encode($positionModel->errors())
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Employee Accounts (contact + bank)
            |--------------------------------------------------------------------------
            */

            $accountFields = [
                'mobile_number'  => $data['mobile_number']  ?? null,
                'address'        => $data['address']        ?? null,
                'bank'           => $data['bank']           ?? null,
                'bank_address'   => $data['bank_address']   ?? null,
                'account_number' => $data['account_number'] ?? null,
                'swift_code'     => $data['swift_code']     ?? null,
            ];

            $existingAccount = $db->table('employee_accounts')
                ->where('employee_id', $employeeId)
                ->get()->getRowArray();

            if ($existingAccount) {
                $db->table('employee_accounts')
                    ->where('employee_id', $employeeId)
                    ->update($accountFields);
            } else {
                $db->table('employee_accounts')
                    ->insert(array_merge(['employee_id' => $employeeId], $accountFields));
            }

            /*
            |--------------------------------------------------------------------------
            | Update Leave Balance
            |--------------------------------------------------------------------------
            */

            if (
                isset($data['leave_total'])
                && $data['leave_total'] !== ''
            ) {

                $leaveTotal = (float) $data['leave_total'];

                if ($leaveTotal < 0) {
                    throw new \InvalidArgumentException(
                        'Leave total cannot be negative.'
                    );
                }

                $this->leaveBalanceModel
                    ->setEntitlement(
                        (int) $employeeId,
                        $leaveTotal
                    );
            }

            // Per-type leave entitlements
            foreach (['vacation', 'sick', 'emergency'] as $lt) {
                $key = $lt . '_leave';
                if (isset($data[$key]) && $data[$key] !== '') {
                    $this->leaveBalanceModel->setLeaveTypeEntitlement((int) $employeeId, $lt, (float) $data[$key]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $db->transCommit();

            return true;

        } catch (\Throwable $e) {

            $db->transRollback();

            throw $e;
        }
    }
}
