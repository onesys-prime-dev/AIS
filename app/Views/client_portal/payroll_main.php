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

</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
    <?php
        $content = view('client_portal/modals/add_payroll_form');
        echo view('components/modal', [
            'id' => 'payrollModal',
            'title' => 'Add Payroll Entry',
            'content' => $content
        ]);
    ?>
    <?php
        $content = view('client_portal/modals/edit_payroll_form');
        echo view('components/modal', [
            'id' => 'editPayrollModal',
            'title' => 'Edit Payroll Entry',
            'content' => $content
        ]);
    ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/components/crud-table.js') ?>"></script>
<script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>
<script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
<script src="<?= base_url('js/components/crud-actions.js') ?>"></script>

<script>
    const PAYROLLS = <?= json_encode($payrolls ?? []) ?>;
    const EDIT_ICON = "<?= base_url('icons/edit.png') ?>";
    const DELETE_ICON = "<?= base_url('icons/delete.png') ?>";
    const UPDATE_URL = "<?= site_url('client_portal/payroll/update') ?>";
    const DELETE_URL = "<?= site_url('client_portal/payroll/delete') ?>";

    function htmlEsc(v) {
        if (v == null) return '';
        return String(v)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function money(v) {
        return Number(v || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function payrollRowTemplate(p) {
        // avatar: photo_path if present, otherwise fallback circle
        const displayName = htmlEsc(p.employee_name || '');
        const initials = displayName ? displayName.substring(0,2).toUpperCase() : 'U';
        const photoUrl = p.photo_path ? p.photo_path : '';
        const avatarHtml = photoUrl
            ? `<div class="w-10 h-10 rounded-full" style="background-image:url('${photoUrl}');background-size:cover;background-position:center;"></div>`
            : `<div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-xs font-bold text-gray-700">${initials}</div>`;

        return `
        <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr] items-center border-b hover:bg-gray-50">
            <div class="p-4 flex items-center gap-3">
                ${avatarHtml}
                <span class="font-medium">${displayName || '---'}</span>
            </div>
            <div class="p-4">${htmlEsc(p.phone || '---')}</div>
            <div class="p-4">${p.currency === 'USD' ? '$' : 'PHP '} ${money(p.daily_rate)}</div>
            <div class="p-4">${Number(p.dst) === 1 ? 'Yes' : 'No'}</div>
            <div class="p-4">${htmlEsc(p.currency || 'PHP')}</div>
            <div class="p-4">${p.status === 'Paid' ? '<span class="bg-green-200 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Paid</span>' : '<span class="bg-yellow-200 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">Pending</span>'}</div>
            <div class="p-4 flex justify-center gap-4">
                <div class="cursor-pointer w-7 h-7 hover:scale-110 transition" data-action="edit" data-modal="editPayrollModal" data-update-url="${UPDATE_URL}" data-id="${htmlEsc(p.id)}" data-employee_id="${htmlEsc(p.employee_id)}" data-employee_name="${htmlEsc(p.employee_name)}" data-phone="${htmlEsc(p.phone)}" data-daily_rate="${htmlEsc(p.daily_rate)}" data-dst="${htmlEsc(p.dst)}" data-currency="${htmlEsc(p.currency)}" data-status="${htmlEsc(p.status ?? 'Pending')}">
                    <img src="${EDIT_ICON}" class="w-7 h-7" alt="Edit">
                </div>
                <div class="cursor-pointer w-7 h-7 hover:scale-110 transition" data-action="delete" data-delete-url="${DELETE_URL}" data-id="${htmlEsc(p.id)}">
                    <img src="${DELETE_ICON}" class="w-7 h-7" alt="Delete">
                </div>
            </div>
        </div>`;
    }

    createCrudTable({
        tableId: "payrollTable",
        data: Array.isArray(PAYROLLS) ? PAYROLLS : [],
        renderRow: payrollRowTemplate,
        search: (item, q) => `${item.employee_name ?? ''} ${item.phone ?? ''} ${item.currency ?? ''} ${item.status ?? ''}`.toLowerCase().includes(q)
    });

    document.getElementById('monthPicker')?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('month', this.value);
        window.location.href = url.toString();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.tab[data-tab]');
        const panels = document.querySelectorAll('.tab-panel[data-panel]');

        function activateTab(tabKey) {
            tabs.forEach(tab => {
                tab.classList.toggle('active', tab.dataset.tab === tabKey);
            });
            panels.forEach(panel => {
                panel.style.display = panel.dataset.panel === tabKey ? 'flex' : 'none';
            });
        }
        // Expose globally so child views (13th month panel) can switch tabs
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