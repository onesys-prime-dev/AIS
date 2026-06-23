<form id="editEmployeeForm" method="post" class="max-h-[75vh] overflow-y-auto px-1 hide-scroll-bar">
<?= csrf_field() ?>

<input type="hidden" id="edit_employee_id">

<div class="space-y-8">

    <!-- ===================================================== -->
    <!-- PERSONAL INFORMATION -->
    <!-- ===================================================== -->

    <div>

        <h2 class="text-lg font-bold text-[#9764F6] mb-4">
            Personal Information
        </h2>

        <div class="grid grid-cols-2 gap-4">

            <div>
                <label class="block text-sm mb-1">First Name</label>
                <input type="text" id="edit_first_name" name="first_name"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Middle Name</label>
                <input type="text" id="edit_middle_name" name="middle_name"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Last Name</label>
                <input type="text" id="edit_last_name" name="last_name"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Date of Birth</label>
                <input type="date" id="edit_dob" name="dob"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Gender</label>
                <select id="edit_gender" name="gender"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Select --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-sm mb-1">Civil Status</label>
                <select id="edit_civil_status" name="civil_status"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Select --</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Separated">Separated</option>
                </select>
            </div>

            <div>
                <label class="block text-sm mb-1">Contact No.</label>
                <input type="text" id="edit_mobile_number" name="mobile_number"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

        </div>

        <div class="mt-4">
            <label class="block text-sm mb-1">Address</label>
            <input type="text" id="edit_address" name="address"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- GOVERNMENT IDS -->
    <!-- ===================================================== -->

    <div>

        <h2 class="text-lg font-bold text-[#9764F6] mb-4">
            Government IDs
        </h2>

        <div class="grid grid-cols-2 gap-4">

            <div>
                <label class="block text-sm mb-1">SSS</label>
                <input type="text"
                    id="edit_sss"
                    name="sss"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">TIN</label>
                <input type="text"
                    id="edit_tin"
                    name="tin"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Pagibig</label>
                <input type="text"
                    id="edit_pagibig"
                    name="pagibig"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">PhilHealth</label>
                <input type="text"
                    id="edit_philhealth"
                    name="philhealth"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- WORK SCHEDULE -->
    <!-- ===================================================== -->

    <div>

        <h2 class="text-lg font-bold text-[#9764F6] mb-4">
            Work Schedule
        </h2>

        <div class="grid grid-cols-2 gap-4">

            <div>
                <label class="block text-sm mb-1">Schedule Type</label>

                <select
                    id="edit_working_day_type"
                    name="working_day_type"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    onchange="toggleEditScheduleTime(this.value)">

                    <option value="full_weekdays">Fixed – Mon to Fri</option>
                    <option value="weekday_with_rest">Fixed – Weekdays with 1 Rest Day</option>
                    <option value="weekend_shift">Fixed – Weekend Shift (Sat–Sun)</option>
                    <option value="custom">Fixed – Custom Days</option>
                    <option value="flexible">Flexible – No Fixed Start Time</option>

                </select>
                <p class="text-xs text-gray-400 mt-1">Fixed schedules track tardiness. Flexible only requires completing daily hours.</p>
            </div>

            <div>
                <label class="block text-sm mb-1">
                    Employment Type
                </label>

                <input type="text"
                    id="edit_employment_type"
                    name="employment_type"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div id="edit_start_time_wrap">
                <label class="block text-sm mb-1">Start Time</label>

                <input type="time"
                    id="edit_start_time"
                    name="start_time"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div id="edit_end_time_wrap">
                <label class="block text-sm mb-1">End Time</label>

                <input type="time"
                    id="edit_end_time"
                    name="end_time"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

        </div>

        <!-- WORKING DAYS -->

        <div class="mt-4">

            <label class="block text-sm mb-2">
                Working Days
            </label>

            <div class="flex gap-2 flex-wrap">

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

                    <span class="
                        edit-day-btn
                        flex items-center justify-center
                        w-10 h-10
                        rounded-full
                        text-xs font-semibold
                        border-2
                        transition-all duration-150

                        bg-white
                        border-gray-300
                        text-gray-500

                        hover:border-[#A891FC]
                        hover:text-[#A891FC]
                    ">
                        <?= $label ?>
                    </span>

                </label>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- EMPLOYMENT DETAILS -->
    <!-- ===================================================== -->

    <div>

        <h2 class="text-lg font-bold text-[#9764F6] mb-4">
            Employment Details
        </h2>

        <div class="grid grid-cols-2 gap-4">

            <div>
                <label class="block text-sm mb-1">Department</label>
                <input type="text" id="edit_department" name="department"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Position</label>
                <input type="text" id="edit_position" name="position"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Email</label>
                <input type="email" id="edit_email" name="email"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Basic Salary</label>
                <input type="number" id="edit_monthly_salary" name="monthly_salary" min="0" step="0.01"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- ===================================================== -->
            <!-- DE MINIMIS BENEFITS -->
            <!-- ===================================================== -->

            <div class="mt-6">

                <h2 class="text-lg font-bold text-[#9764F6] mb-4">
                    De Minimis Benefits
                </h2>

                <div class="bg-violet-50 rounded-3xl p-6 space-y-4">

                    <div class="flex items-center justify-between">

                        <h3 class="text-sm font-semibold text-violet-800 uppercase tracking-wide">
                            Benefit Breakdown
                        </h3>

                        <button type="button"
                            id="edit_de_minimis_reset"
                            class="text-violet-500 hover:text-violet-700 transition-all duration-300">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>

                        </button>

                    </div>

                    <!-- TOTAL -->
                    <div>

                        <label class="block text-sm mb-2">
                            Amount Given (Total)
                        </label>

                        <input type="number"
                            id="edit_de_minimis_benefit"
                            name="de_minimis_benefit"
                            min="0"
                            step="0.01"
                            class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">

                    </div>

                    <input type="hidden"
                        id="edit_de_minimis_breakdown"
                        name="de_minimis_breakdown">

                    <!-- SUB BENEFITS -->
                    <div class="grid grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm mb-1">
                                Rice Subsidy
                            </label>

                            <input type="number"
                                id="edit_rice_subsidy"
                                data-key="rice_subsidy"
                                class="edit-de-minimis-input w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">
                                Uniform Allowance
                            </label>

                            <input type="number"
                                id="edit_uniform_allowance"
                                data-key="uniform_allowance"
                                class="edit-de-minimis-input w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">
                                Laundry Allowance
                            </label>

                            <input type="number"
                                id="edit_laundry_allowance"
                                data-key="laundry_allowance"
                                class="edit-de-minimis-input w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">
                                Medical Allowance
                            </label>

                            <input type="number"
                                id="edit_medical_allowance"
                                data-key="medical_allowance"
                                class="edit-de-minimis-input w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>

                    </div>

                    <!-- LIVE TOTALS -->
                    <div class="flex items-center justify-between text-sm">

                        <span>
                            Allocated:
                            <span id="edit_allocated_total">0</span>
                        </span>

                        <span id="edit_remaining_wrap">
                            Remaining:
                            <span id="edit_remaining_total">0</span>
                        </span>

                    </div>

                    <!-- OTHER NON TAXABLE -->
                    <div class="border-t pt-4">

                        <div class="flex items-center justify-between mb-4">

                            <div>
                                <h4 class="text-sm font-semibold text-violet-800 uppercase tracking-wide">
                                    Other Non-Taxable Compensation
                                </h4>

                                <p class="text-xs text-gray-500 mt-1">
                                    Additional custom non-taxable benefits.
                                </p>
                            </div>

                            <button type="button"
                                id="edit_other_comp_add"
                                class="bg-violet-200 text-violet-800 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-violet-300 transition">

                                + Add Benefit

                            </button>

                        </div>

                        <div id="edit_other_comp_list" class="space-y-3"></div>

                    </div>

                </div>

            </div>

            <div>
                <label class="block text-sm mb-1">Allowances</label>
                <input type="number" id="edit_allowances" name="allowances" min="0" step="0.01"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

        </div>

        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-4 mb-2">Leave Entitlements (days)</p>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm mb-1">Vacation Leave</label>
                <input type="number" id="edit_vacation_leave" name="vacation_leave" min="0" step="0.5"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm mb-1">Sick Leave</label>
                <input type="number" id="edit_sick_leave" name="sick_leave" min="0" step="0.5"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm mb-1">Emergency Leave</label>
                <input type="number" id="edit_emergency_leave" name="emergency_leave" min="0" step="0.5"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
            <div class="col-span-2">
                <label class="block text-sm mb-1">Status</label>
                <select id="edit_status" name="status"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="1">Active</option>
                    <option value="0">Inactive / Deactivated</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Inactive employees can no longer log in.</p>
                <p id="selfDeactivateGuardNote" class="mt-1 text-xs text-amber-700 hidden">
                    You cannot deactivate your own account.
                </p>
            </div>
        </div>

    </div>

    <!-- ===================================================== -->
    <!-- BANKING INFORMATION -->
    <!-- ===================================================== -->

    <div>

        <h2 class="text-lg font-bold text-[#9764F6] mb-4">
            Banking Information
        </h2>

        <div class="grid grid-cols-2 gap-4">

            <div>
                <label class="block text-sm mb-1">Bank</label>
                <input type="text" id="edit_bank" name="bank"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Swift Code</label>
                <input type="text" id="edit_swift_code" name="swift_code"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Account Number</label>
                <input type="text" id="edit_account_number" name="account_number"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Bank Address</label>
                <input type="text" id="edit_bank_address" name="bank_address"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- SUBMIT -->
    <!-- ===================================================== -->

    <div class="flex justify-end pt-4 border-t">

        <button type="submit"
            class="bg-[#9764F6] hover:bg-[#8a58e0]
            text-white px-6 py-2 rounded-lg">

            Update Employee

        </button>

    </div>

</div>

</form>
<script>
    function toggleEditScheduleTime(type) {
        const show = type !== 'flexible';
        ['edit_start_time_wrap','edit_end_time_wrap'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = show ? '' : 'none';
        });
    }

    document.querySelectorAll('.edit-day-toggle').forEach(label => {

    label.addEventListener('click', function () {

        const checkbox = this.querySelector('.edit-day-checkbox');
        const btn = this.querySelector('.edit-day-btn');

        setTimeout(() => {

            if (checkbox.checked) {

                btn.classList.add(
                    'bg-[#A891FC]',
                    'border-[#A891FC]',
                    'text-white'
                );

                btn.classList.remove(
                    'bg-white',
                    'border-gray-300',
                    'text-gray-500'
                );

            } else {

                btn.classList.remove(
                    'bg-[#A891FC]',
                    'border-[#A891FC]',
                    'text-white'
                );

                btn.classList.add(
                    'bg-white',
                    'border-gray-300',
                    'text-gray-500'
                );
            }

        }, 0);

    });

});

document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {

    const checkedDays = document.querySelectorAll(
        '#editEmployeeForm input[name="working_days[]"]:checked'
    );

    if (!checkedDays.length) {

        e.preventDefault();

        alert('Please select at least one working day.');

        return;
    }

    const startTime = document
        .getElementById('edit_start_time')
        .value;

    const endTime = document
        .getElementById('edit_end_time')
        .value;

    if (
        startTime &&
        endTime &&
        startTime >= endTime
    ) {

        e.preventDefault();

        alert('End Time must be later than Start Time.');

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

            remainingWrap.classList.add('text-red-600');

        } else {

            remainingWrap.classList.remove('text-red-600');
        }

        updateEditBreakdownHidden();
    }

    totalInput.addEventListener('input', recalc);

    allocationInputs.forEach(input => {
        input.addEventListener('input', recalc);
    });

    document.getElementById('edit_de_minimis_reset')
        .addEventListener('click', function () {

            totalInput.value = '';

            allocationInputs.forEach(input => input.value = '');

            document.getElementById('edit_other_comp_list').innerHTML = '';

            recalc();
        });

    window.loadEditBreakdown = function (breakdownJson) {

        try {

            const breakdown = JSON.parse(breakdownJson || '{}');

            const allocations = breakdown.allocations || {};

            const other = breakdown.other || [];

            document.getElementById('edit_rice_subsidy').value =
                allocations.rice_subsidy || '';

            document.getElementById('edit_uniform_allowance').value =
                allocations.uniform_allowance || '';

            document.getElementById('edit_laundry_allowance').value =
                allocations.laundry_allowance || '';

            document.getElementById('edit_medical_allowance').value =
                allocations.medical_allowance || '';

            const list = document.getElementById('edit_other_comp_list');

            list.innerHTML = '';

            other.forEach(item => {

                addEditOtherCompRow(
                    item.purpose || '',
                    item.amount || ''
                );
            });

            recalc();

        } catch (e) {

            console.error(e);
        }
    };

    function addEditOtherCompRow(purpose = '', amount = '') {

        const list = document.getElementById('edit_other_comp_list');

        const row = document.createElement('div');

        row.className = 'flex gap-2';

        row.innerHTML = `
            <input type="text"
                class="edit-other-purpose w-full border rounded-lg p-2"
                placeholder="Purpose"
                value="${purpose}">

            <input type="number"
                class="edit-other-amount w-32 border rounded-lg p-2"
                placeholder="Amount"
                value="${amount}">

            <button type="button"
                class="remove-other-comp text-red-500 px-3">
                ✕
            </button>
        `;

        row.querySelector('.remove-other-comp')
            .addEventListener('click', function () {

                row.remove();

                syncOther();
            });

        row.querySelector('.edit-other-purpose')
            .addEventListener('input', syncOther);

        row.querySelector('.edit-other-amount')
            .addEventListener('input', syncOther);

        list.appendChild(row);

        syncOther();
    }

    function syncOther() {

        const rows = document.querySelectorAll('#edit_other_comp_list > div');

        const data = [];

        rows.forEach(row => {

            data.push({
                purpose: row.querySelector('.edit-other-purpose').value,
                amount: parseFloat(
                    row.querySelector('.edit-other-amount').value
                ) || 0
            });
        });

        editBreakdownState.other = data;

        updateEditBreakdownHidden();
    }

    document.getElementById('edit_other_comp_add')
        .addEventListener('click', function () {

            addEditOtherCompRow();
        });

})();
</script>