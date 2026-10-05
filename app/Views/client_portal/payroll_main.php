<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div id="payrollPortalRoot" class="h-full flex flex-col min-h-0 overflow-hidden min-w-0">

    <!-- TOP TABS (three tabs) -->
    <div class="tabs">
        <button type="button" class="tab active" data-tab="payroll-status">Payroll Status</button>
        <button type="button" class="tab" data-tab="generate-payslip">Generate Payslip</button>
        <button type="button" class="tab" data-tab="payroll-settings">Payroll Settings</button>
        <button type="button" class="tab" data-tab="year-end">13th Month &amp; Annualization</button>
        <button type="button" class="tab" data-tab="payroll-register">Payroll Output</button>
        <button type="button" class="tab" data-tab="audit-log">Audit Log</button>
    </div>

    <div class="payroll-tab-panel tab-panel h-full flex flex-col min-h-0 overflow-hidden px-8 pb-8 min-w-0" data-panel="payroll-status" style="display:flex;">
        <?= view('client_portal/payroll_status') ?>
    </div>

    <div class="payroll-tab-panel tab-panel h-full flex flex-col min-h-0 overflow-visible p-8 min-w-0" data-panel="generate-payslip" style="display:none;">
        <?= view('client_portal/payroll_management') ?>
    </div>

    <div class="payroll-tab-panel tab-panel h-full flex flex-col min-h-0 overflow-hidden p-8 min-w-0" data-panel="payroll-settings" style="display:none;">
        <?= view('client_portal/payroll_settings_panel') ?>
    </div>

    <div class="payroll-tab-panel tab-panel h-full flex flex-col min-h-0 overflow-y-auto p-8 min-w-0" data-panel="year-end" style="display:none;">
        <?= view('client_portal/payroll_13th_month') ?>
    </div>

    <div class="payroll-tab-panel tab-panel h-full flex flex-col min-h-0 overflow-hidden p-8 min-w-0" data-panel="payroll-register" style="display:none;">
        <?= view('client_portal/payroll_register') ?>
    </div>

    <div class="payroll-tab-panel tab-panel h-full flex flex-col min-h-0 overflow-hidden p-8 min-w-0" data-panel="audit-log" style="display:none;">
        <?= view('client_portal/payroll_audit_log') ?>
    </div>

</div>

<style>
    @media (max-width: 900px) {
        #payrollPortalRoot {
            overflow-y: auto;
        }

        #payrollPortalRoot .payroll-tab-panel {
            height: auto;
            overflow: visible;
            padding: 0 12px 16px;
        }
    }
</style>

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

        let yearEndLoaded  = false;
        let auditLogLoaded = false;
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
                if (tab.dataset.tab === 'audit-log' && !auditLogLoaded) {
                    auditLogLoaded = true;
                    if (typeof window._loadAuditLog === 'function') {
                        window._loadAuditLog();
                    }
                }
            });
        });

        activateTab('payroll-status');
    });
</script>
<?= $this->endSection() ?>
