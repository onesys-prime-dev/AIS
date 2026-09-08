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

    <!-- STATUS -->
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

    <!-- ADMIN NOTES -->
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">
            Remarks
        </label>

        <textarea
            name="remarks"
            id="edit_remarks"
            rows="3"
            placeholder="Add remarks, restructuring notes, cancellation reasons, etc."
            class="w-full border rounded-lg px-3 py-2 text-sm resize-none"></textarea>
    </div>

    <!-- AUDIT INFO -->
    <div
        id="loanAuditSection"
        class="hidden border rounded-lg bg-gray-50 p-4">

        <h3 class="text-sm font-semibold text-gray-700 mb-3">
            Audit Information
        </h3>

        <div class="grid grid-cols-2 gap-4 text-xs">

            <div>
                <p class="text-gray-400 mb-1">
                    Last Updated By
                </p>

                <p
                    id="audit_updated_by"
                    class="font-medium text-gray-700">
                    —
                </p>
            </div>

            <div>
                <p class="text-gray-400 mb-1">
                    Last Updated At
                </p>

                <p
                    id="audit_updated_at"
                    class="font-medium text-gray-700">
                    —
                </p>
            </div>

            <div>
                <p class="text-gray-400 mb-1">
                    Completed By
                </p>

                <p
                    id="audit_completed_by"
                    class="font-medium text-gray-700">
                    —
                </p>
            </div>

            <div>
                <p class="text-gray-400 mb-1">
                    Completed At
                </p>

                <p
                    id="audit_completed_at"
                    class="font-medium text-gray-700">
                    —
                </p>
            </div>

        </div>

    </div>

    <!-- LOCKED NOTICE -->
    <div
        id="loanLockedNotice"
        class="hidden bg-red-50 border border-red-200 rounded-lg p-3">

        <p class="text-xs font-semibold text-red-700 mb-1">
            Loan Record Locked
        </p>

        <p class="text-xs text-red-600">
            Completed or cancelled loans can no longer be modified.
        </p>

    </div>

    <!-- ACTIONS -->
    <div class="flex justify-between items-center pt-4 border-t">

        <!-- LEFT -->
        <div class="flex gap-2">

            <!-- COMPLETE -->
            <button
                type="button"
                id="completeLoanBtn"
                class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium transition">

                Complete Loan

            </button>

            <!-- CANCEL -->
            <button
                type="button"
                id="cancelLoanBtn"
                class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition">

                Cancel Loan

            </button>

        </div>

        <!-- RIGHT -->
        <button
            type="submit"
            id="saveLoanChangesBtn"
            class="px-4 py-2 rounded-lg bg-[#A891FC] hover:bg-[#8D72F6] text-white text-sm font-medium transition">

            Save Loan Changes

        </button>

    </div>

</form>

<script>

    /*
    |--------------------------------------------------------------------------
    | BUTTON REFERENCES
    |--------------------------------------------------------------------------
    */

    const completeLoanBtn =
        document.getElementById('completeLoanBtn');

    const cancelLoanBtn =
        document.getElementById('cancelLoanBtn');

    const saveLoanChangesBtn =
        document.getElementById('saveLoanChangesBtn');

    const startDateField =
        document.getElementById('edit_start_date');

    const loanLockedNotice =
        document.getElementById('loanLockedNotice');

    /*
    |--------------------------------------------------------------------------
    | APPLY LOAN STATUS RULES
    |--------------------------------------------------------------------------
    */

    function applyLoanStatusRules(status)
    {
        status = (status || '').toLowerCase();

        const isLocked =
            status === 'completed' ||
            status === 'cancelled';

        /*
        |--------------------------------------------------------------------------
        | LOCK FIELDS
        |--------------------------------------------------------------------------
        */

        startDateField.disabled = isLocked;

        if (isLocked)
        {
            startDateField.classList.add(
                'bg-gray-100',
                'cursor-not-allowed'
            );

            loanLockedNotice.classList.remove('hidden');

            saveLoanChangesBtn.classList.add(
                'hidden'
            );
        }
        else
        {
            startDateField.classList.remove(
                'bg-gray-100',
                'cursor-not-allowed'
            );

            loanLockedNotice.classList.add('hidden');

            saveLoanChangesBtn.classList.remove(
                'hidden'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BUTTON VISIBILITY
        |--------------------------------------------------------------------------
        */

        if (status === 'active')
        {
            completeLoanBtn.classList.remove('hidden');
            cancelLoanBtn.classList.remove('hidden');
        }
        else
        {
            completeLoanBtn.classList.add('hidden');
            cancelLoanBtn.classList.add('hidden');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOADING STATE
    |--------------------------------------------------------------------------
    */

    function setButtonLoading(button, text)
    {
        button.disabled = true;

        button.classList.add(
            'opacity-60',
            'cursor-not-allowed'
        );

        button.dataset.originalText = button.innerHTML;

        button.innerHTML = text;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE LOAN
    |--------------------------------------------------------------------------
    */

    completeLoanBtn.addEventListener('click', function ()
    {
        const loanId =
            document.getElementById('edit_loan_id').value;

        if (!loanId) return;

        const confirmed = confirm(
            'Mark this loan as completed?'
        );

        if (!confirmed) return;

        setButtonLoading(
            completeLoanBtn,
            'Completing...'
        );

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

    cancelLoanBtn.addEventListener('click', function ()
    {
        const loanId =
            document.getElementById('edit_loan_id').value;

        if (!loanId) return;

        const confirmed = confirm(
            'Cancel this loan? Pending schedules will also be cancelled.'
        );

        if (!confirmed) return;

        setButtonLoading(
            cancelLoanBtn,
            'Cancelling...'
        );

        const form = document.createElement('form');

        form.method = 'POST';

        form.action =
            `<?= site_url('client_portal/loans/cancel') ?>/${loanId}`;

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
    | SAVE CHANGES LOADING
    |--------------------------------------------------------------------------
    */

    document
    .getElementById('editLoanForm')
    .addEventListener('submit', function ()
    {
        setButtonLoading(
            saveLoanChangesBtn,
            'Saving...'
        );
    });

</script>