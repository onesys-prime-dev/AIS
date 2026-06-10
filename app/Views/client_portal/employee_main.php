<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="h-full flex flex-col min-h-0 overflow-visible">

    <div class="tabs">
        <button type="button" class="tab active" data-tab="employee-management">Employee Management</button>
        <button type="button" class="tab" data-tab="loan-management">Loan Management</button>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8"
        data-panel="employee-management"
        style="display:flex;">
        <?= view('client_portal/employee_management') ?>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8" data-panel="loan-management" style="display:none;">
        <?= view('client_portal/employee_loan') ?>
    </div>


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

    <?php
    $content = view('client_portal/modals/loan_settings');

    echo view('components/loan_settings_modal', [
        'id' => 'loanSettingsModal',
        'title' => 'Loan Settings',
        'content' => $content
    ]);
    ?>

    <?php
    $content = view('client_portal/modals/add_loan_form');

    echo view('components/modal', [
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
    ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
<script src="<?= base_url('js/components/crud-actions.js') ?>"></script>
<script src="<?= base_url('js/components/crud-table.js') ?>"></script>
<script src="<?= base_url('js/components/password-toggle.js') ?>"></script>

<script>

const EMPLOYEES = <?= json_encode($employees) ?>;
const LOANS = <?= json_encode($loans ?? []) ?>;
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

        try {
            workingDays = JSON.parse(employee.working_days || '[]');
        } catch (e) {}

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

                    <!-- ADDRESS / CONTACTS ROW -->
                    <div class="flex divide-x divide-gray-200 mb-6">
                        <div class="flex-1 pr-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
                                Department
                            </p>

                            <p class="text-sm text-gray-700">
                                ${employee.department ?? '—'}
                            </p>
                        </div>

                        <div class="flex-1 px-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
                                Email
                            </p>

                            <p class="text-sm text-gray-700">
                                ${employee.email ?? '—'}
                            </p>
                        </div>

                        <div class="flex-1 pl-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">
                                Birthdate
                            </p>

                            <p class="text-sm text-gray-700">
                                ${employee.birthdate ?? '—'}
                            </p>
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
                            </div>
                        </div>

                        <!-- Gov IDs -->
                        <div class="flex-1 pl-6">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                                Government IDs
                            </p>

                            <div class="space-y-3">
                                ${viewField('SSS', employee.sss)}
                                ${viewField('TIN', employee.tin)}
                                ${viewField('Pagibig', employee.pagibig)}
                                ${viewField('PhilHealth', employee.philhealth)}
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
                    <div class="pb-6">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                            Job Details
                        </p>

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
                    px-3 py-1 rounded-full text-xs font-semibold
                    ${loan.loan_status === 'active'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700'}
                ">
                    ${loan.loan_status || '—'}
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

                </div>

            </div>
        `;

        document.getElementById('loanDetailsContent').innerHTML =
            html;

        openModalById('viewLoanModal');
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
        document.getElementById('edit_employee_id').value =
            button.dataset.id || '';

        document.getElementById('edit_first_name').value =
            button.dataset.first_name || '';

        document.getElementById('edit_middle_name').value =
            button.dataset.middle_name || '';

        document.getElementById('edit_last_name').value =
            button.dataset.last_name || '';

        document.getElementById('edit_dob').value =
            button.dataset.dob || '';

        document.getElementById('edit_department').value =
            button.dataset.department || '';

        document.getElementById('edit_position').value =
            button.dataset.position || '';

        document.getElementById('edit_employment_type').value =
            button.dataset.employment_type || '';

        const form =
            document.getElementById('editEmployeeForm');

        form.action =
            `${UPDATE_URL}/${button.dataset.id}`;

        openModalById('editEmployeeModal');

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
            document.getElementById('edit_start_date').value = loan.start_date || '';
            document.getElementById('edit_installment_count').value = loan.installment_count || '';
            document.getElementById('edit_deduction_per_payroll').value = loan.deduction_per_payroll || '';
            
            // Fix: Use a select or hidden input for status
            const statusSelect = document.getElementById('edit_loan_status_display');
            if (statusSelect) {
                statusSelect.value = loan.loan_status || 'active';
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
        .includes(q)
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
    search: (item, q) =>
        `
        ${item.employee_name}
        ${item.loan_type}
        ${item.loan_status}
        `
        .toLowerCase()
        .includes(q)
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
            alert(result.message || 'Employees imported successfully.');
            window.location.reload();
        } else {
            alert('Import failed: ' + (result.message || 'Unknown error'));
        }
    } catch (err) {
        alert('Network error during import.');
        console.error(err);
    }
    this.value = '';
});

</script>
<?= $this->endSection() ?>