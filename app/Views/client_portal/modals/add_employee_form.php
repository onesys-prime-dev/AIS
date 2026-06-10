<form id="employeeForm" action="<?= site_url('client_portal/employees/create') ?>" method="post" class="flex flex-col h-full">
<?= csrf_field() ?>

<!-- STEP PROGRESS -->
<div class="flex justify-center mb-5 shrink-0">
    <div class="flex w-full" style="height: 40px;">
        <div id="progress1"
            class="flex items-center justify-center text-center text-xs font-semibold px-4 text-white bg-[#A891FC] rounded-l-full flex-1 whitespace-nowrap"
            style="clip-path: polygon(88% 0, 100% 50%, 88% 100%, 0% 100%, 0 50%, 0% 0%); min-width: 0;">
            1. Personal Info
        </div>
        <div id="progress2"
            class="flex items-center justify-center text-center text-xs font-semibold px-4 text-gray-800 bg-gray-300 -ml-3 flex-1 whitespace-nowrap"
            style="clip-path: polygon(88% 0, 100% 50%, 88% 100%, 0% 100%, 12% 50%, 0% 0%); min-width: 0;">
            2. Gov't IDs
        </div>
        <div id="progress3"
            class="flex items-center justify-center text-center text-xs font-semibold px-4 text-gray-800 bg-gray-300 -ml-3 flex-1 whitespace-nowrap"
            style="clip-path: polygon(88% 0, 100% 50%, 88% 100%, 0% 100%, 12% 50%, 0% 0%); min-width: 0;">
            3. Schedule
        </div>
        <div id="progress4"
            class="flex items-center justify-center text-center text-xs font-semibold px-4 text-gray-800 bg-gray-300 -ml-3 rounded-r-full flex-1 whitespace-nowrap"
            style="clip-path: polygon(100% 0, 100% 50%, 100% 100%, 0% 100%, 12% 50%, 0% 0%); min-width: 0;">
            4. Job Details
        </div>
    </div>
</div>

<!-- Steps wrapper: fills remaining height -->
<div class="flex-1 relative min-h-0">

    <!-- ================= STEP 1 ================= -->
    <div class="form-step absolute inset-0 flex flex-col" id="step1">
        <div class="flex-1 space-y-3 overflow-y-auto px-1">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">First Name:</label>
                    <input type="text" name="first_name"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Middle Name:</label>
                    <input type="text" name="middle_name"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Last Name:</label>
                    <input type="text" name="last_name"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Date of Birth:</label>
                    <input type="date" name="dob"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Gender:</label>
                    <select name="gender"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Civil Status:</label>
                    <select name="civil_status"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">-- Select --</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Contact No.:</label>
                    <input type="text" name="mobile_number"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div>
                <label class="block text-sm mb-1">Address:</label>
                <input type="text" name="address"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
        </div>
        <div class="flex justify-end pt-4 shrink-0">
            <button type="button" onclick="nextStep(2)"
                class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-2 rounded-lg">Next</button>
        </div>
    </div>

    <!-- ================= STEP 2 ================= -->
    <div class="form-step absolute inset-0 flex flex-col hidden" id="step2">
        <div class="flex-1 space-y-4 overflow-y-auto px-1">
            <div>
                <label class="block text-sm mb-1">SSS:</label>
                <input type="text" name="sss"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm mb-1">Pagibig:</label>
                <input type="text" name="pagibig"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm mb-1">TIN:</label>
                <input type="text" name="tin"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm mb-1">PhilHealth:</label>
                <input type="text" name="philhealth"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
        </div>
        <div class="flex justify-between pt-4 shrink-0">
            <button type="button" onclick="prevStep(1)"
                class="bg-gray-400 text-white px-6 py-2 rounded-lg">Back</button>
            <button type="button" onclick="nextStep(3)"
                class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-2 rounded-lg">Next</button>
        </div>
    </div>

    <!-- ================= STEP 3 ================= -->
    <div class="form-step absolute inset-0 flex flex-col hidden" id="step3">
        <div class="flex-1 space-y-4 overflow-y-auto px-1">
            <div>
                <label class="block text-sm mb-1">Employment type:</label>
                <input type="text" name="employment_type"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <div class="flex items-center gap-1 mb-1">
                    <label class="text-sm">Working Day Type:</label>
                    <div class="relative group">
                        <svg class="w-3.5 h-3.5 text-gray-400 cursor-pointer hover:text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 w-56 bg-gray-800 text-white text-xs rounded-lg px-3 py-2 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50">
                            This helps define which days count as working days for leave and payroll computation.
                            <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-x-4 border-x-transparent border-t-4 border-t-gray-800"></div>
                        </div>
                    </div>
                </div>
                <select name="working_day_type"
                    class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="full_weekdays" selected>Full Weekdays (Mon-Fri)</option>
                    <option value="weekday_with_rest">Weekday Schedule with One Rest Day</option>
                    <option value="weekend_shift">Weekend Shift (Sat-Sun)</option>
                    <option value="custom">Custom Schedule</option>
                </select>
            </div>

            <div>
                <div class="flex items-center gap-1 mb-2">
                    <label class="text-sm">Working Days:</label>
                    <div class="relative group">
                        <svg class="w-3.5 h-3.5 text-gray-400 cursor-pointer hover:text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 w-52 bg-gray-800 text-white text-xs rounded-lg px-3 py-2 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50">
                            Select the exact days the employee is expected to work.
                            <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-x-4 border-x-transparent border-t-4 border-t-gray-800"></div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <?php
                    $days = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
                    $defaultChecked = ['mon', 'tue', 'wed', 'thu', 'fri'];
                    foreach ($days as $value => $label):
                        $isChecked = in_array($value, $defaultChecked);
                    ?>
                    <label class="day-toggle cursor-pointer">
                        <input type="checkbox" name="working_days[]" value="<?= $value ?>"
                            class="sr-only day-checkbox" <?= $isChecked ? 'checked' : '' ?>>
                        <span class="day-btn flex items-center justify-center w-10 h-10 rounded-full text-xs font-semibold border-2 transition-all duration-150
                            <?= $isChecked ? 'bg-[#A891FC] border-[#A891FC] text-white' : 'bg-white border-gray-300 text-gray-500 hover:border-[#A891FC] hover:text-[#A891FC]' ?>">
                            <?= $label ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Start Time:</label>
                    <input type="time" name="start_time" required
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <div class="flex items-center gap-1 mb-1">
                        <label class="text-sm">End Time:</label>
                        <div class="relative group">
                            <svg class="w-3.5 h-3.5 text-gray-400 cursor-pointer hover:text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <div class="absolute right-0 bottom-full mb-2 w-52 bg-gray-800 text-white text-xs rounded-lg px-3 py-2 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-50">
                                These hours will be used as the employee's standard working schedule.
                                <div class="absolute right-2 top-full w-0 h-0 border-x-4 border-x-transparent border-t-4 border-t-gray-800"></div>
                            </div>
                        </div>
                    </div>
                    <input type="time" name="end_time" required
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
        </div>
        <div class="flex justify-between pt-4 shrink-0">
            <button type="button" onclick="prevStep(2)"
                class="bg-gray-400 text-white px-6 py-2 rounded-lg">Back</button>
            <button type="button" onclick="nextStep(4)"
                class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-2 rounded-lg">Next</button>
        </div>
    </div>

    <!-- ================= STEP 4 ================= -->
    <div class="form-step absolute inset-0 flex flex-col hidden" id="step4">
        <div class="flex-1 space-y-3 overflow-y-auto px-1">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Department:</label>
                    <input type="text" name="department"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Position:</label>
                    <input type="text" name="position"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Email:</label>
                    <input type="email" name="email" required
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="employee@company.com">
                </div>
                <div>
                    <label class="block text-sm mb-1">Password:</label>
                    <div class="relative">
                        <input id="employee-password" type="password" name="password" required
                            class="w-full border rounded-lg p-2 focus:outline-none focus:border-purple-500"
                            placeholder="Enter a strong password">
                        <button type="button" id="toggle-password"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                            </svg>
                            <svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm mb-1">Basic Salary:</label>
                    <input type="number" name="monthly_salary" min="0" step="0.01"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">De Minimis Benefit:</label>
                    <input type="number" name="de_minimis_benefit" min="0" step="0.01"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Allowances:</label>
                    <input type="number" name="allowances" min="0" step="0.01"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-1">Leave Entitlements (days)</p>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm mb-1">Vacation:</label>
                    <input type="number" name="vacation_leave" min="0" step="0.5"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Sick:</label>
                    <input type="number" name="sick_leave" min="0" step="0.5"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Emergency:</label>
                    <input type="number" name="emergency_leave" min="0" step="0.5"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-1">Banking Information</p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Bank:</label>
                    <input type="text" name="bank"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Swift Code:</label>
                    <input type="text" name="swift_code"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Account Number:</label>
                    <input type="text" name="account_number"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm mb-1">Bank Address:</label>
                    <input type="text" name="bank_address"
                        class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
        </div>
        <div class="flex justify-between pt-4 shrink-0">
            <button type="button" onclick="prevStep(3)"
                class="bg-gray-400 text-white px-6 py-2 rounded-lg">Back</button>
            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">Create Employee</button>
        </div>
    </div>

</div><!-- end steps wrapper -->

</form>

<script>
function nextStep(step) {
    document.querySelectorAll('.form-step').forEach(el => el.classList.add('hidden'));
    document.getElementById('step' + step).classList.remove('hidden');
    updateProgress(step);
}

function prevStep(step) {
    document.querySelectorAll('.form-step').forEach(el => el.classList.add('hidden'));
    document.getElementById('step' + step).classList.remove('hidden');
    updateProgress(step);
}

function updateProgress(step) {
    const active   = 'bg-[#A891FC] border-[#A891FC] text-white';
    const inactive = 'bg-gray-300 text-gray-800';
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('progress' + i);
        el.className = el.className
            .replace('bg-[#A891FC]', '').replace('text-white', '').replace('bg-gray-300', '').replace('text-gray-800', '').trim();
        if (i <= step) {
            el.classList.add('bg-[#A891FC]', 'text-white');
            el.classList.remove('bg-gray-300', 'text-gray-800');
        } else {
            el.classList.add('bg-gray-300', 'text-gray-800');
            el.classList.remove('bg-[#A891FC]', 'text-white');
        }
    }
}

// Day toggle buttons
document.querySelectorAll('.day-toggle').forEach(label => {
    label.addEventListener('click', function () {
        const checkbox = this.querySelector('.day-checkbox');
        const btn = this.querySelector('.day-btn');
        setTimeout(() => {
            if (checkbox.checked) {
                btn.classList.add('bg-[#A891FC]', 'border-[#A891FC]', 'text-white');
                btn.classList.remove('bg-white', 'border-gray-300', 'text-gray-500');
            } else {
                btn.classList.remove('bg-[#A891FC]', 'border-[#A891FC]', 'text-white');
                btn.classList.add('bg-white', 'border-gray-300', 'text-gray-500');
            }
        }, 0);
    });
});

// Form validation
document.getElementById('employeeForm').addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value.trim();
    if (!email) { e.preventDefault(); alert('Email is required.'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { e.preventDefault(); alert('Please enter a valid email address.'); return; }
    if (!password) { e.preventDefault(); alert('Password is required.'); return; }
    if (password.length < 8) { e.preventDefault(); alert('Password must be at least 8 characters.'); return; }
    if (!document.querySelectorAll('input[name="working_days[]"]:checked').length) { e.preventDefault(); alert('Please select at least one working day.'); return; }
    const s = document.querySelector('input[name="start_time"]').value;
    const en = document.querySelector('input[name="end_time"]').value;
    if (s && en && s >= en) { e.preventDefault(); alert('End Time must be later than Start Time.'); return; }
});
</script>