<!-- STEP 1 + STEP 2 WRAPPER -->
<div id="loanStepWrapper" class="h-full flex flex-col">

    <!-- ===================================================== -->
    <!-- STEP 1 : LOAN FORM -->
    <!-- ===================================================== -->

    <div id="loanStep1">

        <form
            id="addNewloanForm"
            action="<?= site_url('client_portal/loans/addLoan') ?>"
            method="post"
            class="flex flex-col h-full min-h-full">

            <?= csrf_field() ?>

            <!-- EMPLOYEE -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Employee
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="loanEmployeeSearch"
                        placeholder="Search employee..."
                        autocomplete="off"
                        class="w-full border rounded-lg px-3 py-2"
                        required>
                    <input
                        type="hidden"
                        name="loan_employee_id"
                        id="loan_employee_id"
                        required>
                    <div
                        id="loanEmployeeDropdown"
                        class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden max-h-56 overflow-auto hide-scroll-bar">
                    </div>
                </div>
                <p id="loan_employee_error" class="text-red-500 text-xs mt-1 hidden">
                    Please select a valid employee from the list.
                </p>
            </div>

            <!-- LOAN CATEGORY -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Loan Category
                </label>
                <select
                    id="loanCategory"
                    name="loan_category"
                    class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-gray-700 text-sm"
                    required>
                    <option value="">Select Category</option>
                    <option value="MP2 Savings Program">MP2 Savings Program</option>
                    <option value="SSS">SSS Loans</option>
                    <option value="ADVANCE">Advances</option>
                </select>
            </div>

            <!-- LOAN TYPE -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Loan Type
                </label>
                <select
                    id="loanType"
                    name="loan_type"
                    class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-gray-700 text-sm"
                    required>
                    <option value="">Select Loan Type</option>
                </select>
            </div>

            <!-- PRINCIPAL -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Principal Amount
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="principal_amount"
                    id="principal_amount"
                    placeholder="Enter principal amount"
                    required
                    class="w-full border rounded-lg px-3 py-2">
            </div>

            <!-- DEDUCTION FREQUENCY -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Deduction Frequency
                </label>
                <select
                    id="deduction_frequency"
                    disabled
                    class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-gray-700 text-sm">
                    <option value="Semi-Monthly">Every Cutoff</option>
                    <option value="Monthly">Once per Month</option>
                </select>
                <input
                    type="hidden"
                    name="deduction_frequency"
                    id="deduction_frequency_hidden">
            </div>

            <!-- INSTALLMENT COUNT -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Installment Count
                </label>
                <input
                    type="number"
                    name="installment_count"
                    id="installment_count"
                    placeholder="Enter number of installments"
                    required
                    class="w-full border rounded-lg px-3 py-2">
            </div>

            <!-- START DATE -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Start Date
                </label>
                <input
                    type="date"
                    name="start_date"
                    id="start_date"
                    required
                    class="w-full border rounded-lg px-3 py-2">
            </div>

            <!-- HIDDEN COMPUTED FIELDS -->
            <input type="hidden" name="interest_rate" id="interest_rate">
            <input type="hidden" name="total_interest" id="total_interest">
            <input type="hidden" name="total_payable" id="total_payable">
            <input type="hidden" name="deduction_per_payroll" id="deduction_per_payroll">
            <input type="hidden" name="remaining_balance" id="remaining_balance">
            <input type="hidden" name="estimated_end_date" id="estimated_end_date">
            <input type="hidden" name="loan_status" value="active">

            <!-- EMPLOYEE RATE INFO -->
            <input type="hidden" id="employee_daily_rate">
            <input type="hidden" id="employee_monthly_salary">
            <input type="hidden" id="max_loan_amount">

            <!-- ACTIONS -->
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    onclick="goToLoanPreview()"
                    class="px-4 py-2 bg-[#A891FC] hover:bg-[#9277fc] text-white rounded-lg">
                    Next
                </button>
            </div>

        </form>

    </div>

    <!-- ===================================================== -->
    <!-- STEP 2 : LOAN PREVIEW -->
    <!-- ===================================================== -->

    <div id="loanStep2" class="hidden">

        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800">Loan Summary</h2>
            <p class="text-sm text-gray-500 mt-1">Review and confirm the employee loan details.</p>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="border rounded-xl p-4 bg-gray-50">
                <p class="text-xs uppercase text-gray-400 mb-1">Principal Amount</p>
                <p id="preview_principal" class="text-lg font-bold text-gray-800">₱0.00</p>
            </div>
            <div class="border rounded-xl p-4 bg-gray-50">
                <p class="text-xs uppercase text-gray-400 mb-1">Total Payable</p>
                <p id="preview_total_payable" class="text-lg font-bold text-gray-800">₱0.00</p>
            </div>
            <div class="border rounded-xl p-4 bg-gray-50">
                <p class="text-xs uppercase text-gray-400 mb-1">Interest Rate</p>
                <p id="preview_interest" class="text-lg font-bold text-gray-800">0%</p>
            </div>
            <div class="border rounded-xl p-4 bg-gray-50">
                <p class="text-xs uppercase text-gray-400 mb-1">Deduction Per Payroll</p>
                <p id="preview_deduction" class="text-lg font-bold text-gray-800">₱0.00</p>
            </div>
        </div>

        <!-- DETAILS -->
        <div class="border rounded-xl overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-3 border-b">
                <h3 class="font-semibold text-gray-700">Loan Details</h3>
            </div>
            <div class="p-5 grid grid-cols-2 gap-x-8 gap-y-5">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Employee</p>
                    <p id="preview_employee" class="text-sm font-semibold text-gray-800">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Loan Type</p>
                    <p id="preview_loan_type" class="text-sm font-semibold text-gray-800">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Installments</p>
                    <p id="preview_installments" class="text-sm font-semibold text-gray-800">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Deduction Frequency</p>
                    <p id="preview_frequency" class="text-sm font-semibold text-gray-800">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Start Date</p>
                    <p id="preview_start_date" class="text-sm font-semibold text-gray-800">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Estimated End Date</p>
                    <p id="preview_end_date" class="text-sm font-semibold text-gray-800">—</p>
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="flex justify-end gap-2">
            <button
                type="button"
                onclick="backToLoanForm()"
                class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                Back
            </button>
            <button
                type="button"
                onclick="submitLoanForm()"
                class="px-4 py-2 bg-[#A891FC] hover:bg-[#9277fc] text-white rounded-lg">
                Confirm Loan
            </button>
        </div>

    </div>
    <!-- END STEP 2 -->

</div>
<!-- END WRAPPER -->

<script>
    (async () => {

        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const form =
            document.getElementById('addNewloanForm');

        const searchInput =
            document.getElementById('loanEmployeeSearch');

        const employeeIdInput =
            document.getElementById('loan_employee_id');

        const dropdown =
            document.getElementById('loanEmployeeDropdown');

        const error =
            document.getElementById('loan_employee_error');

        if (
            !form ||
            !searchInput ||
            !employeeIdInput ||
            !dropdown ||
            !error
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | STATE
        |--------------------------------------------------------------------------
        */

        let employees = [];
        let filtered = [];
        let activeIndex = -1;

        /*
        |--------------------------------------------------------------------------
        | LOAD EMPLOYEES
        |--------------------------------------------------------------------------
        */

        async function loadEmployees()
        {
            try
            {
                const res = await fetch(
                    "<?= site_url('client_portal/loans/loanEmployees') ?>",
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    }
                );

                if (!res.ok)
                {
                    throw new Error(
                        'Failed to load employees: ' + res.status
                    );
                }

                const data = await res.json();

                employees = Array.isArray(data)
                    ? data
                    : [];

            }
            catch (err)
            {
                employees = [];

                console.error(
                    'loanEmployees error:',
                    err
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DROPDOWN HELPERS
        |--------------------------------------------------------------------------
        */

        function hideDropdown()
        {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            activeIndex = -1;
        }

        function showDropdown()
        {
            dropdown.classList.remove('hidden');
        }

        function renderDropdown(items)
        {
            if (!items.length)
            {
                hideDropdown();
                return;
            }

            dropdown.innerHTML = items.map((emp, i) => `
                <button
                    type="button"
                    data-index="${i}"
                    class="w-full text-left px-3 py-2 hover:bg-gray-100 ${i === activeIndex ? 'bg-gray-100' : ''}"
                >
                    ${emp.label || emp.name || ''}
                </button>
            `).join('');

            showDropdown();
        }

        /*
        |--------------------------------------------------------------------------
        | EXACT MATCH
        |--------------------------------------------------------------------------
        */

        function exactMatchId(value)
        {
            const typed =
                value.trim().toLowerCase();

            const hit = employees.find(e =>
                (e.name || '')
                    .trim()
                    .toLowerCase() === typed ||

                (e.label || '')
                    .trim()
                    .toLowerCase() === typed
            );

            return hit
                ? String(hit.id)
                : '';
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE LIST
        |--------------------------------------------------------------------------
        */

        function updateList()
        {
            const q =
                searchInput.value
                    .trim()
                    .toLowerCase();

            employeeIdInput.value =
                exactMatchId(searchInput.value);

            error.classList.add('hidden');

            if (!q)
            {
                hideDropdown();
                return;
            }

            filtered = employees
                .filter(e =>
                    (e.name || '')
                        .toLowerCase()
                        .includes(q)

                    ||

                    (e.label || '')
                        .toLowerCase()
                        .includes(q)
                )
                .slice(0, 8);

            activeIndex = -1;

            renderDropdown(filtered);
        }

        /*
        |--------------------------------------------------------------------------
        | SELECT EMPLOYEE
        |--------------------------------------------------------------------------
        */

        function selectEmployee(emp)
        {
            searchInput.value =
                emp.label || emp.name || '';

            employeeIdInput.value =
                String(emp.id || '');

            error.classList.add('hidden');

            hideDropdown();

            /*
            |--------------------------------------------------------------------------
            | OPTIONAL:
            | FETCH EMPLOYEE SALARY INFO
            |--------------------------------------------------------------------------
            */

            fetchEmployeeLoanInfo(emp.id);
        }

        /*
        |--------------------------------------------------------------------------
        | FETCH EMPLOYEE LOAN INFO
        |--------------------------------------------------------------------------
        */

        async function fetchEmployeeLoanInfo(employeeId)
        {
            try
            {
                const response = await fetch(
                    `<?= site_url('client_portal/loans/getEmployeeLoanInfo') ?>/${employeeId}`
                );

                if (!response.ok)
                {
                    throw new Error(
                        'Failed to fetch employee info.'
                    );
                }

                const data = await response.json();

                document.getElementById(
                    'employee_daily_rate'
                ).value = data.daily_rate || 0;

                document.getElementById(
                    'employee_monthly_salary'
                ).value = data.monthly_salary || 0;
            }
            catch (err)
            {
                console.error(
                    'Failed to fetch employee loan info:',
                    err
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INITIAL LOAD
        |--------------------------------------------------------------------------
        */

        await loadEmployees();

        /*
        |--------------------------------------------------------------------------
        | INPUT EVENTS
        |--------------------------------------------------------------------------
        */

        searchInput.addEventListener(
            'input',
            updateList
        );

        searchInput.addEventListener(
            'focus',
            () => {

                if (!searchInput.value.trim())
                {
                    hideDropdown();
                }

            }
        );

        /*
        |--------------------------------------------------------------------------
        | KEYBOARD NAVIGATION
        |--------------------------------------------------------------------------
        */

        searchInput.addEventListener(
            'keydown',
            (e) => {

                if (
                    dropdown.classList.contains('hidden') ||
                    !filtered.length
                ) {
                    return;
                }

                if (e.key === 'ArrowDown')
                {
                    e.preventDefault();

                    activeIndex =
                        (activeIndex + 1) % filtered.length;

                    renderDropdown(filtered);
                }

                else if (e.key === 'ArrowUp')
                {
                    e.preventDefault();

                    activeIndex =
                        (activeIndex - 1 + filtered.length)
                        % filtered.length;

                    renderDropdown(filtered);
                }

                else if (e.key === 'Enter')
                {
                    if (activeIndex >= 0)
                    {
                        e.preventDefault();

                        selectEmployee(
                            filtered[activeIndex]
                        );
                    }
                }

                else if (e.key === 'Escape')
                {
                    hideDropdown();
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | DROPDOWN CLICK
        |--------------------------------------------------------------------------
        */

        dropdown.addEventListener(
            'click',
            (e) => {

                const btn =
                    e.target.closest('button[data-index]');

                if (!btn)
                {
                    return;
                }

                const idx =
                    Number(btn.dataset.index);

                if (
                    !Number.isNaN(idx) &&
                    filtered[idx]
                ) {
                    selectEmployee(filtered[idx]);
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | CLICK OUTSIDE
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            (e) => {

                if (
                    !e.target.closest('#loanEmployeeSearch') &&
                    !e.target.closest('#loanEmployeeDropdown')
                ) {
                    hideDropdown();
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | FORM SUBMIT VALIDATION
        |--------------------------------------------------------------------------
        */

        form.addEventListener(
            'submit',
            (e) => {

                if (!employeeIdInput.value)
                {
                    employeeIdInput.value =
                        exactMatchId(searchInput.value);
                }

                if (!employeeIdInput.value)
                {
                    e.preventDefault();

                    error.classList.remove('hidden');

                    searchInput.focus();
                }
            }
        );


        await loadLoanSettings();

        applyLoanSettings();

    })();

    let LOAN_SETTINGS = [];

    /*
    |--------------------------------------------------------------------------
    | LOAD SETTINGS
    |--------------------------------------------------------------------------
    */

    async function loadLoanSettings()
    {
        try
        {
            const response = await fetch(
                "<?= site_url('client_portal/loans/settings') ?>"
            );

            LOAN_SETTINGS = await response.json();
        }
        catch (err)
        {
            console.error(
                'Failed to load loan settings.',
                err
            );
        }
    }

    function applyLoanSettings()
    {
        const loanTypeElement =
            document.getElementById('loanType');

        const loanCategoryElement =
            document.getElementById('loanCategory');

        if (
            !loanTypeElement ||
            !loanCategoryElement
        ) {
            return;
        }

        const loanType =
            loanTypeElement.value;

        const loanCategory =
            loanCategoryElement.value;

        const setting =
            LOAN_SETTINGS.find(s =>

                s.loan_type === loanType &&
                s.loan_category === loanCategory

            );

        if (!setting)
        {
            return;
        }

        document.getElementById(
            'deduction_frequency'
        ).value =
            setting.deduction_frequency || 'Semi-Monthly';

        document.getElementById(
            'deduction_frequency_hidden'
        ).value =
            setting.deduction_frequency || 'Semi-Monthly';

        const installmentInput =
            document.getElementById(
                'installment_count'
            );

        installmentInput.placeholder =
            `Maximum ${setting.max_installments} installments`;

        installmentInput.max =
            setting.max_installments || 1;

        installmentInput.value =
            setting.max_installments || 1;
    }

    function formatCurrency(value)
    {
        return new Intl.NumberFormat('en-PH', {

            style: 'currency',
            currency: 'PHP'

        }).format(value);
    }

    function goToLoanPreview()
    {
        const principal = parseFloat(
            document.getElementById('principal_amount').value || 0
        );

        const installments = parseInt(
            document.getElementById('installment_count').value || 1
        );

        const startDate =
            document.getElementById('start_date').value;

        

        const employee =
            document.getElementById('loanEmployeeSearch').value;

        const loanType =
            document.getElementById('loanType').value;

        const loanCategory = document.getElementById('loanCategory').value; 

        const setting = LOAN_SETTINGS.find(s => s.loan_type === loanType && s.loan_category === loanCategory );

        if (!setting)
        {
            showToast('Loan settings not configured.', 'warning');
            return;
        }

        const interestRate =
            parseFloat(setting.interest_rate || 0);

        const frequency =
            setting.deduction_frequency;

        document.getElementById(
            'deduction_frequency'
        ).value = frequency;

        document.getElementById(
            'deduction_frequency_hidden'
        ).value = frequency;

        if (
            !principal ||
            !installments ||
            !startDate ||
            !employee
        )
        {
            showToast(
                'Please complete all required fields.', 'warning'
            );

            return;
        }

        if (installments <= 0)
        {
            showToast(
                'Installment count must be greater than 0.', 'warning'
            );

            return;
        }

        if (
            installments >
            parseInt(setting.max_installments)
        )
        {
            showToast(
                `Maximum installment allowed is ${setting.max_installments}`, 'warning'
            );

            return;
        }

        const totalInterest =
            parseFloat(
                (principal * (interestRate / 100)).toFixed(2)
            );

        const totalPayable =
            parseFloat(
                (principal + totalInterest).toFixed(2)
            );

        const deductionPerPayroll =
            parseFloat(
                (totalPayable / installments).toFixed(2)
            );

        document.getElementById(
            'interest_rate'
        ).value = interestRate;

        document.getElementById(
            'total_interest'
        ).value = totalInterest;

        document.getElementById(
            'total_payable'
        ).value = totalPayable;

        document.getElementById(
            'deduction_per_payroll'
        ).value = deductionPerPayroll;

        document.getElementById(
            'remaining_balance'
        ).value = totalPayable;

        const monthlySalary = parseFloat(
            document.getElementById('employee_monthly_salary').value || 0
        );

        const maxLoan =
            monthlySalary *
            parseFloat(
                setting.max_salary_multiplier || 0
            );

        document.getElementById(
            'max_loan_amount'
        ).value = maxLoan;

        if (principal > maxLoan)
        {
            showToast(
                `Maximum allowed loan for ${loanType} is ${formatCurrency(maxLoan)}`, 'warning'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | PREVIEW VALUES
        |--------------------------------------------------------------------------
        */

        document.getElementById(
            'preview_employee'
        ).innerText = employee;

        document.getElementById(
            'preview_loan_type'
        ).innerText = loanType;

        document.getElementById(
            'preview_principal'
        ).innerText = formatCurrency(principal);

        document.getElementById(
            'preview_interest'
        ).innerText = interestRate + '%';

        document.getElementById(
            'preview_total_payable'
        ).innerText = formatCurrency(totalPayable);

        document.getElementById(
            'preview_deduction'
        ).innerText = formatCurrency(deductionPerPayroll);

        document.getElementById(
            'preview_installments'
        ).innerText = installments;

        document.getElementById(
            'preview_frequency'
        ).innerText = frequency;

        document.getElementById(
            'preview_start_date'
        ).innerText = startDate;

        /*
        |--------------------------------------------------------------------------
        | END DATE
        |--------------------------------------------------------------------------
        */

        const endDate = new Date(startDate);

        if (frequency === 'Monthly')
        {
            endDate.setMonth(
                endDate.getMonth() + installments
            );
        }
        else
        {
            endDate.setDate(
                endDate.getDate() + (installments * 15)
            );
        }

        document.getElementById(
            'preview_end_date'
        ).innerText =
            endDate.toLocaleDateString();

        document.getElementById(
            'estimated_end_date'
        ).value =
            endDate.toISOString().split('T')[0];

        /*
        |--------------------------------------------------------------------------
        | SWITCH STEP
        |--------------------------------------------------------------------------
        */

        document.getElementById('loanStep1')
            .classList.add('hidden');

        document.getElementById('loanStep2')
            .classList.remove('hidden');
    }

    function backToLoanForm()
    {
        document.getElementById('loanStep2')
            .classList.add('hidden');

        document.getElementById('loanStep1')
            .classList.remove('hidden');
    }

    function submitLoanForm()
    {
        document.getElementById(
            'addNewloanForm'
        ).submit();
    }

    const loanTypeMap = {

        'MP2 Savings Program': [
            'HDMF (Pag-Ibig) Loan',
            'Multi-Purpose Loan',
            'Calamity Loan'
        ],

        'SSS': [
            'Salary Loan',
            'Calamity Loan'
        ],

        'ADVANCE': [
            'Salary Advance',
            'Emergency Advance',
            'Cash Advance'
        ]
    };

    const loanCategorySelect =
        document.getElementById('loanCategory');

    const loanTypeSelect =
        document.getElementById('loanType');

    loanCategorySelect.addEventListener(
        'change',
        function () {

            const category = this.value;

            loanTypeSelect.innerHTML = `
                <option value="">
                    Select Loan Type
                </option>
            `;

            if (!loanTypeMap[category]) {

                loanTypeSelect.value = '';

                document.getElementById(
                    'installment_count'
                ).placeholder = '';

                return;
            }
            
            loanTypeMap[category].forEach(type => {

                const option =
                    document.createElement('option');

                option.value = type;
                option.textContent = type;

                loanTypeSelect.appendChild(option);

            });

            /*
            |--------------------------------------------------------------------------
            | RESET SETTINGS
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                'installment_count'
            ).value = '';

            document.getElementById(
                'deduction_frequency'
            ).value = 'Semi-Monthly';

            document.getElementById(
                'deduction_frequency_hidden'
            ).value = 'Semi-Monthly';

            applyLoanSettings();
        }
    );

    loanTypeSelect.addEventListener(
        'change',
        applyLoanSettings
    );

</script>