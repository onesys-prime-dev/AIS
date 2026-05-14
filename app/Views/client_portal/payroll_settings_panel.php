<!-- app/Views/client_portal/payroll_settings_panel.php -->
<div class="bg-white rounded-2xl shadow-lg p-8 h-full border border-gray-300 flex flex-col">
    <h3 class="font-semibold text-xl mb-6">Payroll Computation Settings</h3>
    <form id="payrollSettingsForm" action="<?= site_url('client_portal/payroll/saveSettings') ?>" method="post" class="flex-1 overflow-auto hide-scroll-bar">
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
                <h4 class="font-semibold border-b pb-1 mb-2">Overtime Rates</h4>
                <label>Regular OT (x)</label>
                <input type="number" step="0.01" name="setting_ot_regular_rate" id="setting_ot_regular_rate" class="w-full border rounded p-2">
                <label class="mt-2">Rest Day OT (x)</label>
                <input type="number" step="0.01" name="setting_ot_rest_rate" id="setting_ot_rest_rate" class="w-full border rounded p-2">
                <label class="mt-2">Holiday OT (x)</label>
                <input type="number" step="0.01" name="setting_ot_holiday_rate" id="setting_ot_holiday_rate" class="w-full border rounded p-2">
            </div>
            <div class="bg-gray-50 p-4 rounded shadow">
                <h4 class="font-semibold border-b pb-1 mb-2">Government Contributions (%)</h4>
                <label>PhilHealth Rate</label>
                <input type="number" step="0.0001" name="setting_phic_rate" id="setting_phic_rate" class="w-full border rounded p-2">
                <label class="mt-2">Pag-IBIG Rate</label>
                <input type="number" step="0.0001" name="setting_hdmf_rate" id="setting_hdmf_rate" class="w-full border rounded p-2">
                <label class="mt-2">SSS Table (JSON)</label>
                <textarea name="setting_sss_table" id="setting_sss_table" rows="3" class="w-full border rounded p-2" placeholder='[{"range":"0-2500","ee":135,"er":135}]'></textarea>
            </div>
        </div>
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-[#7b5cff] hover:bg-[#6249e0] text-white px-6 py-2 rounded-lg shadow">Save Settings</button>
        </div>
    </form>
</div>

<script>
    async function loadSettings() {
        const res = await fetch('<?= site_url('client_portal/payroll/getSettings') ?>');
        const settings = await res.json();
        document.getElementById('setting_monthly_hours').value = settings.monthly_hours || '160';
        document.getElementById('setting_night_diff_start').value = settings.night_diff_start || '22:00';
        document.getElementById('setting_night_diff_end').value = settings.night_diff_end || '06:00';
        document.getElementById('setting_night_diff_rate').value = settings.night_diff_rate || '0.10';
        document.getElementById('setting_ot_regular_rate').value = settings.ot_regular_rate || '1.25';
        document.getElementById('setting_ot_rest_rate').value = settings.ot_rest_rate || '1.30';
        document.getElementById('setting_ot_holiday_rate').value = settings.ot_holiday_rate || '2.00';
        document.getElementById('setting_phic_rate').value = settings.phic_rate || '0.03';
        document.getElementById('setting_hdmf_rate').value = settings.hdmf_rate || '0.02';
        document.getElementById('setting_sss_table').value = settings.sss_table || '[]';
    }
    document.addEventListener('DOMContentLoaded', loadSettings);
</script>