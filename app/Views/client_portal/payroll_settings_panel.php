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
    </div>

    <!-- Tab: General -->
    <div class="payroll-tab-content flex-1 overflow-auto hide-scroll-bar" id="tab-general">
        <form id="payrollSettingsForm" action="<?= site_url('client_portal/payroll/saveSettings') ?>" method="post">
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
                    <h4 class="font-semibold border-b pb-1 mb-2">Holiday Pay Rates</h4>
                    <label>Regular Holiday Rate (x) <span class="text-xs text-gray-400">&le;8 hrs &mdash; 200%</span></label>
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
        document.getElementById('setting_monthly_hours').value  = s.monthly_hours    || '160';
        document.getElementById('setting_night_diff_start').value = s.night_diff_start || '22:00';
        document.getElementById('setting_night_diff_end').value   = s.night_diff_end   || '06:00';
        document.getElementById('setting_night_diff_rate').value  = s.night_diff_rate  || '0.10';
        document.getElementById('setting_reg_holiday_rate').value  = s.reg_holiday_rate  || '2.00';
        document.getElementById('setting_spec_holiday_rate').value = s.spec_holiday_rate || '1.30';
        document.getElementById('setting_ot_regular_rate').value  = s.ot_regular_rate  || '1.25';
        document.getElementById('setting_ot_rest_rate').value     = s.ot_rest_rate     || '1.69';
        document.getElementById('setting_ot_holiday_rate').value  = s.ot_holiday_rate  || '2.60';
        document.getElementById('setting_phic_rate').value        = s.phic_rate        || '0.05';
        document.getElementById('setting_phic_cap').value         = s.phic_cap         || '80000';
        document.getElementById('setting_hdmf_rate').value        = s.hdmf_rate        || '0.02';
        document.getElementById('setting_hdmf_max').value         = s.hdmf_max         || '100';
    }
    document.addEventListener('DOMContentLoaded', loadSettings);
</script>