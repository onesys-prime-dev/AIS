<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .form-input {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 14px;
        padding: 11px 14px;
        font-size: 14px;
        transition: all .2s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #A891FC;
        box-shadow: 0 0 0 3px rgba(168,145,252,.18);
    }

    .form-select {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 14px;
        padding: 11px 14px;
        font-size: 14px;
        transition: all .2s ease;
    }

    .form-select:focus {
        outline: none;
        border-color: #A891FC;
        box-shadow: 0 0 0 3px rgba(168,145,252,.18);
    }

    .form-step {
        padding-inline: 2px;
    }

    .step-container {
        flex: 1;
        overflow-y: auto;
        padding: 4px 2px 8px;
        min-height: 0;
    }

    .section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: #9ca3af;
    }

    .section-description {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }

    .soft-card {
        border-radius: 24px;
        padding: 20px;
    }

    .btn-primary {
        background: #A891FC;
        color: white;
        padding: 10px 22px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        transition: .2s ease;
        border: none;
        cursor: pointer;
    }

    .btn-primary:hover {
        background: #8e73f7;
    }

    .btn-secondary {
        background: #d1d5db;
        color: #374151;
        padding: 10px 22px;
        border-radius: 14px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: .2s ease;
    }

    .btn-secondary:hover {
        background: #c4c9d2;
    }

    /* Progress bar segments */
    .progress-step {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        padding: 0 16px;
        white-space: nowrap;
        flex: 1;
        height: 42px;
        transition: background-color .25s ease, color .25s ease;
    }

    .progress-step:first-child {
        border-radius: 9999px 0 0 9999px;
        clip-path: polygon(88% 0, 100% 50%, 88% 100%, 0% 100%, 0 50%, 0% 0%);
    }

    .progress-step:not(:first-child):not(:last-child) {
        margin-left: -12px;
        clip-path: polygon(88% 0, 100% 50%, 88% 100%, 0% 100%, 12% 50%, 0% 0%);
    }

    .progress-step:last-child {
        margin-left: -12px;
        border-radius: 0 9999px 9999px 0;
        clip-path: polygon(100% 0, 100% 50%, 100% 100%, 0% 100%, 12% 50%, 0% 0%);
    }

    .progress-step:first-child {
        border-radius: 9999px 0 0 9999px;
        clip-path: polygon(88% 0, 100% 50%, 88% 100%, 0% 100%, 0 50%, 0% 0%);
    }

    .progress-step:not(:first-child):not(:last-child) {
        margin-left: -12px;
        clip-path: polygon(88% 0, 100% 50%, 88% 100%, 0% 100%, 12% 50%, 0% 0%);
    }

    .progress-step:last-child {
        margin-left: -12px;
        border-radius: 0 9999px 9999px 0;
        clip-path: polygon(100% 0, 100% 50%, 100% 100%, 0% 100%, 12% 50%, 0% 0%);
    }

    /* Day toggle buttons */
    .day-btn {
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

    .day-btn:hover {
        border-color: #A891FC;
        color: #A891FC;
    }

    .day-checkbox:checked + .day-btn {
        background: #A891FC;
        border-color: #A891FC;
        color: #ffffff;
    }
</style>

<form id="employeeForm"
    action="<?= site_url('client_portal/employees/create') ?>"
    method="post"
    class="flex flex-col h-full">

    <?= csrf_field() ?>

    <!-- STEP PROGRESS -->
    <div class="flex justify-center mb-6 shrink-0">
        <div class="flex w-full" style="height: 42px;">

            <div id="progress1" class="progress-step bg-[#A891FC] text-white">
                1. Personal
            </div>

            <div id="progress2" class="progress-step bg-gray-300 text-gray-700">
                2. Gov't IDs
            </div>

            <div id="progress3" class="progress-step bg-gray-300 text-gray-700">
                3. Schedule
            </div>

            <div id="progress4" class="progress-step bg-gray-300 text-gray-700">
                4. Employment
            </div>

            <div id="progress5" class="progress-step bg-gray-300 text-gray-700">
                5. Compensation
            </div>

        </div>
    </div>

    <!-- WRAPPER -->
    <div class="flex-1 min-h-0 overflow-hidden">

        <!-- ================= STEP 1 ================= -->
        <div class="form-step h-full flex flex-col" id="step1">

            <div class="step-container hide-scrollbar space-y-6">

                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">
                        Personal Information
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Enter the employee's personal details and contact information.
                    </p>
                </div>

                <div class="soft-card bg-slate-50 from-violet-50 to-purple-50">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">First Name</label>
                            <input type="text" name="first_name"
                                placeholder="e.g., John"
                                class="form-input" required>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Middle Name</label>
                            <input type="text" name="middle_name"
                                placeholder="e.g., Santos"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Last Name</label>
                            <input type="text" name="last_name"
                                placeholder="e.g., Dela Cruz"
                                class="form-input" required>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Date of Birth</label>
                            <input type="date" name="dob" class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Gender</label>

                            <select name="gender" class="form-select">
                                <option value="">Select gender</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Civil Status</label>

                            <select name="civil_status" class="form-select">
                                <option value="">Select civil status</option>
                                <option>Single</option>
                                <option>Married</option>
                                <option>Widowed</option>
                                <option>Separated</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-600 mb-2">Contact Number</label>

                            <input type="text"
                                name="mobile_number"
                                placeholder="e.g., +639171234567"
                                class="form-input">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-600 mb-2">Address</label>

                            <input type="text"
                                name="address"
                                placeholder="e.g., Brgy. Batong Malake, Los Baños, Laguna"
                                class="form-input">
                        </div>

                    </div>

                </div>

            </div>

            <div class="flex justify-end pt-4 shrink-0">
                <button type="button"
                    onclick="nextStep(2)"
                    class="btn-primary">
                    Next
                </button>
            </div>

        </div>

        <!-- ================= STEP 2 ================= -->
        <div class="form-step hidden h-full flex flex-col" id="step2">

            <div class="step-container hide-scrollbar space-y-6">

                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">
                        Government IDs
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Provide mandatory government identification details.
                    </p>
                </div>

                <div class="space-y-4 bg-gray-50 rounded-3xl p-6">

                    <div>
                        <label class="block text-sm text-gray-600 mb-2">SSS Number</label>

                        <input type="text"
                            name="sss"
                            placeholder="e.g., 34-1234567-8"
                            class="form-input">
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-2">Pag-IBIG Number</label>

                        <input type="text"
                            name="pagibig"
                            placeholder="e.g., 1234-5678-9012"
                            class="form-input">
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-2">TIN Number</label>

                        <input type="text"
                            name="tin"
                            placeholder="e.g., 123-456-789-000"
                            class="form-input">
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-2">PhilHealth Number</label>

                        <input type="text"
                            name="philhealth"
                            placeholder="e.g., 12-345678901-2"
                            class="form-input">
                    </div>

                </div>

            </div>

            <div class="flex justify-between pt-4 shrink-0">
                <button type="button"
                    onclick="prevStep(1)"
                    class="btn-secondary">
                    Back
                </button>

                <button type="button"
                    onclick="nextStep(3)"
                    class="btn-primary">
                    Next
                </button>
            </div>

        </div>

        <!-- ================= STEP 3 ================= -->
        <div class="form-step hidden h-full flex flex-col" id="step3">

            <div class="step-container hide-scrollbar space-y-6">

                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">
                        Work Schedule
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Configure the employee's working schedule and setup.
                    </p>
                </div>

                <div class="bg-slate-50 rounded-3xl p-6 space-y-5">

                    <div>
                        <label class="block text-sm text-gray-600 mb-2">
                            Employment Type
                        </label>

                        <select name="employment_type" class="form-select">
                            <option value="">Select employment type</option>
                            <option>Part-time</option>
                            <option>Full-time</option>
                            <option>Contractual</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-2">
                            Schedule Type
                        </label>

                        <select name="working_day_type" class="form-select" id="add_working_day_type" onchange="toggleAddScheduleTime(this.value)">
                            <option value="full_weekdays">Fixed – Mon to Fri</option>
                            <option value="weekday_with_rest">Fixed – Weekdays with 1 Rest Day</option>
                            <option value="weekend_shift">Fixed – Weekend Shift (Sat–Sun)</option>
                            <option value="custom">Fixed – Custom Days</option>
                            <option value="flexible">Flexible – No Fixed Start Time</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Fixed schedules track tardiness. Flexible only requires completing daily hours.</p>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-3">
                            Working Days
                        </label>

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

                            $defaultChecked = ['mon', 'tue', 'wed', 'thu', 'fri'];

                            foreach ($days as $value => $label):
                                $isChecked = in_array($value, $defaultChecked);
                            ?>

                            <label class="day-toggle cursor-pointer">

                                <input type="checkbox"
                                    name="working_days[]"
                                    value="<?= $value ?>"
                                    class="sr-only day-checkbox"
                                    <?= $isChecked ? 'checked' : '' ?>>

                                <span class="day-btn">
                                    <?= $label ?>
                                </span>

                            </label>

                            <?php endforeach; ?>

                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="add_schedule_time_row">

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Start Time
                            </label>

                            <input type="time"
                                name="start_time"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                End Time
                            </label>

                            <input type="time"
                                name="end_time"
                                class="form-input">
                        </div>

                    </div>

                </div>

            </div>

            <div class="flex justify-between pt-4 shrink-0">
                <button type="button"
                    onclick="prevStep(2)"
                    class="btn-secondary">
                    Back
                </button>

                <button type="button"
                    onclick="nextStep(4)"
                    class="btn-primary">
                    Next
                </button>
            </div>

        </div>

        <!-- ================= STEP 4 ================= -->
        <div class="form-step hidden h-full flex flex-col" id="step4">

            <div class="step-container hide-scrollbar space-y-6">

                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">
                        Employment Details
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Configure organizational and account information.
                    </p>
                </div>

                <div class="bg-slate-50 rounded-3xl p-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Department
                            </label>

                            <input type="text"
                                name="department"
                                placeholder="e.g., Information Technology"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Position
                            </label>

                            <input type="text"
                                name="position"
                                placeholder="e.g., Software Engineer"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Company Email
                            </label>

                            <input type="email"
                                name="email"
                                placeholder="employee@company.com"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Password
                            </label>

                            <div class="relative">
                                <input type="password"
                                    name="password"
                                    id="password"
                                    placeholder="Enter strong password"
                                    class="form-input pr-12">

                                <button type="button" id="toggle-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"> 
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

                    </div>

                </div>

            </div>

            <div class="flex justify-between pt-4 shrink-0">
                <button type="button"
                    onclick="prevStep(3)"
                    class="btn-secondary">
                    Back
                </button>

                <button type="button"
                    onclick="nextStep(5)"
                    class="btn-primary">
                    Next
                </button>
            </div>

        </div>

        <!-- ================= STEP 5 ================= -->
        <div class="form-step hidden h-full flex flex-col" id="step5">

            <div class="step-container hide-scrollbar space-y-5">

                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">
                        Compensation & Benefits
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Configure salary, benefits, leaves, and payroll information.
                    </p>
                </div>

                <!-- Salary -->
                <div class="bg-emerald-50 rounded-3xl p-6 space-y-4">

                    <h3 class="text-sm font-semibold text-emerald-800 uppercase tracking-wide">
                        Salary Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Basic Salary
                            </label>

                            <input type="number"
                                name="monthly_salary"
                                placeholder="e.g., 25000"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Non-Basic Allowances
                                <span class="block text-xs text-gray-400 font-normal">excluded from OT/NSD base per DOLE</span>
                            </label>

                            <input type="number"
                                name="allowances"
                                placeholder="e.g., 2925"
                                class="form-input">
                            <p class="text-xs text-gray-400 mt-1">Monthly allowances inside Basic Salary that are NOT basic wage. OT/NSD uses Basic Salary minus this amount.</p>
                        </div>

                    </div>

                </div>

                <!-- De Minimis -->
                <div class="bg-violet-50 rounded-3xl p-6 space-y-4">

                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-violet-800 uppercase tracking-wide">
                            De Minimis Benefits
                        </h3>

                        <button type="button"
                            id="de_minimis_reset"
                            title="Reset De Minimis fields"
                            class="text-violet-500 hover:text-violet-700 hover:rotate-180 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600 mb-2">Amount Given (Total)</label>

                        <div class="flex gap-2">
                            <input type="number" name="de_minimis_benefit"
                                id="de_minimis_total"
                                placeholder="e.g., 3300"
                                class="form-input">

                            <button type="button"
                                id="de_minimis_auto_allocate"
                                class="shrink-0 px-4 rounded-[14px] text-sm font-semibold bg-violet-200 text-violet-800 hover:bg-violet-300 transition-colors">
                                Auto Allocate
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="de_minimis_breakdown" id="de_minimis_breakdown">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Rice Subsidy</label>
                            <input type="number" name="rice_subsidy"
                                data-key="rice_subsidy"
                                placeholder="e.g., 1500"
                                class="form-input de-minimis-allocation">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Uniform / Clothing</label>
                            <input type="number" name="uniform_allowance"
                                data-key="uniform_allowance"
                                placeholder="e.g., 1000"
                                class="form-input de-minimis-allocation">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Laundry Allowance</label>
                            <input type="number" name="laundry_allowance"
                                data-key="laundry_allowance"
                                placeholder="e.g., 300"
                                class="form-input de-minimis-allocation">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Medical Allowance</label>
                            <input type="number" name="medical_allowance"
                                data-key="medical_allowance"
                                placeholder="e.g., 500"
                                class="form-input de-minimis-allocation">
                        </div>

                    </div>

                    <div class="flex items-center justify-between text-sm font-medium pt-1 px-1">
                        <span class="text-gray-600">Allocated: <span id="de_minimis_allocated">0</span></span>
                        <span id="de_minimis_remaining_wrap" class="text-gray-600">
                            Remaining: <span id="de_minimis_remaining">0</span>
                        </span>
                    </div>

                    <!-- Other Non-Taxable Compensation -->
                    <div class="border-t border-violet-200 pt-4 space-y-4">

                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-violet-800 uppercase tracking-wide">
                                    Other Non-Taxable Compensation
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    Add any additional non-taxable benefit and specify its purpose.
                                </p>
                            </div>

                            <button type="button"
                                id="other_comp_add"
                                class="shrink-0 px-4 h-10 rounded-[14px] text-sm font-semibold bg-violet-200 text-violet-800 hover:bg-violet-300 transition-colors">
                                + Add Benefit
                            </button>
                        </div>

                        <div id="other_comp_list" class="space-y-3"></div>

                        <div class="flex items-center justify-end text-sm font-medium pt-1 px-1">
                            <span class="text-gray-600">Total: <span id="other_comp_total_display">0</span></span>
                        </div>

                    </div>

                </div>


                <!-- Leaves -->
                <div class="bg-orange-50 rounded-3xl p-6 space-y-4">

                    <h3 class="text-sm font-semibold text-orange-800 uppercase tracking-wide">
                        Leave Credits
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Vacation Leave</label>
                            <input type="number"
                                name="vacation_leave"
                                placeholder="e.g., 15"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Sick Leave</label>
                            <input type="number"
                                name="sick_leave"
                                placeholder="e.g., 15"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Emergency Leave</label>
                            <input type="number"
                                name="emergency_leave"
                                placeholder="e.g., 5"
                                class="form-input">
                        </div>

                    </div>

                </div>

                <!-- Banking -->
                <div class="bg-blue-50 rounded-3xl p-6 space-y-4">

                    <h3 class="text-sm font-semibold text-blue-800 uppercase tracking-wide">
                        Banking Information
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Bank Name</label>
                            <input type="text"
                                name="bank"
                                placeholder="e.g., BDO Unibank"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">SWIFT Code</label>
                            <input type="text"
                                name="swift_code"
                                placeholder="e.g., BNORPHMM"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Account Number</label>
                            <input type="text"
                                name="account_number"
                                placeholder="e.g., 1234567890"
                                class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Branch Address</label>
                            <input type="text"
                                name="bank_address"
                                placeholder="e.g., Los Baños Branch"
                                class="form-input">
                        </div>

                    </div>

                </div>

            </div>

            <div class="flex justify-between pt-4 shrink-0">

                <button type="button"
                    onclick="prevStep(4)"
                    class="btn-secondary">
                    Back
                </button>

                <button type="submit"
                    class="btn-primary">
                    Create Employee
                </button>

            </div>

        </div>

    </div>

</form>

<script>
function toggleAddScheduleTime(type) {
    const row = document.getElementById('add_schedule_time_row');
    if (row) row.style.display = (type === 'flexible') ? 'none' : '';
}

// Form validation
document.getElementById('employeeForm').addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value.trim();
    const firstName = document.querySelector('input[name="first_name"]').value.trim();
    const lastName = document.querySelector('input[name="last_name"]').value.trim();

    if (!firstName || !lastName) { e.preventDefault(); showToast('First and Last Name are required.', 'warning'); return; }
    if (!email) { e.preventDefault(); showToast('Email is required.', 'warning'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { e.preventDefault(); showToast('Please enter a valid email address.', 'warning'); return; }
    if (!password) { e.preventDefault(); showToast('Password is required.', 'warning'); return; }
    if (password.length < 8) { e.preventDefault(); showToast('Password must be at least 8 characters.', 'warning'); return; }
    if (!document.querySelectorAll('input[name="working_days[]"]:checked').length) { e.preventDefault(); showToast('Please select at least one working day.', 'warning'); return; }

    const s = document.querySelector('input[name="start_time"]').value;
    const en = document.querySelector('input[name="end_time"]').value;
    if (s && en && s >= en) { e.preventDefault(); showToast('End Time must be later than Start Time.', 'warning'); return; }

    // De Minimis: allocated sub-benefits cannot exceed the total amount given
    const deMinimisTotal = parseFloat(document.getElementById('de_minimis_total')?.value) || 0;
    let deMinimisAllocated = 0;
    document.querySelectorAll('.de-minimis-allocation').forEach(input => {
        deMinimisAllocated += parseFloat(input.value) || 0;
    });
    if (deMinimisAllocated > deMinimisTotal) {
        e.preventDefault();
        showToast('De Minimis sub-benefits exceed the Amount Given (Total). Please adjust the values.', 'warning');
        return;
    }
});

// Shared breakdown state for de_minimis_breakdown hidden field
// (combines De Minimis sub-benefit allocations + Other Non-Taxable Compensation)
const deMinimisBreakdownState = {
    allocations: {},
    other: []
};

function writeDeMinimisBreakdown() {
    const breakdownInput = document.getElementById('de_minimis_breakdown');
    if (!breakdownInput) return;

    breakdownInput.value = JSON.stringify({
        allocations: deMinimisBreakdownState.allocations,
        other: deMinimisBreakdownState.other
    });
}

// De Minimis allocation tracker
(function () {
    const totalInput = document.getElementById('de_minimis_total');
    const allocationInputs = document.querySelectorAll('.de-minimis-allocation');
    const allocatedEl = document.getElementById('de_minimis_allocated');
    const remainingEl = document.getElementById('de_minimis_remaining');
    const remainingWrap = document.getElementById('de_minimis_remaining_wrap');

    if (!totalInput) return;

    function syncBreakdown() {
        const breakdown = {};

        allocationInputs.forEach(input => {
            const key = input.dataset.key;
            breakdown[key] = parseFloat(input.value) || 0;
        });

        deMinimisBreakdownState.allocations = breakdown;
        writeDeMinimisBreakdown();
    }

    function recalc() {
        const total = parseFloat(totalInput.value) || 0;

        let allocated = 0;
        allocationInputs.forEach(input => {
            allocated += parseFloat(input.value) || 0;
        });

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

        syncBreakdown();
    }

    totalInput.addEventListener('input', recalc);
    allocationInputs.forEach(input => input.addEventListener('input', recalc));

    recalc();

    // Auto Allocate: evenly distribute total across the 4 benefit fields
    const autoBtn = document.getElementById('de_minimis_auto_allocate');

    autoBtn.addEventListener('click', function () {
        const total = parseFloat(totalInput.value) || 0;
        const count = allocationInputs.length;

        if (total <= 0 || count === 0) return;

        const base = Math.floor((total / count) * 100) / 100;
        let distributed = 0;

        allocationInputs.forEach((input, index) => {
            if (index === count - 1) {
                // Last field gets the remainder to avoid rounding loss
                const remainder = Math.round((total - distributed) * 100) / 100;
                input.value = remainder;
            } else {
                input.value = base;
                distributed += base;
            }
        });

        recalc();
    });

    // Reset: clear total + all allocation fields
    const resetBtn = document.getElementById('de_minimis_reset');

    resetBtn.addEventListener('click', function () {
        totalInput.value = '';
        allocationInputs.forEach(input => input.value = '');
        recalc();
    });
})();

// Other Non-Taxable Compensation: dynamic purpose/amount rows
(function () {
    const listEl = document.getElementById('other_comp_list');
    const addBtn = document.getElementById('other_comp_add');
    const totalDisplay = document.getElementById('other_comp_total_display');

    if (!listEl || !addBtn) return;

    function recalc() {
        let total = 0;
        const breakdown = [];

        listEl.querySelectorAll('.other-comp-row').forEach(row => {
            const purpose = row.querySelector('.other-comp-purpose').value.trim();
            const amount = parseFloat(row.querySelector('.other-comp-amount').value) || 0;

            total += amount;

            if (purpose !== '' || amount !== 0) {
                breakdown.push({ purpose: purpose, amount: amount });
            }
        });

        totalDisplay.textContent = total.toLocaleString();

        deMinimisBreakdownState.other = breakdown;
        writeDeMinimisBreakdown();
    }

    function addRow(purpose = '', amount = '') {
        const row = document.createElement('div');
        row.className = 'other-comp-row';
        row.style.display = 'flex';
        row.style.gap = '8px';
        row.style.alignItems = 'flex-start';
        row.style.width = '100%';

        row.innerHTML = `
            <input type="text"
                class="form-input other-comp-purpose"
                placeholder="e.g., Transportation Allowance"
                value="${purpose}"
                style="flex: 1 1 0%; min-width: 0;">

            <input type="number"
                class="form-input other-comp-amount"
                placeholder="Amount"
                value="${amount}"
                style="flex: 0 0 128px; width: 128px;">

            <button type="button"
                class="other-comp-remove"
                title="Remove"
                style="flex-shrink: 0; width: 40px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 14px; color: #9ca3af; transition: color .2s, background-color .2s;"
                onmouseover="this.style.color='#ef4444'; this.style.backgroundColor='#fef2f2';"
                onmouseout="this.style.color='#9ca3af'; this.style.backgroundColor='transparent';">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;

        row.querySelector('.other-comp-purpose').addEventListener('input', recalc);
        row.querySelector('.other-comp-amount').addEventListener('input', recalc);

        row.querySelector('.other-comp-remove').addEventListener('click', function () {
            row.remove();
            recalc();
        });

        listEl.appendChild(row);
    }

    addBtn.addEventListener('click', function () {
        addRow();
    });

    recalc();
})();

</script>