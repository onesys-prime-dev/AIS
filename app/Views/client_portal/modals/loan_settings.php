<style>
/* FORM */
.ls-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* LOAN TYPE CARD */
.ls-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
}

.ls-card-title {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.ls-card-subtitle {
    font-size: 11px;
    color: #6b7280;
    margin: 0 0 16px 0;
}

/* GRID */
.ls-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* FIELD */
.ls-field label {
    display: block;
    font-size: 11px;
    color: #6b7280;
    margin-bottom: 4px;
}

.ls-field input,
.ls-field select {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    box-sizing: border-box;
    outline: none;
    transition: border-color .15s;
}

.ls-field input:focus,
.ls-field select:focus {
    border-color: #A891FC;
}

/* CHECKBOX ROW */
.ls-checkbox-row {
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
}

/* SAVE ROW */
.ls-save-row {
    display: flex;
    justify-content: flex-end;
    padding-top: 8px;
}

.ls-save-btn {
    background: #A891FC;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s;
}

.ls-save-btn:hover {
    background: #8D72F6;
}
</style>

<form
    id="loanSettingsForm"
    action="<?= site_url('client_portal/loans/settings/save') ?>"
    method="POST"
    class="ls-form">

    <?= csrf_field() ?>

    <?php
    $loanTypes = [
        'Salary Loan',
        'Emergency Loan',
        'Cash Advance'
    ];
    ?>

    <?php foreach ($loanTypes as $index => $loanType): ?>

        <div class="ls-card">

            <h3 class="ls-card-title"><?= esc($loanType) ?></h3>
            <p class="ls-card-subtitle">Configure loan rules and payroll behavior.</p>

            <input type="hidden" name="loan_type[]" value="<?= esc($loanType) ?>">

            <div class="ls-grid">

                <div class="ls-field">
                    <label>Interest Rate (%)</label>
                    <input type="number" step="0.01" name="interest_rate[]" required>
                </div>

                <div class="ls-field">
                    <label>Max Salary Multiplier</label>
                    <input type="number" step="0.1" name="max_salary_multiplier[]" required>
                </div>

                <div class="ls-field">
                    <label>Max Installments</label>
                    <input type="number" name="max_installments[]" required>
                </div>

                <div class="ls-field">
                    <label>Deduction Frequency</label>
                    <select name="deduction_frequency[]">
                        <option value="Semi-Monthly">Semi-Monthly</option>
                        <option value="Monthly">Monthly</option>
                    </select>
                </div>

                <div class="ls-field">
                    <label>Grace Period (Days)</label>
                    <input type="number" name="grace_period_days[]" value="0">
                </div>

                <div class="ls-field">
                    <label>Penalty Rate (%)</label>
                    <input type="number" step="0.01" name="penalty_rate[]" value="0">
                </div>

            </div>

            <label class="ls-checkbox-row">
                <input type="checkbox" name="allow_multiple_loans[<?= $index ?>]" value="1">
                Allow Multiple Active Loans
            </label>

        </div>

    <?php endforeach; ?>

    <div class="ls-save-row">
        <button type="submit" class="ls-save-btn">
            Save Loan Settings
        </button>
    </div>

</form>