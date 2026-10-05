<?= $this->extend('layouts/main') ?>

<?php if (\App\Libraries\PortalAccess::can('face_recognition')): ?>
<?= $this->section('styles') ?>
<link href="<?= base_url('css/face-scan.css') ?>" rel="stylesheet">
<?= $this->endSection() ?>
<?php endif; ?>

<?= $this->section('content') ?>

<div id="employeePortalRoot" class="h-full flex flex-col min-h-0 overflow-visible min-w-0">

    <div class="tabs">
        <button type="button" class="tab active" data-tab="employee-management">Employee Management</button>
        <?php if (\App\Libraries\PortalAccess::canManageAccessControl()): ?><button type="button" class="tab" data-tab="access-control">Access Control</button><?php endif; ?>
        <?php if (\App\Libraries\PortalAccess::can('face_recognition')): ?><button type="button" class="tab" data-tab="face-recognition">Face Recognition</button><?php endif; ?>
        <?php if (\App\Libraries\PortalAccess::can('loans')): ?><button type="button" class="tab" data-tab="loan-management">Loan Management</button><?php endif; ?>
    </div>

    <div class="employee-tab-panel tab-panel h-full min-h-0 overflow-visible px-8 pb-8 min-w-0"
        data-panel="employee-management"
        style="display:flex;">
        <?= view('client_portal/employee_management') ?>
    </div>

    <div class="employee-tab-panel tab-panel h-full min-h-0 overflow-visible px-8 pb-8 min-w-0" data-panel="access-control" style="display:none;">
        <?php if (\App\Libraries\PortalAccess::canManageAccessControl()): ?><?= view('client_portal/employee_access_control') ?><?php endif; ?>
    </div>

    <div class="employee-tab-panel tab-panel h-full min-h-0 overflow-visible px-8 pb-8 min-w-0" data-panel="face-recognition" style="display:none;">
        <?php if (\App\Libraries\PortalAccess::can('face_recognition')): ?><?= view('client_portal/employee_face_recognition') ?><?php endif; ?>
    </div>

    <div class="employee-tab-panel tab-panel h-full min-h-0 overflow-visible px-8 pb-8 min-w-0" data-panel="loan-management" style="display:none;">
        <?php if (\App\Libraries\PortalAccess::can('loans')): ?><?= view('client_portal/employee_loan') ?><?php endif; ?>
    </div>


</div>

<style>
    @media (max-width: 900px) {
        #employeePortalRoot {
            overflow-y: auto;
        }

        #employeePortalRoot .employee-tab-panel {
            height: auto;
            padding: 0 12px 16px;
        }
    }
</style>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <?php
    $content = view('client_portal/modals/add_employee_form', ['activeDepartments' => $activeDepartments ?? [], 'employmentTypeOptions' => $employmentTypeOptions ?? []]);
    echo view('components/add_employee_modal', [
        'id' => 'employeeModal',
        'title' => 'Employee Management',
        'content' => $content
    ]);
    ?>

    <?php
    $content = view('client_portal/modals/edit_employee_form', ['activeDepartments' => $activeDepartments ?? [], 'employmentTypeOptions' => $employmentTypeOptions ?? []]);
    echo view('components/edit_employee_modal', [
        'id' => 'editEmployeeModal',
        'title' => 'Employee Management',
        'content' => $content
    ]);
    ?>

    <?php
    $content = '
    <div id="employeeDetailsContent" class="w-full relative">
    </div>
    ';

    echo view('components/view_employee_modal', [
        'id' => 'viewEmployeeModal',
        'title' => 'Employee Details',
        'content' => $content
    ]);
    ?>

    <?php
    if (\App\Libraries\PortalAccess::can('face_recognition')) {
    $content = view('client_portal/modals/face_recognition_form');

    echo view('components/edit_loan_modal', [
        'id' => 'faceRecognitionModal',
        'title' => 'Face Recognition Enrollment',
        'content' => $content
    ]);
    }
    ?>

    <?php
    if (\App\Libraries\PortalAccess::can('loans')) {
    $content = view('client_portal/modals/loan_settings');

    echo view('components/loan_settings_modal', [
        'id' => 'loanSettingsModal',
        'title' => 'Loan Settings',
        'content' => $content
    ]);
    ?>

    <?php
    $content = view('client_portal/modals/add_loan_form');

    echo view('components/add_loan_modal', [
        'id' => 'addLoanModal',
        'title' => 'Add Employee Loan',
        'content' => $content
    ]);
    ?>

    <?php
    $content = view('client_portal/modals/edit_loan_form');
    echo view('components/edit_loan_modal', [ 
        'id' => 'editLoanModal',
        'title' => 'Edit Employee Loan',
        'content' => $content
    ]);
    ?>

    <?php
    $content = '
    <div id="loanDetailsContent" class="w-full relative">
    </div>
    ';

    echo view('components/view_loan_modal', [
        'id' => 'viewLoanModal',
        'title' => 'Loan Details',
        'content' => $content
    ]);
    }
    ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
<script src="<?= base_url('js/components/crud-actions.js') ?>"></script>
<script src="<?= base_url('js/components/crud-table.js') ?>?v=<?= is_file(FCPATH . 'js/components/crud-table.js') ? filemtime(FCPATH . 'js/components/crud-table.js') : time() ?>"></script>
<script src="<?= base_url('js/components/password-toggle.js') ?>"></script>
<?php if (\App\Libraries\PortalAccess::can('face_recognition')): ?>
<script src="<?= base_url('js/components/face-scan.js') ?>"></script>
<?php endif; ?>

<script>

<?php if (!\App\Libraries\PortalAccess::can('compensation')): ?>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[name=monthly_salary], [name=de_minimis_benefit], [name=de_minimis_breakdown], [name=allowances]').forEach(el => { el.disabled = true; el.value = ''; });
    document.getElementById('importEmployeesBtn')?.remove();
    document.querySelectorAll('input[type=file][name=file]').forEach(el => el.disabled = true);
});
<?php endif; ?>
const EMPLOYEES = <?= json_encode($employees) ?>;
const LOANS = <?= json_encode($loans ?? []) ?>;
const FACE_RECOGNITION_EMPLOYEES = <?= json_encode($faceRecognitionEmployees ?? []) ?>;
const ACCESS_CONTROL_ACCOUNTS = <?= json_encode($accessControlAccounts ?? []) ?>;
const ACCESS_CONTROL_ROLE_OPTIONS = <?= json_encode($accessControlRoleOptions ?? []) ?>;
const ACCESS_CONTROL_ROLE_DESCRIPTIONS = <?= json_encode($accessControlRoleDescriptions ?? []) ?>;
const ACCESS_CONTROL_CURRENT_USER_ID = <?= json_encode((int) $currentUserId) ?>;
const ACCESS_CONTROL_DEPARTMENTS = <?= json_encode($accessControlDepartments ?? []) ?>;
const ACCESS_CONTROL_TEAM_HEAD_ACCOUNTS = <?= json_encode($accessControlTeamHeadAccounts ?? []) ?>;
const ACCESS_CONTROL_DEPARTMENT_TEAM_HEADS = <?= json_encode($accessControlDepartmentTeamHeads ?? []) ?>;
const ACCESS_CONTROL_PERMISSIONS = <?= json_encode($accessControlPermissions ?? []) ?>;
const ACCESS_CONTROL_PERMISSION_LABELS = <?= json_encode($accessControlPermissionLabels ?? []) ?>;
const ACCESS_CONTROL_ACTOR_IS_SUPERADMIN = <?= json_encode((bool) ($accessControlActorIsSuperadmin ?? false)) ?>;
const ACCESS_CONTROL_ATTENDANCE_ALLOWED_IPS = <?= json_encode($accessControlAttendanceAllowedIps ?? '') ?>;
const ACCESS_CONTROL_ATTENDANCE_DETECTED_IP = <?= json_encode($accessControlAttendanceDetectedIp ?? '') ?>;
const ACCESS_CONTROL_ATTENDANCE_PROXY_WARNING = <?= json_encode((bool) ($accessControlAttendanceProxyWarning ?? false)) ?>;
const ACCESS_CONTROL_ATTENDANCE_NETWORK_EXEMPT_IDS = <?= json_encode($accessControlAttendanceNetworkExemptIds ?? []) ?>;
const CSRF_HASH = "<?= csrf_hash() ?>";

function acEscapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
}

// Shared across the Accounts and Departments sections below, so giving
// someone the Team Head role in Accounts immediately makes them pickable
// in the Departments section's Team Head list — no page refresh needed.
let acTeamHeadAccounts = ACCESS_CONTROL_TEAM_HEAD_ACCOUNTS.slice();

// ================= ACCOUNTS (multi-role, edit panel) =================
// Clicking "Edit access" swaps the accounts list for a full edit panel: role
// cards on the right of a "Define user role" label, a live "Allow access to"
// summary below, and Update / Cancel at the bottom. Rules and the save request
// are unchanged: same exclusivity (Employee vs staff roles), same locked rows,
// and the POST still sends only user_id + roles[].
(function () {
    const body = document.getElementById('accessControlBody');
    if (!body) return; // panel not rendered (PortalAccess::canManageAccessControl() was false)

    const accounts = ACCESS_CONTROL_ACCOUNTS;
    const roleOptions = ACCESS_CONTROL_ROLE_OPTIONS;
    const roleDescriptions = ACCESS_CONTROL_ROLE_DESCRIPTIONS;

    if (!accounts.length) {
        body.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">No accounts found.</td></tr>';
        return;
    }

    const svg = function (inner) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + inner + '</svg>';
    };
    const ROLE_ICONS = {
        employee: svg('<circle cx="12" cy="8" r="3.6"/><path d="M5 20c0-3.6 3.1-6 7-6s7 2.4 7 6"/>'),
        client_payroll_staff: svg('<rect x="3" y="6" width="18" height="12" rx="2.2"/><circle cx="12" cy="12" r="2.6"/>'),
        hr_admin: svg('<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c0-3 2.4-5 5.5-5s5.5 2 5.5 5"/><circle cx="17" cy="9.5" r="2.4"/><path d="M16.5 14.2c2.6.2 4.5 1.9 4.5 4.3"/>'),
        team_head: svg('<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>'),
        admin: svg('<path d="M12 3 5 6v5c0 4.6 3 8.3 7 10 4-1.7 7-5.4 7-10V6l-7-3Z"/>'),
        superadmin: svg('<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>'),
    };
    const CHECK_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

    function roleBadges(roles) {
        if (!roles.length) return '<span class="text-xs text-red-500">No role assigned</span>';
        return roles.map(function (r) {
            return '<span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium mr-2 mb-1" style="background:#f1eeff;color:#6650cc;">' + acEscapeHtml(roleOptions[r] || r) + '</span>';
        }).join('');
    }

    function sameRoles(a, b) {
        return a.slice().sort().join(',') === b.slice().sort().join(',');
    }

    // ---- list view ----
    function renderTable() {
        body.innerHTML = accounts.map(function (acc) {
            if (acc.user_id === ACCESS_CONTROL_CURRENT_USER_ID) {
                return '<tr>' +
                    '<td class="px-4 py-3">' + acEscapeHtml(acc.name) + '</td>' +
                    '<td class="px-4 py-3">' + acEscapeHtml(acc.email || '—') + '</td>' +
                    '<td class="px-4 py-3 text-gray-400 italic">' + roleBadges(acc.roles) + ' (you)</td>' +
                    '</tr>';
            }

            const isSuperadminLocked = acc.roles.indexOf('superadmin') !== -1 && !ACCESS_CONTROL_ACTOR_IS_SUPERADMIN;
            if (isSuperadminLocked) {
                return '<tr>' +
                    '<td class="px-4 py-3">' + acEscapeHtml(acc.name) + '</td>' +
                    '<td class="px-4 py-3">' + acEscapeHtml(acc.email || '—') + '</td>' +
                    '<td class="px-4 py-3">' + roleBadges(acc.roles) + '<div class="text-xs text-gray-400 italic mt-1">Only Super Admin can change a Super Admin account.</div></td>' +
                    '</tr>';
            }

            return '<tr>' +
                '<td class="px-4 py-3" style="vertical-align:middle;">' + acEscapeHtml(acc.name) + '</td>' +
                '<td class="px-4 py-3" style="vertical-align:middle;">' + acEscapeHtml(acc.email || '—') + '</td>' +
                '<td class="px-4 py-3" style="vertical-align:middle;">' +
                    '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">' +
                        '<div>' + roleBadges(acc.roles) + '</div>' +
                        '<button type="button" class="acr-edit-btn" data-edit-user="' + acc.user_id + '">Edit access</button>' +
                    '</div>' +
                '</td>' +
                '</tr>';
        }).join('');
    }

    renderTable();
    body.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-edit-user]');
        if (!btn) return;
        const acc = accounts.find(function (a) { return String(a.user_id) === btn.dataset.editUser; });
        if (acc) openPanel(acc);
    });

    // ---- edit panel ----
    const listView = document.getElementById('acListView');
    const editView = document.getElementById('acEditView');
    if (!listView || !editView) return;

    const cardsEl = document.getElementById('acpCards');
    const accessEl = document.getElementById('acpAccess');
    const subtitleEl = document.getElementById('acpSubtitle');
    const warnEl = document.getElementById('acpWarn');
    const noteEl = document.getElementById('acpNote');
    const updateBtn = document.getElementById('acpUpdate');
    const cancelBtn = document.getElementById('acpCancel');
    const backBtn = document.getElementById('acpBack');

    let editing = null;       // the account being edited
    let selection = [];       // role keys currently chosen
    let confirming = false;   // second step before granting Admin / Super Admin

    function isOpen() { return editView.style.display !== 'none'; }

    function grantsAdmin() {
        return selection.indexOf('admin') !== -1 || selection.indexOf('superadmin') !== -1;
    }

    function selectableRoles() {
        return Object.keys(roleOptions).filter(function (key) {
            return key !== 'superadmin' || ACCESS_CONTROL_ACTOR_IS_SUPERADMIN;
        });
    }

    // Permissions a single role grants, from the live Role Permissions matrix.
    function rolePermissionKeys(role) {
        const perms = ACCESS_CONTROL_PERMISSIONS[role] || {};
        return Object.keys(perms).filter(function (k) { return perms[k]; });
    }

    function renderCards() {
        cardsEl.innerHTML = selectableRoles().map(function (key) {
            const selected = selection.indexOf(key) !== -1;
            const isCurrent = editing.roles.indexOf(key) !== -1;
            return '<button type="button" class="acp-role' + (selected ? ' is-selected' : '') + '" role="checkbox" aria-checked="' + (selected ? 'true' : 'false') + '" data-role="' + acEscapeHtml(key) + '">' +
                '<div class="acp-role-top"><span class="acp-icon">' + (ROLE_ICONS[key] || ROLE_ICONS.employee) + '</span><span class="acp-check">' + CHECK_ICON + '</span></div>' +
                '<div class="acp-title">' + acEscapeHtml(roleOptions[key] || key) + (isCurrent ? '<span class="acp-current">Current</span>' : '') + '</div>' +
                '<div class="acp-desc">' + acEscapeHtml(roleDescriptions[key] || '') + '</div>' +
            '</button>';
        }).join('');
    }

    // "Allow access to": one row per module, showing whether the CURRENT
    // selection grants it and via which role. Read-only — what each role can
    // do is still edited in the Role Permissions tab.
    function renderAccess() {
        const modules = Object.keys(ACCESS_CONTROL_PERMISSION_LABELS);
        const rows = modules.map(function (key) {
            const via = selection.filter(function (role) {
                return role === 'superadmin' || rolePermissionKeys(role).indexOf(key) !== -1;
            });
            const on = via.length > 0;
            const viaNames = via.map(function (r) { return roleOptions[r] || r; }).join(', ');
            return '<div class="acp-mod' + (on ? ' is-on' : '') + '">' +
                '<span>' + acEscapeHtml(ACCESS_CONTROL_PERMISSION_LABELS[key]) + '</span>' +
                '<span class="acp-status"><span class="acp-dot"></span>' + (on ? 'Allowed <span class="acp-via">via ' + acEscapeHtml(viaNames) + '</span>' : 'No access') + '</span>' +
            '</div>';
        }).join('');
        accessEl.innerHTML = '<div class="acp-access-head"><span>Module</span><span>Access</span></div>' + rows;
    }

    function renderFooter() {
        const changed = !sameRoles(selection, editing.roles);
        if (confirming) {
            warnEl.style.display = 'block';
            warnEl.textContent = 'You are about to give ' + editing.name + ' Admin and/or Super Admin access. They will be able to manage accounts, payroll and settings. Continue?';
            noteEl.textContent = '';
            cancelBtn.textContent = 'Back';
            updateBtn.textContent = 'Yes, grant access';
            updateBtn.disabled = false;
            return;
        }
        warnEl.style.display = grantsAdmin() ? 'block' : 'none';
        if (grantsAdmin()) warnEl.textContent = 'This gives Admin and/or Super Admin access — you will be asked to confirm before it is saved.';
        noteEl.textContent = changed ? 'You have unsaved changes.' : 'No changes yet.';
        cancelBtn.textContent = 'Cancel';
        updateBtn.textContent = 'Update';
        updateBtn.disabled = !changed;
    }

    function refresh() {
        renderCards();
        renderAccess();
        renderFooter();
    }

    function openPanel(acc) {
        editing = acc;
        selection = acc.roles.slice();
        confirming = false;
        subtitleEl.textContent = acc.name + (acc.email ? ' · ' + acc.email : '');
        refresh();
        listView.style.display = 'none';
        editView.style.display = 'block';
        const scroller = editView.closest('[data-ac-panel]');
        if (scroller) scroller.scrollTop = 0;
        const first = cardsEl.querySelector('.acp-role');
        if (first) first.focus();
    }

    function closePanel() {
        editView.style.display = 'none';
        listView.style.display = '';
        editing = null;
        confirming = false;
    }

    cardsEl.addEventListener('click', function (e) {
        const card = e.target.closest('.acp-role');
        if (!card || !editing) return;
        const role = card.dataset.role;
        const nowSelected = selection.indexOf(role) === -1;
        if (role === 'employee' && nowSelected) {
            selection = ['employee'];
        } else if (nowSelected) {
            selection = selection.filter(function (r) { return r !== 'employee'; }).concat(role);
        } else {
            selection = selection.filter(function (r) { return r !== role; });
        }
        confirming = false;
        refresh();
        const again = cardsEl.querySelector('.acp-role[data-role="' + role + '"]');
        if (again) again.focus();
    });

    backBtn.addEventListener('click', closePanel);
    cancelBtn.addEventListener('click', function () {
        if (confirming) { confirming = false; renderFooter(); return; }
        closePanel();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) closePanel();
    });

    updateBtn.addEventListener('click', function () {
        if (!editing || updateBtn.disabled) return;

        if (grantsAdmin() && !confirming) {
            confirming = true;
            renderFooter();
            return;
        }

        const acc = editing;
        const checked = selection.slice();

        updateBtn.disabled = true;
        updateBtn.textContent = 'Saving…';

        const params = new URLSearchParams({ user_id: acc.user_id });
        checked.forEach(function (r) { params.append('roles[]', r); });

        fetch('<?= site_url('client_portal/access-control/update-roles') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_HASH,
            },
            body: params,
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (window.showToast) window.showToast(data.message || (data.success ? 'Roles updated.' : 'Something went wrong.'), data.success ? 'success' : 'error');
                if (data.success) {
                    const wasTeamHead = acc.roles.indexOf('team_head') !== -1;
                    const isTeamHead = checked.indexOf('team_head') !== -1;
                    if (isTeamHead && !wasTeamHead) {
                        acTeamHeadAccounts.push({ user_id: acc.user_id, name: acc.name });
                    } else if (!isTeamHead && wasTeamHead) {
                        acTeamHeadAccounts = acTeamHeadAccounts.filter(function (t) { return t.user_id !== acc.user_id; });
                    }
                    acc.roles = checked;
                    renderTable();
                    closePanel();
                } else {
                    confirming = false;
                    renderFooter();
                }
            })
            .catch(function () {
                if (window.showToast) window.showToast('Something went wrong.', 'error');
                confirming = false;
                renderFooter();
            });
    });
})();

// ================= DEPARTMENTS =================
(function () {
    const container = document.getElementById('departmentsList');
    if (!container) return;

    // Local, mutable copy so add/rename/deactivate can update the UI without a full reload.
    let departments = ACCESS_CONTROL_DEPARTMENTS.slice();
    const deptTeamHeads = Object.assign({}, ACCESS_CONTROL_DEPARTMENT_TEAM_HEADS);

    function post(url, params) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_HASH,
            },
            body: params,
        }).then(function (res) { return res.json(); });
    }

    function render() {
        if (!departments.length) {
            container.innerHTML = '<p class="text-sm text-gray-400 text-center py-6">No departments yet — add one above.</p>';
            return;
        }

        function teamHeadSummary(d) {
            const assigned = deptTeamHeads[d.id] || [];
            if (!assigned.length) return '<span style="color:#f87171;">No Team Head assigned</span>';
            const names = assigned.map(function (uid) {
                const th = acTeamHeadAccounts.find(function (t) { return t.user_id === uid; });
                return th ? acEscapeHtml(th.name) : null;
            }).filter(Boolean);
            return 'Team Head: ' + names.join(', ');
        }

        // Each department is a compact one-line summary by default — the full
        // rename / Team Head / deactivate controls only appear once you click
        // Manage, so having several departments on screen doesn't mean several
        // full edit forms all open at once.
        container.innerHTML = departments.map(function (d) {
            return '<div class="rounded-2xl border border-gray-200 p-4" data-department-id="' + d.id + '">' +
                '<div class="flex items-center justify-between flex-wrap gap-2">' +
                    '<div>' +
                        '<span class="font-medium text-gray-800 dept-name-display">' + acEscapeHtml(d.name) + '</span> ' +
                        '<button type="button" class="dept-toggle-employees-btn text-xs underline" style="color:#9ca3af;">' + d.employee_count + ' employee' + (d.employee_count === 1 ? '' : 's') + '</button>' +
                        '<div class="text-xs text-gray-400 mt-0.5 dept-team-head-summary">' + teamHeadSummary(d) + '</div>' +
                    '</div>' +
                    '<button type="button" class="dept-manage-btn text-xs font-semibold" style="color:#9678f5;">Manage</button>' +
                '</div>' +
                '<div class="dept-employees-list text-xs text-gray-500 mt-2" style="display:none;">' +
                    (d.employees.length ? d.employees.map(function (e) { return acEscapeHtml(e.name); }).join(', ') : 'No employees in this department yet.') +
                '</div>' +
                '<div class="dept-manage-panel mt-3 pt-3 border-t border-gray-100" style="display:none;"></div>' +
                '</div>';
        }).join('');

        function renderManagePanel(d) {
            const assigned = deptTeamHeads[d.id] || [];
            const teamHeadChecks = acTeamHeadAccounts.length
                ? acTeamHeadAccounts.map(function (th) {
                    const checked = assigned.indexOf(th.user_id) !== -1 ? ' checked' : '';
                    return '<label class="flex items-center gap-2 text-sm py-1">' +
                        '<input type="checkbox" class="dth-box" value="' + th.user_id + '"' + checked + '>' +
                        acEscapeHtml(th.name) +
                        '</label>';
                }).join('')
                : '<p class="text-xs text-gray-400 col-span-2">No accounts hold the Team Head role yet.</p>';

            return '<div class="flex items-center gap-2 mb-3">' +
                    '<input type="text" class="dept-name-input border border-gray-300 rounded-lg px-2 py-1 text-sm" value="' + acEscapeHtml(d.name) + '" style="width:200px;">' +
                    '<button type="button" class="dept-rename-btn text-xs font-semibold" style="color:#9678f5;">Save name</button>' +
                    '<button type="button" class="dept-deactivate-btn text-xs ml-auto" style="color:#9ca3af;" onmouseover="this.style.color=\'#dc2626\'" onmouseout="this.style.color=\'#9ca3af\'">Deactivate department</button>' +
                '</div>' +
                '<div class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Team Head(s)</div>' +
                '<div class="grid grid-cols-2 gap-1">' + teamHeadChecks + '</div>' +
                '<div class="flex justify-end mt-2">' +
                    '<button type="button" class="dth-save-btn px-3 py-1 rounded-lg text-xs font-semibold text-white" style="background:#A891FC;" onmouseover="this.style.background=\'#9678f5\'" onmouseout="this.style.background=\'#A891FC\'">Save Team Heads</button>' +
                '</div>';
        }

        container.querySelectorAll('[data-department-id]').forEach(function (card) {
            const id = card.dataset.departmentId;

            card.querySelector('.dept-toggle-employees-btn').addEventListener('click', function () {
                const list = card.querySelector('.dept-employees-list');
                list.style.display = list.style.display === 'none' ? 'block' : 'none';
            });

            const panel = card.querySelector('.dept-manage-panel');
            const manageBtn = card.querySelector('.dept-manage-btn');
            manageBtn.addEventListener('click', function () {
                const dep = departments.find(function (d) { return String(d.id) === String(id); });
                const opening = panel.style.display === 'none';
                panel.style.display = opening ? 'block' : 'none';
                manageBtn.textContent = opening ? 'Close' : 'Manage';
                if (opening) {
                    panel.innerHTML = renderManagePanel(dep);
                    wireManagePanel(card, panel, id);
                }
            });
        });

        function wireManagePanel(card, panel, id) {
            panel.querySelector('.dept-rename-btn').addEventListener('click', function () {
                const btn = this;
                const newName = panel.querySelector('.dept-name-input').value.trim();
                if (!newName) return;
                btn.disabled = true;
                post('<?= site_url('client_portal/access-control/departments/rename') ?>', new URLSearchParams({ id: id, name: newName }))
                    .then(function (data) {
                        if (window.showToast) window.showToast(data.message || (data.success ? 'Renamed.' : 'Something went wrong.'), data.success ? 'success' : 'error');
                        if (data.success) {
                            const dep = departments.find(function (d) { return String(d.id) === String(id); });
                            if (dep) dep.name = newName;
                            card.querySelector('.dept-name-display').textContent = newName;
                        }
                    })
                    .catch(function () { if (window.showToast) window.showToast('Something went wrong.', 'error'); })
                    .finally(function () { btn.disabled = false; });
            });

            panel.querySelector('.dept-deactivate-btn').addEventListener('click', function () {
                const dep = departments.find(function (d) { return String(d.id) === String(id); });
                if (!confirm('Deactivate "' + (dep ? dep.name : 'this department') + '"? Its Team Head assignments will be cleared. Employees already in it keep their record but the department will no longer be selectable.')) return;
                post('<?= site_url('client_portal/access-control/departments/deactivate') ?>', new URLSearchParams({ id: id }))
                    .then(function (data) {
                        if (window.showToast) window.showToast(data.message || (data.success ? 'Deactivated.' : 'Something went wrong.'), data.success ? 'success' : 'error');
                        if (data.success) {
                            departments = departments.filter(function (d) { return String(d.id) !== String(id); });
                            delete deptTeamHeads[id];
                            render();
                        }
                    })
                    .catch(function () { if (window.showToast) window.showToast('Something went wrong.', 'error'); });
            });

            panel.querySelector('.dth-save-btn').addEventListener('click', function () {
                const btn = this;
                const checked = Array.from(panel.querySelectorAll('.dth-box:checked')).map(function (cb) { return cb.value; });
                btn.disabled = true;
                btn.textContent = 'Saving…';

                const params = new URLSearchParams({ department_id: id });
                checked.forEach(function (uid) { params.append('team_head_user_ids[]', uid); });

                post('<?= site_url('client_portal/access-control/departments/team-heads') ?>', params)
                    .then(function (data) {
                        if (window.showToast) window.showToast(data.message || (data.success ? 'Saved.' : 'Something went wrong.'), data.success ? 'success' : 'error');
                        if (data.success) {
                            deptTeamHeads[id] = checked.map(Number);
                            card.querySelector('.dept-team-head-summary').innerHTML = teamHeadSummary({ id: id });
                        }
                    })
                    .catch(function () { if (window.showToast) window.showToast('Something went wrong.', 'error'); })
                    .finally(function () { btn.disabled = false; btn.textContent = 'Save Team Heads'; });
            });
        }
    }

    render();

    const addBtn = document.getElementById('addDepartmentBtn');
    const nameInput = document.getElementById('newDepartmentName');
    if (addBtn && nameInput) {
        addBtn.addEventListener('click', function () {
            const name = nameInput.value.trim();
            if (!name) return;
            addBtn.disabled = true;
            post('<?= site_url('client_portal/access-control/departments/create') ?>', new URLSearchParams({ name: name }))
                .then(function (data) {
                    if (window.showToast) window.showToast(data.message || (data.success ? 'Added.' : 'Something went wrong.'), data.success ? 'success' : 'error');
                    if (data.success) {
                        departments.push({ id: data.id, name: name, employee_count: 0, employees: [] });
                        nameInput.value = '';
                        render();
                    }
                })
                .catch(function () { if (window.showToast) window.showToast('Something went wrong.', 'error'); })
                .finally(function () { addBtn.disabled = false; });
        });
    }
})();

// ================= ROLE PERMISSIONS =================
(function () {
    const section = document.getElementById('rolePermissionsSection');
    const tabsEl = document.getElementById('rolePermissionTabs');
    const bodyEl = document.getElementById('rolePermissionBody');
    if (!section || !tabsEl || !bodyEl) return;

    const permissions = ACCESS_CONTROL_PERMISSIONS; // { role: { permKey: bool, ... }, ... }
    const roles = Object.keys(permissions);
    if (!roles.length) return;
    section.style.display = 'block';

    // Sensitive permissions — toggling these ON for a role gets a confirm() prompt.
    const SENSITIVE_PERMISSIONS = ['roles', 'payroll', 'compensation'];

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }

    let activeRole = roles[0];

    function renderTabs() {
        tabsEl.innerHTML = roles.map(function (role) {
            const active = role === activeRole;
            const label = (ACCESS_CONTROL_ROLE_OPTIONS[role] || role);
            return '<button type="button" data-role="' + escapeHtml(role) + '" class="px-3 py-2 rounded-lg text-sm font-medium"' +
                (active ? ' style="background:#A891FC;color:#fff;"' : ' style="background:#f3f4f6;color:#374151;"') +
                '>' + escapeHtml(label) + '</button>';
        }).join('');

        tabsEl.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeRole = btn.dataset.role;
                renderTabs();
                renderBody();
            });
        });
    }

    function renderBody() {
        const rolePerms = permissions[activeRole] || {};
        const permKeys = Object.keys(rolePerms);

        bodyEl.innerHTML = '<div class="grid grid-cols-2 gap-2" id="rpChecklist">' +
            permKeys.map(function (key) {
                const label = ACCESS_CONTROL_PERMISSION_LABELS[key] || key;
                const checked = rolePerms[key] ? ' checked' : '';
                return '<label class="flex items-center gap-2 text-sm py-1">' +
                    '<input type="checkbox" value="' + escapeHtml(key) + '"' + checked + '>' +
                    escapeHtml(label) +
                    '</label>';
            }).join('') +
            '</div>' +
            '<div class="flex justify-end mt-4">' +
                '<button type="button" id="rpSaveBtn" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#A891FC;" onmouseover="this.style.background=\'#9678f5\'" onmouseout="this.style.background=\'#A891FC\'">Save Permissions</button>' +
            '</div>';

        document.getElementById('rpSaveBtn').addEventListener('click', function () {
            const saveBtn = this;
            const checked = Array.from(bodyEl.querySelectorAll('#rpChecklist input:checked')).map(function (cb) { return cb.value; });

            const newlySensitive = checked.filter(function (p) {
                return SENSITIVE_PERMISSIONS.indexOf(p) !== -1 && !rolePerms[p];
            });
            if (newlySensitive.length && !confirm(
                'Grant ' + newlySensitive.map(function (p) { return ACCESS_CONTROL_PERMISSION_LABELS[p] || p; }).join(', ') +
                ' to every "' + (ACCESS_CONTROL_ROLE_OPTIONS[activeRole] || activeRole) + '" account? This affects everyone currently assigned that role.'
            )) {
                return;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            const params = new URLSearchParams({ role: activeRole });
            checked.forEach(function (p) { params.append('permissions[]', p); });

            fetch('<?= site_url('client_portal/access-control/update-role-permissions') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_HASH,
                },
                body: params,
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (window.showToast) window.showToast(data.message || (data.success ? 'Saved.' : 'Something went wrong.'), data.success ? 'success' : 'error');
                    if (data.success) {
                        permKeys.forEach(function (key) { rolePerms[key] = checked.indexOf(key) !== -1; });
                    }
                })
                .catch(function () {
                    if (window.showToast) window.showToast('Something went wrong.', 'error');
                })
                .finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Permissions';
                });
        });
    }

    renderTabs();
    renderBody();
})();

// ================= ACCESS CONTROL SUB-TABS =================
// Accounts / Team Head Assignments / Role Permissions used to all sit
// stacked in one scroll area, leaving almost no room to actually work with
// the accounts table. Splitting them into sub-tabs (same pill style as the
// Role Permissions tabs) lets whichever one is active use the full height
// of the panel instead of competing for space with the other two.
(function () {
    const tabsWrap = document.getElementById('acSubTabs');
    if (!tabsWrap) return;

    const panels = {
        accounts: document.querySelector('[data-ac-panel="accounts"]'),
        departments: document.getElementById('departmentsSection'),
        permissions: document.getElementById('rolePermissionsSection'),
        network: document.getElementById('attendanceNetworkSection'),
    };
    const buttons = Array.from(tabsWrap.querySelectorAll('[data-ac-tab]'));

    function isEligible(key) {
        // Accounts, Departments and Role Permissions are all always available —
        // unlike the old per-employee Team Head picker, Departments doesn't
        // depend on any data already existing (you add the first one here).
        return !!panels[key];
    }

    buttons.forEach(function (btn) {
        if (!isEligible(btn.dataset.acTab)) {
            btn.style.display = 'none';
        }
    });

    function activate(key) {
        buttons.forEach(function (btn) {
            const active = btn.dataset.acTab === key;
            btn.style.background = active ? '#A891FC' : '#f3f4f6';
            btn.style.color = active ? '#fff' : '#374151';
        });
        Object.keys(panels).forEach(function (k) {
            if (panels[k]) panels[k].style.display = k === key ? 'block' : 'none';
        });
    }

    buttons.forEach(function (btn) {
        btn.style.cssText += ';padding:8px 12px;border-radius:8px;font-size:13px;font-weight:500;';
        btn.addEventListener('click', function () { activate(btn.dataset.acTab); });
    });

    const firstEligible = buttons.find(function (btn) { return isEligible(btn.dataset.acTab); });
    activate(firstEligible ? firstEligible.dataset.acTab : 'accounts');
})();

// ================= ATTENDANCE NETWORK RESTRICTION =================
(function () {
    const chipsWrap = document.getElementById('attendanceIpChips');
    const ipInput = document.getElementById('attendanceIpInput');
    const ipError = document.getElementById('attendanceIpError');
    const exemptList = document.getElementById('attendanceExemptAccountsList');
    const exemptSearch = document.getElementById('attendanceExemptSearch');
    const exemptCount = document.getElementById('attendanceExemptCount');
    const saveBtn = document.getElementById('saveAttendanceNetworkBtn');
    const statusEl = document.getElementById('attendanceNetworkStatus');
    if (!chipsWrap || !ipInput || !exemptList || !saveBtn) return;

    // Quick client-side shape check for IPv4 / IPv6, optionally with a /prefix.
    // The server does the authoritative validation (IpAllowlist::isValid) on save.
    const IPV4_PATTERN = /^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/;
    const IPV6_PATTERN = /^[0-9a-fA-F:.]+(\/\d{1,3})?$/; // '.' allows ::ffff:1.2.3.4
    const IP_PATTERN = { test: (v) => v.includes(':') ? IPV6_PATTERN.test(v) && (v.match(/:/g) || []).length >= 2 : IPV4_PATTERN.test(v) };
    const octetsValid = (ip) => ip.includes(':') ? true : ip.split('/')[0].split('.').every(n => Number(n) <= 255);

    let ranges = IPAllowlist_parse(ACCESS_CONTROL_ATTENDANCE_ALLOWED_IPS);

    function IPAllowlist_parse(raw) {
        return (raw || '').split(/[,\r\n]+/).map(s => s.trim()).filter(Boolean);
    }

    function showIpError(message) {
        ipError.textContent = message;
        ipError.classList.toggle('hidden', !message);
    }

    // An empty list means nothing is enforced (fail-open by design) — make
    // that state obvious instead of looking like a working-but-silent setup.
    function updateEnforcementBadge() {
        const badge = document.getElementById('attendanceEnforcementBadge');
        if (!badge) return;
        const on = ranges.length > 0;
        badge.textContent = on ? `Enforcement ON · ${ranges.length} address${ranges.length === 1 ? '' : 'es'}` : 'Enforcement OFF · list is empty';
        badge.style.background = on ? '#dcfce7' : '#fee2e2';
        badge.style.color = on ? '#166534' : '#991b1b';
    }

    function renderChips() {
        updateEnforcementBadge();
        chipsWrap.querySelectorAll('.attendance-ip-chip').forEach(el => el.remove());
        ranges.forEach((range, i) => {
            const chip = document.createElement('span');
            chip.className = 'attendance-ip-chip inline-flex items-center gap-1 bg-indigo-100 text-indigo-700 text-xs font-semibold px-2 py-1 rounded-full';
            chip.innerHTML = `${htmlEsc(range)} <button type="button" aria-label="Remove" class="text-indigo-400 hover:text-indigo-700" style="line-height:1;">&times;</button>`;
            chip.querySelector('button').addEventListener('click', () => { ranges.splice(i, 1); renderChips(); });
            chipsWrap.insertBefore(chip, ipInput);
        });
    }

    function addRange(raw) {
        const value = raw.trim();
        if (!value) return true;
        if (!IP_PATTERN.test(value) || !octetsValid(value)) {
            showIpError(`"${value}" isn't a valid IP or CIDR range (e.g. 203.0.113.4 or 203.0.113.0/24).`);
            return false;
        }
        if (!ranges.includes(value)) ranges.push(value);
        showIpError('');
        return true;
    }

    // Returns true if everything typed was accepted. Invalid entries stay in
    // the box (with the error shown) instead of being wiped and silently lost.
    function addFromInput() {
        const rejected = IPAllowlist_parse(ipInput.value).filter(v => !addRange(v));
        renderChips();
        ipInput.value = rejected.join(', ');
        return rejected.length === 0;
    }

    ipInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addFromInput();
        } else if (e.key === 'Backspace' && !ipInput.value && ranges.length) {
            ranges.pop();
            renderChips();
        }
    });
    ipInput.addEventListener('blur', addFromInput);
    chipsWrap.addEventListener('click', () => ipInput.focus());

    // Shows the address the server sees for THIS admin, with a one-click add —
    // the reliable way to enter the office address (open this tab from a
    // computer on the office network).
    const detectedEl = document.getElementById('attendanceDetectedIp');
    const addDetectedBtn = document.getElementById('attendanceAddDetectedBtn');
    const detectedIp = ACCESS_CONTROL_ATTENDANCE_DETECTED_IP || '';
    const isLoopback = detectedIp === '' || detectedIp === '::1' || detectedIp.startsWith('127.');
    if (detectedEl) {
        detectedEl.textContent = detectedIp || 'unknown';
        if (isLoopback) {
            const note = document.createElement('div');
            note.className = 'text-xs text-amber-600 mt-1';
            note.textContent = 'This is the server\'s own computer (localhost), not the office network. Open this page from an office computer using the server\'s network address or the live site URL, then it will show the real address.';
            detectedEl.closest('div').parentElement.insertBefore(note, detectedEl.closest('div').nextSibling);
        }
    }
    if (ACCESS_CONTROL_ATTENDANCE_PROXY_WARNING && detectedEl) {
        const warn = document.createElement('div');
        warn.className = 'text-xs text-red-600 font-semibold mt-1';
        warn.textContent = 'Warning: this request came through a proxy/CDN that the server isn\'t configured to trust, so the address above is the proxy\'s, not the visitor\'s. The network check won\'t work correctly until proxyIPs is set in app/Config/App.php.';
        detectedEl.closest('div').parentElement.insertBefore(warn, detectedEl.closest('div').nextSibling);
    }
    if (addDetectedBtn) {
        if (isLoopback) {
            addDetectedBtn.disabled = true;
            addDetectedBtn.style.opacity = '.4';
            addDetectedBtn.style.cursor = 'not-allowed';
        }
        addDetectedBtn.addEventListener('click', function () {
            if (!isLoopback && addRange(detectedIp)) renderChips();
        });
    }

    renderChips();

    // Exempt accounts — searchable checklist with a live selected count.
    const exemptIds = new Set(ACCESS_CONTROL_ATTENDANCE_NETWORK_EXEMPT_IDS);

    function updateExemptCount() {
        exemptCount.textContent = exemptIds.size ? `${exemptIds.size} selected` : '';
    }

    function renderExemptList(filter) {
        const q = (filter || '').trim().toLowerCase();
        const accounts = ACCESS_CONTROL_ACCOUNTS.filter(acc =>
            !q || acc.name.toLowerCase().includes(q) || (acc.email || '').toLowerCase().includes(q)
        );

        exemptList.innerHTML = accounts.map(acc => {
            const checked = exemptIds.has(acc.user_id) ? 'checked' : '';
            return `
                <label class="flex items-center gap-2 text-sm px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" class="attendance-exempt-checkbox" value="${acc.user_id}" ${checked}>
                    <span class="font-medium text-gray-700">${htmlEsc(acc.name)}</span>
                    <span class="text-gray-400 truncate">${htmlEsc(acc.email || '')}</span>
                </label>
            `;
        }).join('') || `<p class="text-sm text-gray-400 px-2 py-1.5">${q ? 'No matching accounts.' : 'No accounts found.'}</p>`;

        exemptList.querySelectorAll('.attendance-exempt-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                // Numeric: the Set is seeded with integer user ids, so a string
                // here would never match (or remove) an existing exemption.
                const id = Number(this.value);
                if (this.checked) exemptIds.add(id); else exemptIds.delete(id);
                updateExemptCount();
            });
        });
    }

    renderExemptList('');
    updateExemptCount();
    if (exemptSearch) exemptSearch.addEventListener('input', () => renderExemptList(exemptSearch.value));

    saveBtn.addEventListener('click', function () {
        // Don't save past an invalid entry the admin is still looking at.
        if (!addFromInput()) return;

        if (ranges.length === 0 && !confirm('The allowed list is empty, so clock-ins will NOT be restricted to the office network. Save anyway?')) return;

        const fd = new FormData();
        fd.set('allowed_ips', ranges.join('\n'));
        Array.from(exemptIds).forEach(id => fd.append('exempt_user_ids[]', id));

        saveBtn.disabled = true;
        const originalHtml = saveBtn.innerHTML;
        saveBtn.innerHTML = 'Saving…';
        if (statusEl) statusEl.textContent = '';

        fetch('<?= site_url('client_portal/access-control/attendance-network') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_HASH },
            body: fd,
        })
            .then(res => res.json())
            .then(data => {
                if (window.showToast) window.showToast(data.message || (data.success ? 'Saved.' : 'Something went wrong.'), data.success ? 'success' : 'error');
                if (data.success && statusEl) {
                    statusEl.textContent = 'Saved just now.';
                    setTimeout(() => { statusEl.textContent = ''; }, 4000);
                }
            })
            .catch(() => {
                if (window.showToast) window.showToast('Network error. Please try again.', 'error');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalHtml;
            });
    });
})();

const CURRENT_USER_ID = <?= (int) ($currentUserId ?? 0) ?>;
const EDIT_ICON = "<?= base_url('icons/edit.png') ?>";
const VIEW_ICON = "<?= base_url('icons/view.png') ?>";
const LOAN_UPDATE_URL = "<?= site_url('client_portal/loans/update') ?>";
const UPDATE_URL = "<?= site_url('client_portal/employees/update') ?>";

function htmlEsc(val)
{
    if (val == null) return '';

    return String(val)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE ROW
    |--------------------------------------------------------------------------
    */
    function employeeRowTemplate(p) {
        // Generate display name: "LastName, FirstName"
        const displayName = `${htmlEsc(p.last_name)}, ${htmlEsc(p.first_name)}${p.middle_name ? ' ' + htmlEsc(p.middle_name) : ''}`;
        
        // Generate initials
        const avatarSource = displayName.trim();
        const avatarInitials = avatarSource ? avatarSource.substring(0, 2).toUpperCase() : 'U';
        
        // Photo URL if available
        const photoUrl = p.photo_path ? p.photo_path : '';
        
        // Avatar HTML
        const avatarHtml = photoUrl 
            ? `<div class="w-10 h-10 rounded-full" style="background-image:url('${photoUrl}');background-size:cover;background-position:center;"></div>`
            : `<div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-xs font-bold text-gray-700">${avatarInitials}</div>`;

        // Leave balance
        const leaveTotal     = p.leave_total     != null ? Number(p.leave_total)     : null;
        const leaveRemaining = p.leave_remaining != null ? Number(p.leave_remaining) : null;

        const leaveTotalHtml = leaveTotal != null
            ? `${leaveTotal}d`
            : '<span class="text-gray-400 text-xs">—</span>';

        let leaveRemainingHtml;
        if (leaveRemaining == null) {
            leaveRemainingHtml = '<span class="text-gray-400 text-xs">—</span>';
        } else if (leaveRemaining <= 0) {
            leaveRemainingHtml = '<span class="text-red-600 font-semibold text-xs">0d</span>';
        } else if (leaveTotal != null && leaveRemaining / leaveTotal <= 0.25) {
            leaveRemainingHtml = `<span class="text-orange-500 font-semibold text-xs">${leaveRemaining}d</span>`;
        } else {
            leaveRemainingHtml = `<span class="text-green-600 font-semibold text-xs">${leaveRemaining}d</span>`;
        }
        
        return `
            <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_0.9fr_0.8fr_0.8fr_0.8fr_1fr] items-center border-b hover:bg-gray-50">

                <div class="p-4 flex items-center gap-3">
                    ${avatarHtml}
                    <span class="font-medium">
                        ${htmlEsc(p.last_name)}, 
                        ${htmlEsc(p.first_name)}
                        ${p.middle_name ? htmlEsc(p.middle_name.charAt(0).toUpperCase()) + '.' : ''}
                        ${Number(p.user_id) === CURRENT_USER_ID ? '<span class="ml-2 bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-[10px] font-semibold align-middle">Me</span>' : ''}
                    </span>
                </div>

                <div class="p-4">${htmlEsc(p.position) || '---'}</div>
                <div class="p-4">${htmlEsc(p.department) || '---'}</div>
                <div class="p-4">${htmlEsc(p.employee_number) || '---'}</div>
                <div class="p-4">${htmlEsc(p.employment_type) || '---'}</div>

                <div class="p-4 text-center">${leaveTotalHtml}</div>
                <div class="p-4 text-center">${leaveRemainingHtml}</div>

                <div class="p-4">
                    ${(() => {
                        const isActive = p.status == 1;
                        if (!isActive) {
                            return '<span class="bg-red-200 text-red-600 px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap">Inactive</span>';
                        }
                        const presence = p.online_status ?? 'Offline';
                        const badgeColors = {
                            Online:  'bg-green-200 text-green-700',
                            Away:    'bg-yellow-200 text-yellow-700',
                            Offline: 'bg-gray-200 text-gray-500',
                        };
                        const color = badgeColors[presence] ?? badgeColors['Offline'];
                        return `<span class="${color} px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                                    data-presence-badge="${p.id}"
                                    data-current-status="${presence}">${presence}</span>`;
                    })()}
                </div>

                <div class="p-4 flex justify-center gap-4">

                    <div
                        class="cursor-pointer w-7 h-7 hover:scale-110 transition"
                        onclick="viewEmployeeDetails(${htmlEsc(p.id)})">
                        <img src="${VIEW_ICON}" class="w-6 h-6" alt="View">
                    </div>

                    <div
                        class="cursor-pointer w-7 h-7 hover:scale-110 transition"
                        data-action="edit"
                        data-modal="editEmployeeModal"
                        data-update-url="${UPDATE_URL}"

                        data-id="${htmlEsc(p.id)}"
                        data-user_id="${htmlEsc(p.user_id)}"

                        data-first_name="${htmlEsc(p.first_name)}"
                        data-middle_name="${htmlEsc(p.middle_name)}"
                        data-last_name="${htmlEsc(p.last_name)}"
                        data-dob="${htmlEsc(p.birthdate)}"
                        data-gender="${htmlEsc(p.gender)}"
                        data-civil_status="${htmlEsc(p.civil_status)}"
                        data-mobile_number="${htmlEsc(p.mobile_number)}"
                        data-address="${htmlEsc(p.address)}"

                        data-department="${htmlEsc(p.department)}"
                        data-department_id="${htmlEsc(p.department_id)}"
                        data-position="${htmlEsc(p.position)}"
                        data-employment_type="${htmlEsc(p.employment_type)}"

                        data-working_day_type="${htmlEsc(p.working_day_type)}"
                        data-working_days='${htmlEsc(p.working_days || "[]")}'
                        data-wfh_days='${htmlEsc(p.wfh_days || "[]")}'
                        data-start_time="${htmlEsc(p.start_time)}"
                        data-end_time="${htmlEsc(p.end_time)}"
                        data-break_schedule='${htmlEsc(p.break_schedule || "[]")}'
                        data-break_windows='${htmlEsc(p.break_windows || "[]")}'
                        data-tardiness_grace_minutes="${htmlEsc(p.tardiness_grace_minutes ?? 0)}"
                        data-ot_grace_minutes="${htmlEsc(p.ot_grace_minutes ?? 0)}"

                        data-sss="${htmlEsc(p.sss)}"
                        data-tin="${htmlEsc(p.tin)}"
                        data-pagibig="${htmlEsc(p.pagibig)}"
                        data-philhealth="${htmlEsc(p.philhealth)}"

                        data-email="${htmlEsc(p.email)}"
                        data-monthly_salary="${htmlEsc(p.monthly_salary)}"
                        data-de_minimis_benefit="${htmlEsc(p.de_minimis_benefit)}"
                        data-de_minimis_breakdown='${htmlEsc(p.de_minimis_breakdown || "{}")}'
                        data-additional_de_minimis_benefit="${htmlEsc(p.additional_de_minimis_benefit)}"
                        data-additional_de_minimis_breakdown='${htmlEsc(p.additional_de_minimis_breakdown || "{}")}'
                        data-allowances="${htmlEsc(p.allowances)}"

                        data-vacation_leave="${htmlEsc(p.vacation_leave)}"
                        data-sick_leave="${htmlEsc(p.sick_leave)}"
                        data-emergency_leave="${htmlEsc(p.emergency_leave)}"

                        data-bank="${htmlEsc(p.bank)}"
                        data-bank_address="${htmlEsc(p.bank_address)}"
                        data-account_number="${htmlEsc(p.account_number)}"
                        data-swift_code="${htmlEsc(p.swift_code)}"

                        data-leave_total="${htmlEsc(p.leave_total)}"
                        data-status="${htmlEsc(p.status ?? 0)}">

                        <img src="${EDIT_ICON}" class="w-6 h-6" alt="Edit">
                    </div>

                </div>

            </div>
            `;
    }

    function viewEmployeeDetails(employeeId) {
 
        const employee = EMPLOYEES.find(emp => emp.id == employeeId);
 
        if (!employee) return;
 
        let workingDays = [];
        let wfhDays = [];
 
        let deMinimisBreakdown = {
            allocations: {},
            other: []
        };
 
        try {
            deMinimisBreakdown = JSON.parse(employee.de_minimis_breakdown || '{}');
        } catch (e) {}
 
        try {
            workingDays = JSON.parse(employee.working_days || '[]');
        } catch (e) {}

        try {
            wfhDays = JSON.parse(employee.wfh_days || '[]');
        } catch (e) {}
 
        const allocations = deMinimisBreakdown.allocations || {};
        const otherComp = Array.isArray(deMinimisBreakdown.other) ? deMinimisBreakdown.other : [];
 
        const allocationLabels = {
            rice_subsidy: 'Rice Subsidy',
            uniform_allowance: 'Uniform Allowance',
            laundry_allowance: 'Laundry Allowance',
            medical_allowance: 'Medical Allowance',
        };
 
        const hasAllocations = Object.values(allocations).some(v => Number(v) > 0);
        const hasOtherComp = otherComp.some(item => Number(item.amount) > 0);
 
        const html = `
            <div class="flex flex-col h-[70vh]">
                <!-- HEADER BANNER -->
                <div class="bg-[#A891FC] rounded-t-xl px-8 py-6 flex items-center gap-6 -mx-6 mb-6 shrink-0">
                    <div class="w-20 h-20 rounded-full bg-white/30 flex items-center justify-center shrink-0 overflow-hidden border-4 border-white/50">
                        ${employee.photo_path
                            ? `<img src="${htmlEsc(employee.photo_path)}" alt="${htmlEsc(employee.first_name)} ${htmlEsc(employee.last_name)}" class="w-full h-full object-cover">`
                            : `<svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                </svg>`
                        }
                    </div>
 
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            ${htmlEsc(employee.first_name)} ${htmlEsc(employee.last_name)}
                        </h1>
 
                        <p class="text-white/80 italic text-sm mt-0.5">
                            ${htmlEsc(employee.position ?? 'No Position Set')}
                        </p>
 
                        <span class="inline-block mt-2 px-3 py-0.5 rounded-full text-xs font-semibold
                            ${employee.status == 1
                                ? 'bg-green-400/30 text-white border border-green-300/50'
                                : 'bg-red-400/30 text-white border border-red-300/50'}">
 
                            ${employee.status == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </div>
 
                    <div class="ml-auto text-right">
                        <p class="text-white/60 text-xs uppercase tracking-widest">
                            Employee ID
                        </p>
 
                        <p class="text-white font-bold text-lg">
                            ${htmlEsc(employee.employee_number)}
                        </p>
                    </div>
                </div>
 
                <!-- FIXED TOP DETAILS -->
                <div class="shrink-0 px-2">
 
                    <div class="flex divide-x divide-gray-200 mb-6">
                        <div class="flex-1 pr-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Department</p>
                            <p class="text-sm text-gray-700">${htmlEsc(employee.department ?? '—')}</p>
                        </div>
 
                        <div class="flex-1 px-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Email</p>
                            <p class="text-sm text-gray-700">${htmlEsc(employee.email ?? '—')}</p>
                        </div>
 
                        <div class="flex-1 px-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Birthdate</p>
                            <p class="text-sm text-gray-700">${htmlEsc(employee.birthdate ?? '—')}</p>
                        </div>
 
                        <div class="flex-1 pl-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Contact No.</p>
                            <p class="text-sm text-gray-700">${htmlEsc(employee.mobile_number ?? '—')}</p>
                        </div>
                    </div>
 
                    <hr class="border-gray-200 mb-6">
                </div>
 
                <!-- SCROLLABLE CONTENT -->
                <div class="overflow-y-auto hide-scroll-bar flex-1 min-h-0 pr-2 px-2">
 
                    <!-- PERSONAL INFO + GOV IDS -->
                    <div class="flex divide-x divide-gray-200 mb-6">
 
                        <!-- Personal Info -->
                        <div class="flex-1 pr-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                                Personal Information
                            </p>
 
                            <div class="space-y-3">
                                ${viewField('First Name', employee.first_name)}
                                ${viewField('Middle Name', employee.middle_name)}
                                ${viewField('Last Name', employee.last_name)}
                                ${viewField('Gender', employee.gender)}
                                ${viewField('Civil Status', employee.civil_status)}
                                ${viewField('Address', employee.address)}
                            </div>
                        </div>
 
                        <!-- Gov IDs -->
                        <div class="flex-1 pl-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                                Government IDs
                            </p>
 
                            <div class="space-y-3">
                                ${viewField('SSS No.', employee.sss)}
                                ${viewField('TIN', employee.tin)}
                                ${viewField('Pag-IBIG No.', employee.pagibig)}
                                ${viewField('PhilHealth No.', employee.philhealth)}
                            </div>
                        </div>
 
                    </div>
 
                    <hr class="border-gray-200 mb-6">
 
                    <!-- WORK SCHEDULE -->
                    <div class="mb-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                            Work Schedule
                        </p>
 
                        <div class="flex divide-x divide-gray-200">
                            <div class="flex-1 pr-6 space-y-3">
                                ${viewField('Employment Type', employee.employment_type)}
                                ${viewField('Working Day Type', employee.working_day_type)}
                            </div>
 
                            <div class="flex-1 pl-6 space-y-3">
                                ${viewField('Start Time', employee.start_time)}
                                ${viewField('End Time', employee.end_time)}
                            </div>
                        </div>
 
                        <!-- Working Days -->
                        <div class="mt-4">
                            <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">
                                Working Days
                            </p>
 
                            <div class="flex gap-2 flex-wrap">
                                ${['mon','tue','wed','thu','fri','sat','sun'].map(day => {
                                    const active = workingDays.includes(day);
                                    const isWfh = active && wfhDays.includes(day);

                                    return `
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-semibold
                                                ${active
                                                    ? 'bg-[#A891FC] text-white'
                                                    : 'bg-gray-100 text-gray-400'}">

                                                ${day.charAt(0).toUpperCase() + day.slice(1)}
                                            </div>
                                            ${isWfh ? '<span class="text-[9px] font-semibold text-[#6650cc]">WFH</span>' : ''}
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    </div>
 
                    <hr class="border-gray-200 mb-6">
 
                    <!-- JOB DETAILS -->
                    <div class="mb-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                            Job Details
                        </p>
 
                        <div class="flex divide-x divide-gray-200">
                            <div class="flex-1 pr-6 space-y-3">
                                ${viewField('Department', employee.department)}
                                ${viewField('Position', employee.position)}
                            </div>
 
                            <div class="flex-1 pl-6 space-y-3">
                                ${viewField('Status', employee.status == 1 ? 'Active' : 'Inactive')}
                                ${viewField('Basic Salary', employee.monthly_salary ? '₱' + Number(employee.monthly_salary).toLocaleString() : '—')}
                            </div>
                        </div>
                    </div>
 
                    <hr class="border-gray-200 mb-6">
 
                    <!-- DE MINIMIS BENEFITS -->
                    <div class="mb-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                            De Minimis Benefits
                        </p>
 
                        <div class="bg-violet-50 rounded-2xl p-5">
 
                            <div class="flex items-center justify-between mb-4 pb-3 border-b border-violet-100">
                                <span class="text-sm text-gray-600">Amount Given (Total)</span>
                                <span class="text-sm font-semibold text-violet-800">
                                    ${employee.de_minimis_benefit ? '₱' + Number(employee.de_minimis_benefit).toLocaleString() : '—'}
                                </span>
                            </div>
 
                            ${hasAllocations ? `
                                <div class="grid grid-cols-2 gap-3 mb-1">
                                    ${Object.keys(allocationLabels)
                                        .filter(key => Number(allocations[key]) > 0)
                                        .map(key => `
                                            <div class="flex items-center justify-between bg-white rounded-xl px-4 py-2.5">
                                                <span class="text-xs text-gray-500">${allocationLabels[key]}</span>
                                                <span class="text-sm font-medium text-gray-800">
                                                    ₱${Number(allocations[key]).toLocaleString()}
                                                </span>
                                            </div>
                                        `).join('')}
                                </div>
                            ` : `
                                <p class="text-xs text-gray-400 italic">No sub-benefit allocations recorded.</p>
                            `}
 
                            ${hasOtherComp ? `
                                <div class="mt-4 pt-4 border-t border-violet-100">
                                    <p class="text-xs font-semibold text-violet-700 uppercase tracking-wide mb-3">
                                        Other Non-Taxable Compensation
                                    </p>
 
                                    <div class="space-y-2">
                                        ${otherComp
                                            .filter(item => Number(item.amount) > 0)
                                            .map(item => `
                                                <div class="flex items-center justify-between bg-white rounded-xl px-4 py-2.5">
                                                    <span class="text-sm text-gray-700">
                                                        ${htmlEsc(item.purpose || 'Unnamed Benefit')}
                                                    </span>
                                                    <span class="text-sm font-semibold text-gray-800">
                                                        ₱${Number(item.amount || 0).toLocaleString()}
                                                    </span>
                                                </div>
                                            `).join('')}
                                    </div>
                                </div>
                            ` : ''}
 
                        </div>
                    </div>
 
                    <hr class="border-gray-200 mb-6">
 
                    <!-- COMPENSATION & LEAVE -->
                    <div class="mb-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                            Compensation &amp; Leave
                        </p>
 
                        <div class="flex divide-x divide-gray-200">
                            <div class="flex-1 pr-6 space-y-3">
                                ${viewField('Allowances', employee.allowances ? '₱' + Number(employee.allowances).toLocaleString() : '—')}
                            </div>
 
                            <div class="flex-1 pl-6 space-y-3">
                                ${viewField('Vacation Leave', (employee.vacation_leave || 0) + ' days')}
                                ${viewField('Sick Leave', (employee.sick_leave || 0) + ' days')}
                                ${viewField('Emergency Leave', (employee.emergency_leave || 0) + ' days')}
                            </div>
                        </div>
                    </div>
 
                    <hr class="border-gray-200 mb-6">
 
                    <!-- BANKING -->
                    <div class="pb-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                            Banking Information
                        </p>
 
                        <div class="flex divide-x divide-gray-200">
                            <div class="flex-1 pr-6 space-y-3">
                                ${viewField('Bank', employee.bank)}
                                ${viewField('Account Number', employee.account_number)}
                            </div>
 
                            <div class="flex-1 pl-6 space-y-3">
                                ${viewField('Bank Address', employee.bank_address)}
                                ${viewField('Swift Code', employee.swift_code)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
 
        document.getElementById('employeeDetailsContent').innerHTML = html;
        openModalById('viewEmployeeModal');
    }
 
    function viewField(label, value = '') {
        return `
            <div>
                <p class="text-xs text-gray-400 mb-0.5">${label}</p>
                <p class="text-sm font-medium text-gray-800">${htmlEsc(value || '—')}</p>
            </div>
        `;
    }

/*
|--------------------------------------------------------------------------
| FACE RECOGNITION ROW
|--------------------------------------------------------------------------
*/

const FACE_REQUIREMENT_LABELS = { default: 'System Default', required: 'Always Require', exempt: 'Exempt' };
const FACE_REQUIREMENT_BADGE_CLASSES = {
    default: 'bg-gray-200 text-gray-500',
    required: 'bg-green-100 text-green-700',
    exempt: 'bg-blue-100 text-blue-700',
};

function faceRecognitionRowTemplate(p) {
    // Same "LastName, FirstName MI." format as employeeRowTemplate — but this
    // table (unlike the main employee list) also includes admin/superadmin
    // accounts with no employees row at all, so fall back to p.name (already
    // resolved server-side to their username / "User #id") when there's no
    // first/last name to format.
    const hasEmployeeName = (p.first_name || '').trim() !== '' || (p.last_name || '').trim() !== '';
    const middleName = (p.middle_name || '').trim();
    const middleInitial = middleName ? htmlEsc(middleName.charAt(0).toUpperCase()) + '.' : '';
    const displayName = hasEmployeeName
        ? `${htmlEsc(p.last_name)}, ${htmlEsc(p.first_name)}${middleInitial ? ' ' + middleInitial : ''}`
        : htmlEsc(p.name);

    const photoUrl = p.photo_path ? p.photo_path : '';
    const avatarSource = displayName.trim();
    const avatarInitials = avatarSource ? avatarSource.substring(0, 2).toUpperCase() : 'U';

    const avatarHtml = photoUrl
        ? `<div class="w-10 h-10 rounded-full" style="background-image:url('${photoUrl}');background-size:cover;background-position:center;"></div>`
        : `<div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-xs font-bold text-gray-700">${avatarInitials}</div>`;

    const statusHtml = p.enrolled
        ? `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Registered (${p.enrollment_count})</span>`
        : `<span class="bg-gray-200 text-gray-500 px-3 py-1 rounded-full text-xs font-semibold">Not Registered</span>`;

    const requirement = p.face_requirement || 'default';
    const requirementHtml = `
        <div class="relative inline-block" data-face-requirement-cell data-user_id="${htmlEsc(p.user_id)}" data-value="${requirement}">
            <button type="button" class="${FACE_REQUIREMENT_BADGE_CLASSES[requirement]} px-3 py-1 rounded-full text-xs font-semibold cursor-pointer inline-flex items-center gap-1"
                data-face-requirement-badge onclick="toggleFaceRequirementMenu(this)">
                ${FACE_REQUIREMENT_LABELS[requirement]}
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="hidden absolute left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-md text-xs z-10 overflow-hidden w-40" data-face-requirement-menu>
                <button type="button" class="block w-full text-left px-3 py-2 hover:bg-gray-50" onclick="selectFaceRequirement(this, 'default')">System Default</button>
                <button type="button" class="block w-full text-left px-3 py-2 hover:bg-gray-50" onclick="selectFaceRequirement(this, 'required')">Always Require</button>
                <button type="button" class="block w-full text-left px-3 py-2 hover:bg-gray-50" onclick="selectFaceRequirement(this, 'exempt')">Exempt</button>
            </div>
        </div>`;

    return `
        <div class="grid items-center border-b hover:bg-gray-50" style="grid-template-columns: 2fr 1fr 1.1fr 1fr 1.1fr 1.3fr 0.8fr;">

            <div class="p-4 flex items-center gap-3">
                ${avatarHtml}
                <span class="font-medium">
                    ${displayName}
                    ${Number(p.user_id) === CURRENT_USER_ID ? '<span class="ml-2 bg-blue-100 text-blue-700 px-2 py-1 rounded-full text-[10px] font-semibold align-middle">Me</span>' : ''}
                </span>
            </div>

            <div class="p-4">${htmlEsc(p.position) || '---'}</div>
            <div class="p-4">${htmlEsc(p.department) || '---'}</div>

            <div class="p-4">${htmlEsc(p.employee_number) || '---'}</div>

            <div class="p-4">${statusHtml}</div>

            <div class="p-4">${requirementHtml}</div>

            <div class="p-4 flex justify-center gap-4">
                <div
                    class="cursor-pointer w-7 h-7 hover:scale-110 transition"
                    data-action="edit-face"
                    data-user_id="${htmlEsc(p.user_id)}"
                    data-name="${htmlEsc(p.name)}"
                    data-enrolled="${p.enrolled ? '1' : '0'}"
                    data-enrollment_count="${htmlEsc(p.enrollment_count)}"
                    data-enrollment_latest_at="${htmlEsc(p.enrollment_latest_at)}">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-label="Edit face enrollment">
                        <path d="M4 8V6a2 2 0 0 1 2-2h2" />
                        <path d="M16 4h2a2 2 0 0 1 2 2v2" />
                        <path d="M20 16v2a2 2 0 0 1-2 2h-2" />
                        <path d="M8 20H6a2 2 0 0 1-2-2v-2" />
                        <circle cx="12" cy="10" r="2.2" />
                        <path d="M8.5 17c0-2 1.5-3.2 3.5-3.2s3.5 1.2 3.5 3.2" />
                    </svg>
                </div>
            </div>

        </div>
        `;
}

// Inline control on the Face Recognition table itself (not tucked inside
// the enroll modal) — System Default follows the global FaceRecognition.enabled
// switch, Always Require / Exempt override it for just this one account.
// Rendered as a colored badge (same pill style as the Face Status column)
// with a small click-to-open menu, rather than a plain <select>.
function closeAllFaceRequirementMenus(except) {
    document.querySelectorAll('[data-face-requirement-menu]').forEach(menu => {
        if (menu !== except) menu.classList.add('hidden');
    });
}

function toggleFaceRequirementMenu(badgeBtn) {
    const menu = badgeBtn.closest('[data-face-requirement-cell]').querySelector('[data-face-requirement-menu]');
    const willOpen = menu.classList.contains('hidden');
    closeAllFaceRequirementMenus();
    menu.classList.toggle('hidden', !willOpen);
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-face-requirement-cell]')) closeAllFaceRequirementMenus();
});

function selectFaceRequirement(optionBtn, value) {
    const cell = optionBtn.closest('[data-face-requirement-cell]');
    const badge = cell.querySelector('[data-face-requirement-badge]');
    const menu = cell.querySelector('[data-face-requirement-menu]');
    const userId = cell.dataset.user_id;
    const previous = cell.dataset.value || 'default';
    menu.classList.add('hidden');

    if (value === previous) return;

    const applyBadge = (val) => {
        badge.className = `${FACE_REQUIREMENT_BADGE_CLASSES[val]} px-3 py-1 rounded-full text-xs font-semibold cursor-pointer inline-flex items-center gap-1`;
        badge.innerHTML = `${FACE_REQUIREMENT_LABELS[val]}
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    };

    // Optimistic update — reverted below if the save fails.
    applyBadge(value);
    cell.dataset.value = value;

    const params = new URLSearchParams({ requirement: value });

    fetch(`<?= site_url('client_portal/face-recognition/requirement') ?>/${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF_HASH,
        },
        body: params,
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (window.showToast) window.showToast('Face verification requirement updated.', 'success');
            } else {
                cell.dataset.value = previous;
                applyBadge(previous);
                if (window.showToast) window.showToast(data.message || 'Unable to update this setting.', 'warning');
            }
        })
        .catch(err => {
            console.error(err);
            cell.dataset.value = previous;
            applyBadge(previous);
            if (window.showToast) window.showToast('Unable to reach the server right now.', 'warning');
        });
}

/*
|--------------------------------------------------------------------------
| LOAN ROW
|--------------------------------------------------------------------------
*/

function loanRowTemplate(loan)
{
    return `
        <!-- border-left/right: transparent — the header row above has a real
             1px border on both sides (crud_table.php's "border" class), which
             eats 2px from ITS content box that this row's box doesn't lose,
             so each fr track came out ~1px narrower on the header than here.
             Matching (invisible) border-width keeps both content boxes the
             same size so the columns land exactly on top of each other. -->
        <div class="grid items-center border-b hover:bg-gray-50" style="grid-template-columns: minmax(0,1.5fr) minmax(0,1.2fr) minmax(0,1.2fr) minmax(0,1.2fr) minmax(0,1fr) minmax(0,0.7fr) minmax(0,1.2fr) minmax(0,1.1fr) minmax(0,1fr); border-left: 1px solid transparent; border-right: 1px solid transparent;">

            <div class="p-4 font-medium truncate">
                ${loan.employee_name || '—'}
            </div>

            <div class="p-4 truncate">
                ${loan.loan_type || '—'}
            </div>

             <div class="p-4 truncate">
                ₱${Number(loan.principal_amount || 0).toLocaleString()}
            </div>

            <div class="p-4 truncate">
                ₱${Number(loan.remaining_balance || 0).toLocaleString()}
            </div>

            <div class="p-4 truncate">
                ₱${Number(loan.deduction_per_payroll || 0).toLocaleString()}
            </div>

            <div class="p-4 text-center truncate">
                ${loan.installment_count || 0}
            </div>

            <div class="p-4 truncate">
                ${loan.start_date || '—'}
            </div>

            <!-- overflow-hidden (not truncate — this wraps a pill, not plain
                 text) so a long status like "active ongoing" now clips
                 inside its fixed-width column instead of visually bleeding
                 into the next one, now that minmax(0,fr) above stops it
                 from widening the column to fit. -->
            <div class="p-4 overflow-hidden">
                <span class="
                    capitalize px-3 py-1 whitespace-nowrap rounded-full text-xs font-semibold
                    ${loan.loan_status === 'active'
                        ? 'bg-green-100 text-green-700'
                        : loan.loan_status === 'active_ongoing'
                            ? 'bg-blue-100 text-blue-700'
                            : 'bg-red-100 text-red-700'}
                ">
                    ${(loan.loan_status || '—').replace(/_/g, ' ')}
                </span>
            </div>

            <div class="p-4 flex justify-center gap-4">
                <!-- VIEW -->
                <div
                    class="cursor-pointer w-7 h-7 flex items-center justify-center hover:scale-110 transition"
                    data-loan-action="view"
                    data-loan-id="${loan.id}">
                    <img src="${VIEW_ICON}" class="w-6 h-6" alt="View">
                </div>

                <!-- EDIT - pass ID instead of encoded object -->
                <div
                    class="cursor-pointer w-7 h-7 flex items-center justify-center hover:scale-110 transition"
                    data-loan-action="edit"
                    data-loan-id="${loan.id}">
                    <img src="${EDIT_ICON}" class="w-6 h-6" alt="Edit">
                </div>
            </div>

        </div>
    `;
}

function viewLoanDetails(loanId) {

    const loan =
        LOANS.find(l => String(l.id) === String(loanId));

    if (!loan) return;

        const statusClass =
            loan.loan_status === 'active'
                ? 'bg-green-100 text-green-700'
                : loan.loan_status === 'completed'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-red-100 text-red-700';

        const html = `
            <div class="flex flex-col h-[75vh] overflow-hidden">

                <!-- HEADER -->
                <div class="bg-[#A891FC] rounded-t-xl px-8 py-6 flex items-center justify-between -mx-6 mb-6 shrink-0">

                    <div>

                        <h1 class="text-2xl font-bold text-white">
                            ${loan.loan_type || 'Loan'}
                        </h1>

                        <p class="text-sm text-white/80 mt-1">
                            ${loan.employee_name || 'Unknown Employee'}
                        </p>

                        <span class="
                            inline-block mt-3 px-3 py-1 rounded-full text-xs font-semibold
                            ${statusClass}
                        ">
                            ${(loan.loan_status || 'unknown').toUpperCase()}
                        </span>

                    </div>

                    <div class="text-right">

                        <p class="text-white/70 text-xs uppercase tracking-widest">
                            Remaining Balance
                        </p>

                        <p class="text-3xl font-bold text-white">
                            ₱${Number(
                                loan.remaining_balance || 0
                            ).toLocaleString()}
                        </p>

                    </div>

                </div>

                <!-- BODY -->
                <div class="overflow-y-auto flex-1 min-h-0 pr-2 hide-scroll-bar">

                    <!-- SUMMARY -->
                    <div class="grid grid-cols-2 gap-4 mb-6">

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-gray-400 uppercase mb-1">
                                Principal Amount
                            </p>

                            <p class="text-lg font-bold text-gray-800">
                                ₱${Number(
                                    loan.principal_amount || 0
                                ).toLocaleString()}
                            </p>
                        </div>

                        <div class="border rounded-xl p-4">
                            <p class="text-xs text-gray-400 uppercase mb-1">
                                Deduction Per Payroll
                            </p>

                            <p class="text-lg font-bold text-gray-800">
                                ₱${Number(
                                    loan.deduction_per_payroll || 0
                                ).toLocaleString()}
                            </p>
                        </div>

                    </div>

                    <!-- DETAILS -->
                    <div class="border rounded-xl overflow-hidden mb-6">

                        <div class="bg-gray-50 px-4 py-3 border-b">

                            <h3 class="font-semibold text-gray-700">
                                Loan Information
                            </h3>

                        </div>

                        <div class="p-5 grid grid-cols-2 gap-x-8 gap-y-5">

                            ${loanViewField(
                                'Employee',
                                loan.employee_name
                            )}

                            ${loanViewField(
                                'Loan Type',
                                loan.loan_type
                            )}

                            ${loanViewField(
                                'Installments',
                                loan.installment_count
                            )}

                            ${loanViewField(
                                'Start Date',
                                loan.start_date
                            )}

                            ${loanViewField(
                                'Estimated End Date',
                                loan.estimated_end_date
                            )}

                            ${loanViewField(
                                'Loan Status',
                                loan.loan_status
                            )}

                        </div>

                    </div>

                    <!-- FINANCIAL -->
                    <div class="border rounded-xl overflow-hidden mb-6">

                        <div class="bg-gray-50 px-4 py-3 border-b">

                            <h3 class="font-semibold text-gray-700">
                                Financial Breakdown
                            </h3>

                        </div>

                        <div class="p-5 grid grid-cols-2 gap-x-8 gap-y-5">

                            ${loanViewField(
                                'Principal Amount',
                                '₱' + Number(
                                    loan.principal_amount || 0
                                ).toLocaleString()
                            )}

                            ${loanViewField(
                                'Remaining Balance',
                                '₱' + Number(
                                    loan.remaining_balance || 0
                                ).toLocaleString()
                            )}

                            ${loanViewField(
                                'Deduction Per Payroll',
                                '₱' + Number(
                                    loan.deduction_per_payroll || 0
                                ).toLocaleString()
                            )}

                            ${loanViewField(
                                'Total Payable',
                                '₱' + Number(
                                    loan.total_payable || 0
                                ).toLocaleString()
                            )}

                        </div>

                    </div>

                    <!-- PAYMENT SCHEDULE (uneven/custom amounts, editable
                         while still pending) -->
                    <div class="border rounded-xl overflow-hidden mb-6">

                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h3 class="font-semibold text-gray-700">
                                Payment Schedule
                            </h3>
                        </div>

                        <div id="loanScheduleSection" class="p-5">
                            <p class="text-sm text-gray-400">Loading schedule...</p>
                        </div>

                    </div>

                </div>

            </div>
        `;

        document.getElementById('loanDetailsContent').innerHTML =
            html;

        openModalById('viewLoanModal');

        loadLoanSchedule(loan.id);
}

/*
|--------------------------------------------------------------------------
| PAYMENT SCHEDULE — VIEW + INLINE EDIT (uneven/custom installments)
|--------------------------------------------------------------------------
|
| The final pending installment is deliberately shown read-only (it always
| auto-balances server-side to guarantee the loan resolves to exactly ₱0 —
| see LoanService::updateInstallmentAmount()); every other pending row can
| have its amount adjusted in place.
*/

async function loadLoanSchedule(loanId) {

    const section = document.getElementById('loanScheduleSection');
    if (!section) return;

    try {
        const res = await fetch(`<?= site_url('client_portal/loans/viewSchedule') ?>/${loanId}`);
        if (!res.ok) throw new Error('Failed to load schedule: ' + res.status);

        const data = await res.json();
        renderLoanSchedule(loanId, data.schedule || []);
    } catch (err) {
        console.error('loadLoanSchedule error:', err);
        section.innerHTML = '<p class="text-sm text-red-500">Unable to load payment schedule.</p>';
    }
}

function renderLoanSchedule(loanId, schedule) {

    const section = document.getElementById('loanScheduleSection');
    if (!section) return;

    if (!schedule.length) {
        section.innerHTML = '<p class="text-sm text-gray-400">No schedule found for this loan.</p>';
        return;
    }

    const pendingNos = schedule
        .filter(row => row.schedule_status === 'pending')
        .map(row => Number(row.installment_no));
    const lastPendingNo = pendingNos.length ? Math.max(...pendingNos) : null;

    section.innerHTML = `
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-400 text-xs uppercase">
                    <th class="pb-2">#</th>
                    <th class="pb-2">Due Date</th>
                    <th class="pb-2 text-right">Amount</th>
                    <th class="pb-2 pr-4 text-right">Balance After</th>
                    <th class="pb-2 pl-3">Status</th>
                    <th class="pb-2"></th>
                </tr>
            </thead>
            <tbody>
                ${schedule.map(row => {
                    const no = Number(row.installment_no);
                    const isPending = row.schedule_status === 'pending';
                    const isLastPending = no === lastPendingNo;
                    const editable = isPending && !isLastPending;

                    return `
                        <tr class="border-t" data-installment="${no}">
                            <td class="py-2">${no}</td>
                            <td class="py-2">${row.due_date}</td>
                            <td class="py-2 text-right">
                                ${editable
                                    ? `<input type="number" step="0.01" min="0.01" class="w-28 border rounded px-2 py-1 text-right schedule-amount-input" value="${row.amount_due}">`
                                    : `₱${Number(row.amount_due).toLocaleString()}`
                                }
                                ${isLastPending ? '<span class="block text-[10px] text-gray-400">auto-balances</span>' : ''}
                            </td>
                            <td class="py-2 pr-4 text-right">₱${Number(row.remaining_balance).toLocaleString()}</td>
                            <td class="py-2 pl-3 capitalize">${row.schedule_status}</td>
                            <td class="py-2 text-right">
                                ${editable
                                    ? `<button type="button" class="text-xs text-purple-600 hover:text-purple-700 font-semibold" onclick="saveInstallmentAmount(${loanId}, ${no}, this)">Save</button>`
                                    : ''
                                }
                            </td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
    `;
}

async function saveInstallmentAmount(loanId, installmentNo, btn) {

    const row = btn.closest('tr');
    const input = row.querySelector('.schedule-amount-input');
    const newAmount = parseFloat(input.value || 0);

    if (!(newAmount > 0)) {
        showToast('Enter an amount greater than 0.', 'warning');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
        const res = await fetch(
            `<?= site_url('client_portal/loans/schedule') ?>/${loanId}/${installmentNo}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_HASH,
                },
                body: new URLSearchParams({ amount_due: newAmount }),
            }
        );
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.message || 'Unable to update this installment.');
        }

        showToast('Installment updated.', 'success');
        renderLoanSchedule(loanId, data.schedule);
    } catch (err) {
        showToast(err.message, 'error');
        btn.disabled = false;
        btn.textContent = 'Save';
    }
}

    function loanViewField(label, value = '') {

        return `
            <div>

                <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">
                    ${label}
                </p>

                <p class="text-sm font-semibold text-gray-800">
                    ${value || '—'}
                </p>

            </div>
        `;
    }

/*
|--------------------------------------------------------------------------
| EMPLOYEE EDIT CLICK
|--------------------------------------------------------------------------
*/

window.addEventListener('click', function(e)
{
    /*
    |----------------------------------------------------------
    | EMPLOYEE EDIT
    |----------------------------------------------------------
    */
    const button =
        e.target.closest('[data-action="edit"]');

    if (button)
    {
        const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };

        setVal('edit_employee_id',       button.dataset.id);
        setVal('edit_first_name',        button.dataset.first_name);
        setVal('edit_middle_name',       button.dataset.middle_name);
        setVal('edit_last_name',         button.dataset.last_name);
        setVal('edit_dob',               button.dataset.dob);
        setVal('edit_gender',            button.dataset.gender);
        setVal('edit_civil_status',      button.dataset.civil_status);
        setVal('edit_mobile_number',     button.dataset.mobile_number);
        setVal('edit_address',           button.dataset.address);

        setVal('edit_department_id',     button.dataset.department_id);
        setVal('edit_position',          button.dataset.position);
        setVal('edit_employment_type',   button.dataset.employment_type);
        setVal('edit_email',             button.dataset.email);
        setVal('edit_monthly_salary',    button.dataset.monthly_salary);
        setVal('edit_de_minimis_benefit',button.dataset.de_minimis_benefit);
        loadEditBreakdown( button.dataset.de_minimis_breakdown || '{}' );
        if (typeof loadAdditionalDeMinimis === 'function') {
            loadAdditionalDeMinimis(button.dataset.additional_de_minimis_breakdown || '{}');
        }

        setVal('edit_allowances',        button.dataset.allowances);
        setVal('edit_vacation_leave',    button.dataset.vacation_leave);
        setVal('edit_sick_leave',        button.dataset.sick_leave);
        setVal('edit_emergency_leave',   button.dataset.emergency_leave);
        setVal('edit_leave_total',       button.dataset.leave_total);

        setVal('edit_bank',              button.dataset.bank);
        setVal('edit_bank_address',      button.dataset.bank_address);
        setVal('edit_account_number',    button.dataset.account_number);
        setVal('edit_swift_code',        button.dataset.swift_code);

        setVal('edit_sss',               button.dataset.sss);
        setVal('edit_tin',               button.dataset.tin);
        setVal('edit_pagibig',           button.dataset.pagibig);
        setVal('edit_philhealth',        button.dataset.philhealth);
        setVal('edit_status',            button.dataset.status);

        setVal('edit_working_day_type',  button.dataset.working_day_type);
        if (typeof toggleEditScheduleTime === 'function') toggleEditScheduleTime(button.dataset.working_day_type);
        setVal('edit_start_time',        button.dataset.start_time);
        setVal('edit_end_time',          button.dataset.end_time);
        setVal('edit_tardiness_grace_minutes', button.dataset.tardiness_grace_minutes || 0);
        setVal('edit_ot_grace_minutes',        button.dataset.ot_grace_minutes || 0);
        if (typeof toggleEditScheduleTime === 'function') toggleEditScheduleTime(button.dataset.working_day_type);

        // Restore break schedule (falls back to a single 60-min break if unset)
        if (typeof rebuildBreakRows === 'function') {
            let breakMinutes = [];
            try { breakMinutes = JSON.parse(button.dataset.break_schedule || '[]'); } catch (e) { breakMinutes = []; }
            let breakWindows = [];
            try { breakWindows = JSON.parse(button.dataset.break_windows || '[]'); } catch (e) { breakWindows = []; }
            rebuildBreakRows('edit', Array.isArray(breakMinutes) && breakMinutes.length ? breakMinutes : [60], breakWindows);
        }

        // Restore working day checkboxes
        try {
            const days = JSON.parse(button.dataset.working_days || '[]');
            document.querySelectorAll('#editEmployeeForm .edit-day-checkbox').forEach(cb => {
                const active = days.includes(cb.value);
                cb.checked = active;
                const btn = cb.closest('.edit-day-toggle')?.querySelector('.edit-day-btn');
                if (btn) {
                    btn.classList.toggle('bg-[#A891FC]', active);
                    btn.classList.toggle('border-[#A891FC]', active);
                    btn.classList.toggle('text-white', active);
                    btn.classList.toggle('bg-white', !active);
                    btn.classList.toggle('border-gray-300', !active);
                    btn.classList.toggle('text-gray-500', !active);
                }
            });
        } catch(e) {}

        // Rebuild the Work Location list for whichever days are now checked,
        // then mark the previously-saved WFH days within it.
        if (typeof renderEditWfhList === 'function') {
            renderEditWfhList();
            try {
                const wfhDays = JSON.parse(button.dataset.wfh_days || '[]');
                document.querySelectorAll('#edit_wfh_list .edit-wfh-checkbox').forEach(cb => {
                    const isWfh = wfhDays.includes(cb.value);
                    cb.checked = isWfh;
                    const sw = cb.closest('.edit-wfh-switch');
                    if (sw) {
                        sw.classList.toggle('is-wfh', isWfh);
                        const lbl = sw.querySelector('.edit-wfh-switch-label');
                        if (lbl) lbl.textContent = isWfh ? 'WFH' : 'Onsite';
                    }
                });
            } catch(e) {}
        }

        // Guard self-deactivate note
        const selfGuard = document.getElementById('selfDeactivateGuardNote');
        if (selfGuard) selfGuard.classList.add('hidden');
        const statusSel = document.getElementById('edit_status');
        if (statusSel && button.dataset.user_id == CURRENT_USER_ID) {
            statusSel.addEventListener('change', function() {
                if (selfGuard) selfGuard.classList.toggle('hidden', this.value !== '0');
            }, {once: true});
        }

        const form = document.getElementById('editEmployeeForm');
        form.action = `${UPDATE_URL}/${button.dataset.id}`;

        openModalById('editEmployeeModal');

        return;
    }

    /*
    |----------------------------------------------------------
    | FACE RECOGNITION EDIT
    |----------------------------------------------------------
    */
    const faceEditBtn = e.target.closest('[data-action="edit-face"]');

    if (faceEditBtn)
    {
        if (typeof window.openFaceRecognitionModal === 'function') {
            window.openFaceRecognitionModal({
                user_id: Number(faceEditBtn.dataset.user_id),
                name: faceEditBtn.dataset.name,
                enrolled: faceEditBtn.dataset.enrolled === '1',
                enrollment_count: Number(faceEditBtn.dataset.enrollment_count || 0),
                enrollment_latest_at: faceEditBtn.dataset.enrollment_latest_at || null,
            });
        }

        return;
    }

    /*
    |----------------------------------------------------------
    | LOAN VIEW
    |----------------------------------------------------------
    */
    const viewBtn = e.target.closest('[data-loan-action="view"]');
    
    if (viewBtn)
    {
        const loanId = viewBtn.dataset.loanId;
        // Find the loan from the LOANS array
        const loan = LOANS.find(l => String(l.id) === String(loanId));
        
        if (loan) {
            viewLoanDetails(loanId);
        }
        
        return;
    }

    /*
    |----------------------------------------------------------
    | LOAN EDIT
    |----------------------------------------------------------
    */
    const editBtn = e.target.closest('[data-loan-action="edit"]');
    
    if (editBtn)
    {
        e.stopPropagation();
        
        const loanId = editBtn.dataset.loanId;
        // Find the loan from the LOANS array
        const loan = LOANS.find(l => String(l.id) === String(loanId));
        
        if (loan) {
            document.getElementById('edit_loan_id').value = loan.id || '';
            document.getElementById('edit_loan_employee').value = loan.employee_name || '';
            document.getElementById('edit_loan_type').value = loan.loan_type || '';
            document.getElementById('edit_installment_count').value = loan.installment_count || '';
            document.getElementById('edit_deduction_per_payroll').value = loan.deduction_per_payroll || '';
            document.getElementById('edit_deduction_frequency_hidden').value = loan.deduction_frequency || 'Semi-Monthly';

            // Effective date defaults to "Custom Date" prefilled with the
            // loan's current start_date; switching modes/dates re-resolves
            // it against payroll_settings via refreshEditEffectiveDate().
            document.getElementById('edit_effective_mode_custom').checked = true;
            document.getElementById('edit_effective_mode_next_cutoff').checked = false;
            document.getElementById('editNextCutoffPreview').classList.add('hidden');
            const customDateInput = document.getElementById('edit_custom_start_date');
            customDateInput.classList.remove('hidden');
            customDateInput.value = loan.start_date || '';
            document.getElementById('edit_start_date').value = loan.start_date || '';
            if (typeof refreshEditEffectiveDate === 'function') {
                refreshEditEffectiveDate();
            }

            // Fix: Use a select or hidden input for status
            const statusSelect = document.getElementById('edit_loan_status_display');
            if (statusSelect) {
                statusSelect.value = loan.loan_status || 'active';
            }

            if (typeof applyLoanStatusRules === 'function') {
                applyLoanStatusRules(loan.loan_status);
            }

            document.getElementById('editLoanForm').action = `${LOAN_UPDATE_URL}/${loan.id}`;
            openModalById('editLoanModal');
        }
        
        return;
    }
});

/*
|--------------------------------------------------------------------------
| TAB SWITCHING
|--------------------------------------------------------------------------
*/

const tabs =
    document.querySelectorAll('.tab[data-tab]');

const panels =
    document.querySelectorAll('.tab-panel[data-panel]');

function activateTab(tabKey)
{
    tabs.forEach(tab => {

        tab.classList.toggle(
            'active',
            tab.dataset.tab === tabKey
        );

    });

    panels.forEach(panel => {

        panel.style.display =
            panel.dataset.panel === tabKey
                ? 'flex'
                : 'none';

    });

    // Reflect the active tab in the URL (?tab=...) so a refresh — or a
    // shared link — lands back on this same tab instead of always
    // resetting to Employee Management. replaceState (not pushState) so
    // switching tabs doesn't spam the browser's back/forward history.
    const tabUrl = new URL(window.location.href);
    tabUrl.searchParams.set('tab', tabKey);
    window.history.replaceState({}, '', tabUrl);
}

tabs.forEach(tab => {

    tab.addEventListener('click', function ()
    {
        activateTab(tab.dataset.tab);
    });

});

// Honor ?tab= from the URL on load, but only if it names a tab actually
// rendered for this user's role — PHP already only renders the tab
// buttons a user is allowed to see, so checking against the live DOM here
// is sufficient (no separate allow-list to keep in sync). Falls back to
// Employee Management for a missing/invalid/unauthorized value.
const requestedTab = new URLSearchParams(window.location.search).get('tab');
const hasRequestedTab = requestedTab && Array.from(tabs).some(tab => tab.dataset.tab === requestedTab);

activateTab(hasRequestedTab ? requestedTab : 'employee-management');
/*
|--------------------------------------------------------------------------
| CREATE EMPLOYEE TABLE
|--------------------------------------------------------------------------
*/

createCrudTable({
    tableId: "employeeTable",
    data: EMPLOYEES,
    renderRow: employeeRowTemplate,
    search: (item, q) =>
        `
        ${item.first_name}
        ${item.middle_name}
        ${item.last_name}
        ${item.position}
        ${item.department}
        ${item.employee_number}
        `
        .toLowerCase()
        .includes(q),
    sort: ({ a, b, sortBy, sortOrder }) => {
        let left, right;

        if (sortBy === 'name') {
            // Sort by family name to match the displayed "LastName, FirstName"
            // order (crud-table.js's own default sorts first_name-first instead).
            left = (a.last_name || '').toLowerCase();
            right = (b.last_name || '').toLowerCase();
        } else if (sortBy === 'status') {
            left = String(a.status ?? 0);
            right = String(b.status ?? 0);
        } else {
            left = String(a[sortBy] ?? '').toLowerCase();
            right = String(b[sortBy] ?? '').toLowerCase();
        }

        if (left < right) return sortOrder === 'asc' ? -1 : 1;
        if (left > right) return sortOrder === 'asc' ? 1 : -1;
        return 0;
    }
});

/*
|--------------------------------------------------------------------------
| CREATE FACE RECOGNITION TABLE
|--------------------------------------------------------------------------
*/

createCrudTable({
    tableId: "faceRecognitionTable",
    data: FACE_RECOGNITION_EMPLOYEES,
    renderRow: faceRecognitionRowTemplate,
    search: (item, q) =>
        `
        ${item.name}
        ${item.position}
        ${item.department}
        ${item.employee_number}
        `
        .toLowerCase()
        .includes(q),
    filters: ({ item, query, values } = {}) => {
        item = item || {};
        values = values || {};

        const matchesSearch = !query || `
            ${item.name}
            ${item.department}
            ${item.position}
            ${item.employee_number}
            `
            .toLowerCase()
            .includes(query);

        const matchesStatus =
            !values.face_status ||
            (values.face_status === 'registered' ? item.enrolled : !item.enrolled);

        const matchesDepartment =
            !values.department ||
            (item.department || '').toLowerCase() === values.department;

        return matchesSearch && matchesStatus && matchesDepartment;
    },
    sort: ({ a, b, sortBy, sortOrder }) => {
        let left, right;

        if (sortBy === 'enrolled') {
            left = a.enrolled ? 1 : 0;
            right = b.enrolled ? 1 : 0;
        } else if (sortBy === 'name') {
            // Sort by family name to match the displayed "LastName, FirstName"
            // order, not p.name (which reads "FirstName MI. LastName" and
            // would sort by given name instead). Falls back to p.name for
            // admin-only rows with no employee profile (no last_name at all).
            const familyName = (row) => ((row.last_name || row.first_name || row.name || '')).toLowerCase();
            left = familyName(a);
            right = familyName(b);
        } else {
            left = String(a[sortBy] ?? '').toLowerCase();
            right = String(b[sortBy] ?? '').toLowerCase();
        }

        if (left < right) return sortOrder === 'asc' ? -1 : 1;
        if (left > right) return sortOrder === 'asc' ? 1 : -1;
        return 0;
    }
});

/*
|--------------------------------------------------------------------------
| CREATE LOAN TABLE
|--------------------------------------------------------------------------
*/

createCrudTable({
    tableId: "loanTable",
    data: LOANS,
    renderRow: loanRowTemplate,
    filters: ({ item, query, values } = {}) => {
        item = item || {};
        values = values || {};

        const matchesSearch = !query || `
            ${item.employee_name}
            ${item.loan_type}
            ${item.loan_status}
            `
            .toLowerCase()
            .includes(query);

        const matchesStatus =
            !values.status ||
            (item.loan_status || '').toLowerCase() === values.status;

        const matchesCategory =
            !values.loan_category ||
            (item.loan_category || '').toLowerCase() === values.loan_category;

        const matchesType =
            !values.loan_type ||
            (item.loan_type || '').toLowerCase() === values.loan_type;

        return matchesSearch && matchesStatus && matchesCategory && matchesType;
    },
    sort: ({ a, b, sortBy, sortOrder }) => {

        let left, right;

        if (sortBy === 'remaining_balance') {
            left = Number(a.remaining_balance || 0);
            right = Number(b.remaining_balance || 0);
        } else {
            left = String(a[sortBy] ?? '').toLowerCase();
            right = String(b[sortBy] ?? '').toLowerCase();
        }

        if (left < right) return sortOrder === 'asc' ? -1 : 1;
        if (left > right) return sortOrder === 'asc' ? 1 : -1;
        return 0;
    }
});

// ========== EMPLOYEE IMPORT / EXPORT ==========

document.getElementById('exportEmployeesBtn')?.addEventListener('click', function() {
    window.location.href = '<?= site_url('client_portal/employees/export') ?>';
});

document.getElementById('employeeImportInput')?.addEventListener('change', async function() {
    const file = this.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('file', file);
    try {
        const res = await fetch('<?= site_url('client_portal/employees/import') ?>', {
            method: 'POST',
            // Without this the CSRF filter rejects the upload ("The action you requested
            // is not allowed") — same fix as the Attendance Corrections save. Don't set
            // Content-Type here: the browser needs to add its own multipart boundary for FormData.
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' },
            body: formData,
        });
        let result;
        try { result = await res.json(); } catch (e) { throw new Error(res.status === 403 ? 'Session expired or request blocked. Refresh the page and try again.' : 'Unexpected server response (' + res.status + ').'); }
        if (result.success) {
            showToast(result.message || 'Employees imported successfully.', 'success');
            // Temporary passwords are generated per row and shown only here —
            // there is no other record of them, so this must be read before reloading.
            const lines = [];
            if (result.imported?.length) {
                lines.push('Temporary passwords (share with each employee, who should change it on first login):');
                result.imported.forEach(r => lines.push(`  ${r.email}: ${r.temp_password}`));
            }
            if (result.errors?.length) {
                lines.push('', 'Rows not imported:');
                result.errors.forEach(e => lines.push(`  ${e}`));
            }
            if (lines.length) alert(lines.join('\n'));
            setTimeout(() => window.location.reload(), lines.length ? 0 : 1200);
        } else {
            showToast('Import failed: ' + (result.message || 'Unknown error'), 'error');
        }
    } catch (err) {
        showToast(err?.message || 'Network error during import.', 'error');
        console.error(err);
    }
    this.value = '';
});

window.openEmployeeImportHistory = async function() {
    const modal = document.getElementById('employeeImportHistoryModal');
    const list = document.getElementById('employeeImportHistoryList');
    if (!modal || !list) return;
    modal.style.display = 'block';
    list.innerHTML = '<p style="color:#888;font-size:13px;">Loading…</p>';
    try {
        const res = await fetch('<?= site_url('client_portal/employees/import-history') ?>');
        const data = await res.json();
        if (!data.success) throw new Error(data.message || data.error || 'Could not load import history.');
        if (!data.batches || !data.batches.length) {
            list.innerHTML = '<p style="color:#888;font-size:13px;">No employee imports recorded yet.</p>';
            return;
        }
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        list.innerHTML = `<table style="width:100%;font-size:13px;border-collapse:collapse;">
            <thead><tr style="text-align:left;border-bottom:1px solid #ddd;">
                <th style="padding:6px;">When</th><th style="padding:6px;">File</th><th style="padding:6px;">By</th>
                <th style="padding:6px;text-align:right;">Imported</th><th style="padding:6px;text-align:right;">Errors</th>
            </tr></thead><tbody>${data.batches.map(b => `<tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:6px;">${esc(b.created_at)}</td>
                <td style="padding:6px;">${esc(b.filename || '(unknown)')}</td>
                <td style="padding:6px;">${esc(b.imported_by_name || '—')}</td>
                <td style="padding:6px;text-align:right;">${esc(b.ready_count)}</td>
                <td style="padding:6px;text-align:right;">${esc(b.error_count)}</td>
            </tr>`).join('')}</tbody></table>`;
    } catch (err) {
        list.innerHTML = `<p style="color:#c0392b;font-size:13px;">${err.message || 'Could not load import history.'}</p>`;
    }
};

</script>
<?= $this->endSection() ?>
