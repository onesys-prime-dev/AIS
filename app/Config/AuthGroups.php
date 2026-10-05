<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /** Shared by 'admin' and 'client_payroll_staff' in $matrix so the two lists can't silently drift apart. */
    private const PORTAL_PAYROLL_PERMISSIONS = [
        'portal.employees', 'portal.compensation', 'portal.payroll', 'portal.release', 'portal.loans',
        'portal.requests', 'portal.request_codes', 'portal.attendance', 'portal.announcements', 'portal.help', 'portal.branding',
    ];

    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'user';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'hr_admin' => [
            'title' => 'HR Admin', 
            'description' => 'Employee records, attendance and requests.'
        ],
        'team_head' => ['title' => 'Team Head', 
            'description' => 'Attendance and requests for explicitly assigned employees.'
        ],
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Complete control of the site.',
        ],
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Day to day administrators of the site.',
        ],
        'developer' => [
            'title'       => 'Developer',
            'description' => 'Site programmers.',
        ],
        'user' => [
            'title'       => 'User',
            'description' => 'General users of the site. Often customers.',
        ],
        'beta' => [
            'title'       => 'Beta User',
            'description' => 'Has access to beta-level features.',
        ],
        'client_payroll_staff' => [
            'title' => 'Client Payroll Staff',
            'description' => 'Client payroll managers and payroll processors'
        ],
        'employee' => [
            'title' => 'Employee',
            'description' => 'Employees who can view payslips and payroll records'
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'portal.employees' => 'employees access',
        'portal.compensation' => 'compensation access',
        'portal.payroll' => 'payroll access',
        'portal.release' => 'release access',
        'portal.loans' => 'loans access',
        'portal.requests' => 'requests access',
        'portal.request_codes' => 'request_codes access',
        'portal.attendance' => 'attendance access',
        'portal.announcements' => 'announcements access',
        'portal.help' => 'help access',
        'portal.branding' => 'branding access',
        'portal.roles' => 'roles access',
        'portal.team' => 'team access',
        'portal.face_recognition' => 'face recognition management access',

        'admin.access'        => 'Can access the sites admin area',
        'admin.settings'      => 'Can access the main site settings',
        'users.manage-admins' => 'Can manage other admins',
        'users.create'        => 'Can create new non-admin users',
        'users.edit'          => 'Can edit existing non-admin users',
        'users.delete'        => 'Can delete existing non-admin users',
        'beta.access'         => 'Can access beta-level features',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'superadmin' => [
            'portal.*',
            'admin.*',
            'users.*',
            'beta.*',
        ],
        'admin' => [
            ...self::PORTAL_PAYROLL_PERMISSIONS,
            // Not part of PORTAL_PAYROLL_PERMISSIONS (shared with
            // client_payroll_staff) — face enrollment is deliberately
            // restricted to superadmin/admin only.
            'portal.face_recognition',
            'admin.access',
            'users.create',
            'users.edit',
            'users.delete',
            'beta.access',
        ],
        'developer' => [
            'admin.access',
            'admin.settings',
            'users.create',
            'users.edit',
            'beta.access',
        ],
        'user' => [],
        'beta' => [
            'beta.access',
        ],
        'employee' => [
            /* 'employee.view',
            'employee.edit', */
        ],
        'client_payroll_staff' => self::PORTAL_PAYROLL_PERMISSIONS,
        'hr_admin' => ['portal.employees', 'portal.requests', 'portal.request_codes', 'portal.attendance', 'portal.announcements'],
        'team_head' => ['portal.team'],
    ];
}
