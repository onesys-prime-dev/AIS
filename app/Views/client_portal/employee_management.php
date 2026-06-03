<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="h-full flex flex-col min-h-0 overflow-hidden px-8 pb-8">

    <!-- Page Header -->
    <div class="flex justify-between items-center my-4">

        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">
                Employee Management
            </h1>

            <p class="text-gray-500 text-sm">
                Manage employee data here.
            </p>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center gap-3">

            <!-- IMPORT -->
            <div>

                <input
                    type="file"
                    id="employeeImportInput"
                    accept=".xlsx,.xls,.csv"
                    class="hidden">

                <button
                    type="button"
                    onclick="document.getElementById('employeeImportInput').click()"
                    class="
                        bg-white
                        border
                        border-gray-300
                        hover:bg-gray-100
                        text-gray-700
                        px-4 py-3
                        rounded-lg
                        shadow-sm
                        flex items-center gap-2
                        transition
                    ">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">

                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4" />

                    </svg>

                    <span class="font-medium">
                        Import
                    </span>

                </button>

            </div>

            <!-- EXPORT -->
            <button
                type="button"
                id="exportEmployeesBtn"
                class="
                    bg-[#9764F6]
                    hover:bg-[#8a58e0]
                    text-white
                    px-4 py-3
                    rounded-lg
                    shadow-sm
                    flex items-center gap-2
                    transition
                ">

                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">

                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M16 8l-4-4m0 0L8 8m4-4v12" />

                </svg>

                <span class="font-medium">
                    Export
                </span>

            </button>

            <!-- ADD EMPLOYEE -->
            <button
                type="button"
                data-modal-open="employeeModal"
                onclick="openModalById('employeeModal')"
                class="
                    bg-[#FF2D2D]
                    hover:bg-[#ff2d2dd2]
                    text-white
                    px-4 py-3
                    rounded-lg
                    shadow-sm
                    inline-flex
                    items-center
                    gap-2
                    whitespace-nowrap
                    h-auto
                    w-auto
                    shrink-0
                    transition
                ">

                <div class="w-5 h-5 shrink-0 flex items-center justify-center">

                    <img
                        src="<?= base_url('icons/add.png'); ?>"
                        alt="Add"
                        class="w-5 h-5 object-contain block">

                </div>

                <span class="font-medium">
                    New Employee
                </span>

            </button>

        </div>

    </div>

    <?= view('components/crud_table', [
        'title' => 'Employee Management',
        'tableId' => 'employeeTable',
        'grid' => 'grid-cols-[2fr_1.5fr_1.5fr_1.2fr_0.9fr_0.8fr_0.8fr_0.8fr_1fr]',
        'columns' => [
            'Employee Name',
            'Position',
            'Department',
            'ID',
            'Type',
            'Leave Total',
            'Leave Left',
            'Status',
            'Action'
        ]
    ]) ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <?php
    $content = view('client_portal/modals/add_employee_form');
    echo view('components/add_employee_modal', [
        'id' => 'employeeModal',
        'title' => 'Employee Management',
        'content' => $content
    ]);
    ?>

    <?php
    $content = view('client_portal/modals/edit_employee_form');
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
<script src="<?= base_url('js/components/crud-actions.js') ?>"></script>
<script src="<?= base_url('js/components/crud-table.js') ?>"></script>
<script src="<?= base_url('js/components/password-toggle.js') ?>"></script>

<script>

const EMPLOYEES = <?= json_encode($employees) ?>;
const CURRENT_USER_ID = <?= (int) ($currentUserId ?? 0) ?>;
const EDIT_ICON = "<?= base_url('icons/edit.png') ?>";
const VIEW_ICON = "<?= base_url('icons/view.png') ?>";
const UPDATE_URL = "<?= site_url('client_portal/employees/update') ?>";

    function htmlEsc(val) {
        if (val == null) return '';
        return String(val)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

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
                        <img src="${VIEW_ICON}" class="w-7 h-7" alt="View">
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

                        data-department="${htmlEsc(p.department)}"
                        data-position="${htmlEsc(p.position)}"
                        data-employment_type="${htmlEsc(p.employment_type)}"

                        data-working_day_type="${htmlEsc(p.working_day_type)}"
                        data-working_days='${htmlEsc(p.working_days || "[]")}'
                        data-start_time="${htmlEsc(p.start_time)}"
                        data-end_time="${htmlEsc(p.end_time)}"

                        data-sss="${htmlEsc(p.sss)}"
                        data-tin="${htmlEsc(p.tin)}"
                        data-pagibig="${htmlEsc(p.pagibig)}"
                        data-philhealth="${htmlEsc(p.philhealth)}"

                        data-email="${htmlEsc(p.email)}"

                        data-leave_total="${htmlEsc(p.leave_total)}"
                        data-status="${htmlEsc(p.status ?? 0)}">

                        <img src="${EDIT_ICON}" class="w-7 h-7" alt="Edit">
                    </div>

                </div>

            </div>
            `;
        }

    function viewEmployeeDetails(employeeId) {

        const employee = EMPLOYEES.find(emp => emp.id == employeeId);

        if (!employee) return;

        let workingDays = [];

        try {
            workingDays = JSON.parse(employee.working_days || '[]');
        } catch (e) {}

        const html = `

            <!-- HEADER BANNER -->
            <div class="bg-[#A891FC] rounded-t-xl px-8 py-6 flex items-center gap-6 -mx-6 mb-6">
                <div class="w-20 h-20 rounded-full bg-white/30 flex items-center justify-center shrink-0 overflow-hidden border-4 border-white/50">
                    ${employee.photo_path
                        ? `<img src="${employee.photo_path}" alt="${employee.first_name} ${employee.last_name}" class="w-full h-full object-cover">`
                        : `<svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>`
                    }
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">${employee.first_name} ${employee.last_name}</h1>
                    <p class="text-white/80 italic text-sm mt-0.5">${employee.position ?? 'No Position Set'}</p>
                    <span class="inline-block mt-2 px-3 py-0.5 rounded-full text-xs font-semibold ${employee.status == 1 ? 'bg-green-400/30 text-white border border-green-300/50' : 'bg-red-400/30 text-white border border-red-300/50'}">
                        ${employee.status == 1 ? 'Active' : 'Inactive'}
                    </span>
                </div>
                <div class="ml-auto text-right">
                    <p class="text-white/60 text-xs uppercase tracking-widest">Employee ID</p>
                    <p class="text-white font-bold text-lg">${employee.employee_number}</p>
                </div>
            </div>

            <!-- ADDRESS / CONTACTS ROW -->
            <div class="flex divide-x divide-gray-200 mb-6 px-2">
                <div class="flex-1 pr-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Department</p>
                    <p class="text-sm text-gray-700">${employee.department ?? '—'}</p>
                </div>
                <div class="flex-1 px-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Email</p>
                    <p class="text-sm text-gray-700">${employee.email ?? '—'}</p>
                </div>
                <div class="flex-1 pl-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Birthdate</p>
                    <p class="text-sm text-gray-700">${employee.birthdate ?? '—'}</p>
                </div>
            </div>

            <hr class="border-gray-200 mb-6">

            <!-- PERSONAL SKILLS / DEGREES ROW (Personal Info + Gov IDs) -->
            <div class="flex divide-x divide-gray-200 mb-6 px-2">

                <!-- Personal Info -->
                <div class="flex-1 pr-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Personal Information</p>
                    <div class="space-y-3">
                        ${viewField('First Name', employee.first_name)}
                        ${viewField('Middle Name', employee.middle_name)}
                        ${viewField('Last Name', employee.last_name)}
                    </div>
                </div>

                <!-- Gov IDs -->
                <div class="flex-1 pl-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Government IDs</p>
                    <div class="space-y-3">
                        ${viewField('SSS', employee.sss)}
                        ${viewField('TIN', employee.tin)}
                        ${viewField('Pagibig', employee.pagibig)}
                        ${viewField('PhilHealth', employee.philhealth)}
                    </div>
                </div>

            </div>

            <hr class="border-gray-200 mb-6">

            <!-- HOBBIES & INTERESTS ROW (Work Schedule) -->
            <div class="px-2 mb-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Work Schedule</p>

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
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-2">Working Days</p>
                    <div class="flex gap-2 flex-wrap">
                        ${['mon','tue','wed','thu','fri','sat','sun'].map(day => {
                            const active = workingDays.includes(day);
                            return `
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-semibold
                                    ${active ? 'bg-[#A891FC] text-white' : 'bg-gray-100 text-gray-400'}">
                                    ${day.charAt(0).toUpperCase() + day.slice(1)}
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 mb-6">

            <!-- JOB DETAILS -->
            <div class="px-2">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Job Details</p>
                <div class="flex divide-x divide-gray-200">
                    <div class="flex-1 pr-6 space-y-3">
                        ${viewField('Department', employee.department)}
                        ${viewField('Position', employee.position)}
                        ${viewField('Status', employee.status == 1 ? 'Active' : 'Inactive')}
                    </div>
                    <div class="flex-1 pl-6 space-y-3">
                        ${viewField('Leave Total', employee.leave_total + ' days')}
                        ${viewField('Leave Remaining', employee.leave_remaining + ' days')}
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

    createCrudTable({
        tableId: "employeeTable",
        data: EMPLOYEES,
        renderRow: employeeRowTemplate,
        search: (item, q) =>
            `${item.first_name} ${item.middle_name} ${item.last_name} 
            ${item.position} ${item.department} ${item.employee_number}
            ${item.employment_type} ${item.status} ${item.leave_remaining} `
            .toLowerCase()
            .includes(q)
    });

    document.addEventListener('click', function(e) {

        const button = e.target.closest('[data-action="edit"]');

        if (!button) return;

        /*
        |--------------------------------------------------------------------------
        | Populate Basic Fields
        |--------------------------------------------------------------------------
        */

        document.getElementById('edit_employee_id').value = button.dataset.id;

        document.getElementById('edit_first_name').value = button.dataset.first_name || '';
        document.getElementById('edit_middle_name').value = button.dataset.middle_name || '';
        document.getElementById('edit_last_name').value = button.dataset.last_name || '';
        document.getElementById('edit_dob').value = button.dataset.dob || '';

        document.getElementById('edit_department').value = button.dataset.department || '';
        document.getElementById('edit_position').value = button.dataset.position || '';
        document.getElementById('edit_employment_type').value = button.dataset.employment_type || '';

        document.getElementById('edit_working_day_type').value = button.dataset.working_day_type || '';

        document.getElementById('edit_start_time').value = button.dataset.start_time || '';
        document.getElementById('edit_end_time').value = button.dataset.end_time || '';

        document.getElementById('edit_sss').value = button.dataset.sss || '';
        document.getElementById('edit_tin').value = button.dataset.tin || '';
        document.getElementById('edit_pagibig').value = button.dataset.pagibig || '';
        document.getElementById('edit_philhealth').value = button.dataset.philhealth || '';

        document.getElementById('edit_email').value = button.dataset.email || '';

        document.getElementById('edit_leave_total').value = button.dataset.leave_total || '';

        document.getElementById('edit_status').value = button.dataset.status || '1';

        /*
        |--------------------------------------------------------------------------
        | Working Days
        |--------------------------------------------------------------------------
        */

        let workingDays = [];

        try {

            workingDays = JSON.parse(
                button.dataset.working_days || '[]'
            );

        } catch (e) {

            console.error('Working day parse error:', e);

            workingDays = [];
        }

        document.querySelectorAll('.edit-day-checkbox').forEach(cb => {

            cb.checked = false;

            const dayBtn = cb.closest('.edit-day-toggle')
                .querySelector('.edit-day-btn');

            dayBtn.classList.remove(
                'bg-[#A891FC]',
                'border-[#A891FC]',
                'text-white'
            );

            dayBtn.classList.add(
                'bg-white',
                'border-gray-300',
                'text-gray-500'
            );

            if (workingDays.includes(cb.value)) {

                cb.checked = true;

                dayBtn.classList.add(
                    'bg-[#A891FC]',
                    'border-[#A891FC]',
                    'text-white'
                );

                dayBtn.classList.remove(
                    'bg-white',
                    'border-gray-300',
                    'text-gray-500'
                );
            }

        });

        /*
        |--------------------------------------------------------------------------
        | Update Form Action
        |--------------------------------------------------------------------------
        */

        const form = document.getElementById('editEmployeeForm');

        form.action = `${button.dataset.updateUrl}/${button.dataset.id}`;

    });

    document
    .getElementById('employeeImportInput')
    .addEventListener('change', async function(e) {

        const file = e.target.files[0];

        if (!file) return;

        const formData = new FormData();

        formData.append('file', file);

        try {

            const response = await fetch(
                "<?= site_url('client_portal/employees/import') ?>",
                {
                    method: 'POST',
                    body: formData
                }
            );

            const result = await response.json();

            if (result.success) {

                alert(result.message);

                location.reload();

            } else {

                alert(result.message || 'Import failed.');

            }

        } catch (err) {

            console.error(err);

            alert('Import failed.');

        }

    });

    document
    .getElementById('exportEmployeesBtn')
    .addEventListener('click', function() {

        window.location.href =
            "<?= site_url('client_portal/employees/export') ?>";

    });
</script>

<?= $this->endSection() ?>