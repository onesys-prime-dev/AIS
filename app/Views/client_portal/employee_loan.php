<div class="flex flex-col w-full min-w-0 min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex justify-between items-center my-4">

        <div class="p-1">
            <h1 class="text-3xl font-bold mb-1" style="color:var(--content-text,#1f2937);">
                Loan Management
            </h1>

            <p class="text-sm" style="color:var(--content-text-muted,#6b7280);">
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
        // Raw CSS value, not a Tailwind arbitrary class — this build's
        // public/css/main.css is precompiled (no Node/npm to rebuild it
        // here), so a grid-cols-[...] class not already in that file is a
        // silent no-op: with no grid-template-columns at all, every cell
        // stacks in one column instead of forming a row.
        //
        // Each value is minmax(0, Nfr), not a bare Nfr — a bare fr track's
        // implicit minimum is its content's own natural width, so a longer
        // value in one row (e.g. "active ongoing" vs "cancelled" in Status)
        // silently widened that row's columns independently of every other
        // row AND of this header, each one drifting out of alignment by a
        // different amount depending on its own longest cell's content.
        // minmax(0, Nfr) pins every track to its proportional share no
        // matter what's inside it. Must exactly match loanRowTemplate()'s
        // own gridTemplateColumns in employee_main.php.
        // Installments' own DATA is a bare number ("6"), but its LABEL is
        // the longest word in the header and can't wrap (no space to break
        // on) — it was overlapping "Next Deduction" next to it. Traded
        // 0.3fr from Installments (renamed "Terms", short enough to fit
        // on one line) to Status, whose longest real value ("active
        // ongoing"/"Cancelled") was getting clipped mid-word instead.
        'gridStyle' => 'minmax(0,1.5fr) minmax(0,1.2fr) minmax(0,1.2fr) minmax(0,1.2fr) minmax(0,1fr) minmax(0,0.7fr) minmax(0,1.2fr) minmax(0,1.1fr) minmax(0,1fr)',
        'columns' => [
            'Employee',
            'Loan Type',
            'Original Amount',
            'Remaining Balance',
            'Deduction',
            'Terms',
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
