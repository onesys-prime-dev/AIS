<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="p-5 h-full overflow-y-auto">

    <div class="flex justify-between items-center mb-6">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Payroll Status</h1>
            <p class="text-gray-500 text-sm">Check the payroll status of your employees.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Month Picker -->
            <input type="month" id="monthPicker" value="<?= $selected_month ?>" class="bg-[#969595] hover:bg-[#797777d2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
            <button 
                data-modal-open="payrollModal"
                class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                    <div id="add-icon" class="w-7 h-7 cursor-pointer">
                        <img src="<?php echo base_url('icons/add.png'); ?>" alt="Add"/>
                    </div>
                New Payroll Entry
            </button>
        </div>
    </div>

    <?= view('components/crud_table', [
        'title' => date('F Y', strtotime($selected_month . '-01')) . ' Payroll',
        'tableId' => 'payrollTable',
        'grid' => 'grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr]',
        'columns' => [
            'Employee Name',
            'Phone',
            'Daily Rate',
            'DST',
            'Currency',
            'Status',
            'Action'
        ]
    ]) ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <!-- Add Payroll Modal -->
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
    const PAYROLLS = <?= json_encode($payrolls) ?>;
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

        return `
        <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr] items-center border-b hover:bg-gray-50">

            <div class="p-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                <span class="font-medium">${htmlEsc(p.employee_name || '---')}</span>
            </div>

            <div class="p-4">${htmlEsc(p.phone || '---')}</div>

            <div class="p-4">
                ${p.currency === 'USD' ? '$' : '₱'} ${money(p.daily_rate)}
            </div>

            <div class="p-4">${Number(p.dst) === 1 ? 'Yes' : 'No'}</div>

            <div class="p-4">${htmlEsc(p.currency || 'PHP')}</div>

            <div class="p-4">
                ${p.status === 'Paid'
                    ? '<span class="bg-green-200 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Paid</span>'
                    : '<span class="bg-yellow-200 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">Pending</span>'}
            </div>

            <div class="p-4 flex justify-center gap-4">

                <!-- EDIT -->
                <div
                    class="cursor-pointer w-7 h-7 hover:scale-110 transition"
                    data-action="edit"
                    data-modal="editPayrollModal"
                    data-update-url="${UPDATE_URL}"
                    data-id="${htmlEsc(p.id)}"
                    data-employee_id="${htmlEsc(p.employee_id)}"
                    data-employee_name="${htmlEsc(p.employee_name)}"
                    data-phone="${htmlEsc(p.phone)}"
                    data-daily_rate="${htmlEsc(p.daily_rate)}"
                    data-dst="${htmlEsc(p.dst)}"
                    data-currency="${htmlEsc(p.currency)}"
                    data-status="${htmlEsc(p.status ?? 'Pending')}">

                    <img src="${EDIT_ICON}" class="w-7 h-7" alt="Edit">

                </div>

                <!-- DELETE -->
                <div
                    class="cursor-pointer w-7 h-7 hover:scale-110 transition"
                    data-action="delete"
                    data-delete-url="${DELETE_URL}"
                    data-id="${htmlEsc(p.id)}">

                    <img src="${DELETE_ICON}" class="w-7 h-7" alt="Delete">

                </div>

            </div>

        </div>
        `;
    }

    createCrudTable({

        tableId: "payrollTable",

        data: PAYROLLS,

        renderRow: payrollRowTemplate,

        search: (item, q) =>
            String(item.employee_name || '')
                .toLowerCase()
                .includes(q)

    });

</script>

<?= $this->endSection() ?>