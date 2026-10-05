<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Libraries\PortalAccess;

/**
 * Access Control tab inside Employee Management (Admin/Super Admin only):
 * assigning portal roles (multi-role — an account can hold several at once),
 * Departments (a normalized replacement for free-text department names),
 * which Team Head(s) cover each department, and role-permission edits via
 * CodeIgniter's Settings library.
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

    /** Every real, independently-checked portal.* permission in the app. */
    private const KNOWN_PERMISSIONS = [
        'employees', 'compensation', 'payroll', 'release', 'loans', 'requests',
        'request_codes', 'attendance', 'announcements', 'help', 'branding',
        'roles', 'team', 'face_recognition',
    ];

    private function guardManager()
    {
        if (! PortalAccess::canManageAccessControl()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only Admin and Super Admin can manage access control.']);
        }
        return null;
    }

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

        // Priority order for "which one role to show" wherever the rest of the
        // app displays a single role/badge for a multi-role account.
        $priorityOrder = ['superadmin', 'admin', 'hr_admin', 'team_head', 'client_payroll_staff', 'employee'];

        $accounts = array_map(function ($row) use ($groupLabels, $priorityOrder) {
            $allGroups = $row['all_groups'] ? explode(',', $row['all_groups']) : [];
            $roles = array_values(array_intersect(self::ASSIGNABLE_ROLES, $allGroups));

            $primaryRole = null;
            foreach ($priorityOrder as $role) {
                if (in_array($role, $roles, true)) {
                    $primaryRole = $role;
                    break;
                }
            }

            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

            return [
                'user_id'      => (int) $row['user_id'],
                'name'         => $name !== '' ? $name : $row['username'],
                'email'        => $row['email'],
                'roles'        => $roles,
                'primary_role' => $primaryRole,
            ];
        }, $rows);

        $roleOptions = [];
        $roleDescriptions = [];
        foreach (self::ASSIGNABLE_ROLES as $role) {
            $roleOptions[$role] = $groupLabels[$role]['title'] ?? ucfirst($role);
            $roleDescriptions[$role] = $groupLabels[$role]['description'] ?? '';
        }

        // Departments (active only, for the picker) — id/name plus how many
        // employees currently sit in each, so an admin can see at a glance
        // before deactivating one.
        // MAX(d.name) not a bare d.name: MariaDB's ONLY_FULL_GROUP_BY rejects
        // a bare column even from the table being grouped by its own PK —
        // see the identical fix already applied throughout EmployeeController.
        $departmentRows = $db->table('departments d')
            ->select('d.id, MAX(d.name) AS name, COUNT(ep.id) AS employee_count', false)
            ->join('employee_positions ep', 'ep.department_id = d.id', 'left')
            ->where('d.is_active', 1)
            ->where('d.deleted_at', null)
            ->groupBy('d.id')
            ->orderBy('d.name', 'ASC')
            ->get()->getResultArray();
        // Names of who's actually in each department — so an admin can see
        // exactly who's affected before deactivating one or assigning Team
        // Heads, not just a count.
        $employeeNameRows = $db->table('employee_positions ep')
            ->select("ep.department_id, e.id AS employee_id, CONCAT(e.first_name, ' ', e.last_name) AS name", false)
            ->join('employees e', 'e.id = ep.employee_id')
            ->where('ep.department_id IS NOT NULL', null, false)
            ->orderBy('e.first_name', 'ASC')
            ->get()->getResultArray();
        $employeesByDepartment = [];
        foreach ($employeeNameRows as $row) {
            $employeesByDepartment[(int) $row['department_id']][] = ['id' => (int) $row['employee_id'], 'name' => trim($row['name'])];
        }

        $departments = array_map(fn ($d) => [
            'id' => (int) $d['id'], 'name' => $d['name'], 'employee_count' => (int) $d['employee_count'],
            'employees' => $employeesByDepartment[(int) $d['id']] ?? [],
        ], $departmentRows);

        // Which accounts currently hold the team_head role — the pool a
        // department's Team Head picker chooses from.
        $teamHeadAccounts = array_values(array_filter($accounts, fn ($a) => in_array('team_head', $a['roles'], true)));

        // Current department -> [team_head_user_id, ...] assignments.
        $deptTeamHeadRows = $db->table('department_team_heads')->select('department_id, team_head_user_id')->get()->getResultArray();
        $departmentTeamHeads = [];
        foreach ($deptTeamHeadRows as $row) {
            $departmentTeamHeads[(int) $row['department_id']][] = (int) $row['team_head_user_id'];
        }

        $networkSettingsModel = new \App\Models\Client\PayrollSettingsModel();
        $exemptIds = json_decode((string) ($networkSettingsModel->getSetting('attendance_network_exempt_ids') ?? ''), true);

        return [
            'accounts'            => $accounts,
            'roleOptions'         => $roleOptions,
            'roleDescriptions'    => $roleDescriptions,
            'departments'         => $departments,
            'teamHeadAccounts'    => array_map(fn ($a) => ['user_id' => $a['user_id'], 'name' => $a['name']], $teamHeadAccounts),
            'departmentTeamHeads' => $departmentTeamHeads,
            'permissions'         => self::getRolePermissions(),
            'permissionLabels'    => self::permissionLabels(),
            'attendanceDetectedIp'       => service('request')->getIPAddress(),
            // A forwarding header with no trusted proxy configured means the
            // detected address is the proxy's, not the visitor's — every
            // employee would look like the same IP, so the check is meaningless.
            'attendanceProxyWarning'     => (service('request')->hasHeader('X-Forwarded-For') || service('request')->hasHeader('CF-Connecting-IP'))
                                            && empty(config('App')->proxyIPs),
            'attendanceAllowedIps'       => (string) ($networkSettingsModel->getSetting('attendance_allowed_ips') ?? ''),
            'attendanceNetworkExemptIds' => is_array($exemptIds) ? array_map('intval', $exemptIds) : [],
        ];
    }

    /**
     * Sets exactly which roles an account holds — a diff against its current
     * groups, not a wholesale replace, so an account can hold several roles
     * at once (e.g. hr_admin + team_head). 'employee' stays mutually
     * exclusive with every staff role on both sides of the diff.
     */
    public function updateRoles()
    {
        if ($denied = $this->guardManager()) return $denied;

        $targetUserId = (int) $this->request->getPost('user_id');
        $submitted    = array_values(array_unique(array_intersect(self::ASSIGNABLE_ROLES, (array) $this->request->getPost('roles'))));

        if (! $targetUserId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request.']);
        }

        $currentUser = auth()->user();
        if ($targetUserId === (int) $currentUser->id) {
            return $this->response->setJSON(['success' => false, 'message' => 'You cannot change your own roles.']);
        }

        $targetUser = auth()->getProvider()->findById($targetUserId);
        if (! $targetUser) {
            return $this->response->setJSON(['success' => false, 'message' => 'Account not found.']);
        }

        // Employee is mutually exclusive with every staff role — if any staff
        // role was submitted, employee is dropped even if it was also checked.
        $staffRoles = array_diff($submitted, ['employee']);
        if ($staffRoles !== []) {
            $submitted = array_values($staffRoles);
        }

        $currentRoles = array_values(array_intersect(self::ASSIGNABLE_ROLES, $targetUser->getGroups()));

        // Super Admin can change anyone. Admin can change everyone EXCEPT a
        // Super Admin account — neither removing that role nor granting it —
        // so the Super Admin tier can only ever be touched by another Super Admin.
        $actorIsSuperadmin = $currentUser->inGroup('superadmin');
        $targetIsSuperadmin = in_array('superadmin', $currentRoles, true);
        $wouldAddSuperadmin = in_array('superadmin', $submitted, true);
        if (! $actorIsSuperadmin && ($targetIsSuperadmin || $wouldAddSuperadmin)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only Super Admin can change a Super Admin account.']);
        }

        $toAdd    = array_diff($submitted, $currentRoles);
        $toRemove = array_diff($currentRoles, $submitted);

        if ($toAdd === [] && $toRemove === []) {
            return $this->response->setJSON(['success' => true, 'message' => 'No changes.']);
        }

        $logger = service('activityLogger');
        $wasTeamHead = in_array('team_head', $currentRoles, true);

        foreach ($toRemove as $role) {
            $targetUser->removeGroup($role);
            $logger->roleRemoved($targetUserId, $targetUser->username, $role);
        }
        foreach ($toAdd as $role) {
            $targetUser->addGroup($role);
            $logger->roleAssigned($targetUserId, $targetUser->username, $role);
        }

        // A demoted Team Head's department headships would otherwise sit
        // stale and silently reappear if they're ever re-promoted later.
        if ($wasTeamHead && ! in_array('team_head', $submitted, true)) {
            \Config\Database::connect()->table('department_team_heads')
                ->where('team_head_user_id', $targetUserId)
                ->delete();
        }

        $summary = [];
        if ($toAdd !== [])    $summary[] = 'added ' . implode(', ', $toAdd);
        if ($toRemove !== []) $summary[] = 'removed ' . implode(', ', $toRemove);

        return $this->response->setJSON(['success' => true, 'message' => 'Roles updated (' . implode('; ', $summary) . ').']);
    }

    public function createDepartment()
    {
        if ($denied = $this->guardManager()) return $denied;

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Department name is required.']);
        }

        $db = \Config\Database::connect();
        $existing = $db->table('departments')->where('name', $name)->where('deleted_at', null)->get()->getRowArray();
        if ($existing) {
            return $this->response->setJSON(['success' => false, 'message' => 'A department with that name already exists.']);
        }

        $id = $db->table('departments')->insert([
            'name' => $name, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ], true);

        service('activityLogger')->log('created', 'departments', "Department '{$name}' created", $db->insertID(), null, ['name' => $name]);

        return $this->response->setJSON(['success' => true, 'message' => 'Department added.', 'id' => (int) $db->insertID()]);
    }

    public function renameDepartment()
    {
        if ($denied = $this->guardManager()) return $denied;

        $id   = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        if (! $id || $name === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request.']);
        }

        $db = \Config\Database::connect();
        $dept = $db->table('departments')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        if (! $dept) {
            return $this->response->setJSON(['success' => false, 'message' => 'Department not found.']);
        }

        $dupe = $db->table('departments')->where('name', $name)->where('id !=', $id)->where('deleted_at', null)->get()->getRowArray();
        if ($dupe) {
            return $this->response->setJSON(['success' => false, 'message' => 'A department with that name already exists.']);
        }

        // Employees and department-targeted announcements keep a copy of the
        // department NAME as text (matched by name for announcements), so the
        // rename has to reach them too or they stop matching the department.
        // All-or-nothing so a half-renamed state can't be left behind.
        $db->transBegin();
        $db->table('departments')->where('id', $id)->update(['name' => $name, 'updated_at' => date('Y-m-d H:i:s')]);
        $db->table('employee_positions')->where('department_id', $id)->update(['department' => $name]);
        $db->table('announcements')->where('department', $dept['name'])->update(['department' => $name]);
        if (! $db->transStatus()) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Could not rename the department. Please try again.']);
        }
        $db->transCommit();

        service('activityLogger')->log('updated', 'departments', "Department renamed", $id, ['name' => $dept['name']], ['name' => $name]);

        return $this->response->setJSON(['success' => true, 'message' => 'Department renamed.']);
    }

    /** Soft-deactivate — never a hard delete, so employee records already
     *  pointed at this department never dangle. */
    public function deactivateDepartment()
    {
        if ($denied = $this->guardManager()) return $denied;

        $id = (int) $this->request->getPost('id');
        if (! $id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request.']);
        }

        $db = \Config\Database::connect();
        $dept = $db->table('departments')->where('id', $id)->get()->getRowArray();
        if (! $dept) {
            return $this->response->setJSON(['success' => false, 'message' => 'Department not found.']);
        }

        $db->table('departments')->where('id', $id)->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        $db->table('department_team_heads')->where('department_id', $id)->delete();

        service('activityLogger')->log('updated', 'departments', "Department '{$dept['name']}' deactivated", $id, ['is_active' => 1], ['is_active' => 0]);

        return $this->response->setJSON(['success' => true, 'message' => 'Department deactivated.']);
    }

    /**
     * Sets exactly which accounts are Team Head for one department — replaces
     * the old per-employee picker. Every submitted account must currently
     * hold the team_head role.
     */
    public function updateDepartmentTeamHeads()
    {
        if ($denied = $this->guardManager()) return $denied;

        $departmentId = (int) $this->request->getPost('department_id');
        $userIds      = array_map('intval', (array) $this->request->getPost('team_head_user_ids'));

        if (! $departmentId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request.']);
        }

        $db = \Config\Database::connect();
        $dept = $db->table('departments')->where('id', $departmentId)->where('is_active', 1)->where('deleted_at', null)->get()->getRowArray();
        if (! $dept) {
            return $this->response->setJSON(['success' => false, 'message' => 'Department not found.']);
        }

        $validUserIds = [];
        if ($userIds !== []) {
            $validUserIds = array_map('intval', array_column(
                $db->table('auth_groups_users')->select('user_id')->where('group', 'team_head')->whereIn('user_id', array_unique($userIds))->get()->getResultArray(),
                'user_id'
            ));
        }

        $db->transStart();
        $db->table('department_team_heads')->where('department_id', $departmentId)->delete();
        if ($validUserIds !== []) {
            $currentUser = auth()->user();
            $rows = array_map(fn ($uid) => [
                'department_id' => $departmentId, 'team_head_user_id' => $uid,
                'assigned_by' => (int) $currentUser->id, 'created_at' => date('Y-m-d H:i:s'),
            ], $validUserIds);
            $db->table('department_team_heads')->insertBatch($rows);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to save Team Heads.']);
        }

        service('activityLogger')->log(
            'updated', 'department_team_heads',
            "Team Heads updated for department '{$dept['name']}': " . count($validUserIds) . ' account(s)',
            $departmentId, null, ['team_head_user_ids' => $validUserIds]
        );

        return $this->response->setJSON(['success' => true, 'message' => 'Team Heads updated.']);
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
        if ($denied = $this->guardManager()) return $denied;

        $role = (string) $this->request->getPost('role');
        $submittedPermissions = (array) $this->request->getPost('permissions');

        if (! in_array($role, self::EDITABLE_ROLES, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid role.']);
        }

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

    /**
     * Onsite clock-ins are restricted to this IP/CIDR allowlist (see
     * AttendanceSessionService::enforceOnsiteNetwork()); WFH days bypass it
     * entirely, and accounts in the exempt list bypass it on any day. An
     * empty allowlist enforces nothing (fail-open) — turning this on is
     * opt-in so a misconfiguration can't lock out every onsite employee.
     */
    public function updateAttendanceNetworkSettings()
    {
        if ($denied = $this->guardManager()) return $denied;

        $ranges = \App\Libraries\IpAllowlist::parse((string) $this->request->getPost('allowed_ips'));
        foreach ($ranges as $range) {
            if (! \App\Libraries\IpAllowlist::isValid($range)) {
                return $this->response->setJSON(['success' => false, 'message' => "'{$range}' isn't a valid IP address or CIDR range."]);
            }
        }

        $exemptIds = array_values(array_unique(array_map('intval', (array) $this->request->getPost('exempt_user_ids'))));

        $model = new \App\Models\Client\PayrollSettingsModel();
        $model->setSetting('attendance_allowed_ips', implode("\n", $ranges));
        $model->setSetting('attendance_network_exempt_ids', json_encode($exemptIds));

        service('activityLogger')->log(
            'updated', 'attendance_network_settings',
            'Attendance network restriction settings updated',
            null, null, ['allowed_ips' => $ranges, 'exempt_user_ids' => $exemptIds]
        );

        return $this->response->setJSON(['success' => true, 'message' => 'Attendance network settings saved.']);
    }
}
