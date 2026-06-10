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
                <label class="block text-sm mb-1">Working Day Type</label>

                <select
                    id="edit_working_day_type"
                    name="working_day_type"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">

                    <option value="full_weekdays">Full Weekdays</option>
                    <option value="weekday_with_rest">Weekday + Rest Day</option>
                    <option value="weekend_shift">Weekend Shift</option>
                    <option value="custom">Custom</option>

                </select>
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

            <div>
                <label class="block text-sm mb-1">Start Time</label>

                <input type="time"
                    id="edit_start_time"
                    name="start_time"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
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

            <div>
                <label class="block text-sm mb-1">De Minimis Benefit</label>
                <input type="number" id="edit_de_minimis_benefit" name="de_minimis_benefit" min="0" step="0.01"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
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
</script>