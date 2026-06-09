<form
    id="editLoanForm"
    action="<?= site_url('client_portal/loans/update') ?>"
    method="POST"
    class="space-y-4">

    <?= csrf_field() ?>

    <input
        type="hidden"
        name="loan_id"
        id="edit_loan_id">

    <!-- EMPLOYEE -->
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
            Employee
        </label>

        <input
            type="text"
            id="edit_loan_employee"
            class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-sm"
            readonly>
    </div>

    <!-- LOAN TYPE -->
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
            Loan Type
        </label>

        <input
            type="text"
            id="edit_loan_type"
            class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-sm"
            readonly>
    </div>

    <!-- START DATE -->
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
            Start Date
        </label>

        <input
            type="date"
            name="start_date"
            id="edit_start_date"
            class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>

    <!-- INSTALLMENTS -->
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
            Installment Count
        </label>

        <input
            type="number"
            name="installment_count"
            id="edit_installment_count"
            min="1"
            max="60"
            class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-sm cursor-not-allowed"
            readonly>
    </div>

    <!-- DEDUCTION -->
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
            Deduction Per Payroll
        </label>

        <input
            type="number"
            step="0.01"
            name="deduction_per_payroll"
            id="edit_deduction_per_payroll"
            class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-sm cursor-not-allowed"
            readonly>
    </div>

    <!-- STATUS DISPLAY -->
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
            Loan Status
        </label>

        <input
            type="text"
            id="edit_loan_status_display"
            class="w-full border rounded-lg px-3 py-2 bg-gray-100 text-sm capitalize"
            readonly>
    </div>

    <!-- INFO BOX -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">

        <p class="text-xs text-yellow-800 font-medium mb-0.5">
            Loan Schedule Protection
        </p>

        <p class="text-xs text-yellow-700 leading-relaxed">
            Installment count and deduction amount are locked because
            changing them may invalidate existing payroll deductions
            and amortization schedules.
        </p>

    </div>

    <!-- ACTIONS -->
    <div class="flex justify-between items-center pt-4 border-t">

        <!-- DANGER ZONE -->
        <div class="flex gap-2">

            <!-- COMPLETE -->
            <button
                type="button"
                id="completeLoanBtn"
                class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium">

                Complete Loan
            </button>

            <!-- CANCEL -->
            <button
                type="button"
                id="cancelLoanBtn"
                class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium">

                Cancel Loan
            </button>

        </div>

        <!-- SAVE -->
        <button
            type="submit"
            class="px-4 py-2 rounded-lg bg-[#A891FC] hover:bg-[#8D72F6] text-white text-sm font-medium">

            Save Changes
        </button>

    </div>

</form>

<script>

    /*
    |--------------------------------------------------------------------------
    | COMPLETE LOAN
    |--------------------------------------------------------------------------
    */

    document
    .getElementById('completeLoanBtn')
    .addEventListener('click', function () {

        const loanId =
            document.getElementById('edit_loan_id').value;

        if (!loanId) return;

        const confirmed = confirm(
            'Mark this loan as completed?'
        );

        if (!confirmed) return;

        const form = document.createElement('form');

        form.method = 'POST';

        form.action =
            `<?= site_url('client_portal/loans/complete') ?>/${loanId}`;

        form.innerHTML = `
            <input
                type="hidden"
                name="<?= csrf_token() ?>"
                value="<?= csrf_hash() ?>">
        `;

        document.body.appendChild(form);

        form.submit();

    });

    /*
    |--------------------------------------------------------------------------
    | CANCEL LOAN
    |--------------------------------------------------------------------------
    */

    document
    .getElementById('cancelLoanBtn')
    .addEventListener('click', function () {

        const loanId =
            document.getElementById('edit_loan_id').value;

        if (!loanId) return;

        const confirmed = confirm(
            'Cancel this loan? Pending schedules will also be cancelled.'
        );

        if (!confirmed) return;

        const form = document.createElement('form');

        form.method = 'POST';

        form.action =
            `<?= site_url('client_portal/loans/close') ?>/${loanId}`;

        form.innerHTML = `
            <input
                type="hidden"
                name="<?= csrf_token() ?>"
                value="<?= csrf_hash() ?>">
        `;

        document.body.appendChild(form);

        form.submit();

    });

</script>
