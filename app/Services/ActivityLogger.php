<?php

namespace App\Services;

use App\Models\ActivityLogModel;

/**
 * ActivityLogger
 *
 * Central service for recording auditable events across all modules.
 * Call via the CI4 service locator: service('activityLogger')
 *
 * Modules (mirrors table names):
 *   attendance, tasks, saved_tasks, requests,
 *   employees, employee_accounts, employee_government_ids, employee_positions,
 *   payroll, users, auth_groups_users, wtax_brackets
 */
class ActivityLogger
{
    protected ActivityLogModel $model;

    public function __construct()
    {
        $this->model = new ActivityLogModel();
    }

    // =========================================================================
    // Core log method
    // =========================================================================

    /**
     * Write a single activity log entry.
     *
     * @param  string     $action      'created' | 'updated' | 'deleted'
     * @param  string     $module      Table name the event relates to
     * @param  string     $description Human-readable summary
     * @param  int|null   $recordId    PK of the affected row
     * @param  array|null $oldValues   State before the change (updates/deletes)
     * @param  array|null $newValues   State after the change (creates/updates)
     */
    public function log(
        string  $action,
        string  $module,
        string  $description,
        ?int    $recordId  = null,
        ?array  $oldValues = null,
        ?array  $newValues = null
    ): bool {
        $user = auth()->user();

        return (bool) $this->model->insert([
            'user_id'     => $user->id,
            'username'    => $user->username,
            'action'      => $action,
            'module'      => $module,
            'record_id'   => $recordId,
            'description' => $description,
            'old_values'  => $oldValues !== null ? json_encode($oldValues) : null,
            'new_values'  => $newValues !== null ? json_encode($newValues) : null,
            'ip_address'  => service('request')->getIPAddress(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // Generic convenience wrappers
    // =========================================================================

    public function created(string $module, string $description, int $recordId, array $newValues): bool
    {
        return $this->log('created', $module, $description, $recordId, null, $newValues);
    }

    public function updated(string $module, string $description, int $recordId, array $oldValues, array $newValues): bool
    {
        return $this->log('updated', $module, $description, $recordId, $oldValues, $newValues);
    }

    public function deleted(string $module, string $description, int $recordId, array $oldValues): bool
    {
        return $this->log('deleted', $module, $description, $recordId, $oldValues, null);
    }

    // =========================================================================
    // ATTENDANCE
    // =========================================================================

    /**
     * Employee clocked in — creates a new attendance row.
     */
    public function attendanceClockedIn(int $attendanceId, array $data): bool
    {
        return $this->created('attendance', 'Clocked in', $attendanceId, [
            'date'    => $data['date'],
            'time_in' => $data['time_in'],
        ]);
    }

    /**
     * Employee clocked out — updates time_out and total_hours.
     */
    public function attendanceClockedOut(int $attendanceId, array $before, array $after): bool
    {
        return $this->updated('attendance', 'Clocked out', $attendanceId,
            ['time_out' => $before['time_out'], 'total_hours' => $before['total_hours']],
            ['time_out' => $after['time_out'],  'total_hours' => $after['total_hours']]
        );
    }

    /**
     * Break start/end recorded.
     */
    public function attendanceBreakRecorded(int $attendanceId, array $before, array $after): bool
    {
        return $this->updated('attendance', 'Break recorded', $attendanceId,
            $this->pick($before, ['break_start', 'break_end', 'break_duration']),
            $this->pick($after,  ['break_start', 'break_end', 'break_duration'])
        );
    }

    /**
     * Admin manually edits an attendance entry.
     */
    public function attendanceManualEdit(int $attendanceId, array $before, array $after): bool
    {
        return $this->updated('attendance', 'Attendance entry manually edited', $attendanceId,
            $before, $after
        );
    }

    /**
     * Attendance entry deleted.
     */
    public function attendanceDeleted(int $attendanceId, array $row): bool
    {
        return $this->deleted('attendance', 'Attendance entry deleted', $attendanceId, $row);
    }

    // =========================================================================
    // TASKS
    // =========================================================================

    public function taskLogged(int $taskId, int $attendanceId, array $data): bool
    {
        return $this->created('tasks', "Task logged for attendance #{$attendanceId}", $taskId, [
            'start_time'     => $data['start_time'],
            'end_time'       => $data['end_time'],
            'task_performed' => $data['task_performed'],
        ]);
    }

    public function taskUpdated(int $taskId, array $before, array $after): bool
    {
        return $this->updated('tasks', 'Task entry updated', $taskId,
            $this->pick($before, ['start_time', 'end_time', 'task_performed']),
            $this->pick($after,  ['start_time', 'end_time', 'task_performed'])
        );
    }

    public function taskDeleted(int $taskId, array $row): bool
    {
        return $this->deleted('tasks', 'Task entry deleted', $taskId,
            $this->pick($row, ['attendance_id', 'start_time', 'end_time', 'task_performed'])
        );
    }

    // =========================================================================
    // SAVED TASKS
    // =========================================================================

    public function savedTaskCreated(int $savedTaskId, string $taskPerformed): bool
    {
        return $this->created('saved_tasks', 'Task template saved', $savedTaskId, [
            'task_performed' => $taskPerformed,
        ]);
    }

    public function savedTaskDeleted(int $savedTaskId, string $taskPerformed): bool
    {
        return $this->deleted('saved_tasks', 'Task template deleted', $savedTaskId, [
            'task_performed' => $taskPerformed,
        ]);
    }

    // =========================================================================
    // REQUESTS (overtime, undertime, leave — all share this table)
    // =========================================================================

    /**
     * A new request was filed.
     * Pass in the full new row including duration and approval_code once available.
     */
    public function requestFiled(int $requestId, array $data): bool
    {
        $newValues = $this->pick($data, [
            'request_type', 'start_date', 'end_date', 'reason',
            'duration',      // include once column exists
            'approval_code', // include once column exists
        ]);

        $label = ucfirst(strtolower($data['request_type'] ?? 'Request'));

        return $this->created('requests', "{$label} request filed", $requestId, $newValues);
    }

    /**
     * Request status changed (approved / rejected / any other transition).
     */
    public function requestStatusChanged(int $requestId, string $oldStatus, string $newStatus, int $processedBy): bool
    {
        $label = ucfirst(strtolower($newStatus));

        return $this->updated('requests', "Request {$label}", $requestId,
            ['status' => $oldStatus, 'processed_by' => null],
            ['status' => $newStatus, 'processed_by' => $processedBy]
        );
    }

    /**
     * Approval code generated for a request.
     */
    public function requestApprovalCodeGenerated(int $requestId): bool
    {
        return $this->updated('requests', "Approval code generated for request #{$requestId}", $requestId,
            ['approval_code' => null],
            ['approval_code' => '[generated]'] // don't log the raw code value
        );
    }

    /**
     * Approval code was consumed/used.
     */
    public function requestApprovalCodeUsed(int $requestId): bool
    {
        return $this->updated('requests', "Approval code used for request #{$requestId}", $requestId,
            ['approval_code' => '[active]'],
            ['approval_code' => '[consumed]']
        );
    }

    /**
     * Request was cancelled (treated as a soft delete / logical removal).
     */
    public function requestCancelled(int $requestId, array $row): bool
    {
        return $this->deleted('requests', 'Request cancelled', $requestId,
            $this->pick($row, ['request_type', 'start_date', 'end_date', 'status'])
        );
    }

    // =========================================================================
    // EMPLOYEES
    // =========================================================================

    public function employeeCreated(int $employeeId, array $data): bool
    {
        $name = trim("{$data['first_name']} {$data['last_name']}");

        return $this->created('employees', "Employee record created: {$name}", $employeeId,
            $this->pick($data, ['employee_number', 'first_name', 'middle_name', 'last_name', 'birthdate'])
        );
    }

    public function employeeUpdated(int $employeeId, array $before, array $after): bool
    {
        $diff = $this->diff($before, $after, ['first_name', 'middle_name', 'last_name', 'birthdate']);

        return $this->updated('employees', 'Employee core info updated', $employeeId,
            $diff['old'], $diff['new']
        );
    }

    public function employeeDeleted(int $employeeId, array $row): bool
    {
        $name = trim("{$row['first_name']} {$row['last_name']}");

        return $this->deleted('employees', "Employee record deleted: {$name}", $employeeId, $row);
    }

    // =========================================================================
    // EMPLOYEE ACCOUNTS
    // =========================================================================

    public function employeeAccountCreated(int $accountId, array $data): bool
    {
        return $this->created('employee_accounts', 'Employee account profile created', $accountId,
            // Omit photo_path — just note it was set if present
            $this->pick($data, ['secondary_email', 'mobile_number', 'address'])
        );
    }

    public function employeeContactUpdated(int $accountId, array $before, array $after): bool
    {
        $fields = ['secondary_email', 'mobile_number', 'address'];
        $diff   = $this->diff($before, $after, $fields);

        return $this->updated('employee_accounts', 'Employee contact info updated', $accountId,
            $diff['old'], $diff['new']
        );
    }

    public function employeeEmergencyContactUpdated(int $accountId, array $before, array $after): bool
    {
        $fields = ['emergency_name', 'emergency_relationship', 'emergency_phone_primary', 'emergency_phone_alternate'];
        $diff   = $this->diff($before, $after, $fields);

        return $this->updated('employee_accounts', 'Emergency contact updated', $accountId,
            $diff['old'], $diff['new']
        );
    }

    /**
     * Photo update — only record that it changed, not the path itself.
     */
    public function employeePhotoUpdated(int $accountId): bool
    {
        return $this->updated('employee_accounts', 'Profile photo updated', $accountId,
            ['photo_path' => '[previous]'],
            ['photo_path' => '[updated]']
        );
    }

    // =========================================================================
    // EMPLOYEE GOVERNMENT IDs
    // =========================================================================

    public function governmentIdsCreated(int $govIdId, int $employeeId, array $data): bool
    {
        return $this->created('employee_government_ids',
            "Government IDs record created for employee #{$employeeId}",
            $govIdId,
            [
                'sss'        => $this->mask($data['sss']        ?? ''),
                'tin'        => $this->mask($data['tin']        ?? ''),
                'pagibig'    => $this->mask($data['pagibig']    ?? ''),
                'philhealth' => $this->mask($data['philhealth'] ?? ''),
            ]
        );
    }

    public function governmentIdsUpdated(int $govIdId, array $before, array $after): bool
    {
        $fields  = ['sss', 'tin', 'pagibig', 'philhealth'];
        $diff    = $this->diff($before, $after, $fields);

        // Mask sensitive values before storing
        $maskFn = fn(array $vals) => array_map([$this, 'mask'], $vals);

        return $this->updated('employee_government_ids', 'Government ID updated', $govIdId,
            $maskFn($diff['old']),
            $maskFn($diff['new'])
        );
    }

    // =========================================================================
    // EMPLOYEE POSITIONS
    // =========================================================================

    public function positionCreated(int $positionId, array $data): bool
    {
        return $this->created('employee_positions',
            "Position assigned: {$data['position']} at {$data['department']}",
            $positionId,
            $this->pick($data, ['department', 'position', 'employment_type'])
        );
    }

    public function positionUpdated(int $positionId, array $before, array $after): bool
    {
        $diff = $this->diff($before, $after, ['department', 'position', 'employment_type']);

        return $this->updated('employee_positions', 'Employee position updated', $positionId,
            $diff['old'], $diff['new']
        );
    }

    // =========================================================================
    // PAYROLL
    // =========================================================================

    public function payrollCreated(int $payrollId, array $data): bool
    {
        return $this->created('payroll', "Payroll record created for {$data['month_year']}", $payrollId,
            $this->pick($data, ['month_year', 'daily_rate', 'currency', 'dst'])
        );
    }

    public function payrollRateUpdated(int $payrollId, string $oldRate, string $newRate): bool
    {
        return $this->updated('payroll', 'Daily rate updated', $payrollId,
            ['daily_rate' => $oldRate],
            ['daily_rate' => $newRate]
        );
    }

    public function payrollStatusChanged(int $payrollId, string $oldStatus, string $newStatus): bool
    {
        return $this->updated('payroll', "Payroll status changed to {$newStatus}", $payrollId,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    public function payrollDeleted(int $payrollId, array $row): bool
    {
        return $this->deleted('payroll', "Payroll record deleted for {$row['month_year']}", $payrollId,
            $this->pick($row, ['month_year', 'daily_rate', 'currency', 'status'])
        );
    }

    public function payslipGenerated(int $payslipId, string $employeeName, string $periodStart, string $periodEnd, float $netPay): bool
    {
        return $this->created('employee_payslips',
            "Payslip generated for {$employeeName} ({$periodStart} – {$periodEnd})",
            $payslipId,
            [
                'employee_name' => $employeeName,
                'period_start'  => $periodStart,
                'period_end'    => $periodEnd,
                'net_pay'       => $netPay,
            ]
        );
    }

    public function payslipSent(int $payslipId, string $employeeName, string $periodStart, string $periodEnd, string $emailAddress): bool
    {
        return $this->updated('employee_payslips',
            "Payslip sent to {$employeeName} ({$periodStart} – {$periodEnd})",
            $payslipId,
            ['sent_at' => null],
            ['sent_at' => date('Y-m-d H:i:s'), 'sent_to' => $emailAddress]
        );
    }

    // =========================================================================
    // USERS
    // =========================================================================

    public function userActivated(int $userId, string $username): bool
    {
        return $this->updated('users', "User account activated: {$username}", $userId,
            ['active' => 0],
            ['active' => 1]
        );
    }

    public function userDeactivated(int $userId, string $username): bool
    {
        return $this->updated('users', "User account deactivated: {$username}", $userId,
            ['active' => 1],
            ['active' => 0]
        );
    }

    public function userStatusChanged(int $userId, array $before, array $after): bool
    {
        $diff = $this->diff($before, $after, ['status', 'status_message']);

        return $this->updated('users', 'User status updated', $userId,
            $diff['old'], $diff['new']
        );
    }

    // =========================================================================
    // ROLES (auth_groups_users)
    // =========================================================================

    public function roleAssigned(int $targetUserId, string $targetUsername, string $group): bool
    {
        // The actor is the currently authenticated admin;
        // the subject is the user receiving the role.
        return $this->log('created', 'auth_groups_users',
            "Role '{$group}' assigned to {$targetUsername}",
            $targetUserId,
            null,
            ['user_id' => $targetUserId, 'group' => $group]
        );
    }

    public function roleRemoved(int $targetUserId, string $targetUsername, string $group): bool
    {
        return $this->log('deleted', 'auth_groups_users',
            "Role '{$group}' removed from {$targetUsername}",
            $targetUserId,
            ['user_id' => $targetUserId, 'group' => $group],
            null
        );
    }

    // =========================================================================
    // WITHHOLDING TAX BRACKETS
    // =========================================================================

    public function wtaxBracketCreated(int $bracketId, array $data): bool
    {
        return $this->created('wtax_brackets', 'Tax bracket created', $bracketId,
            $this->pick($data, ['min_amount', 'max_amount', 'flat', 'percentage'])
        );
    }

    public function wtaxBracketUpdated(int $bracketId, array $before, array $after): bool
    {
        $diff = $this->diff($before, $after, ['min_amount', 'max_amount', 'flat', 'percentage']);

        return $this->updated('wtax_brackets', 'Tax bracket updated', $bracketId,
            $diff['old'], $diff['new']
        );
    }

    public function wtaxBracketDeleted(int $bracketId, array $row): bool
    {
        return $this->deleted('wtax_brackets', 'Tax bracket deleted', $bracketId,
            $this->pick($row, ['min_amount', 'max_amount', 'flat', 'percentage'])
        );
    }

    // =========================================================================
    // ANNOUNCEMENTS
    // =========================================================================

    public function announcementPublished(int $announcementId, array $data): bool
    {
        $label = ucfirst($data['category'] ?? 'announcement');
        $audience = $data['audience'] === 'department' && !empty($data['department'])
            ? "department: {$data['department']}"
            : $data['audience'];

        return $this->created(
            'announcements',
            "{$label} announcement published for {$audience}",
            $announcementId,
            $this->pick($data, [
                'title', 'category', 'audience', 'department',
                'priority', 'publish_at', 'expires_at', 'require_acknowledgement',
            ])
        );
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Pick only the specified keys from an array.
     * Silently skips keys that don't exist (handles missing columns like
     * `duration` and `approval_code` gracefully).
     */
    private function pick(array $data, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $data[$key];
            }
        }
        return $result;
    }

    /**
     * Return only the keys that actually changed between two arrays,
     * split into 'old' and 'new' sub-arrays.
     *
     * @param  array $before
     * @param  array $after
     * @param  array $watchKeys  Limit comparison to these keys
     * @return array{old: array, new: array}
     */
    private function diff(array $before, array $after, array $watchKeys = []): array
    {
        $old = [];
        $new = [];

        $keys = $watchKeys ?: array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($keys as $key) {
            $prev = $before[$key] ?? null;
            $curr = $after[$key]  ?? null;

            if ($prev != $curr) {
                $old[$key] = $prev;
                $new[$key] = $curr;
            }
        }

        return ['old' => $old, 'new' => $new];
    }

    /**
     * Mask a sensitive ID number, showing only the last 3 characters.
     * e.g. "34-1234567-8" → "***-***-***-8"
     * Leaves empty/null values untouched.
     */
    private function mask(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $visible = 3;
        $len     = strlen($value);

        if ($len <= $visible) {
            return $value;
        }

        return str_repeat('*', $len - $visible) . substr($value, -$visible);
    }
}