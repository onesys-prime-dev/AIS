<?= $this->extend('layouts/main') ?>

<?php if (\App\Libraries\PortalAccess::can('face_recognition')): ?>
<?= $this->section('styles') ?>
<link href="<?= base_url('css/face-scan.css') ?>" rel="stylesheet">
<?= $this->endSection() ?>
<?php endif; ?>

<?= $this->section('content') ?>

<div class="h-full flex flex-col min-h-0 overflow-visible">

    <div class="tabs">
        <button type="button" class="tab active" data-tab="employee-management">Employee Management</button>
        <?php if (\App\Libraries\PortalAccess::canManageAccessControl()): ?><button type="button" class="tab" data-tab="access-control">Access Control</button><?php endif; ?>
        <?php if (\App\Libraries\PortalAccess::can('face_recognition')): ?><button type="button" class="tab" data-tab="face-recognition">Face Recognition</button><?php endif; ?>
        <?php if (\App\Libraries\PortalAccess::can('loans')): ?><button type="button" class="tab" data-tab="loan-management">Loan Management</button><?php endif; ?>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8"
        data-panel="employee-management"
        style="display:flex;">
        <?= view('client_portal/employee_management') ?>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8" data-panel="access-control" style="display:none;">
        <?php if (\App\Libraries\PortalAccess::canManageAccessControl()): ?><?= view('client_portal/employee_access_control') ?><?php endif; ?>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8" data-panel="face-recognition" style="display:none;">
        <?php if (\App\Libraries\PortalAccess::can('face_recognition')): ?><?= view('client_portal/employee_face_recognition') ?><?php endif; ?>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8" data-panel="loan-management" style="display:none;">
        <?php if (\App\Libraries\PortalAccess::can('loans')): ?><?= view('client_portal/employee_loan') ?><?php endif; ?>
    </div>


</div>

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
const ACCESS_CONTROL_CURRENT_USER_ID = <?= json_encode((int) $currentUserId) ?>;
const ACCESS_CONTROL_DEPARTMENTS = <?= json_encode($accessControlDepartments ?? []) ?>;
const ACCESS_CONTROL_TEAM_HEAD_ACCOUNTS = <?= json_encode($accessControlTeamHeadAccounts ?? []) ?>;
const ACCESS_CONTROL_DEPARTMENT_TEAM_HEADS = <?= json_encode($accessControlDepartmentTeamHeads ?? []) ?>;
const ACCESS_CONTROL_PERMISSIONS = <?= json_encode($accessControlPermissions ?? []) ?>;
const ACCESS_CONTROL_PERMISSION_LABELS = <?= json_encode($accessControlPermissionLabels ?? []) ?>;
const ACCESS_CONTROL_ACTOR_IS_SUPERADMIN = <?= json_encode((bool) ($accessControlActorIsSuperadmin ?? false)) ?>;
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

// ================= ACCOUNTS (multi-role) =================
(function () {
    const body = document.getElementById('accessControlBody');
    if (!body) return; // panel not rendered (PortalAccess::canManageAccessControl() was false)

    const accounts = ACCESS_CONTROL_ACCOUNTS;
    const roleOptions = ACCESS_CONTROL_ROLE_OPTIONS;

    if (!accounts.length) {
        body.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">No accounts found.</td></tr>';
        return;
    }

    function roleBadges(roles) {
        if (!roles.length) return '<span class="text-xs text-red-500">No role assigned</span>';
        return roles.map(function (r) {
            return '<span class="inline-flex items-center rounded-full bg-gray-100 text-gray-700 px-2 py-1 text-xs font-medium mr-2 mb-1">' + acEscapeHtml(roleOptions[r] || r) + '</span>';
        }).join('');
    }

    function setPillSelected(pill, selected) {
        pill.dataset.selected = selected ? '1' : '0';
        pill.style.background = selected ? '#A891FC' : '#f3f4f6';
        pill.style.color = selected ? '#fff' : '#374151';
    }

    function currentSelection(container) {
        return Array.from(container.querySelectorAll('.ac-role-pill[data-selected="1"]')).map(function (p) { return p.dataset.role; });
    }

    function sameRoles(a, b) {
        const sa = a.slice().sort().join(',');
        const sb = b.slice().sort().join(',');
        return sa === sb;
    }

    // Roles are always shown as clickable pills — no separate "Edit" step to
    // find first. Save only appears once the selection actually differs from
    // what's saved, same as the original single-role dropdown used to work.
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

        const selectable = Object.keys(roleOptions).filter(function (key) {
            return key !== 'superadmin' || ACCESS_CONTROL_ACTOR_IS_SUPERADMIN;
        });
        const pills = selectable.map(function (key) {
            const selected = acc.roles.indexOf(key) !== -1;
            return '<button type="button" class="ac-role-pill px-3 py-1 rounded-full text-xs font-medium" data-role="' + acEscapeHtml(key) + '" data-selected="' + (selected ? '1' : '0') + '" style="background:' + (selected ? '#A891FC' : '#f3f4f6') + ';color:' + (selected ? '#fff' : '#374151') + ';">' + acEscapeHtml(roleOptions[key]) + '</button>';
        }).join('');

        return '<tr data-user-id="' + acc.user_id + '">' +
            '<td class="px-4 py-3" style="vertical-align:top;">' + acEscapeHtml(acc.name) + '</td>' +
            '<td class="px-4 py-3" style="vertical-align:top;">' + acEscapeHtml(acc.email || '—') + '</td>' +
            '<td class="px-4 py-3" style="vertical-align:top;">' +
                '<div class="flex flex-wrap gap-2 ac-role-checklist">' + pills + '</div>' +
                '<button type="button" class="ac-save-btn mt-2 px-3 py-1 rounded-lg text-xs font-semibold text-white" style="display:none;background:#A891FC;" onmouseover="this.style.background=\'#9678f5\'" onmouseout="this.style.background=\'#A891FC\'">Save</button>' +
            '</td>' +
            '</tr>';
    }).join('');

    body.querySelectorAll('tr[data-user-id]').forEach(function (row) {
        const userId = row.dataset.userId;
        const acc = accounts.find(function (a) { return String(a.user_id) === String(userId); });
        if (!acc) return;

        const checklist = row.querySelector('.ac-role-checklist');
        const saveBtn = row.querySelector('.ac-save-btn');
        if (!checklist || !saveBtn) return;
        const pills = Array.from(checklist.querySelectorAll('.ac-role-pill'));

        function refreshSaveVisibility() {
            saveBtn.style.display = sameRoles(currentSelection(checklist), acc.roles) ? 'none' : 'inline-block';
        }

        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                const nowSelected = pill.dataset.selected !== '1';
                if (pill.dataset.role === 'employee' && nowSelected) {
                    pills.forEach(function (p) { setPillSelected(p, p === pill); });
                } else {
                    setPillSelected(pill, nowSelected);
                    if (nowSelected) {
                        const employeePill = pills.find(function (p) { return p.dataset.role === 'employee'; });
                        if (employeePill) setPillSelected(employeePill, false);
                    }
                }
                refreshSaveVisibility();
            });
        });

        saveBtn.addEventListener('click', function () {
            const checked = currentSelection(checklist);

            if ((checked.indexOf('admin') !== -1 || checked.indexOf('superadmin') !== -1) &&
                !confirm('Save this role change? The account will have Admin and/or Super Admin access.')) {
                return;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            const params = new URLSearchParams({ user_id: userId });
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
                        saveBtn.style.display = 'none';
                    }
                })
                .catch(function () {
                    if (window.showToast) window.showToast('Something went wrong.', 'error');
                })
                .finally(function () {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                });
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
                            return '<span class="bg-red-200 text-red-600 px-3 py-1 rounded-full text-xs font-semibold">Inactive</span>';
                        }
                        const presence = p.online_status ?? 'Offline';
                        const badgeColors = {
                            Online:  'bg-green-200 text-green-700',
                            Away:    'bg-yellow-200 text-yellow-700',
                            Offline: 'bg-gray-200 text-gray-500',
                        };
                        const color = badgeColors[presence] ?? badgeColors['Offline'];
                        return `<span class="${color} px-3 py-1 rounded-full text-xs font-semibold"
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
                        data-start_time="${htmlEsc(p.start_time)}"
                        data-end_time="${htmlEsc(p.end_time)}"
                        data-break_schedule='${htmlEsc(p.break_schedule || "[]")}'
                        data-break_windows='${htmlEsc(p.break_windows || "[]")}'

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
                            ? `<img src="${employee.photo_path}" alt="${employee.first_name} ${employee.last_name}" class="w-full h-full object-cover">`
                            : `<svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                                </svg>`
                        }
                    </div>
 
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            ${employee.first_name} ${employee.last_name}
                        </h1>
 
                        <p class="text-white/80 italic text-sm mt-0.5">
                            ${employee.position ?? 'No Position Set'}
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
                            ${employee.employee_number}
                        </p>
                    </div>
                </div>
 
                <!-- FIXED TOP DETAILS -->
                <div class="shrink-0 px-2">
 
                    <div class="flex divide-x divide-gray-200 mb-6">
                        <div class="flex-1 pr-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Department</p>
                            <p class="text-sm text-gray-700">${employee.department ?? '—'}</p>
                        </div>
 
                        <div class="flex-1 px-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Email</p>
                            <p class="text-sm text-gray-700">${employee.email ?? '—'}</p>
                        </div>
 
                        <div class="flex-1 px-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Birthdate</p>
                            <p class="text-sm text-gray-700">${employee.birthdate ?? '—'}</p>
                        </div>
 
                        <div class="flex-1 pl-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Contact No.</p>
                            <p class="text-sm text-gray-700">${employee.mobile_number ?? '—'}</p>
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
 
                                    return `
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-semibold
                                            ${active
                                                ? 'bg-[#A891FC] text-white'
                                                : 'bg-gray-100 text-gray-400'}">
 
                                            ${day.charAt(0).toUpperCase() + day.slice(1)}
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
                                                        ${item.purpose || 'Unnamed Benefit'}
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
                <p class="text-sm font-medium text-gray-800">${value || '—'}</p>
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
        <div class="grid grid-cols-[1.5fr_1.2fr_1.2fr_1.2fr_1fr_1fr_1.2fr_0.8fr_1fr] items-center border-b hover:bg-gray-50">

            <div class="p-4 font-medium truncate">
                ${loan.employee_name || '—'}
            </div>

            <div class="p-4 truncate">
                ${loan.loan_type || '—'}
            </div>

             <div class="p-4">
                ₱${Number(loan.principal_amount || 0).toLocaleString()}
            </div>

            <div class="p-4">
                ₱${Number(loan.remaining_balance || 0).toLocaleString()}
            </div>

            <div class="p-4">
                ₱${Number(loan.deduction_per_payroll || 0).toLocaleString()}
            </div>

            <div class="p-4 text-center">
                ${loan.installment_count || 0}
            </div>

            <div class="p-4">
                ${loan.start_date || '—'}
            </div>

            <div class="p-4">
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
                    <th class="pb-2 text-right">Balance After</th>
                    <th class="pb-2">Status</th>
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
                            <td class="py-2 text-right">₱${Number(row.remaining_balance).toLocaleString()}</td>
                            <td class="py-2 capitalize">${row.schedule_status}</td>
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
}

tabs.forEach(tab => {

    tab.addEventListener('click', function ()
    {
        activateTab(tab.dataset.tab);
    });

});

activateTab('employee-management');
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
            body: formData,
        });
        const result = await res.json();
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
        showToast('Network error during import.', 'error');
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