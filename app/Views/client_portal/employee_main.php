<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="h-full flex flex-col min-h-0 overflow-visible">

    <div class="tabs">
        <button type="button" class="tab active" data-tab="employee-management">Employee Management</button>
        <?php if (\App\Libraries\PortalAccess::can('loans')): ?><button type="button" class="tab" data-tab="loan-management">Loan Management</button><?php endif; ?>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8"
        data-panel="employee-management"
        style="display:flex;">
        <?= view('client_portal/employee_management') ?>
    </div>

    <div class="tab-panel h-full min-h-0 overflow-visible px-8 pb-8" data-panel="loan-management" style="display:none;">
        <?php if (\App\Libraries\PortalAccess::can('loans')): ?><?= view('client_portal/employee_loan') ?><?php endif; ?>
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
<script src="<?= base_url('js/components/crud-table.js') ?>"></script>
<script src="<?= base_url('js/components/password-toggle.js') ?>"></script>

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
                        data-position="${htmlEsc(p.position)}"
                        data-employment_type="${htmlEsc(p.employment_type)}"

                        data-working_day_type="${htmlEsc(p.working_day_type)}"
                        data-working_days='${htmlEsc(p.working_days || "[]")}'
                        data-start_time="${htmlEsc(p.start_time)}"
                        data-end_time="${htmlEsc(p.end_time)}"
                        data-break_schedule='${htmlEsc(p.break_schedule || "[]")}'

                        data-sss="${htmlEsc(p.sss)}"
                        data-tin="${htmlEsc(p.tin)}"
                        data-pagibig="${htmlEsc(p.pagibig)}"
                        data-philhealth="${htmlEsc(p.philhealth)}"

                        data-email="${htmlEsc(p.email)}"
                        data-monthly_salary="${htmlEsc(p.monthly_salary)}"
                        data-de_minimis_benefit="${htmlEsc(p.de_minimis_benefit)}"
                        data-de_minimis_breakdown='${htmlEsc(p.de_minimis_breakdown || "{}")}'
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

        setVal('edit_department',        button.dataset.department);
        setVal('edit_position',          button.dataset.position);
        setVal('edit_employment_type',   button.dataset.employment_type);
        setVal('edit_email',             button.dataset.email);
        setVal('edit_monthly_salary',    button.dataset.monthly_salary);
        setVal('edit_de_minimis_benefit',button.dataset.de_minimis_benefit);
        loadEditBreakdown( button.dataset.de_minimis_breakdown || '{}' ); 

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
        setVal('edit_start_time',        button.dataset.start_time);
        setVal('edit_end_time',          button.dataset.end_time);
        if (typeof toggleEditScheduleTime === 'function') toggleEditScheduleTime(button.dataset.working_day_type);

        // Restore break schedule (falls back to a single 60-min break if unset)
        if (typeof rebuildBreakRows === 'function') {
            let breakMinutes = [];
            try { breakMinutes = JSON.parse(button.dataset.break_schedule || '[]'); } catch (e) { breakMinutes = []; }
            rebuildBreakRows('edit', Array.isArray(breakMinutes) && breakMinutes.length ? breakMinutes : [60]);
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