<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="p-5 h-full flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-4">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Timesheets</h1>
            <p class="text-gray-500 text-sm">Manage timesheets here.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Month Picker -->
            <button 
                data-modal-open="payrollModal"
                class="bg-[#FFFFFF] hover:bg-[#bbbbbbd6] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                    <div id="add-icon" class="w-7 h-7 cursor-pointer">
                        <img src="<?php echo base_url('icons/alarms.png'); ?>" alt="Add"/>
                    </div>
            </button>
            <button 
                data-modal-open="payrollModal"
                class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                    <div id="add-icon" class="w-7 h-7 cursor-pointer">
                        <img src="<?php echo base_url('icons/payslip.png'); ?>" alt="Add"/>
                    </div>
                View Payslip
            </button>
        </div>

    </div>

    <?= view('components/crud_table', [
        'title' => 'Timesheet',
        'tableId' => 'timesheetTable',
        'grid' => 'grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr]',
        'columns' => [
            'Date',
            'Time Start',
            'Time End',
            'Client',
            'Task Performed',
            'Action'
        ]
    ]) ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <?php
    $content = view('client_portal/modals/add_employee_form');
    echo view('components/modal', [
        'id' => 'employeeModal',
        'title' => 'Employee Management',
        'content' => $content
    ]);
    ?>

    <?php
    $content = view('client_portal/modals/edit_employee_form');
    echo view('components/modal', [
        'id' => 'editEmployeeModal',
        'title' => 'Employee Management',
        'content' => $content
    ]);
    ?>

<?= $this->endSection() ?>