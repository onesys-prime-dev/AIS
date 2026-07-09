<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="h-full flex flex-col min-h-0 overflow-hidden">

    <!-- TOP TABS (three tabs) -->
    <div class="tabs">
        <button type="button" class="tab active" data-tab="payroll-status">Payroll Status</button>
        <button type="button" class="tab" data-tab="generate-payslip">Generate Payslip</button>
        <button type="button" class="tab" data-tab="payroll-settings">Payroll Settings</button>
        <button type="button" class="tab" data-tab="payslip-history">Payslip History</button>
        <button type="button" class="tab" data-tab="year-end">13th Month &amp; Annualization</button>
        <button type="button" class="tab" data-tab="payroll-register">Payroll Register</button>
    </div>

    <div class="tab-panel h-full flex flex-col min-h-0 overflow-hidden px-8 pb-8" data-panel="payroll-status" style="display:flex;">
        <?= view('client_portal/payroll_status') ?>
    </div>

    <div class="tab-panel h-full flex flex-col min-h-0 overflow-visible p-8" data-panel="generate-payslip" style="display:none;">
        <?= view('client_portal/payroll_management') ?>
    </div>

    <div class="tab-panel h-full flex flex-col min-h-0 overflow-hidden p-8" data-panel="payroll-settings" style="display:none;">
        <?= view('client_portal/payroll_settings_panel') ?>
    </div>

    <div class="tab-panel h-full flex flex-col min-h-0 overflow-hidden p-8" data-panel="payslip-history" style="display:none;">
        <?= view('client_portal/payroll_payslip_history') ?>
    </div>

    <div class="tab-panel h-full flex flex-col min-h-0 overflow-y-auto p-8" data-panel="year-end" style="display:none;">
        <?= view('client_portal/payroll_13th_month') ?>
    </div>

    <div class="tab-panel h-full flex flex-col min-h-0 overflow-hidden p-8" data-panel="payroll-register" style="display:none;">
        <?= view('client_portal/payroll_register') ?>
    </div>

</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script src="<?= base_url('js/components/modal-handler.js') ?>?v=2"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs   = document.querySelectorAll('.tab[data-tab]');
        const panels = document.querySelectorAll('.tab-panel[data-panel]');

        function activateTab(tabKey) {
            tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === tabKey));
            panels.forEach(panel => {
                panel.style.display = panel.dataset.panel === tabKey ? 'flex' : 'none';
            });
        }
        window.activatePayrollTab = activateTab;

        let yearEndLoaded = false;
        tabs.forEach(tab => {
            tab.addEventListener('click', function () {
                activateTab(tab.dataset.tab);
                if (tab.dataset.tab === 'year-end' && !yearEndLoaded) {
                    yearEndLoaded = true;
                    const yr = document.getElementById('yearEndYear')?.value ?? new Date().getFullYear();
                    if (typeof window._loadYearEndRecords === 'function') {
                        window._loadYearEndRecords(yr);
                    }
                }
            });
        });

        activateTab('payroll-status');
    });
</script>
<?= $this->endSection() ?>