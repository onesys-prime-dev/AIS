<div class="flex flex-col w-full min-w-0 min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex justify-between items-center my-4">

        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">
                Loan Management
            </h1>

            <p class="text-gray-500 text-sm">
                Manage employee loans here.
            </p>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center gap-3">

            <!-- LOAN SETTINGS -->
             <button
                type="button"
                onclick="openModalById('loanSettingsModal')"
                class="bg-gray-800 hover:bg-black text-white px-4 py-3 rounded-lg shadow-sm inline-flex items-center gap-2">

                <img
                    src="<?= base_url('icons/settings.png') ?>"
                    class="w-5 h-5"
                    alt="Settings">

                <span>Loan Settings</span>

            </button>

            <!-- ADD EMPLOYEE LOAN -->
            <button type="button" onclick="openModalById('addLoanModal')" class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] 
                    text-white px-4 py-3 rounded-lg shadow-sm inline-flex items-center gap-2 whitespace-nowrap h-auto w-auto shrink-0 transition">
                <div class="w-5 h-5 shrink-0 flex items-center justify-center">
                    <img src="<?= base_url('icons/add.png'); ?>" alt="Add" class="w-5 h-5 object-contain block">
                </div>
                <span class="font-medium"> New loan </span>
            </button>
        </div>
    </div>

    <?= view('components/crud_table', [
        'title' => 'Employees Loan Management',
        'tableId' => 'loanTable',
        'grid' => 'grid-cols-[1.5fr_1.2fr_1.2fr_1.2fr_1fr_1fr_1.2fr_0.8fr_1fr]',
        'columns' => [
            'Employee',
            'Loan Type',
            'Original Amount',
            'Remaining Balance',
            'Deduction',
            'Installments',
            'Next Deduction',
            'Status',
            'Action'
        ],
        'filters' => [
            [
                'key' => 'status',
                'label' => 'Status',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'active_ongoing', 'label' => 'Active Ongoing'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                ],
            ],
            [
                'key' => 'loan_category',
                'label' => 'Loan Category',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'MP2 Savings Program', 'label' => 'MP2 Savings Program'],
                    ['value' => 'SSS', 'label' => 'SSS Loans'],
                    ['value' => 'ADVANCE', 'label' => 'Advances'],
                ],
            ],
            [
                'key' => 'loan_type',
                'label' => 'Loan Type',
                'autofill' => true,
            ],
        ],
        'sortOptions' => [
            ['value' => 'employee_name', 'label' => 'Employee Name'],
            ['value' => 'loan_status', 'label' => 'Status'],
            ['value' => 'loan_type', 'label' => 'Loan Type'],
            ['value' => 'remaining_balance', 'label' => 'Remaining Balance'],
            ['value' => 'start_date', 'label' => 'Next Deduction'],
        ],
    ]) ?>

</div>
