<?php

namespace App\Services\Client;

use App\Models\Client\LoanModel;
use App\Models\Client\LoanScheduleModel;
use App\Models\Client\LoanPaymentModel;
use App\Models\Client\EmployeePositionModel;
use App\Models\Client\LoanSettingModel;
use App\Models\Client\PayrollSettingsModel;

class LoanService
{
    protected LoanModel $loanModel;

    protected LoanScheduleModel $scheduleModel;

    protected LoanPaymentModel $paymentModel;

    protected LoanSettingModel $loanSettingModel;

    protected PayrollSettingsModel $payrollSettingsModel;

    public function __construct()
    {
        $this->loanModel     = new LoanModel();
        $this->scheduleModel = new LoanScheduleModel();
        $this->paymentModel  = new LoanPaymentModel();
        $this->loanSettingModel = new LoanSettingModel();
        $this->payrollSettingsModel = new PayrollSettingsModel();
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE NEXT PAYROLL CUTOFF END DATE
    |--------------------------------------------------------------------------
    |
    | Loan deductions must land on an actual payroll cutoff, not an arbitrary
    | day count. Given a target date, returns the end date (Y-m-d) of the
    | payroll cutoff period that date falls under — using the same
    | half1_end_day/half2_end_day (or first_half_cutoff) settings PayrollController
    | uses to build the Payroll Status period dates. If $monthlyOnly is true,
    | only month-end cutoffs (the 2nd half / full-month end) are considered,
    | which is what a 'Monthly' deduction_frequency loan should land on.
    |
    | Because cutoff periods are contiguous, the smallest cutoff end date that
    | is >= $from IS the end of whichever period $from falls in — so this one
    | method covers both "snap this date to its cutoff" and "find the next
    | upcoming cutoff" (pass today's date for the latter).
    */

    public function resolveCutoffEndDate(\DateTime $from, bool $monthlyOnly = false): string
    {
        $h1ED = (int) ($this->payrollSettingsModel->getSetting('half1_end_day')    ?? 0);
        $h1EO = (int) ($this->payrollSettingsModel->getSetting('half1_end_offset') ?? 0);
        $h2ED = (int) ($this->payrollSettingsModel->getSetting('half2_end_day')    ?? 0);
        $h2EO = (int) ($this->payrollSettingsModel->getSetting('half2_end_offset') ?? 0);
        $crossMonth = ($h1ED > 0 && $h2ED > 0);

        $cutoff = (int) ($this->payrollSettingsModel->getSetting('first_half_cutoff') ?? 15);

        $fromStr = $from->format('Y-m-d');
        $candidates = [];

        // Scan the target month plus the next two — enough headroom for any
        // offset configuration to produce a candidate >= $from.
        for ($step = 0; $step <= 2; $step++) {
            $rawM = (int) $from->format('n') + $step;
            $y    = (int) $from->format('Y') + (int) floor(($rawM - 1) / 12);
            $m    = (int) ((($rawM - 1) % 12 + 12) % 12 + 1);

            if ($crossMonth) {
                if (!$monthlyOnly) {
                    $candidates[] = $this->buildPeriodDate($y, $m, $h1ED, $h1EO);
                }
                $candidates[] = $this->buildPeriodDate($y, $m, $h2ED, $h2EO);
            } else {
                $lastDay = (int) date('t', mktime(0, 0, 0, $m, 1, $y));
                if (!$monthlyOnly) {
                    $candidates[] = sprintf('%04d-%02d-%02d', $y, $m, min($cutoff, $lastDay));
                }
                $candidates[] = sprintf('%04d-%02d-%02d', $y, $m, $lastDay);
            }
        }

        $candidates = array_values(array_filter(
            $candidates,
            fn($d) => $d >= $fromStr
        ));

        sort($candidates);

        return $candidates[0] ?? $fromStr;
    }

    /**
     * PHP mirror of PayrollController::buildPeriodDate() — resolves a
     * (salary-year, salary-month, day, monthOffset) tuple to YYYY-MM-DD.
     * day=0 means "last day of the target month".
     */
    private function buildPeriodDate(int $y, int $m, int $day, int $offset): string
    {
        $rawM    = $m + $offset;
        $tY      = $y + (int) floor(($rawM - 1) / 12);
        $tM      = (int) ((($rawM - 1) % 12 + 12) % 12 + 1);
        $lastDay = (int) date('t', mktime(0, 0, 0, $tM, 1, $tY));
        $d       = ($day === 0) ? $lastDay : min($day, $lastDay);
        return sprintf('%04d-%02d-%02d', $tY, $tM, $d);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE LOAN
    |--------------------------------------------------------------------------
    */

    public function createLoan(array $data): int
    {
        /*
        |--------------------------------------------------------------------------
        | GET EMPLOYEE SALARY
        |--------------------------------------------------------------------------
        */
        $employeePositionModel = new EmployeePositionModel();

        $employeePosition = $employeePositionModel
            ->where(
                'employee_id',
                $data['loan_employee_id']
            )
            ->first();
        if (!$employeePosition) {
            throw new \Exception(
                'Employee salary information not found.'
            );
        }
        $monthlySalary =
            (float) ($employeePosition['monthly_salary'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | DETERMINE MAX LOAN
        |--------------------------------------------------------------------------
        */

        $loanType = $data['loan_type'];
        $maxLoan = 0;

        /*
        |--------------------------------------------------------------------------
        | GET LOAN SETTINGS
        |--------------------------------------------------------------------------
        */

        $loanSetting = $this->loanSettingModel
            ->where('loan_type', $loanType)
            ->where('is_active', 1)
            ->first();

        if (!$loanSetting) {

            throw new \Exception(
                'Loan settings not configured for ' .
                $loanType
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MAX LOAN
        |--------------------------------------------------------------------------
        */

        $maxLoan =
            $monthlySalary *
            (float) $loanSetting['max_salary_multiplier'];

        /*
        |--------------------------------------------------------------------------
        | VALIDATE MAX LOAN
        |--------------------------------------------------------------------------
        */

        $principal =
            (float) $data['principal_amount'];
        
        $installments =
            (int) $data['installment_count'];

        if ($installments <= 0) {

            throw new \Exception(
                'Invalid installment count.'
            );
        }

        if (
            $installments >
            (int) $loanSetting['max_installments']
        ) {

            throw new \Exception(
                'Installment count exceeds maximum allowed.'
            );
        }

        if ($principal <= 0) {
            throw new \Exception(
                'Principal amount must be greater than 0.'
            );
        }

        if ($principal > $maxLoan) {

            throw new \Exception(

                'Loan amount exceeds maximum allowed amount of ₱' .

                number_format($maxLoan, 2)

            );
        }

        /*
        |--------------------------------------------------------------------------
        | CONTINUE SAVING LOAN
        |--------------------------------------------------------------------------
        */

        if (!(bool) $loanSetting['allow_multiple_active_loans']) {

            $existingLoan = $this->loanModel 
                ->where(
                    'employee_id',
                    $data['loan_employee_id']
                )
                ->where(
                    'loan_type',
                    $data['loan_type']
                )
                ->where(
                    'loan_status',
                    'active'
                )
                ->first();

            if ($existingLoan) {

                throw new \Exception(
                    'Employee already has an active ' .
                    $data['loan_type']
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AUTO APPLY SETTINGS
        |--------------------------------------------------------------------------
        */

        $data['interest_rate'] =
            $loanSetting['interest_rate'];

        $data['deduction_frequency'] =
            $loanSetting['deduction_frequency'];

        /*
        |--------------------------------------------------------------------------
        | CALCULATE FINANCIALS
        |--------------------------------------------------------------------------
        */

        $interestRate =
            (float) $data['interest_rate'];

        $principal =
            (float) $data['principal_amount'];

        $totalInterest =
            $principal * ($interestRate / 100);

        $totalPayable =
            $principal + $totalInterest;

        $deductionPerPayroll =
            $totalPayable / $installments;

        $data['total_interest'] =
            round($totalInterest, 2);

        $data['total_payable'] =
            round($totalPayable, 2);

        $data['deduction_per_payroll'] =
            round($deductionPerPayroll, 2);

        $data['remaining_balance'] =
            round($totalPayable, 2);

        /*
        |--------------------------------------------------------------------------
        | VALIDATE CUSTOM (UNEVEN) INSTALLMENT AMOUNTS, IF PROVIDED
        |--------------------------------------------------------------------------
        |
        | e.g. a ₱10,000 loan over 3 cutoffs doesn't have to be split evenly —
        | it can be entered as 2000/5000/3000 as long as they add up to the
        | loan's total_payable. One amount per installment_count, rounded to
        | centavos; a centavo of rounding tolerance covers float drift.
        */

        if (!empty($data['custom_amounts'])) {
            $customAmounts = array_map(
                static fn($v) => round((float) $v, 2),
                $data['custom_amounts']
            );

            if (count($customAmounts) !== $installments) {
                throw new \Exception(
                    'Custom schedule must have exactly ' . $installments . ' amount(s), one per installment.'
                );
            }

            foreach ($customAmounts as $customAmount) {
                if ($customAmount <= 0) {
                    throw new \Exception('Each custom installment amount must be greater than 0.');
                }
            }

            $customTotal = round(array_sum($customAmounts), 2);
            if (abs($customTotal - $data['total_payable']) > 0.01) {
                throw new \Exception(
                    'Custom installment amounts must add up to the total payable of ₱' .
                    number_format($data['total_payable'], 2) .
                    ' (entered: ₱' . number_format($customTotal, 2) . ').'
                );
            }

            $data['custom_amounts'] = $customAmounts;
        }

        /*
        |--------------------------------------------------------------------------
        | SNAP START DATE TO ITS ACTUAL PAYROLL CUTOFF
        |--------------------------------------------------------------------------
        |
        | Whether the caller chose "next cutoff" or a custom date, the first
        | deduction must land on a real payroll cutoff end date (per
        | payroll_settings), not the raw date picked.
        */

        $monthlyOnly = $data['deduction_frequency'] === 'Monthly';

        $data['start_date'] = $this->resolveCutoffEndDate(
            new \DateTime($data['start_date']),
            $monthlyOnly
        );

        /*
        |--------------------------------------------------------------------------
        | ESTIMATED END DATE
        |--------------------------------------------------------------------------
        |
        | Walk forward through real cutoff dates (same logic used per-installment
        | in generateLoanSchedule()) rather than naive +15 days / +1 month math.
        */

        $endDate = new \DateTime($data['start_date']);

        for ($n = 1; $n < $installments; $n++) {
            $next = (clone $endDate)->modify('+1 day');
            $endDate = new \DateTime(
                $this->resolveCutoffEndDate($next, $monthlyOnly)
            );
        }

        $data['estimated_end_date'] =
            $endDate->format('Y-m-d');

        $db = \Config\Database::connect();
        $db->transBegin();

        $loanId = $this->loanModel->insert([
            'employee_id'            => $data['loan_employee_id'],
            'loan_type'              => $data['loan_type'],
            'loan_category'          => $data['loan_category'],
            'principal_amount'       => $data['principal_amount'],
            'interest_rate'          => $data['interest_rate'],
            'total_interest'         => $data['total_interest'],
            'total_payable'          => $data['total_payable'],
            'deduction_per_payroll'  => $data['deduction_per_payroll'],
            'deduction_frequency'    => $data['deduction_frequency'],
            'installment_count'      => $data['installment_count'],
            'remaining_balance'      => $data['remaining_balance'],
            'start_date'             => $data['start_date'],
            'estimated_end_date'     => $data['estimated_end_date'],
            'loan_status'            => $data['loan_status'] ?? 'active',
            'approved_by'            => auth()->id(),
            'approved_at'            => date('Y-m-d H:i:s'),
            'created_by'             => auth()->id(),
        ], true);

        if (!$loanId) {
            $db->transRollback();
            throw new \Exception(
                'Failed to create loan: ' .
                implode(', ', $this->loanModel->errors() ?: ['Unknown error.'])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Amortization Schedule
        |--------------------------------------------------------------------------
        */

        try {
            $this->generateLoanSchedule(
                $loanId,
                $data
            );
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            throw new \Exception(
                'Failed to create loan.'
            );
        }

        $db->transCommit();

        return (int) $loanId;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE LOAN SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function generateLoanSchedule(
        int $loanId,
        array $loan
    ): void
    {
        $balance = (float) $loan['total_payable'];

        // Uneven/custom schedule: an admin can set per-installment amounts
        // that don't have to be equal (e.g. ₱10,000 over 3 cutoffs as
        // 2000/5000/3000) as long as they sum to total_payable — validated
        // by the caller (createLoan()) before this ever runs. Falls back to
        // the even split when not provided, so every existing caller is
        // unaffected.
        $customAmounts = array_map('floatval', $loan['custom_amounts'] ?? []);
        $useCustom = !empty($customAmounts);

        $amount = (float) $loan['deduction_per_payroll'];

        $date = new \DateTime(
            $loan['start_date']
        );

        for (
            $i = 1;
            $i <= (int) $loan['installment_count'];
            $i++
        ) {
            $thisAmount = $useCustom ? $customAmounts[$i - 1] : $amount;
            $balance -= $thisAmount;
            $this->scheduleModel->insert([
                'loan_id' => $loanId,

                'installment_no' => $i,

                'due_date' => $date->format('Y-m-d'),

                'amount_due' => $thisAmount,

                'remaining_balance' => max(
                    round($balance, 2),
                    0
                ),

                'schedule_status' => 'pending',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Next Due Date — the next real payroll cutoff, not a naive
            | +15 days / +1 month offset, so every installment lands exactly
            | on a payroll_settings cutoff end date.
            |--------------------------------------------------------------------------
            */

            if (
                $loan['deduction_frequency']
                === 'Monthly'
            ) {

                $next = (clone $date)->modify('+1 day');
                $date = new \DateTime($this->resolveCutoffEndDate($next, true));

            } else {

                $next = (clone $date)->modify('+1 day');
                $date = new \DateTime($this->resolveCutoffEndDate($next, false));
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE LOAN DEDUCTION
    |--------------------------------------------------------------------------
    */

    /* public function getEmployeeLoanDeduction( int $employeeId, string $periodStart, string $periodEnd): float
    {
        $schedule = $this->scheduleModel
            ->select('employee_loan_schedule.*')
            ->join(
                'employee_loans',
                'employee_loans.id = employee_loan_schedule.loan_id'
            )
            ->where('employee_loans.employee_id', $employeeId)
            ->where('employee_loans.loan_status', 'active')
            ->where('employee_loan_schedule.schedule_status', 'pending')
            ->where(
                'employee_loan_schedule.due_date >=',
                $periodStart
            )
            ->where(
                'employee_loan_schedule.due_date <=',
                $periodEnd
            )
            ->orderBy(
                'employee_loan_schedule.installment_no',
                'ASC'
            )

            ->first();

        if (!$schedule) {
            return 0;
        }

        return (float) $schedule['amount_due'];
    } */

    /*
    |--------------------------------------------------------------------------
    | MARK INSTALLMENT AS PAID
    |--------------------------------------------------------------------------
    */
    public function markInstallmentPaid(int $employeeId, string $periodStart, string $periodEnd, ?int $payrollId = null): void
    {

        $schedules = $this->scheduleModel

            ->select('employee_loan_schedule.*')

            ->join(
                'employee_loans',
                'employee_loans.id = employee_loan_schedule.loan_id'
            )

            ->where('employee_loans.employee_id', $employeeId)

            ->whereIn('employee_loans.loan_status', ['active', 'active_ongoing'])

            ->where('employee_loan_schedule.schedule_status', 'pending')

            ->where(
                'employee_loan_schedule.due_date >=',
                $periodStart
            )

            ->where(
                'employee_loan_schedule.due_date <=',
                $periodEnd
            )

            ->orderBy(
                'employee_loan_schedule.installment_no',
                'ASC'
            )

            ->findAll();

        if (empty($schedules)) {
            return;
        }

        foreach ($schedules as $schedule) {

            /*
            |--------------------------------------------------------------------------
            | Mark Schedule Paid
            |--------------------------------------------------------------------------
            */

            $this->scheduleModel->update(
                $schedule['id'],
                [

                    'schedule_status' => 'paid',

                    'paid_at' => date('Y-m-d H:i:s'),

                    'payroll_id' => $payrollId,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Save Payment Record
            |--------------------------------------------------------------------------
            */

            $loan = $this->loanModel->find(
                $schedule['loan_id']
            );

            if (!$loan) {
                continue;
            }

            $this->paymentModel->insert([

                'loan_id' => $schedule['loan_id'],

                'schedule_id' => $schedule['id'],

                'employee_id' => $employeeId,

                'payroll_id' => $payrollId,

                'payment_amount' =>
                    $schedule['amount_due'],

                'payment_method' =>
                    'Payroll Deduction',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Remaining Balance
            |--------------------------------------------------------------------------
            */

            $newBalance = max(
                (
                    (float) $loan['remaining_balance']
                    - (float) $schedule['amount_due']
                ),
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Close Loan If Fully Paid
            |--------------------------------------------------------------------------
            */

            // Preserve whatever status the loan is already in (e.g.
            // 'active_ongoing', just set by markLoansActiveOngoing() before
            // this ran) — only a fully-paid-off loan should change status
            // here, to 'completed'. Hardcoding 'active' would silently
            // undo the active_ongoing lock on every installment.
            $status =
                $newBalance <= 0
                ? 'completed'
                : $loan['loan_status'];

            $this->loanModel->update(
                $loan['id'],
                [

                    'remaining_balance' =>
                        round($newBalance, 2),

                    'loan_status' => $status,
                ]
            );
        }
    }

    public function getLoanPayments(int $loanId): array
    {
        return $this->paymentModel
            ->where('loan_id', $loanId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function updateLoan(int $loanId, array $data): bool
    {
        $loan = $this->loanModel->find($loanId);

        if (!$loan) {
            throw new \Exception('Loan not found.');
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent editing locked loans
        |--------------------------------------------------------------------------
        |
        | active_ongoing means this cutoff's deduction has already been saved
        | against the loan — same as completed/cancelled, it's locked from
        | further edits until the cycle moves on.
        */

        if (
            in_array(
                $loan['loan_status'],
                ['completed', 'cancelled', 'active_ongoing']
            )
        ) {
            throw new \Exception(
                'Cannot edit a completed, cancelled, or currently-locked (active ongoing) loan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Snap start date to its actual payroll cutoff — same rule as createLoan().
        |--------------------------------------------------------------------------
        */

        $monthlyOnly = $loan['deduction_frequency'] === 'Monthly';

        $startDate = $this->resolveCutoffEndDate(
            new \DateTime($data['start_date']),
            $monthlyOnly
        );

        /*
        |--------------------------------------------------------------------------
        | Shift the schedule's due dates to match the new start date.
        |--------------------------------------------------------------------------
        |
        | A loan is only editable while it's 'active' (see the lock check
        | above) — active_ongoing/completed/cancelled are all blocked — so
        | every schedule row here is still 'pending'; nothing has been paid
        | yet. Re-walk the same cutoff sequence generateLoanSchedule() uses,
        | starting from the new start date, and re-point each installment's
        | due_date at it.
        */

        $db = \Config\Database::connect();
        $db->transBegin();

        $scheduleRows = $this->scheduleModel
            ->where('loan_id', $loanId)
            ->where('schedule_status', 'pending')
            ->orderBy('installment_no', 'ASC')
            ->findAll();

        $dueDate = new \DateTime($startDate);
        $estimatedEndDate = $startDate;

        foreach ($scheduleRows as $row) {
            $this->scheduleModel->update($row['id'], [
                'due_date' => $dueDate->format('Y-m-d'),
            ]);

            $estimatedEndDate = $dueDate->format('Y-m-d');

            $next = (clone $dueDate)->modify('+1 day');
            $dueDate = new \DateTime($this->resolveCutoffEndDate($next, $monthlyOnly));
        }

        /*
        |--------------------------------------------------------------------------
        | Update Loan
        |--------------------------------------------------------------------------
        */

        $updated = $this->loanModel->update(
            $loanId,
            [
                'start_date' =>
                    $startDate,

                'estimated_end_date' =>
                    $estimatedEndDate,

                'remarks' =>
                    $data['remarks'] ?? null,

                'updated_by' =>
                    auth()->id(),
            ]
        );

        if ($db->transStatus() === false) {
            $db->transRollback();
            throw new \Exception('Failed to update loan schedule.');
        }

        $db->transCommit();

        return $updated;
    }

    public function cancelLoan(int $loanId): bool
    {
        $this->scheduleModel
            ->where('loan_id', $loanId)
            ->where('schedule_status', 'pending')
            ->set([
                'schedule_status' => 'cancelled'
            ])
            ->update();

       return $this->loanModel->update(
            $loanId,
            [
                'loan_status' => 'cancelled',

                'cancelled_at' => date('Y-m-d H:i:s'),

                'cancelled_by' => auth()->id(),
            ]
        );
    }

    public function completeLoan(int $loanId): bool
    {
        $this->scheduleModel
            ->where('loan_id', $loanId)
            ->where('schedule_status', 'pending')
            ->set([
                'schedule_status' => 'completed'
            ])
            ->update();

        return $this->loanModel->update(
            $loanId,
            [
                'loan_status' => 'completed',

                'remaining_balance' => 0,

                'completed_at' => date('Y-m-d H:i:s'),

                'completed_by' => auth()->id(),
            ]
        );
    }

    public function getEmployeeLoanBreakdown(
        int $employeeId,
        string $periodStart,
        string $periodEnd
    ): array
    {
        $schedules = $this->scheduleModel
            ->select('
                employee_loan_schedule.id AS schedule_id,
                employee_loan_schedule.loan_id,
                employee_loan_schedule.amount_due,
                employee_loan_schedule.due_date,
                employee_loan_schedule.remaining_balance,
                employee_loans.loan_category,
                employee_loans.loan_type
            ')
            ->join(
                'employee_loans',
                'employee_loans.id = employee_loan_schedule.loan_id'
            )
            ->where('employee_loans.employee_id', $employeeId)
            ->whereIn('employee_loans.loan_status', ['active', 'active_ongoing'])
            ->where('employee_loan_schedule.schedule_status', 'pending')
            ->where('employee_loan_schedule.due_date >=', $periodStart)
            ->where('employee_loan_schedule.due_date <=', $periodEnd)
            ->findAll();

        $breakdown = [];

        foreach ($schedules as $row) {
            $loanCategory = trim($row['loan_category'] ?? '');
            $loanType     = trim($row['loan_type'] ?? 'Loan');
            $amount        = (float) ($row['amount_due'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $existingIndex = null;

            foreach ($breakdown as $index => $loan) {
                if (
                    strtolower($loan['loan_type']) === strtolower($loanType)
                    &&
                    strtolower($loan['loan_category']) === strtolower($loanCategory)
                ) {
                    $existingIndex = $index;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $breakdown[$existingIndex]['amount'] += $amount;
            } else {
                $breakdown[] = [
                    'schedule_id' => (int) ($row['schedule_id'] ?? 0),
                    'loan_id' => (int) ($row['loan_id'] ?? 0),
                    'loan_category' => $loanCategory,
                    'loan_type' => $loanType,
                    'amount' => $amount,
                    'due_date' => $row['due_date'],
                    'remaining_balance' => (float) ($row['remaining_balance'] ?? 0),
                ];
            }
        }

        return $breakdown;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ONE PENDING INSTALLMENT'S AMOUNT (UNEVEN SCHEDULE EDITING)
    |--------------------------------------------------------------------------
    |
    | Lets an admin adjust a still-pending installment after the loan is
    | already active — e.g. circumstances changed and this cutoff's
    | deduction needs to be lower or higher than originally scheduled.
    |
    | The final pending installment is deliberately never directly editable:
    | it always auto-absorbs whatever balance remains after every other
    | pending installment, which is what guarantees the loan resolves to
    | exactly ₱0 no matter how the earlier ones are adjusted. Already-paid
    | installments are untouched — editing history isn't allowed.
    */

    public function updateInstallmentAmount(int $loanId, int $installmentNo, float $newAmount): array
    {
        if ($newAmount <= 0) {
            throw new \Exception('Amount must be greater than 0.');
        }

        $loan = $this->loanModel->find($loanId);
        if (!$loan) {
            throw new \Exception('Loan not found.');
        }

        $rows = $this->scheduleModel
            ->where('loan_id', $loanId)
            ->orderBy('installment_no', 'ASC')
            ->findAll();

        $byNo = [];
        foreach ($rows as $row) {
            $byNo[(int) $row['installment_no']] = $row;
        }

        if (!isset($byNo[$installmentNo])) {
            throw new \Exception('Installment not found.');
        }

        $target = $byNo[$installmentNo];
        if ($target['schedule_status'] !== 'pending') {
            throw new \Exception('Only a pending (not yet paid) installment can be edited.');
        }

        $pendingNos = array_values(array_filter(
            array_keys($byNo),
            fn($no) => $byNo[$no]['schedule_status'] === 'pending'
        ));
        sort($pendingNos);
        $lastPendingNo = end($pendingNos);

        if ($installmentNo === $lastPendingNo) {
            throw new \Exception('The final pending installment auto-balances to close the loan out and can\'t be edited directly — adjust an earlier installment instead.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $runningBalance = ($installmentNo === 1)
                ? (float) $loan['total_payable']
                : (float) $byNo[$installmentNo - 1]['remaining_balance'];

            $runningBalance -= $newAmount;

            $this->scheduleModel->update($target['id'], [
                'amount_due'        => round($newAmount, 2),
                'remaining_balance' => max(round($runningBalance, 2), 0),
            ]);

            // Cascade through the remaining pending installments: amounts in
            // between stay as they were (only remaining_balance shifts to
            // reflect the edit above), while the last one is recomputed to
            // absorb whatever's left, keeping the ₱0 payoff guarantee.
            foreach ($pendingNos as $no) {
                if ($no <= $installmentNo) {
                    continue;
                }

                if ($no === $lastPendingNo) {
                    $absorbAmount = round($runningBalance, 2);
                    if ($absorbAmount <= 0) {
                        throw new \Exception('This change leaves nothing for the final installment — reduce an earlier amount instead.');
                    }
                    $this->scheduleModel->update($byNo[$no]['id'], [
                        'amount_due'        => $absorbAmount,
                        'remaining_balance' => 0,
                    ]);
                    $runningBalance = 0;
                } else {
                    $runningBalance -= (float) $byNo[$no]['amount_due'];
                    $this->scheduleModel->update($byNo[$no]['id'], [
                        'remaining_balance' => max(round($runningBalance, 2), 0),
                    ]);
                }
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return $this->scheduleModel
            ->where('loan_id', $loanId)
            ->orderBy('installment_no', 'ASC')
            ->findAll();
    }
}