<style>
.hide-scroll-bar::-webkit-scrollbar {
    display: none;
}

.hide-scroll-bar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.edit-form-input {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #e5e7eb;
    background: white;
    border-radius: 14px;
    padding: 11px 14px;
    font-size: 14px;
    transition: all .2s ease;
}

.edit-form-input:focus {
    outline: none;
    border-color: #A891FC;
    box-shadow: 0 0 0 3px rgba(168,145,252,.18);
}

.edit-form-select {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #e5e7eb;
    background: white;
    border-radius: 14px;
    padding: 11px 14px;
    font-size: 14px;
    transition: all .2s ease;
}

.edit-form-select:focus {
    outline: none;
    border-color: #A891FC;
    box-shadow: 0 0 0 3px rgba(168,145,252,.18);
}

.edit-form-label {
    display: block;
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 6px;
}

.edit-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #9ca3af;
}

.edit-soft-card {
    border-radius: 24px;
    padding: 20px;
}

.edit-day-btn {
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    border: 2px solid #d1d5db;
    background: #ffffff;
    color: #6b7280;
    transition: all .15s ease;
}

.edit-day-btn:hover {
    border-color: #A891FC;
    color: #A891FC;
}

.edit-day-checkbox:checked + .edit-day-btn {
    background: #A891FC;
    border-color: #A891FC;
    color: #ffffff;
}

.edit-btn-primary {
    background: #A891FC;
    color: white;
    padding: 10px 22px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: .2s ease;
}

.edit-btn-primary:hover {
    background: #8e73f7;
}

/* teal-* Tailwind utilities aren't in the compiled main.css safelist,
   so this section's accent color is defined directly instead. */
.edit-teal-icon-link {
    color: #14b8a6;
    transition: color .15s ease;
}

.edit-teal-icon-link:hover {
    color: #0f766e;
}
</style>

<form id="editEmployeeForm" method="post" class="max-h-[75vh] overflow-y-auto px-1 hide-scroll-bar">
<?= csrf_field() ?>

<input type="hidden" id="edit_employee_id">

<div class="space-y-6">

    <!-- ===================================================== -->
    <!-- PERSONAL INFORMATION -->
    <!-- ===================================================== -->

    <div>
        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Personal Information</h2>
            <p class="text-sm text-gray-500 mt-1">Update the employee's personal details and contact information.</p>
        </div>

        <div class="edit-soft-card bg-gradient-to-br from-violet-50 to-purple-50">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="edit-form-label">First Name</label>
                    <input type="text" id="edit_first_name" name="first_name" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Middle Name</label>
                    <input type="text" id="edit_middle_name" name="middle_name" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Last Name</label>
                    <input type="text" id="edit_last_name" name="last_name" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Date of Birth</label>
                    <input type="date" id="edit_dob" name="dob" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Gender</label>
                    <select id="edit_gender" name="gender" class="edit-form-select">
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="edit-form-label">Civil Status</label>
                    <select id="edit_civil_status" name="civil_status" class="edit-form-select">
                        <option value="">Select civil status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="edit-form-label">Contact Number</label>
                    <input type="text" id="edit_mobile_number" name="mobile_number" class="edit-form-input">
                </div>

                <div class="md:col-span-2">
                    <label class="edit-form-label">Address</label>
                    <input type="text" id="edit_address" name="address" class="edit-form-input">
                </div>

            </div>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- GOVERNMENT IDS -->
    <!-- ===================================================== -->

    <div>
        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Government IDs</h2>
            <p class="text-sm text-gray-500 mt-1">Mandatory government identification details.</p>
        </div>

        <div class="space-y-4 bg-gray-50 rounded-3xl p-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="edit-form-label">SSS Number</label>
                    <input type="text" id="edit_sss" name="sss" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">TIN Number</label>
                    <input type="text" id="edit_tin" name="tin" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Pag-IBIG Number</label>
                    <input type="text" id="edit_pagibig" name="pagibig" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">PhilHealth Number</label>
                    <input type="text" id="edit_philhealth" name="philhealth" class="edit-form-input">
                </div>

            </div>

        </div>
    </div>

    <!-- ===================================================== -->
    <!-- WORK SCHEDULE -->
    <!-- ===================================================== -->

    <div>

        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Work Schedule</h2>
            <p class="text-sm text-gray-500 mt-1">Configure the employee's working schedule and setup.</p>
        </div>

        <div class="bg-indigo-50 rounded-3xl p-6 space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="edit-form-label">Working Day Type</label>
                    <select id="edit_working_day_type" name="working_day_type" class="edit-form-select" onchange="toggleEditScheduleTime(this.value)">
                        <option value="full_weekdays">Fixed – Mon to Fri</option>
                        <option value="weekday_with_rest">Fixed – Weekdays with 1 Rest Day</option>
                        <option value="weekend_shift">Fixed – Weekend Shift (Sat–Sun)</option>
                        <option value="custom">Fixed – Custom Days</option>
                        <option value="flexible">Flexible – No Fixed Start Time</option>
                    </select>
                </div>

                <div>
                    <label class="edit-form-label">Employment Type</label>
                    <select id="edit_employment_type" name="employment_type" class="edit-form-select">
                        <option value="">Select employment type</option>
                        <?php foreach ($employmentTypeOptions ?? [] as $type): ?>
                            <option value="<?= esc($type) ?>"><?= esc($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div>
                <label class="edit-form-label mb-3">Working Days</label>

                <div class="flex flex-wrap gap-3">

                    <?php
                    $days = [
                        'mon' => 'Mon',
                        'tue' => 'Tue',
                        'wed' => 'Wed',
                        'thu' => 'Thu',
                        'fri' => 'Fri',
                        'sat' => 'Sat',
                        'sun' => 'Sun'
                    ];

                    foreach ($days as $value => $label):
                    ?>

                    <label class="edit-day-toggle cursor-pointer">

                        <input
                            type="checkbox"
                            name="working_days[]"
                            value="<?= $value ?>"
                            class="sr-only edit-day-checkbox">

                        <span class="edit-day-btn">
                            <?= $label ?>
                        </span>

                    </label>

                    <?php endforeach; ?>

                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="edit_schedule_time_row">

                <div>
                    <label class="edit-form-label">Start Time</label>
                    <input type="time" id="edit_start_time" name="start_time" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">End Time</label>
                    <input type="time" id="edit_end_time" name="end_time" class="edit-form-input">
                </div>

            </div>

            <div>
                <label class="edit-form-label">
                    Break Schedule
                    <span class="text-xs text-gray-400 font-normal">(set each break's own duration)</span>
                </label>

                <div id="edit_break_list" class="space-y-2"></div>

                <button type="button" onclick="addBreakRow('edit')"
                    class="text-xs text-[#7b5cff] hover:underline mt-2">
                    + Add Break
                </button>

                <input type="hidden" name="break_schedule" id="edit_break_schedule_input">
                <input type="hidden" name="break_windows" id="edit_break_windows_input">
            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- EMPLOYMENT DETAILS -->
    <!-- ===================================================== -->

    <div>

        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Employment Details</h2>
            <p class="text-sm text-gray-500 mt-1">Organizational and account information.</p>
        </div>

        <div class="bg-slate-50 rounded-3xl p-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="edit-form-label">Department</label>
                    <select id="edit_department_id" name="department_id" class="edit-form-input">
                        <option value="">-- Select department --</option>
                        <?php foreach ($activeDepartments ?? [] as $dept): ?>
                            <option value="<?= esc($dept['id']) ?>"><?= esc($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="edit-form-label">Position</label>
                    <input type="text" id="edit_position" name="position" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Email</label>
                    <input type="email" id="edit_email" name="email" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Basic Salary</label>
                    <input type="number" id="edit_monthly_salary" name="monthly_salary" min="0" step="0.01" class="edit-form-input">
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- DE MINIMIS BENEFITS -->
    <!-- ===================================================== -->

    <div>

        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">De Minimis Benefits</h2>
            <p class="text-sm text-gray-500 mt-1">Non-taxable benefit allocation. The total is the figure used in payroll.</p>
        </div>

        <div class="bg-violet-50 rounded-3xl p-6 space-y-4">

            <div class="flex items-center justify-between">

                <h3 class="edit-section-title text-violet-800">Benefit Breakdown</h3>

                <button type="button"
                    id="edit_de_minimis_reset"
                    title="Reset De Minimis fields"
                    class="text-violet-500 hover:text-violet-700 hover:rotate-180 transition-all duration-300">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>

                </button>

            </div>

            <div>
                <label class="edit-form-label">Amount Given (Total)</label>
                <input type="number"
                    id="edit_de_minimis_benefit"
                    name="de_minimis_benefit"
                    min="0"
                    step="0.01"
                    placeholder="e.g., 3300"
                    class="edit-form-input">
            </div>

            <input type="hidden" id="edit_de_minimis_breakdown" name="de_minimis_breakdown">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="edit-form-label">Rice Subsidy</label>
                    <input type="number"
                        id="edit_rice_subsidy"
                        data-key="rice_subsidy"
                        placeholder="e.g., 1500"
                        class="edit-de-minimis-input edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Uniform / Clothing</label>
                    <input type="number"
                        id="edit_uniform_allowance"
                        data-key="uniform_allowance"
                        placeholder="e.g., 1000"
                        class="edit-de-minimis-input edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Laundry Allowance</label>
                    <input type="number"
                        id="edit_laundry_allowance"
                        data-key="laundry_allowance"
                        placeholder="e.g., 300"
                        class="edit-de-minimis-input edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Medical Allowance</label>
                    <input type="number"
                        id="edit_medical_allowance"
                        data-key="medical_allowance"
                        placeholder="e.g., 500"
                        class="edit-de-minimis-input edit-form-input">
                </div>

            </div>

            <div class="flex items-center justify-between text-sm font-medium pt-1 px-1">
                <span class="text-gray-600">Allocated: <span id="edit_allocated_total">0</span></span>
                <span id="edit_remaining_wrap" class="text-gray-600">
                    Remaining: <span id="edit_remaining_total">0</span>
                </span>
            </div>

            <!-- OTHER NON TAXABLE -->
            <div class="border-t border-violet-200 pt-4 space-y-4">

                <div class="flex items-center justify-between">

                    <div>
                        <h4 class="edit-section-title text-violet-800">Other Non-Taxable Compensation</h4>
                        <p class="text-xs text-gray-500 mt-1">Add any additional non-taxable benefit and specify its purpose.</p>
                    </div>

                    <button type="button"
                        id="edit_other_comp_add"
                        class="shrink-0 px-4 h-10 rounded-[14px] text-sm font-semibold bg-violet-200 text-violet-800 hover:bg-violet-300 transition-colors">
                        + Add Benefit
                    </button>

                </div>

                <div id="edit_other_comp_list" class="space-y-3"></div>

                <div class="flex items-center justify-end text-sm font-medium pt-1 px-1">
                    <span class="text-gray-600">Total: <span id="edit_other_comp_total_display">0</span></span>
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- ADDITIONAL DE MINIMIS (BIR) — a separate, additive set of
         categories from the "De Minimis Benefits" section above, which
         stays completely untouched: different fields, different total
         (additional_de_minimis_benefit / _breakdown), computed and added
         on top of payroll's existing non-taxable/taxable totals rather
         than merged into them. -->
    <!-- ===================================================== -->

    <div>

        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Additional De Minimis (BIR)</h2>
            <p class="text-sm text-gray-500 mt-1">Other BIR-recognized categories (RR No. 29-2025), on top of the breakdown above. Amounts over a category's cap are taxed automatically when the payslip is generated.</p>
        </div>

        <div class="rounded-3xl p-6 space-y-4" style="background:#f0fdfa;">

            <div class="flex items-center justify-between">

                <h3 class="edit-section-title" style="color:#115e59;">Category Breakdown</h3>

                <a href="<?= site_url('client_portal/payroll') ?>"
                    title="Manage categories in Payroll Settings"
                    class="edit-teal-icon-link">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>

                </a>

            </div>

            <div>
                <label class="edit-form-label">Total (computed from the breakdown below)</label>
                <div class="edit-form-input font-semibold" style="color:#115e59;">
                    ₱<span id="edit_additional_deminimis_total">0</span>
                </div>
            </div>

            <div id="edit_additional_deminimis_list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <p class="text-sm text-gray-400 md:col-span-2">Loading categories...</p>
            </div>

            <input type="hidden" id="edit_additional_de_minimis_benefit" name="additional_de_minimis_benefit">
            <input type="hidden" id="edit_additional_de_minimis_breakdown" name="additional_de_minimis_breakdown">

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- COMPENSATION & LEAVE -->
    <!-- ===================================================== -->

    <div>

        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Compensation &amp; Leave</h2>
            <p class="text-sm text-gray-500 mt-1">General allowances and leave entitlements.</p>
        </div>

        <div class="bg-emerald-50 rounded-3xl p-6 space-y-5">

            <div>
                <label class="edit-form-label">Non-Basic Allowances <span class="text-xs font-normal text-gray-500">(excluded from OT/NSD base per DOLE)</span></label>
                <input type="number" id="edit_allowances" name="allowances" min="0" step="0.01" class="edit-form-input" placeholder="e.g., 2925">
                <p class="text-xs text-gray-400 mt-1">Monthly allowances included in Basic Salary above that are NOT part of basic wage (e.g., transportation, COLA). OT and NSD will be computed on Basic Salary minus this amount.</p>
            </div>

            <div>
                <p class="edit-section-title mb-3">Leave Entitlements (days)</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <div>
                        <label class="edit-form-label">Vacation Leave</label>
                        <input type="number" id="edit_vacation_leave" name="vacation_leave" min="0" step="0.5" class="edit-form-input">
                    </div>

                    <div>
                        <label class="edit-form-label">Sick Leave</label>
                        <input type="number" id="edit_sick_leave" name="sick_leave" min="0" step="0.5" class="edit-form-input">
                    </div>

                    <div>
                        <label class="edit-form-label">Emergency Leave</label>
                        <input type="number" id="edit_emergency_leave" name="emergency_leave" min="0" step="0.5" class="edit-form-input">
                    </div>

                </div>
            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- ACCOUNT STATUS -->
    <!-- ===================================================== -->

    <div>
        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Account Status</h2>
        </div>

        <div class="bg-amber-50 rounded-3xl p-6">

            <label class="edit-form-label">Status</label>
            <select id="edit_status" name="status" class="edit-form-select">
                <option value="1">Active</option>
                <option value="0">Inactive / Deactivated</option>
            </select>

            <p class="mt-2 text-xs text-gray-500">Inactive employees can no longer log in.</p>

            <p id="selfDeactivateGuardNote" class="mt-1 text-xs text-amber-700 hidden">
                You cannot deactivate your own account.
            </p>
        </div>
    </div>

    <!-- ===================================================== -->
    <!-- BANKING INFORMATION -->
    <!-- ===================================================== -->

    <div>

        <div class="mb-3">
            <h2 class="text-xl font-semibold text-gray-800">Banking Information</h2>
            <p class="text-sm text-gray-500 mt-1">Payroll deposit account details.</p>
        </div>

        <div class="bg-blue-50 rounded-3xl p-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="edit-form-label">Bank Name</label>
                    <input type="text" id="edit_bank" name="bank" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">SWIFT Code</label>
                    <input type="text" id="edit_swift_code" name="swift_code" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Account Number</label>
                    <input type="text" id="edit_account_number" name="account_number" class="edit-form-input">
                </div>

                <div>
                    <label class="edit-form-label">Branch Address</label>
                    <input type="text" id="edit_bank_address" name="bank_address" class="edit-form-input">
                </div>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- SUBMIT -->
    <!-- ===================================================== -->

    <div class="flex justify-end pt-4 border-t border-gray-200">

        <button type="submit" class="edit-btn-primary">
            Update Employee
        </button>

    </div>

</div>

</form>

<script>
function toggleEditScheduleTime(type) {
    const row = document.getElementById('edit_schedule_time_row');
    if (row) row.style.display = (type === 'flexible') ? 'none' : '';
    if (typeof toggleBreakWindowUI === 'function') toggleBreakWindowUI('edit', type);
}

document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {

    const checkedDays = document.querySelectorAll(
        '#editEmployeeForm input[name="working_days[]"]:checked'
    );

    if (!checkedDays.length) {
        e.preventDefault();
        showToast('Please select at least one working day.', 'warning');
        return;
    }

    const startTime = document.getElementById('edit_start_time').value;
    const endTime = document.getElementById('edit_end_time').value;

    if (startTime && endTime && startTime === endTime) {
        e.preventDefault();
        showToast('Start and end must differ. An earlier end means the following day.', 'warning');
        return;
    }

    const deMinTotal = parseFloat(document.getElementById('edit_de_minimis_benefit')?.value) || 0;
    let deMinAllocated = 0;
    document.querySelectorAll('.edit-de-minimis-input').forEach(input => {
        deMinAllocated += parseFloat(input.value) || 0;
    });
    if (deMinAllocated > deMinTotal) {
        e.preventDefault();
        showToast('De Minimis sub-benefits exceed the Amount Given (Total). Please adjust the values.', 'warning');
        return;
    }
});

/* ===================================================== */
/* EDIT DE MINIMIS SYSTEM */
/* ===================================================== */

const editBreakdownState = {
    allocations: {},
    other: []
};

function updateEditBreakdownHidden() {
    const hidden = document.getElementById('edit_de_minimis_breakdown');
    hidden.value = JSON.stringify({
        allocations: editBreakdownState.allocations,
        other: editBreakdownState.other
    });
}

(function () {

    const totalInput = document.getElementById('edit_de_minimis_benefit');
    if (!totalInput) return;

    const allocationInputs = document.querySelectorAll('.edit-de-minimis-input');
    const allocatedEl = document.getElementById('edit_allocated_total');
    const remainingEl = document.getElementById('edit_remaining_total');
    const remainingWrap = document.getElementById('edit_remaining_wrap');
    const otherCompTotalDisplay = document.getElementById('edit_other_comp_total_display');

    function recalc() {
        const total = parseFloat(totalInput.value) || 0;
        let allocated = 0;
        const allocations = {};

        allocationInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            allocated += value;
            allocations[input.dataset.key] = value;
        });

        editBreakdownState.allocations = allocations;

        const remaining = total - allocated;

        allocatedEl.textContent = allocated.toLocaleString();
        remainingEl.textContent = remaining.toLocaleString();

        if (remaining < 0) {
            remainingWrap.classList.remove('text-gray-600');
            remainingWrap.classList.add('text-red-600');
        } else {
            remainingWrap.classList.remove('text-red-600');
            remainingWrap.classList.add('text-gray-600');
        }

        updateEditBreakdownHidden();
    }

    totalInput.addEventListener('input', recalc);
    allocationInputs.forEach(input => input.addEventListener('input', recalc));

    document.getElementById('edit_de_minimis_reset').addEventListener('click', function () {
        totalInput.value = '';
        allocationInputs.forEach(input => input.value = '');
        document.getElementById('edit_other_comp_list').innerHTML = '';
        editBreakdownState.other = [];
        if (otherCompTotalDisplay) otherCompTotalDisplay.textContent = '0';
        recalc();
    });

    window.loadEditBreakdown = function (breakdownJson) {
        try {
            const breakdown = JSON.parse(breakdownJson || '{}');
            const allocations = breakdown.allocations || {};
            const other = breakdown.other || [];

            document.getElementById('edit_rice_subsidy').value = allocations.rice_subsidy || '';
            document.getElementById('edit_uniform_allowance').value = allocations.uniform_allowance || '';
            document.getElementById('edit_laundry_allowance').value = allocations.laundry_allowance || '';
            document.getElementById('edit_medical_allowance').value = allocations.medical_allowance || '';

            const list = document.getElementById('edit_other_comp_list');
            list.innerHTML = '';

            other.forEach(item => {
                addEditOtherCompRow(item.purpose || '', item.amount || '');
            });

            recalc();
            syncOther();

        } catch (e) {
            console.error(e);
        }
    };

    function addEditOtherCompRow(purpose = '', amount = '') {
        const list = document.getElementById('edit_other_comp_list');
        const row = document.createElement('div');
        row.className = 'other-comp-row';
        row.style.display = 'flex';
        row.style.gap = '8px';
        row.style.alignItems = 'flex-start';
        row.style.width = '100%';

        row.innerHTML = `
            <input type="text"
                class="edit-other-purpose edit-form-input"
                placeholder="e.g., Transportation Allowance"
                value="${purpose}"
                style="flex: 1 1 0%; min-width: 0;">

            <input type="number"
                class="edit-other-amount edit-form-input"
                placeholder="Amount"
                value="${amount}"
                style="flex: 0 0 128px; width: 128px;">

            <button type="button"
                class="remove-other-comp"
                title="Remove"
                style="flex-shrink: 0; width: 40px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 14px; color: #9ca3af; transition: color .2s, background-color .2s; border: none; background: transparent; cursor: pointer;"
                onmouseover="this.style.color='#ef4444'; this.style.backgroundColor='#fef2f2';"
                onmouseout="this.style.color='#9ca3af'; this.style.backgroundColor='transparent';">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;

        row.querySelector('.remove-other-comp').addEventListener('click', function () {
            row.remove();
            syncOther();
        });

        row.querySelector('.edit-other-purpose').addEventListener('input', syncOther);
        row.querySelector('.edit-other-amount').addEventListener('input', syncOther);

        list.appendChild(row);
        syncOther();
    }

    function syncOther() {
        const rows = document.querySelectorAll('#edit_other_comp_list > div');
        const data = [];
        let total = 0;

        rows.forEach(row => {
            const purpose = row.querySelector('.edit-other-purpose').value;
            const amount = parseFloat(row.querySelector('.edit-other-amount').value) || 0;
            total += amount;
            data.push({ purpose, amount });
        });

        editBreakdownState.other = data;

        if (otherCompTotalDisplay) {
            otherCompTotalDisplay.textContent = total.toLocaleString();
        }

        updateEditBreakdownHidden();
    }

    document.getElementById('edit_other_comp_add').addEventListener('click', function () {
        addEditOtherCompRow();
    });

})();

/* ===================================================== */
/* ADDITIONAL DE MINIMIS (BIR) — separate from the existing */
/* De Minimis Benefits block above; own fields, own total.  */
/* ===================================================== */
(function () {

    const list = document.getElementById('edit_additional_deminimis_list');
    if (!list) return;

    const totalDisplay = document.getElementById('edit_additional_deminimis_total');
    const totalHidden = document.getElementById('edit_additional_de_minimis_benefit');
    const breakdownHidden = document.getElementById('edit_additional_de_minimis_breakdown');

    let categories = [];
    let pendingBreakdown = null; // set if loadAdditionalDeMinimis() is called before categories finish loading

    function recalc() {
        let total = 0;
        const breakdown = {};

        list.querySelectorAll('input[data-key]').forEach(input => {
            const value = parseFloat(input.value) || 0;
            total += value;
            breakdown[input.dataset.key] = value;
        });

        totalDisplay.textContent = total.toLocaleString();
        totalHidden.value = total;
        breakdownHidden.value = JSON.stringify(breakdown);
    }

    function applyBreakdown(breakdown) {
        list.querySelectorAll('input[data-key]').forEach(input => {
            input.value = breakdown[input.dataset.key] ?? '';
        });
        recalc();
    }

    function render() {
        const active = categories.filter(c => Number(c.is_active) === 1);

        if (!active.length) {
            list.innerHTML = '<p class="text-sm text-gray-400 md:col-span-2">No additional categories configured yet — set them up in Payroll Settings → De Minimis Categories.</p>';
            return;
        }

        list.innerHTML = active.map(c => {
            const capNote = c.note || (Number(c.cap_amount) > 0
                ? `Cap: ₱${Number(c.cap_amount).toLocaleString()} / ${c.cap_period.replace('_', ' ')}`
                : 'No fixed peso cap');
            return `
            <div>
                <label class="edit-form-label">${c.label}</label>
                <input type="number" step="0.01" min="0" data-key="${c.category_key}" placeholder="0.00" class="edit-form-input">
                <p class="text-xs text-gray-400 mt-1">${capNote}</p>
            </div>
        `;
        }).join('');

        list.querySelectorAll('input[data-key]').forEach(input => input.addEventListener('input', recalc));

        if (pendingBreakdown) {
            applyBreakdown(pendingBreakdown);
            pendingBreakdown = null;
        } else {
            recalc();
        }
    }

    window.loadAdditionalDeMinimis = function (breakdownJson) {
        let breakdown = {};
        try { breakdown = JSON.parse(breakdownJson || '{}'); } catch (e) { breakdown = {}; }

        if (categories.length) {
            applyBreakdown(breakdown);
        } else {
            pendingBreakdown = breakdown;
        }
    };

    fetch('<?= site_url('client_portal/payroll/deminimis/categories') ?>')
        .then(res => res.json())
        .then(data => {
            categories = Array.isArray(data) ? data : [];
            render();
        })
        .catch(err => {
            console.error('Failed to load additional De Minimis categories.', err);
            list.innerHTML = '<p class="text-sm text-red-400">Failed to load categories.</p>';
        });

})();
</script>
