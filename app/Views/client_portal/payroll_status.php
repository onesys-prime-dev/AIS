<div class="h-full flex flex-col min-h-0 overflow-hidden">

    <div class="flex justify-between items-center my-4">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Payroll Status</h1>
            <p class="text-gray-500 text-sm">Check the payroll status of your employees.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Month Picker -->
            <input type="month" id="monthPicker" value="<?= $selected_month ?>" class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
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