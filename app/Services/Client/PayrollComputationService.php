<?php

namespace App\Services\Client;

use App\Models\Client\PayrollSettingsModel;
use App\Models\Client\RequestModel;
use App\Models\Client\PayrollTransactionModel;
use App\Services\Client\LoanService;

class PayrollComputationService
{
    protected $settings;
    protected $requestModel;

    public function __construct()
    {
        $this->settings = new PayrollSettingsModel();
        $this->requestModel = new RequestModel();
    }

    /**
     * Compute payroll from raw sheet data (POST from UI)
     */
    public function computeFromRawSheet(array $raw, int $employeeId, string $periodStart, string $periodEnd): array
    {
        $loanService = new LoanService();

        // 1. Basic pay components
        $basicPay        = (float)($raw['basic_pay'] ?? 0);
        $absentAmount    = (float)($raw['absent_amount'] ?? 0);
        $undertimeAmount = (float)($raw['undertime_amount'] ?? 0);
        $nightDiff       = (float)($raw['night_diff_pay'] ?? 0);

        $netBasic = $basicPay - $absentAmount - $undertimeAmount + $nightDiff;

        // 2. Overtime
        $regOt      = (float)($raw['reg_ot_pay'] ?? 0);
        $restOt     = (float)($raw['rest_ot_pay'] ?? 0);
        $holidayOt  = (float)($raw['holiday_ot_pay'] ?? 0);

        $totalOvertime = $regOt + $restOt + $holidayOt;

        // 3. Other earnings
        $deMinimis       = (float)($raw['de_minimis'] ?? 0);
        $otherTaxable    = (float)($raw['other_taxable'] ?? 0);
        $otherNonTaxable = (float)($raw['other_non_taxable'] ?? 0);

        $totalEarnings =
            $netBasic +
            $totalOvertime +
            $deMinimis +
            $otherTaxable +
            $otherNonTaxable;

        // 4. Government contributions
        $monthlySalary = (float)($raw['monthly_salary'] ?? ($basicPay * 2));

        $sss  = $this->calculateSSS($monthlySalary);
        $phic = $this->calculatePhilHealth($monthlySalary);
        $hdmf = $this->calculatePagIbig($monthlySalary);

        // 5. Withholding tax — 1st-half method (Excel payroll register):
        //    Apply semi-monthly brackets directly to gross taxable (no contribution deduction).
        //    For 2nd half cumulative computation, the JS UI handles prior-period adjustment.
        $currentTaxable = $netBasic + $totalOvertime + $otherTaxable;
        $withholdingTax = $this->calculateSemiMonthlyWithholdingTax($currentTaxable);

        // 6. Loans & deductions
        $otherDeductions = (float)($raw['other_deductions'] ?? 0);

        $loanDeductions = $loanService->getEmployeeLoanBreakdown(
            $employeeId,
            $periodStart,
            $periodEnd
        );

        $totalLoanDeductions = 0;

        foreach ($loanDeductions as $loan) {
            $totalLoanDeductions += (float)($loan['amount'] ?? 0);
        }
        // Final deductions
        $totalDeductions =
            $sss +
            $phic +
            $hdmf +
            $withholdingTax +
            $totalLoanDeductions +
            $otherDeductions;


        $netPay = $totalEarnings - $totalDeductions;

        return [
            'employee_id' => $employeeId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,

            'basic_pay' => round($basicPay, 2),
            'absent_amount' => round($absentAmount, 2),
            'undertime_amount' => round($undertimeAmount, 2),
            'night_diff_pay' => round($nightDiff, 2),

            'reg_ot_pay' => round($regOt, 2),
            'rest_ot_pay' => round($restOt, 2),
            'holiday_ot_pay' => round($holidayOt, 2),
            'overtime_pay' => round($totalOvertime, 2),

            'de_minimis' => round($deMinimis, 2),
            'other_earnings_taxable' => round($otherTaxable, 2),
            'other_earnings_non_taxable' => round($otherNonTaxable, 2),

            'total_earnings' => round($totalEarnings, 2),

            'sss_ee' => round($sss, 2),
            'phic_ee' => round($phic, 2),
            'hdmf_ee' => round($hdmf, 2),
            'withholding_tax' => round($withholdingTax, 2),

            'loan_deductions' => $loanDeductions,
            'total_loan_deductions' => round($totalLoanDeductions, 2),

            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => round($totalDeductions, 2),

            'net_pay' => round($netPay, 2),
        ];
    }

    /**
     * Compute payroll using attendance summary + rates
     */
    public function computePayroll(
        int $employeeId,
        string $start,
        string $end,
        array $adjustments
    ): array
    {
        $loanService = new LoanService();
        $basicPay        = (float)($adjustments['basic_pay']                   ?? 0);
        $nightDiff       = (float)($adjustments['night_diff_pay']              ?? 0);
        $overtime        = (float)($adjustments['overtime_pay']                ?? 0);
        $deMinimis       = (float)($adjustments['de_minimis']                  ?? 0);
        $otherTaxable    = (float)($adjustments['other_earnings_taxable']      ?? 0);
        $otherNonTaxable = (float)($adjustments['other_earnings_non_taxable']  ?? 0);

        $totalEarnings =
            $basicPay +
            $nightDiff +
            $overtime +
            $deMinimis +
            $otherTaxable +
            $otherNonTaxable;

        // =========================================================
        // GOVERNMENT CONTRIBUTIONS
        // =========================================================

        // Use monthly salary from employee management as the base for contributions.
        // The payload may include it if sent by the client; fall back to employee_positions.
        $monthlySalary = (float)($adjustments['monthly_salary'] ?? 0);
        if ($monthlySalary <= 0) {
            $db     = \Config\Database::connect();
            $posRow = $db->table('employee_positions')
                ->select('monthly_salary')
                ->where('employee_id', $employeeId)
                ->get()->getRowArray();
            $monthlySalary = (float)($posRow['monthly_salary'] ?? 0);
        }
        if ($monthlySalary <= 0) {
            $monthlySalary = $basicPay * 2; // last-resort approximation
        }
        $sss = $this->calculateSSS($monthlySalary);
        $phic = $this->calculatePhilHealth($monthlySalary);
        $hdmf = $this->calculatePagIbig($monthlySalary);

        // Withholding tax — 1st-half method (Excel payroll register):
        //    Apply semi-monthly brackets directly to gross taxable (no contribution deduction).
        $currentTaxable = $basicPay + $nightDiff + $overtime + $otherTaxable;
        $withholdingTax = $this->calculateSemiMonthlyWithholdingTax($currentTaxable);

        // =========================================================
        // LOAN DEDUCTIONS
        // =========================================================

        $otherDeductions =
            (float)($adjustments['other_deductions'] ?? 0);

        $rawLoanDeductions =
            $loanService->getEmployeeLoanBreakdown(
                $employeeId,
                $start,
                $end
            );

        /*
        |------------------------------------------------------------
        | SANITIZE LOAN ARRAY
        |------------------------------------------------------------
        |
        | Prevent malformed numeric arrays / objects
        | from breaking CI4 insert bindings.
        |
        */

        $loanDeductions = [];

        foreach ($rawLoanDeductions as $loan) {

            $loanDeductions[] = [

                'loan_id' =>
                    (int)($loan['loan_id'] ?? 0),

                'schedule_id' =>
                    (int)($loan['schedule_id'] ?? 0),

                'loan_name' =>
                    (string)($loan['loan_name'] ?? 'Loan'),

                'amount' =>
                    round(
                        (float)($loan['amount'] ?? 0),
                        2
                    ),

                'due_date' =>
                    (string)($loan['due_date'] ?? ''),

                'remaining_balance' =>
                    round(
                        (float)($loan['remaining_balance'] ?? 0),
                        2
                    ),
            ];
        }

        $totalLoanDeductions =
            array_sum(
                array_map(
                    fn($loan) =>
                        (float)($loan['amount'] ?? 0),
                    $loanDeductions
                )
            );

        // =========================================================
        // TOTAL DEDUCTIONS
        // =========================================================

        $totalDeductions =
            $sss +
            $phic +
            $hdmf +
            $withholdingTax +
            $totalLoanDeductions +
            $otherDeductions;

        // =========================================================
        // NET PAY
        // =========================================================

        $netPay =
            $totalEarnings -
            $totalDeductions;

        // =========================================================
        // RETURN PAYROLL DATA
        // =========================================================

        return [

            'employee_id' =>
                $employeeId,

            'period_start' =>
                $start,

            'period_end' =>
                $end,

            // -----------------------------------------------------
            // Earnings
            // -----------------------------------------------------

            'basic_pay' =>
                round($basicPay, 2),

            'night_diff_pay' =>
                round($nightDiff, 2),

            'overtime_pay' =>
                round($overtime, 2),

            'de_minimis' =>
                round($deMinimis, 2),

            'other_earnings_taxable' =>
                round($otherTaxable, 2),

            'other_earnings_non_taxable' =>
                round($otherNonTaxable, 2),

            'total_earnings' =>
                round($totalEarnings, 2),

            // -----------------------------------------------------
            // Deductions
            // -----------------------------------------------------

            'sss_ee' =>
                round($sss, 2),

            'phic_ee' =>
                round($phic, 2),

            'hdmf_ee' =>
                round($hdmf, 2),

            'withholding_tax' =>
                round($withholdingTax, 2),

            // -----------------------------------------------------
            // Loans
            // -----------------------------------------------------

            'loan_deductions' =>
                $loanDeductions,

            'total_loan_deductions' =>
                round($totalLoanDeductions, 2),

            // -----------------------------------------------------
            // Other
            // -----------------------------------------------------

            'other_deductions' =>
                round($otherDeductions, 2),

            'total_deductions' =>
                round($totalDeductions, 2),

            'net_pay' =>
                round($netPay, 2),
        ];
    }

    // ========== Contribution Calculators using DB tables ==========

    protected function calculateSSS(float $monthlySalary): float
    {
        // Only match brackets belonging to the currently active year.
        // range_end NULL means the top open-ended bracket (no upper limit).
        $db      = \Config\Database::connect();
        $bracket = $db->table('sss_brackets')
            ->where('is_active', 1)
            ->where('range_start <=', $monthlySalary)
            ->groupStart()
                ->where('range_end >=', $monthlySalary)
                ->orWhere('range_end IS NULL')
            ->groupEnd()
            ->orderBy('range_start', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$bracket) return 0;
        return (float)(($bracket['ee_regular'] ?? 0) + ($bracket['ee_wisp'] ?? 0));
    }

    protected function calculatePhilHealth(float $monthlySalary): float
    {
        // Rate and income cap come from payroll_settings so admin can update
        // without touching code. 2025 PhilHealth: 5% on salary up to ₱100,000/mo.
        $rate = (float)($this->settings->getSetting('phic_rate') ?? 0.05);
        $cap  = (float)($this->settings->getSetting('phic_cap')  ?? 100000);

        $cappedSalary  = min($monthlySalary, $cap);
        $employeeShare = ($cappedSalary * $rate) / 2;   // total premium split 50/50

        return round($employeeShare, 2);
    }

    protected function calculatePagIbig(float $monthlySalary): float
    {
        // 2% of salary, capped at ₱100 employee share — both configurable.
        $rate = (float)($this->settings->getSetting('hdmf_rate') ?? 0.02);
        $max  = (float)($this->settings->getSetting('hdmf_max')  ?? 100);

        return min(round($monthlySalary * $rate, 2), $max);
    }

    protected function calculateWithholdingTax(float $monthlyTaxableIncome): float
    {
        // Use the active year's brackets only.
        $db      = \Config\Database::connect();
        $bracket = $db->table('wtax_brackets_monthly')
            ->where('is_active', 1)
            ->where('min_amount <=', $monthlyTaxableIncome)
            ->groupStart()
                ->where('max_amount >=', $monthlyTaxableIncome)
                ->orWhere('max_amount IS NULL')
            ->groupEnd()
            ->orderBy('min_amount', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$bracket) return 0;
        $excess = $monthlyTaxableIncome - (float)$bracket['min_amount'];
        $tax    = (float)$bracket['fixed_tax'] + ($excess * (float)$bracket['percent_over']);
        return max(0, round($tax, 2));
    }

    /**
     * BIR Revised WT Table (Jan 1 2023) — official semi-monthly brackets.
     * Applied per-period to semi-monthly taxable compensation directly.
     */
    protected function calculateSemiMonthlyWithholdingTax(float $semiMonthlyTaxable): float
    {
        if ($semiMonthlyTaxable <= 10417)  return 0.0;
        if ($semiMonthlyTaxable <= 16667)  return round(($semiMonthlyTaxable - 10417)  * 0.15, 2);
        if ($semiMonthlyTaxable <= 33333)  return round(937.50  + ($semiMonthlyTaxable - 16667) * 0.20, 2);
        if ($semiMonthlyTaxable <= 83333)  return round(4270.70 + ($semiMonthlyTaxable - 33333) * 0.25, 2);
        if ($semiMonthlyTaxable <= 333333) return round(16770.70 + ($semiMonthlyTaxable - 83333) * 0.30, 2);
        return round(91770.00 + ($semiMonthlyTaxable - 333333) * 0.35, 2);
    }

    // ========== Helpers for previous cut‑off data ==========

    /**
     * Resolve the [start, end] date strings for the cut-off period immediately
     * before $currentStart.
     *
     * Semi-monthly cut-offs:
     *   1st–15th  → previous cut-off was 16th–last-day of the prior month
     *   16th–31st → previous cut-off was 1st–15th of the same month
     *
     * The original code mutated a single DateTime object for both $prevStart and
     * $prevEnd, so by the time $prevEnd was formatted the object had already been
     * moved, producing wrong dates.  We use immutable clones instead.
     */
    private function resolvePreviousCutoff(string $currentStart): array
    {
        $dt  = new \DateTime($currentStart);
        $day = (int)$dt->format('d');

        if ($day <= 15) {
            // Current period is 1st–15th → previous is 16th–EOM of last month
            $prevMonth     = (clone $dt)->modify('first day of last month');
            $lastDayOfPrev = (int)(clone $prevMonth)->modify('last day of this month')->format('d');
            $y = $prevMonth->format('Y');
            $m = $prevMonth->format('m');
            $prevStart = "{$y}-{$m}-16";
            $prevEnd   = "{$y}-{$m}-{$lastDayOfPrev}";
        } else {
            // Current period is 16th–EOM → previous is 1st–15th of same month
            $y = $dt->format('Y');
            $m = $dt->format('m');
            $prevStart = "{$y}-{$m}-01";
            $prevEnd   = "{$y}-{$m}-15";
        }

        return [$prevStart, $prevEnd];
    }

    private function getPreviousCutoffTaxable(int $employeeId, string $currentStart): float
    {
        [$prevStart, $prevEnd] = $this->resolvePreviousCutoff($currentStart);

        $prevTx = (new PayrollTransactionModel())
            ->where('employee_id', $employeeId)
            ->where('period_start', $prevStart)
            ->where('period_end',   $prevEnd)
            ->first();

        if (!$prevTx) return 0;

        return (float)(
            ($prevTx['basic_pay']               ?? 0) +
            ($prevTx['night_diff_pay']           ?? 0) +
            ($prevTx['overtime_pay']             ?? 0) +
            ($prevTx['other_earnings_taxable']   ?? 0)
        );
    }

    private function getPreviousWtaxPaid(int $employeeId, string $currentStart): float
    {
        [$prevStart, $prevEnd] = $this->resolvePreviousCutoff($currentStart);

        $prevTx = (new PayrollTransactionModel())
            ->where('employee_id', $employeeId)
            ->where('period_start', $prevStart)
            ->where('period_end',   $prevEnd)
            ->first();

        return $prevTx ? (float)($prevTx['withholding_tax'] ?? 0) : 0;
    }

    /**
     * Annual withholding tax using the active monthly brackets scaled ×12.
     * Brackets are fetched from DB and matched in PHP to avoid CI4 query-builder
     * backtick-escaping arithmetic expressions (e.g. `min_amount * 12`).
     */
    public function calculateAnnualWithholdingTax(float $annualTaxableIncome): float
    {
        $db       = \Config\Database::connect();
        $brackets = $db->table('wtax_brackets_monthly')
            ->where('is_active', 1)
            ->orderBy('min_amount', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($brackets as $bracket) {
            $annualMin = (float)$bracket['min_amount'] * 12;
            $annualMax = $bracket['max_amount'] !== null
                ? (float)$bracket['max_amount'] * 12
                : null;

            if ($annualTaxableIncome >= $annualMin &&
                ($annualMax === null || $annualTaxableIncome <= $annualMax)
            ) {
                $annualFixed = (float)$bracket['fixed_tax'] * 12;
                $excess      = $annualTaxableIncome - $annualMin;
                return max(0, round($annualFixed + ($excess * (float)$bracket['percent_over']), 2));
            }
        }

        return 0;
    }

    /**
     * Apply approved leave and overtime requests to raw data (kept for compatibility)
     */
    public function applyRequests(int $employeeId, string $start, string $end, array &$rawData): void
    {
        // Implementation optional
    }
}