<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;

/**
 * Assign a portal role (Shield group) to an existing login — the piece that
 * was missing: a role could only ever be picked once, at registration time
 * (ClientRegisterController), with no way to change it afterward. Also
 * covers which specific employees a Team Head can review (team_access_
 * assignments existed in the schema already but nothing ever wrote to it,
 * so every Team Head account could review no one), and role-permission
 * edits via CodeIgniter's Settings library (Shield's own can() already
 * resolves the group matrix through setting('AuthGroups.matrix'), so this
 * needs no changes to PortalAccess.php at all).
 */
class AccessControlController extends BaseController
{
    /** The only groups this app's portal role model actually uses — not
     *  Shield's generic 'user'/'beta'/'developer' groups, which don't map
     *  to anything in PortalAccess. Same list ClientRegisterController
     *  already validates against. */
    private const ASSIGNABLE_ROLES = ['employee', 'client_payroll_staff', 'hr_admin', 'team_head', 'admin', 'superadmin'];

    /** Superadmin is deliberately excluded — PortalAccess::can() bypasses
     *  the matrix entirely for it (inGroup('superadmin') short-circuit), so
     *  editing its permissions here would have no effect and could look
     *  misleadingly like it did. */
    private const EDITABLE_ROLES = ['employee', 'client_payroll_staff', 'hr_admin', 'team_head', 'admin'];

    /** Every real, independently-checked portal.* permission in the app —
     *  confirmed by cross-referencing every PortalAccess::can() call site
     *  (both literal and via requirement()'s dynamic default case) against
     *  AuthGroups::$permissions. Deliberately excludes requirement()
     *  buckets like 'self'/'edit_employee'/'attendance_roster'/
     *  'review_attendance'/'review_request' — those are compound logic
     *  (ownership/team-membership checks), not standalone permissions, so
     *  they can't be meaningfully toggled here. */
    private const KNOWN_PERMISSIONS = [
        'employees', 'compensation', 'payroll', 'release', 'loans', 'requests',
        'request_codes', 'attendance', 'announcements', 'help', 'branding',
        'roles', 'team', 'face_recognition',
    ];

    /**
     * Data for the Access Control tab inside Employee Management (embedded
     * via EmployeeController::employeeView(), same pattern as the Face
     * Recognition / Loan Management tabs — not a standalone page/route.
     */
    public static function getAccountsData(): array
    {
        $db = \Config\Database::connect();

        $rows = $db->table('users u')
            ->select("
                u.id AS user_id,
                u.username,
                MAX(ai.secret) AS email,
                GROUP_CONCAT(DISTINCT agu.group) AS all_groups,
                MAX(e.first_name) AS first_name,
                MAX(e.last_name) AS last_name
            ", false)
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left')
            ->join('auth_groups_users agu', 'agu.user_id = u.id', 'left')
            ->join('employees e', 'e.user_id = u.id', 'left')
            ->where('u.active', 1)
            ->groupBy('u.id')
            ->orderBy('u.username', 'ASC')
            ->get()
            ->getResultArray();

        $groupLabels = config('AuthGroups')->groups;

        $accounts = array_map(function ($row) use ($groupLabels) {
            $groups = $row['all_groups'] ? explode(',', $row['all_groups']) : [];
            // null (not a default of 'employee') when the account has no
            // recognized portal role at all — several real accounts here
            // have zero group rows, and silently showing "Employee" for
            // those would look like someone had already deliberately set
            // it, hiding that nothing has actually been assigned yet.
            $currentRole = null;
            foreach (self::ASSIGNABLE_ROLES as $role) {
                if (in_array($role, $groups, true)) {
                    $currentRole = $role;
                    break;
                }
            }

            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

            return [
                'user_id'      => (int) $row['user_id'],
                'name'         => $name !== '' ? $name : $row['username'],
                'email'        => $row['email'],
                'current_role' => $currentRole,
            ];
        }, $rows);

        $roleOptions = [];
        foreach (self::ASSIGNABLE_ROLES as $role) {
            $roleOptions[$role] = $groupLabels[$role]['title'] ?? ucfirst($role);
        }

        // Every employee, for the "which employees can this Team Head
        // review?" picker — keyed by employee_id, since team_access_
        // assignments stores employee_id (PortalAccess::teamUserIds()
        // joins back to users.id from there).
        $employeeRows = $db->table('employees')
            ->select('id, first_name, last_name')
            ->orderBy('first_name', 'ASC')
            ->get()->getResultArray();
        $employees = array_map(fn ($e) => [
            'employee_id' => (int) $e['id'],
            'name'        => trim($e['first_name'] . ' ' . $e['last_name']),
        ], $employeeRows);

        // Current assignments per team_head_user_id, so the UI can
        // pre-check whichever employees are already assigned to each.
        $assignmentRows = $db->table('team_access_assignments')
            ->select('team_head_user_id, employee_id')
            ->get()->getResultArray();
        $teamAssignments = [];
        foreach ($assignmentRows as $row) {
            $teamAssignments[(int) $row['team_head_user_id']][] = (int) $row['employee_id'];
        }

        return [
            'accounts'        => $accounts,
            'roleOptions'     => $roleOptions,
            'employees'       => $employees,
            'teamAssignments' => $teamAssignments,
            'permissions'     => self::getRolePermissions(),
            'permissionLabels'=> self::permissionLabels(),
        ];
    }

    public function updateRole()
    {
        $targetUserId = (int) $this->request->getPost('user_id');
        $newRole      = (string) $this->request->getPost('role');

        if (! $targetUserId || ! in_array($newRole, self::ASSIGNABLE_ROLES, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request.']);
        }

        $currentUser = auth()->user();
        if ($targetUserId === (int) $currentUser->id) {
            return $this->response->setJSON(['success' => false, 'message' => 'You cannot change your own role.']);
        }

        $targetUser = auth()->getProvider()->findById($targetUserId);
        if (! $targetUser) {
            return $this->response->setJSON(['success' => false, 'message' => 'Account not found.']);
        }

        $logger = service('activityLogger');
        $wasTeamHead = $targetUser->inGroup('team_head');

        foreach (self::ASSIGNABLE_ROLES as $role) {
            if ($targetUser->inGroup($role)) {
                $targetUser->removeGroup($role);
                $logger->roleRemoved($targetUserId, $targetUser->username, $role);
            }
        }
        $targetUser->addGroup($newRole);
        $logger->roleAssigned($targetUserId, $targetUser->username, $newRole);

        // A demoted Team Head's old assignments would otherwise sit stale
        // in team_access_assignments and silently reappear if they're ever
        // re-promoted later.
        if ($wasTeamHead && $newRole !== 'team_head') {
            \Config\Database::connect()->table('team_access_assignments')
                ->where('team_head_user_id', $targetUserId)
                ->delete();
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Role updated.']);
    }

    /**
     * Sets exactly which employees a Team Head is allowed to review — the
     * piece team_access_assignments was missing entirely; without this, a
     * Team Head account has no assigned employees and can review no one.
     */
    public function updateTeamAssignments()
    {
        $teamHeadUserId = (int) $this->request->getPost('user_id');
        $employeeIds    = array_map('intval', (array) $this->request->getPost('employee_ids'));

        if (! $teamHeadUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request.']);
        }

        $targetUser = auth()->getProvider()->findById($teamHeadUserId);
        if (! $targetUser || ! $targetUser->inGroup('team_head')) {
            return $this->response->setJSON(['success' => false, 'message' => 'That account is not a Team Head.']);
        }

        // Validate every submitted employee_id is real, rather than trusting
        // the request body — same defense-in-depth reasoning as the
        // permission-key whitelist below.
        $db = \Config\Database::connect();
        $validEmployeeIds = [];
        if ($employeeIds !== []) {
            $validEmployeeIds = array_map('intval', array_column(
                $db->table('employees')->select('id')->whereIn('id', array_unique($employeeIds))->get()->getResultArray(),
                'id'
            ));
        }

        $db->transStart();

        $db->table('team_access_assignments')->where('team_head_user_id', $teamHeadUserId)->delete();

        if ($validEmployeeIds !== []) {
            $currentUser = auth()->user();
            $rows = array_map(fn ($employeeId) => [
                'team_head_user_id' => $teamHeadUserId,
                'employee_id'       => $employeeId,
                'assigned_by'       => (int) $currentUser->id,
                'created_at'        => date('Y-m-d H:i:s'),
            ], $validEmployeeIds);
            $db->table('team_access_assignments')->insertBatch($rows);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to save assignments.']);
        }

        service('activityLogger')->log(
            'updated',
            'team_access_assignments',
            "Team Head assignments updated for {$targetUser->username}: " . count($validEmployeeIds) . ' employee(s)',
            $teamHeadUserId,
            null,
            ['employee_ids' => $validEmployeeIds]
        );

        return $this->response->setJSON(['success' => true, 'message' => 'Assigned employees updated.']);
    }

    /**
     * Current permission grants per editable role, resolved exactly the way
     * Shield's Authorizable::can() does — via setting('AuthGroups.matrix'),
     * which transparently falls back to the AuthGroups config file default
     * until an override has actually been saved. No new table, no changes
     * to PortalAccess.php: this is Shield's own already-tested override
     * mechanism, not a parallel system.
     */
    private static function getRolePermissions(): array
    {
        $matrix = service('settings')->get('AuthGroups.matrix') ?? config('AuthGroups')->matrix;

        $result = [];
        foreach (self::EDITABLE_ROLES as $role) {
            $granted = $matrix[$role] ?? [];
            $result[$role] = [];
            foreach (self::KNOWN_PERMISSIONS as $permission) {
                $result[$role][$permission] = in_array('portal.' . $permission, $granted, true);
            }
        }
        return $result;
    }

    private static function permissionLabels(): array
    {
        $configured = config('AuthGroups')->permissions;
        $labels = [];
        foreach (self::KNOWN_PERMISSIONS as $permission) {
            $labels[$permission] = ucwords(str_replace('_', ' ', $permission));
        }
        return $labels;
    }

    /**
     * Saves one role's permission checkboxes. Reads the FULL current matrix
     * (every group, not just the 5 editable ones) and replaces only that
     * one role's entry before writing the whole thing back — setting()->set()
     * replaces the entire 'matrix' property, so a partial write would
     * silently wipe out every other role's (including superadmin's, and
     * Shield's own developer/user/beta groups') permissions.
     */
    public function updateRolePermissions()
    {
        $role = (string) $this->request->getPost('role');
        $submittedPermissions = (array) $this->request->getPost('permissions');

        if (! in_array($role, self::EDITABLE_ROLES, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid role.']);
        }

        // Whitelist against KNOWN_PERMISSIONS — never trust the request body
        // to decide what a "valid" permission key looks like.
        $grantedPermissions = array_values(array_intersect(self::KNOWN_PERMISSIONS, $submittedPermissions));

        $settings = service('settings');
        $matrix = $settings->get('AuthGroups.matrix') ?? config('AuthGroups')->matrix;

        $before = $matrix[$role] ?? [];
        $matrix[$role] = array_map(fn ($p) => 'portal.' . $p, $grantedPermissions);

        $settings->set('AuthGroups.matrix', $matrix);

        service('activityLogger')->log(
            'updated',
            'auth_groups_permissions',
            "Permissions updated for role '{$role}'",
            null,
            ['permissions' => $before],
            ['permissions' => $matrix[$role]]
        );

        return $this->response->setJSON(['success' => true, 'message' => 'Permissions updated.']);
    }
}
