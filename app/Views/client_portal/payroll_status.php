<div class="h-full flex flex-col min-h-0 overflow-hidden">

    <div class="flex justify-between items-center my-4">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Payroll Status</h1>
            <p class="text-gray-500 text-sm">Check the payroll status of your employees.</p>
        </div>

        <div class="flex items-center gap-3">

            <!-- Month Picker -->
            <div class="flex items-center bg-white border border-gray-200 rounded-lg shadow-sm px-3 py-2">
                <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                </svg>
                <input 
                    type="month" 
                    id="monthPicker" 
                    value="<?= $selected_month ?>" 
                    class="outline-none text-gray-700 bg-transparent">
            </div>

            <!-- New Payroll Button -->
            <button 
                data-modal-open="payrollModal"
                class="bg-[#FF2D2D] hover:bg-[#e12626] text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition">

                <img src="<?php echo base_url('icons/add.png'); ?>" class="w-5 h-5" alt="Add"/>
                <span class="font-medium">New Payroll Entry</span>
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