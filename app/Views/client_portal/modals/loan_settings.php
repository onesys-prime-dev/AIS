<style>
/* FORM */
.ls-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* LOAN TYPE CARD */
.ls-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
}

/* LOAN TITLE */
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

/* CHECKBOX */
.ls-checkbox-row {
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #374151;
}

/* SAVE */
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

/* CATEGORY */
.ls-category-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    padding-bottom: 6px;
    border-bottom: 2px solid #e5e7eb;
}
</style>

<form
    id="loanSettingsForm"
    action="<?= site_url('client_portal/loans/settings/save') ?>"
    method="POST"
    class="ls-form">

    <?= csrf_field() ?>

    <?php

    /*
    |--------------------------------------------------------------------------
    | LOAN CATEGORIES
    |--------------------------------------------------------------------------
    */

    $loanCategories = [

        'MP2 Savings Program' => [
            'HDMF (Pag-Ibig) Loan',
            'Multi-Purpose Loan',
            'Calamity Loan'
        ],

        'SSS' => [
            'Salary Loan',
            'Calamity Loan'
        ],

        'ADVANCE' => [
            'Salary Advance',
            'Emergency Advance',
            'Cash Advance'
        ]
    ];

    /*
    |--------------------------------------------------------------------------
    | SETTINGS MAP
    |--------------------------------------------------------------------------
    */

    $settingsMap = [];

    foreach (($settings ?? []) as $settingRow) {

        $key =
            trim($settingRow['loan_category'])
            . '|'
            . trim($settingRow['loan_type']);

        $settingsMap[$key] = $settingRow;
    }

    ?>

    <?php $globalIndex = 0; ?>

    <?php foreach ($loanCategories as $category => $loanTypes): ?>

        <div class="ls-category-title">
            <?= esc($category) ?>
        </div>

        <?php foreach ($loanTypes as $loanType): ?>

            <?php

            $settingKey =
                trim($category)
                . '|'
                . trim($loanType);

            $setting =
                $settingsMap[$settingKey] ?? [];

            ?>

            <div class="ls-card">

                <h3 class="ls-card-title">
                    <?= esc($loanType) ?>
                </h3>

                <p class="ls-card-subtitle">
                    Configure loan rules and payroll behavior.
                </p>

                <!-- CATEGORY -->
                <input
                    type="hidden"
                    name="loan_category[]"
                    value="<?= esc($category) ?>">

                <!-- TYPE -->
                <input
                    type="hidden"
                    name="loan_type[]"
                    value="<?= esc($loanType) ?>">

                <div class="ls-grid">

                    <!-- INTEREST RATE -->
                    <div class="ls-field">

                        <label>
                            Interest Rate (%)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="interest_rate[]"
                            value="<?= esc($setting['interest_rate'] ?? 0) ?>"
                            required>

                    </div>

                    <!-- MAX SALARY MULTIPLIER -->
                    <div class="ls-field">

                        <label>
                            Max Salary Multiplier
                        </label>

                        <input
                            type="number"
                            step="0.1"
                            name="max_salary_multiplier[]"
                            value="<?= esc($setting['max_salary_multiplier'] ?? 0) ?>"
                            required>

                    </div>

                    <!-- MAX INSTALLMENTS -->
                    <div class="ls-field">

                        <label>
                            Max Installments
                        </label>

                        <input
                            type="number"
                            name="max_installments[]"
                            value="<?= esc($setting['max_installments'] ?? 0) ?>"
                            required>

                    </div>

                    <!-- DEDUCTION FREQUENCY -->
                    <div class="ls-field">

                        <label>
                            Deduction Frequency
                        </label>

                        <select name="deduction_frequency[]">

                            <option
                                value="Semi-Monthly"
                                <?= ($setting['deduction_frequency'] ?? '') === 'Semi-Monthly'
                                    ? 'selected'
                                    : '' ?>>

                                Semi-Monthly

                            </option>

                            <option
                                value="Monthly"
                                <?= ($setting['deduction_frequency'] ?? '') === 'Monthly'
                                    ? 'selected'
                                    : '' ?>>

                                Monthly

                            </option>

                        </select>

                    </div>

                    <!-- GRACE PERIOD -->
                    <div class="ls-field">

                        <label>
                            Grace Period (Days)
                        </label>

                        <input
                            type="number"
                            name="grace_period_days[]"
                            value="<?= esc($setting['grace_period_days'] ?? 0) ?>">

                    </div>

                    <!-- PENALTY RATE -->
                    <div class="ls-field">

                        <label>
                            Penalty Rate (%)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="penalty_rate[]"
                            value="<?= esc($setting['penalty_rate'] ?? 0) ?>">

                    </div>

                </div>

                <!-- ALLOW MULTIPLE -->
                <label class="ls-checkbox-row">
                    <input
                        type="checkbox"
                        name="allow_multiple_loans[<?= $globalIndex ?>]"
                        value="1"
                        <?= !empty($setting['allow_multiple_active_loans'])
                            ? 'checked'
                            : '' ?>>
                    Allow Multiple Active Loans
                </label>

            </div>

            <?php $globalIndex++; ?>

        <?php endforeach; ?>

    <?php endforeach; ?>

    <div class="ls-save-row">

        <button
            type="submit"
            class="ls-save-btn">

            Save Loan Settings

        </button>

    </div>

</form>