<?php
declare(strict_types=1);

namespace App\Libraries;

/** Shared by server-side filters, query scopes and navigation. */
final class PortalAccess
{
    public static function can(string $permission, ?object $user = null): bool
    {
        $user ??= auth()->user();
        return $user !== null && ($user->inGroup('superadmin') || $user->can('portal.' . $permission));
    }

    public static function reviewsRequests(?object $user = null): bool
    {
        return self::can('requests', $user) || self::can('team', $user);
    }

    public static function reviewsAttendance(?object $user = null): bool
    {
        return self::can('attendance', $user) || self::can('team', $user);
    }

    /** Explicit assignments, never a guessed relationship based on a department/job title. */
    public static function teamUserIds(?object $user = null): array
    {
        $user ??= auth()->user();
        if (!$user || !self::can('team', $user)) return [];
        $db = db_connect();
        if (!$db->tableExists('team_access_assignments')) return [];
        return array_map('intval', array_column($db->table('team_access_assignments a')
            ->select('e.user_id')->join('employees e', 'e.id=a.employee_id')
            ->where('a.team_head_user_id', (int) $user->id)->get()->getResultArray(), 'user_id'));
    }

    public static function canReviewEmployee(int $employeeId, ?object $user = null): bool
    {
        $user ??= auth()->user();
        if (!$user) return false;
        if (self::can('attendance', $user)) return true;
        $row = db_connect()->table('employees')->select('user_id')->where('id', $employeeId)->get()->getRowArray();
        return $row && in_array((int) $row['user_id'], self::teamUserIds($user), true);
    }

    public static function canReviewRequest(int $requestId, ?object $user = null): bool
    {
        $user ??= auth()->user();
        if (!$user) return false;
        if (self::can('requests', $user)) return true;
        $row = db_connect()->table('requests')->select('requested_by')->where('id', $requestId)->get()->getRowArray();
        return $row && (int) $row['requested_by'] !== (int) $user->id
            && in_array((int) $row['requested_by'], self::teamUserIds($user), true);
    }

    public static function scopeRequests($builder, string $column = 'requests.requested_by')
    {
        if (self::can('requests')) return $builder;
        return $builder->whereIn($column, array_values(array_unique([(int) auth()->id(), ...self::teamUserIds()])));
    }

    public static function hideCompensation(array $row): array
    {
        if (self::can('compensation')) return $row;
        foreach (['monthly_salary', 'de_minimis_benefit', 'de_minimis_breakdown', 'allowances', 'salary', 'daily_rate', 'net_pay'] as $key) unset($row[$key]);
        return $row;
    }

    public static function canEditEmployee(int $employeeId): bool
    {
        if (!self::can('employees')) return false;
        if (self::can('roles')) return true;
        $row = db_connect()->table('employees')->select('user_id')->where('id', $employeeId)->get()->getRowArray();
        if (!$row) return false;
        // Updating a login email could otherwise take over a more privileged account.
        $groups = db_connect()->table('auth_groups_users')->select('group')->where('user_id', $row['user_id'])->get()->getResultArray();
        $hasPermissions = db_connect()->table('auth_permissions_users')->where('user_id', $row['user_id'])->countAllResults() > 0;
        return !$hasPermissions && !array_diff(array_column($groups, 'group'), ['employee', 'user', 'beta']);
    }

    public static function employeeInput(array $data, ?int $employeeId = null): array
    {
        if (self::can('compensation')) return $data;
        $existing = $employeeId ? db_connect()->table('employee_positions')->where('employee_id', $employeeId)->get()->getRowArray() : [];
        foreach (['monthly_salary', 'de_minimis_benefit', 'de_minimis_breakdown', 'allowances'] as $key) {
            $data[$key] = $existing[$key] ?? null;
        }
        return $data;
    }

    /** Unknown portal endpoints fail closed. Existing self-service controllers retain ownership checks. */
    public static function requirement(string $path): string
    {
        $path = strtolower(trim($path, '/'));
        if ($path === 'register') return 'register';
        if (preg_match('~^client_portal/employees/update/[0-9]+$~', $path)) return 'edit_employee';
        if ($path === 'client_portal/employees/import') return 'compensation';
        if ($path === 'client_portal/timesheet/review-employees') return 'attendance_roster';
        if (preg_match('~^client_portal/employees(?:/|$)~', $path)) return 'employees';
        if (preg_match('~^client_portal/loans(?:/|$)~', $path)) return 'loans';
        if (preg_match('~^client_portal/payroll/(?:getmypayslips)$~', $path)) return 'self';
        if (preg_match('~^client_portal/payroll/(?:downloadpayslip|getpayslipdata)/[0-9]+$~', $path)) return 'self';
        if (preg_match('~^client_portal/payroll/(?:markstatus|send(?:/.*)?|sendpdf|sendstatic)$~', $path)) return 'release';
        if (preg_match('~^client_portal/payroll(?:/|$)~', $path)) return 'payroll';
        if (preg_match('~^client_portal/timesheet/employee(?:/|$)~', $path)) return 'review_attendance';
        if (preg_match('~^client_portal/requests/update/[0-9]+$~', $path)) return 'review_request';
        if ($path === 'client_portal/requests/generate-code') return 'request_codes';
        if (preg_match('~^client_portal/announcements/(?:store|delete/[0-9]+)$~', $path)) return 'announcements';
        if (preg_match('~^client_portal/help/(?:update/[0-9]+)$~', $path)) return 'help';
        if (preg_match('~^settings/(?:customization(?:/save)?|face-recognition(?:/(?:enroll|status))?)$~', $path)) return 'self';
        if (preg_match('~^client_portal/(?:dashboard|profile(?:/(?:photo|identity|contact|username|emergency|password|payslip-password(?:/reset)?))?|presence/(?:heartbeat|statuses)|help(?:/store)?|company-logo|time-in|time-out/[0-9]+|toggle-break/[0-9]+|requests(?:/(?:new|store|lookup-code))?|request-notifications/(?:viewed|dismiss)|announcements/(?:viewed|dismiss)|tasks/(?:submit|saved)|timesheets/daily-log|timesheet(?:/(?:history-details|export-history|tasks/[0-9]+(?:/create)?|tasks/(?:edit|delete)/[0-9]+|update-attendance/[0-9]+))?)$~', $path)) return 'self';
        return 'deny';
    }
}
