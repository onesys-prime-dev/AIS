<!-- app/Views/client_portal/payroll_settings_panel.php -->
<div class="bg-white rounded-2xl shadow-lg p-8 h-full border border-gray-300 flex flex-col">
    <h3 class="font-semibold text-xl mb-6">Payroll Computation Settings</h3>

    <!-- Tab Nav -->
    <div class="flex border-b border-gray-200 mb-6 gap-1">
        <button type="button" class="payroll-tab active px-5 py-2 text-sm font-medium rounded-t-lg border border-b-0 border-transparent"
            data-tab="general">General</button>
        <button type="button" class="payroll-tab px-5 py-2 text-sm font-medium rounded-t-lg border border-b-0 border-transparent"
            data-tab="sss">SSS Brackets</button>
        <button type="button" class="payroll-tab px-5 py-2 text-sm font-medium rounded-t-lg border border-b-0 border-transparent"
            data-tab="wtax">Withholding Tax</button>
        <button type="button" class="payroll-tab px-5 py-2 text-sm font-medium rounded-t-lg border border-b-0 border-transparent"
            data-tab="holidays" onclick="loadHolidays()">Holidays</button>
        <button type="button" class="payroll-tab px-5 py-2 text-sm font-medium rounded-t-lg border border-b-0 border-transparent"
            data-tab="deminimis">De Minimis Categories</button>
    </div>

    <!-- Tab: General -->
    <div class="payroll-tab-content flex-1 overflow-auto hide-scroll-bar" id="tab-general">
        <form id="payrollSettingsForm" action="<?= site_url('client_portal/payroll/saveSettings') ?>" method="post">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded shadow">
                    <h4 class="font-semibold border-b pb-1 mb-2">Working Hours</h4>
                    <label>Monthly Working Hours</label>
                    <input type="number" name="setting_monthly_hours" id="setting_monthly_hours" class="w-full border rounded p-2" step="0.01">
                </div>
                <div class="bg-gray-50 p-4 rounded shadow">
                    <h4 class="font-semibold border-b pb-1 mb-2">Night Differential</h4>
                    <label>Start Time</label>
                    <input type="time" name="setting_night_diff_start" id="setting_night_diff_start" class="w-full border rounded p-2">
                    <label class="mt-2">End Time</label>
                    <input type="time" name="setting_night_diff_end" id="setting_night_diff_end" class="w-full border rounded p-2">
                    <label class="mt-2">Rate (e.g., 0.10 = 10%)</label>
                    <input type="number" step="0.01" name="setting_night_diff_rate" id="setting_night_diff_rate" class="w-full border rounded p-2">
                </div>
                <div class="bg-gray-50 p-4 rounded shadow">
                    <h4 class="font-semibold border-b pb-1 mb-2">Holiday &amp; Rest Day Rates</h4>
                    <label>Rest Day Base Rate (x) <span class="text-xs text-gray-400">&le;8 hrs &mdash; 130%</span></label>
                    <input type="number" step="0.01" name="setting_rest_day_rate" id="setting_rest_day_rate" class="w-full border rounded p-2" placeholder="1.30">
                    <label class="mt-2">Regular Holiday Rate (x) <span class="text-xs text-gray-400">&le;8 hrs &mdash; 200%</span></label>
                    <input type="number" step="0.01" name="setting_reg_holiday_rate" id="setting_reg_holiday_rate" class="w-full border rounded p-2" placeholder="2.00">
                    <label class="mt-2">Special Holiday Rate (x) <span class="text-xs text-gray-400">&le;8 hrs &mdash; 130%</span></label>
                    <input type="number" step="0.01" name="setting_spec_holiday_rate" id="setting_spec_holiday_rate" class="w-full border rounded p-2" placeholder="1.30">
                </div>
                <div class="bg-gray-50 p-4 rounded shadow">
                    <h4 class="font-semibold border-b pb-1 mb-2">Overtime Rates</h4>
                    <label>Regular OT (x)</label>
                    <input type="number" step="0.01" name="setting_ot_regular_rate" id="setting_ot_regular_rate" class="w-full border rounded p-2">
                    <label class="mt-2">Rest Day / Spec Holiday OT (x) <span class="text-xs text-gray-400">130%&times;130%=169%</span></label>
                    <input type="number" step="0.01" name="setting_ot_rest_rate" id="setting_ot_rest_rate" class="w-full border rounded p-2">
                    <label class="mt-2">Regular Holiday OT (x) <span class="text-xs text-gray-400">200%&times;130%=260%</span></label>
                    <input type="number" step="0.01" name="setting_ot_holiday_rate" id="setting_ot_holiday_rate" class="w-full border rounded p-2">
                </div>
                <div class="bg-gray-50 p-4 rounded shadow">
                    <h4 class="font-semibold border-b pb-1 mb-2">Government Contributions</h4>
                    <label>PhilHealth Rate <span class="text-xs text-gray-400">(e.g., 0.05 = 5%)</span></label>
                    <input type="number" step="0.0001" name="setting_phic_rate" id="setting_phic_rate" class="w-full border rounded p-2">
                    <label class="mt-2">PhilHealth Monthly Cap (₱)</label>
                    <input type="number" step="0.01" name="setting_phic_cap" id="setting_phic_cap" class="w-full border rounded p-2" placeholder="e.g., 80000">
                    <label class="mt-2">Pag-IBIG Rate <span class="text-xs text-gray-400">(e.g., 0.02 = 2%)</span></label>
                    <input type="number" step="0.0001" name="setting_hdmf_rate" id="setting_hdmf_rate" class="w-full border rounded p-2">
                    <label class="mt-2">Pag-IBIG Max Contribution (₱)</label>
                    <input type="number" step="0.01" name="setting_hdmf_max" id="setting_hdmf_max" class="w-full border rounded p-2" placeholder="e.g., 100">
                </div>
                <div class="bg-gray-50 p-4 rounded shadow">
                    <h4 class="font-semibold border-b pb-1 mb-2">Payroll Period</h4>

                    <label class="font-medium">Default Period Type</label>
                    <select name="setting_default_period_type" id="setting_default_period_type" class="w-full border rounded p-2 mt-1">
                        <option value="auto">Auto-detect from cutoff day below</option>
                        <option value="first_half">1st Half — semi-monthly WT, contributions in 2nd half</option>
                        <option value="second_half">2nd Half — cumulative monthly WT, contributions deducted</option>
                        <option value="monthly">Monthly — monthly WT, full contributions deducted</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1 mb-3">
                        Sets the default selection on the payslip tab. Choose <strong>Auto-detect</strong>
                        to have the system pick 1st or 2nd half based on the period start date and the cutoff day below.
                    </p>

                    <label class="font-medium">1st Half Cutoff Day <span class="text-xs text-gray-400">(fallback if Period Definition below is not set)</span></label>
                    <input type="number" min="1" max="28" name="setting_first_half_cutoff" id="setting_first_half_cutoff"
                           class="w-full border rounded p-2 mt-1" placeholder="15">
                    <p class="text-xs text-gray-400 mt-1">
                        Used when the Period Definition below is left blank. Default: 15.
                    </p>
                </div>

                <!-- Cross-month Period Definition -->
                <div class="bg-indigo-50 p-4 rounded shadow border border-indigo-200 md:col-span-2">
                    <h4 class="font-semibold border-b border-indigo-200 pb-1 mb-1 text-indigo-800">Period Definition <span class="text-xs font-normal text-indigo-500">(cross-month cutoffs)</span></h4>
                    <p class="text-xs text-gray-500 mb-3">
                        Set this when your attendance cutoff spans two calendar months — e.g., May&nbsp;26&ndash;Jun&nbsp;10 (salary released Jun&nbsp;15).
                        The <strong>salary month</strong> is the month selected in the Raw Sheet month picker.
                        Leave both halves blank to fall back to the simple cutoff day above.
                    </p>

                    <!-- 1st Half -->
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-indigo-700 mb-2">1st Half Cutoff Period</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Start Day</label>
                                <input type="number" min="1" max="31" name="setting_half1_start_day" id="setting_half1_start_day"
                                       class="w-full border rounded p-2 mt-1" placeholder="e.g. 26">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Start Month</label>
                                <select name="setting_half1_start_offset" id="setting_half1_start_offset" class="w-full border rounded p-2 mt-1">
                                    <option value="-1">Previous month</option>
                                    <option value="0" selected>Same (salary) month</option>
                                    <option value="1">Next month</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">End Day <span class="text-gray-400">(0&nbsp;=&nbsp;last day)</span></label>
                                <input type="number" min="0" max="31" name="setting_half1_end_day" id="setting_half1_end_day"
                                       class="w-full border rounded p-2 mt-1" placeholder="e.g. 10">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">End Month</label>
                                <select name="setting_half1_end_offset" id="setting_half1_end_offset" class="w-full border rounded p-2 mt-1">
                                    <option value="-1">Previous month</option>
                                    <option value="0" selected>Same (salary) month</option>
                                    <option value="1">Next month</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2nd Half -->
                    <div class="mb-3">
                        <p class="text-sm font-semibold text-indigo-700 mb-2">2nd Half Cutoff Period</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Start Day</label>
                                <input type="number" min="1" max="31" name="setting_half2_start_day" id="setting_half2_start_day"
                                       class="w-full border rounded p-2 mt-1" placeholder="e.g. 11">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Start Month</label>
                                <select name="setting_half2_start_offset" id="setting_half2_start_offset" class="w-full border rounded p-2 mt-1">
                                    <option value="-1">Previous month</option>
                                    <option value="0" selected>Same (salary) month</option>
                                    <option value="1">Next month</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">End Day <span class="text-gray-400">(0&nbsp;=&nbsp;last day)</span></label>
                                <input type="number" min="0" max="31" name="setting_half2_end_day" id="setting_half2_end_day"
                                       class="w-full border rounded p-2 mt-1" placeholder="e.g. 25">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">End Month</label>
                                <select name="setting_half2_end_offset" id="setting_half2_end_offset" class="w-full border rounded p-2 mt-1">
                                    <option value="-1">Previous month</option>
                                    <option value="0" selected>Same (salary) month</option>
                                    <option value="1">Next month</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly -->
                    <div class="mb-3">
                        <p class="text-sm font-semibold text-indigo-700 mb-2">Monthly Cutoff Period</p>
                        <p class="text-xs text-gray-500 mb-2">
                            For employees paid on the <strong>Monthly</strong> period button. Leave both blank for the
                            full calendar month (day&nbsp;1&ndash;last day). Give it a gap before the Monthly Salary Day below
                            (e.g. cutoff ends the 20th, paid the 25th) so there's time to process payroll instead of
                            releasing pay the same day the cutoff closes.
                        </p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Start Day</label>
                                <input type="number" min="1" max="31" name="setting_monthly_start_day" id="setting_monthly_start_day"
                                       class="w-full border rounded p-2 mt-1" placeholder="e.g. 1">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Start Month</label>
                                <select name="setting_monthly_start_offset" id="setting_monthly_start_offset" class="w-full border rounded p-2 mt-1">
                                    <option value="-1">Previous month</option>
                                    <option value="0" selected>Same (salary) month</option>
                                    <option value="1">Next month</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">End Day <span class="text-gray-400">(0&nbsp;=&nbsp;last day)</span></label>
                                <input type="number" min="0" max="31" name="setting_monthly_end_day" id="setting_monthly_end_day"
                                       class="w-full border rounded p-2 mt-1" placeholder="e.g. 20">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">End Month</label>
                                <select name="setting_monthly_end_offset" id="setting_monthly_end_offset" class="w-full border rounded p-2 mt-1">
                                    <option value="-1">Previous month</option>
                                    <option value="0" selected>Same (salary) month</option>
                                    <option value="1">Next month</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Live preview -->
                    <div class="bg-white border border-indigo-200 rounded p-3 text-xs text-indigo-700" id="periodPreviewBox">
                        <strong>Preview</strong> — select a salary month to see resolved dates:<br>
                        <input type="month" id="previewMonth" class="border rounded p-1 mt-1 mr-2" style="font-size:12px;">
                        <span id="periodPreviewResult" class="text-gray-600"></span>
                    </div>

                    <p class="text-xs text-indigo-600 mt-2">
                        <strong>Example</strong> &mdash; cutoff May&nbsp;26&ndash;Jun&nbsp;10 (salary Jun&nbsp;15):<br>
                        1st&nbsp;Half Start = Day&nbsp;26, <em>Previous month</em> &mdash; End = Day&nbsp;10, <em>Same month</em><br>
                        2nd&nbsp;Half Start = Day&nbsp;11, <em>Same month</em> &mdash; End = Day&nbsp;25, <em>Same month</em>
                    </p>
                </div>

                <!-- Salary Day — default send date on Payroll Output → Payslip -->
                <div class="bg-emerald-50 p-4 rounded shadow border border-emerald-200 md:col-span-2">
                    <h4 class="font-semibold border-b border-emerald-200 pb-1 mb-1 text-emerald-800">Salary Day <span class="text-xs font-normal text-emerald-600">(when each half's payslips go out)</span></h4>
                    <p class="text-xs text-gray-500 mb-3">
                        The day of the salary month each half is paid. It's the default date when scheduling on
                        <strong>Payroll Output &rarr; Payslip</strong>, and the date payslips are scheduled to automatically
                        when Release mode is <strong>Automatic</strong>. Leave a half blank to always choose the date by hand.
                        Works with both the simple cutoff day and the Period Definition above.
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs font-medium text-gray-600">1st Half Salary Day <span class="text-gray-400">(0&nbsp;=&nbsp;last day)</span></label>
                            <input type="number" min="0" max="31" name="setting_half1_salary_day" id="setting_half1_salary_day"
                                   class="w-full border rounded p-2 mt-1" placeholder="e.g. 15">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">1st Half Month</label>
                            <select name="setting_half1_salary_offset" id="setting_half1_salary_offset" class="w-full border rounded p-2 mt-1">
                                <option value="-1">Previous month</option>
                                <option value="0" selected>Same (salary) month</option>
                                <option value="1">Next month</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">2nd Half Salary Day <span class="text-gray-400">(0&nbsp;=&nbsp;last day)</span></label>
                            <input type="number" min="0" max="31" name="setting_half2_salary_day" id="setting_half2_salary_day"
                                   class="w-full border rounded p-2 mt-1" placeholder="e.g. 0">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">2nd Half Month</label>
                            <select name="setting_half2_salary_offset" id="setting_half2_salary_offset" class="w-full border rounded p-2 mt-1">
                                <option value="-1">Previous month</option>
                                <option value="0" selected>Same (salary) month</option>
                                <option value="1">Next month</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Monthly Salary Day <span class="text-gray-400">(0&nbsp;=&nbsp;last day)</span></label>
                            <input type="number" min="0" max="31" name="setting_monthly_salary_day" id="setting_monthly_salary_day"
                                   class="w-full border rounded p-2 mt-1" placeholder="e.g. 0">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Monthly Month</label>
                            <select name="setting_monthly_salary_offset" id="setting_monthly_salary_offset" class="w-full border rounded p-2 mt-1">
                                <option value="-1">Previous month</option>
                                <option value="0" selected>Same (salary) month</option>
                                <option value="1">Next month</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Used for employees on a full-Month cutoff (<strong>Monthly</strong> period button), instead of the
                        1st/2nd Half settings above &mdash; independent so it still works correctly when the Period Definition
                        above spans across months.
                    </p>
                    <div class="mt-3">
                        <label class="text-xs font-medium text-gray-600">If the salary day is a weekend or holiday</label>
                        <select name="setting_salary_day_adjust" id="setting_salary_day_adjust" class="w-full md:w-1/2 border rounded p-2 mt-1">
                            <option value="before" selected>Move to the working day before</option>
                            <option value="after">Move to the working day after</option>
                            <option value="keep">Keep the date as is</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Holidays come from the Holidays tab (recurring holidays count every year).</p>
                    </div>
                    <p class="text-xs text-emerald-700 mt-2" id="salaryDayPreview"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-[#7b5cff] hover:bg-[#6249e0] text-white px-6 py-2 rounded-lg shadow">Save Settings</button>
            </div>
        </form>
    </div>

    <!-- Tab: SSS Brackets -->
    <div class="payroll-tab-content hidden flex-1 min-h-0 flex flex-col" id="tab-sss">
        <?= view('client_portal/sss_brackets_panel') ?>
    </div>

    <!-- Tab: Withholding Tax -->
    <div class="payroll-tab-content hidden flex-1 min-h-0 flex flex-col" id="tab-wtax">
        <?= view('client_portal/wtax_brackets_panel') ?>
    </div>

    <!-- Tab: Additional De Minimis Categories (BIR) -->
    <div class="payroll-tab-content hidden flex-1 min-h-0 flex flex-col" id="tab-deminimis">
        <?= view('client_portal/deminimis_categories_panel') ?>
    </div>

    <!-- Tab: Holidays -->
    <div class="payroll-tab-content hidden flex-1 overflow-auto hide-scroll-bar" id="tab-holidays">
        <div class="flex items-center gap-3 mb-4">
            <label class="font-medium text-sm">Year:</label>
            <input type="number" id="holidayYear" value="<?= date('Y') ?>" min="2020" max="2099"
                   class="border rounded p-1 w-24 text-sm"
                   onchange="loadHolidays()">
            <button type="button" onclick="loadHolidays()"
                    class="text-sm bg-gray-100 hover:bg-gray-200 border rounded px-3 py-1">Refresh</button>
            <button type="button" onclick="loadPhHolidays()"
                    class="text-sm bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded px-3 py-1 text-indigo-700">
                Load PH Holidays
            </button>
        </div>

        <!-- Add / Edit form -->
        <div class="bg-gray-50 border rounded p-4 mb-4">
            <h4 class="font-semibold text-sm mb-3">Add / Edit Holiday</h4>
            <input type="hidden" id="hol_edit_id" value="">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="text-xs text-gray-500">Date</label>
                    <input type="date" id="hol_date" class="w-full border rounded p-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-gray-500">Holiday Name</label>
                    <input type="text" id="hol_name" placeholder="e.g. New Year's Day" class="w-full border rounded p-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Type</label>
                    <select id="hol_type" class="w-full border rounded p-2 text-sm">
                        <option value="regular">Regular Holiday</option>
                        <option value="special">Special Non-Working</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-3">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" id="hol_recurring" class="rounded">
                    Recurring every year
                </label>
                <button type="button" onclick="saveHoliday()"
                        class="bg-[#7b5cff] hover:bg-[#6249e0] text-white px-5 py-2 rounded text-sm">Save Holiday</button>
                <button type="button" onclick="clearHolForm()" id="hol_cancel_btn"
                        class="hidden text-sm text-gray-500 hover:text-gray-700 underline">Cancel edit</button>
            </div>
        </div>

        <!-- Holiday list table -->
        <div id="holidayTableWrap">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="p-2 border">Date</th>
                        <th class="p-2 border">Name</th>
                        <th class="p-2 border">Type</th>
                        <th class="p-2 border text-center">Recurring</th>
                        <th class="p-2 border text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="holidayTableBody">
                    <tr><td colspan="5" class="p-4 text-center text-gray-400">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .payroll-tab {
        color: #6b7280;
        background: transparent;
        transition: all 0.15s;
    }
    .payroll-tab:hover {
        color: #7b5cff;
        background: #f5f3ff;
    }
    .payroll-tab.active {
        color: #7b5cff;
        background: #fff;
        border-color: #e5e7eb;
        border-bottom-color: #fff;
        margin-bottom: -1px;
    }
</style>

<script>
    // Tab switching
    document.querySelectorAll('.payroll-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.payroll-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.payroll-tab-content').forEach(c => c.classList.add('hidden'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
        });
    });

    // Load general settings
    async function loadSettings() {
        const res = await fetch('<?= site_url('client_portal/payroll/getSettings') ?>');
        const s = await res.json();
        document.getElementById('setting_monthly_hours').value    = s.monthly_hours      || '174';
        document.getElementById('setting_night_diff_start').value = s.night_diff_start || '22:00';
        document.getElementById('setting_night_diff_end').value   = s.night_diff_end   || '06:00';
        document.getElementById('setting_night_diff_rate').value  = s.night_diff_rate  || '0.10';
        document.getElementById('setting_rest_day_rate').value      = s.rest_day_rate     || '1.30';
        document.getElementById('setting_reg_holiday_rate').value  = s.reg_holiday_rate  || '2.00';
        document.getElementById('setting_spec_holiday_rate').value = s.spec_holiday_rate || '1.30';
        document.getElementById('setting_ot_regular_rate').value  = s.ot_regular_rate  || '1.25';
        document.getElementById('setting_ot_rest_rate').value     = s.ot_rest_rate     || '1.69';
        document.getElementById('setting_ot_holiday_rate').value  = s.ot_holiday_rate  || '2.60';
        document.getElementById('setting_phic_rate').value        = s.phic_rate        || '0.05';
        document.getElementById('setting_phic_cap').value              = s.phic_cap              || '100000';
        document.getElementById('setting_hdmf_rate').value             = s.hdmf_rate             || '0.02';
        document.getElementById('setting_hdmf_max').value              = s.hdmf_max              || '200';
        document.getElementById('setting_default_period_type').value   = s.default_period_type   || 'auto';
        document.getElementById('setting_first_half_cutoff').value     = s.first_half_cutoff     || '15';

        // Cross-month period definition
        if (s.half1_start_day) document.getElementById('setting_half1_start_day').value   = s.half1_start_day;
        if (s.half1_start_offset !== undefined) document.getElementById('setting_half1_start_offset').value = s.half1_start_offset;
        if (s.half1_end_day   !== undefined) document.getElementById('setting_half1_end_day').value     = s.half1_end_day;
        if (s.half1_end_offset   !== undefined) document.getElementById('setting_half1_end_offset').value   = s.half1_end_offset;
        if (s.half2_start_day) document.getElementById('setting_half2_start_day').value   = s.half2_start_day;
        if (s.half2_start_offset !== undefined) document.getElementById('setting_half2_start_offset').value = s.half2_start_offset;
        if (s.half2_end_day   !== undefined) document.getElementById('setting_half2_end_day').value     = s.half2_end_day;
        if (s.half2_end_offset   !== undefined) document.getElementById('setting_half2_end_offset').value   = s.half2_end_offset;
        if (s.monthly_start_day) document.getElementById('setting_monthly_start_day').value = s.monthly_start_day;
        if (s.monthly_start_offset !== undefined) document.getElementById('setting_monthly_start_offset').value = s.monthly_start_offset;
        if (s.monthly_end_day   !== undefined) document.getElementById('setting_monthly_end_day').value   = s.monthly_end_day;
        if (s.monthly_end_offset !== undefined) document.getElementById('setting_monthly_end_offset').value = s.monthly_end_offset;

        // Salary day (Payslip tab's schedule default / Automatic mode date)
        const setIf = (id, v) => { const el = document.getElementById(id); if (el && v !== undefined && v !== null) el.value = v; };
        setIf('setting_half1_salary_day',    s.half1_salary_day);
        setIf('setting_half1_salary_offset', s.half1_salary_offset ?? '0');
        setIf('setting_half2_salary_day',    s.half2_salary_day);
        setIf('setting_half2_salary_offset', s.half2_salary_offset ?? '0');
        setIf('setting_monthly_salary_day',    s.monthly_salary_day);
        setIf('setting_monthly_salary_offset', s.monthly_salary_offset ?? '0');
        setIf('setting_salary_day_adjust',   s.salary_day_adjust || 'before');

        updatePeriodPreview();
    }

    // Live preview helper — mirrors buildPeriodDate from payroll_management.php
    function settingsBuildDate(salaryY, salaryM, day, offset) {
        var rawM = salaryM + parseInt(offset || 0);
        var tY   = salaryY + Math.floor((rawM - 1) / 12);
        var tM   = ((rawM - 1) % 12 + 12) % 12 + 1;
        var lastDay = new Date(tY, tM, 0).getDate();
        var d = parseInt(day) === 0 ? lastDay : Math.min(parseInt(day) || 1, lastDay);
        return tY + '-' + String(tM).padStart(2,'0') + '-' + String(d).padStart(2,'0');
    }

    function updatePeriodPreview() {
        const previewEl = document.getElementById('previewMonth');
        const resultEl  = document.getElementById('periodPreviewResult');
        if (!previewEl || !resultEl) return;

        const h1sd = document.getElementById('setting_half1_start_day')?.value;
        const h1ed = document.getElementById('setting_half1_end_day')?.value;
        const h2sd = document.getElementById('setting_half2_start_day')?.value;
        const h2ed = document.getElementById('setting_half2_end_day')?.value;

        const fmt = d => new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });

        // Monthly preview — independent of the 1st/2nd Half fields, defaults
        // to the full calendar month when its own fields are left blank.
        let monthlyHtml = '';
        if (previewEl.value) {
            const [my, mm] = previewEl.value.split('-').map(Number);
            const msd = document.getElementById('setting_monthly_start_day')?.value || 1;
            const med = document.getElementById('setting_monthly_end_day')?.value ?? 0;
            const mso = document.getElementById('setting_monthly_start_offset')?.value || 0;
            const meo = document.getElementById('setting_monthly_end_offset')?.value || 0;
            const ms = settingsBuildDate(my, mm, msd, mso);
            const me = settingsBuildDate(my, mm, med, meo);
            monthlyHtml = '<strong>Monthly:</strong> ' + fmt(ms) + ' – ' + fmt(me);
        }

        if (!h1sd || !h2sd || !previewEl.value) {
            resultEl.innerHTML = (monthlyHtml || '— select a salary month above —')
                + (monthlyHtml ? ' &nbsp;|&nbsp; 1st/2nd Half: fill in all four day fields above' : '');
            updateSalaryDayPreview();
            return;
        }

        const [py, pm] = previewEl.value.split('-').map(Number);
        const h1so = document.getElementById('setting_half1_start_offset')?.value || 0;
        const h1eo = document.getElementById('setting_half1_end_offset')?.value   || 0;
        const h2so = document.getElementById('setting_half2_start_offset')?.value || 0;
        const h2eo = document.getElementById('setting_half2_end_offset')?.value   || 0;

        const h1s = settingsBuildDate(py, pm, h1sd, h1so);
        const h1e = settingsBuildDate(py, pm, h1ed, h1eo);
        const h2s = settingsBuildDate(py, pm, h2sd, h2so);
        const h2e = settingsBuildDate(py, pm, h2ed, h2eo);

        resultEl.innerHTML =
            '<strong>1st Half:</strong> ' + fmt(h1s) + ' – ' + fmt(h1e) +
            ' &nbsp;|&nbsp; <strong>2nd Half:</strong> ' + fmt(h2s) + ' – ' + fmt(h2e) +
            ' &nbsp;|&nbsp; ' + monthlyHtml;
        updateSalaryDayPreview();
    }

    // Salary days for the preview month (before any weekend/holiday move —
    // that's applied against the Holidays list when a payslip is scheduled).
    function updateSalaryDayPreview() {
        const el = document.getElementById('salaryDayPreview');
        const month = document.getElementById('previewMonth')?.value;
        if (!el) return;
        const d1 = document.getElementById('setting_half1_salary_day')?.value;
        const d2 = document.getElementById('setting_half2_salary_day')?.value;
        const dm = document.getElementById('setting_monthly_salary_day')?.value;
        if (!month || (d1 === '' && d2 === '' && dm === '')) { el.textContent = ''; return; }
        const [py, pm] = month.split('-').map(Number);
        const fmt = d => new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        const part = (label, day, off) => day === '' ? `${label}: not set` : `${label}: ${fmt(settingsBuildDate(py, pm, day, off))}`;
        el.textContent = 'Salary days for the preview month — ' +
            part('1st half', d1, document.getElementById('setting_half1_salary_offset')?.value || 0) + ', ' +
            part('2nd half', d2, document.getElementById('setting_half2_salary_offset')?.value || 0) + ', ' +
            part('monthly', dm, document.getElementById('setting_monthly_salary_offset')?.value || 0) +
            ' (a weekend/holiday is moved when scheduling).';
    }
    ['setting_half1_salary_day', 'setting_half1_salary_offset', 'setting_half2_salary_day', 'setting_half2_salary_offset',
     'setting_monthly_salary_day', 'setting_monthly_salary_offset']
        .forEach(id => document.getElementById(id)?.addEventListener('input', updateSalaryDayPreview));

    // ===== HOLIDAY MANAGEMENT =====
    const HOL_LIST_URL   = '<?= site_url('client_portal/payroll/holidays/list') ?>';
    const HOL_SAVE_URL   = '<?= site_url('client_portal/payroll/holidays/save') ?>';
    const HOL_DELETE_URL = '<?= site_url('client_portal/payroll/holidays/delete') ?>';

    async function loadHolidays() {
        const year = document.getElementById('holidayYear').value || new Date().getFullYear();
        const res  = await fetch(HOL_LIST_URL + '?year=' + year);
        const json = await res.json();
        const tbody = document.getElementById('holidayTableBody');
        if (!json.success || !json.data.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-400">No holidays for ' + year + '. Add one above.</td></tr>';
            return;
        }
        tbody.innerHTML = json.data.map(h => {
            const dateLabel = h.is_recurring
                ? '<span title="Recurring">' + h.holiday_date.substr(5) + ' (every year)</span>'
                : h.holiday_date;
            const typeBadge = h.type === 'regular'
                ? '<span class="bg-red-100 text-red-700 rounded px-2 py-0.5 text-xs">Regular</span>'
                : '<span class="bg-yellow-100 text-yellow-700 rounded px-2 py-0.5 text-xs">Special</span>';
            return `<tr class="hover:bg-gray-50">
                <td class="p-2 border">${dateLabel}</td>
                <td class="p-2 border">${h.name}</td>
                <td class="p-2 border">${typeBadge}</td>
                <td class="p-2 border text-center">${h.is_recurring ? '✓' : ''}</td>
                <td class="p-2 border text-center">
                    <button onclick="editHoliday(${JSON.stringify(h).replace(/"/g,'&quot;')})"
                            class="text-indigo-600 hover:underline text-xs mr-2">Edit</button>
                    <button onclick="deleteHoliday(${h.id}, '${h.name.replace(/'/g,'\\\'')}')"
                            class="text-red-500 hover:underline text-xs">Delete</button>
                </td>
            </tr>`;
        }).join('');
    }

    async function saveHoliday() {
        const id   = document.getElementById('hol_edit_id').value;
        const date = document.getElementById('hol_date').value;
        const name = document.getElementById('hol_name').value.trim();
        if (!date || !name) { showToast('Date and name are required.', 'warning'); return; }
        const payload = {
            id:           id || null,
            holiday_date: date,
            name,
            type:         document.getElementById('hol_type').value,
            is_recurring: document.getElementById('hol_recurring').checked ? 1 : 0,
        };
        const res  = await fetch(HOL_SAVE_URL, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) { clearHolForm(); loadHolidays(); }
        else showToast(json.message || 'Save failed.', 'error');
    }

    function editHoliday(h) {
        document.getElementById('hol_edit_id').value    = h.id;
        document.getElementById('hol_date').value       = h.holiday_date;
        document.getElementById('hol_name').value       = h.name;
        document.getElementById('hol_type').value       = h.type;
        document.getElementById('hol_recurring').checked = !!h.is_recurring;
        document.getElementById('hol_cancel_btn').classList.remove('hidden');
    }

    function clearHolForm() {
        document.getElementById('hol_edit_id').value    = '';
        document.getElementById('hol_date').value       = '';
        document.getElementById('hol_name').value       = '';
        document.getElementById('hol_type').value       = 'regular';
        document.getElementById('hol_recurring').checked = false;
        document.getElementById('hol_cancel_btn').classList.add('hidden');
    }

    async function deleteHoliday(id, name) {
        if (!confirm('Delete "' + name + '"?')) return;
        const res  = await fetch(HOL_DELETE_URL, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id})
        });
        const json = await res.json();
        if (json.success) loadHolidays();
        else showToast(json.message || 'Delete failed.', 'error');
    }

    // Pre-fill with standard PH public holidays for the selected year
    function loadPhHolidays() {
        const year = document.getElementById('holidayYear').value || new Date().getFullYear();
        const ph = [
            { date: year+'-01-01', name: "New Year's Day",              type: 'regular',  recurring: true  },
            { date: year+'-04-09', name: 'Araw ng Kagitingan',          type: 'regular',  recurring: true  },
            { date: year+'-05-01', name: 'Labor Day',                   type: 'regular',  recurring: true  },
            { date: year+'-06-12', name: 'Independence Day',            type: 'regular',  recurring: true  },
            { date: year+'-08-25', name: 'National Heroes Day',         type: 'regular',  recurring: false },
            { date: year+'-11-30', name: 'Bonifacio Day',               type: 'regular',  recurring: true  },
            { date: year+'-12-25', name: 'Christmas Day',               type: 'regular',  recurring: true  },
            { date: year+'-12-30', name: 'Rizal Day',                   type: 'regular',  recurring: true  },
            { date: year+'-08-21', name: 'Ninoy Aquino Day',            type: 'special',  recurring: true  },
            { date: year+'-11-01', name: "All Saints' Day",             type: 'special',  recurring: true  },
            { date: year+'-11-02', name: "All Souls' Day",              type: 'special',  recurring: true  },
            { date: year+'-12-08', name: 'Feast of Immaculate Conception', type: 'special', recurring: true },
            { date: year+'-12-31', name: "New Year's Eve",              type: 'special',  recurring: true  },
        ];
        if (!confirm('This will add ' + ph.length + ' standard PH holidays for ' + year + '. Existing entries will not be overwritten. Continue?')) return;
        Promise.all(ph.map(h => fetch(HOL_SAVE_URL, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                holiday_date: h.date, name: h.name, type: h.type, is_recurring: h.recurring ? 1 : 0
            })
        }))).then(() => loadHolidays());
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadSettings();

        // Set previewMonth default to current month
        const pm = document.getElementById('previewMonth');
        if (pm) {
            const now = new Date();
            pm.value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2,'0');
            pm.addEventListener('change', updatePeriodPreview);
        }

        // Live preview on any period field change
        ['half1_start_day','half1_start_offset','half1_end_day','half1_end_offset',
         'half2_start_day','half2_start_offset','half2_end_day','half2_end_offset',
         'monthly_start_day','monthly_start_offset','monthly_end_day','monthly_end_offset'].forEach(function(id) {
            const el = document.getElementById('setting_' + id);
            if (el) el.addEventListener('change', updatePeriodPreview);
        });
    });
</script>