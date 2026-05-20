<?php

namespace App\Services\Client;

use App\Models\Client\PayrollSettingsModel;
use App\Models\Client\RequestModel;

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
        // 1. Basic pay components
        $basicPay = (float)($raw['basic_pay'] ?? 0);
        $absentAmount = (float)($raw['absent_amount'] ?? 0);
        $undertimeAmount = (float)($raw['undertime_amount'] ?? 0);
        $nightDiff = (float)($raw['night_diff_pay'] ?? 0);
        $netBasic = $basicPay - $absentAmount - $undertimeAmount + $nightDiff;

        // 2. Overtime
        $regOt = (float)($raw['reg_ot_pay'] ?? 0);
        $restOt = (float)($raw['rest_ot_pay'] ?? 0);
        $holidayOt = (float)($raw['holiday_ot_pay'] ?? 0);
        $totalOvertime = $regOt + $restOt + $holidayOt;

        // 3. Other earnings
        $deMinimis = (float)($raw['de_minimis'] ?? 0);
        $otherTaxable = (float)($raw['other_taxable'] ?? 0);
        $otherNonTaxable = (float)($raw['other_non_taxable'] ?? 0);

        $totalEarnings = $netBasic + $totalOvertime + $deMinimis + $otherTaxable + $otherNonTaxable;

        // 4. Government contributions (based on monthly salary)
        $monthlySalary = (float)($raw['monthly_salary'] ?? ($basicPay * 2));
        $sss = $this->calculateSSS($monthlySalary);
        $phic = $this->calculatePhilHealth($monthlySalary);
        $hdmf = $this->calculatePagIbig($monthlySalary);

        // 5. Withholding tax (annualized based on monthly salary)
        $withholdingTax = $this->calculateWithholdingTax($monthlySalary, $totalEarnings);

        // 6. Loans & other deductions
        $sssLoan = (float)($raw['sss_loan'] ?? 0);
        $calamityLoan = (float)($raw['calamity_loan'] ?? 0);
        $salaryAdvance = (float)($raw['salary_advance'] ?? 0);
        $otherDeductions = (float)($raw['other_deductions'] ?? 0);

        $totalDeductions = $sss + $phic + $hdmf + $withholdingTax + $sssLoan + $calamityLoan + $salaryAdvance + $otherDeductions;

        $netPay = $totalEarnings - $totalDeductions;

        // 7. 13th month (if enabled) – prorated
        $thirteenMonth = 0;
        if ($this->settings->getSetting('thirteenth_month_enabled') == 1) {
            $rate = $this->settings->getSetting('thirteenth_month_rate', '1/12');
            // Evaluate fraction like "1/12"
            if (strpos($rate, '/') !== false) {
                list($num, $den) = explode('/', $rate);
                $factor = (float)$num / (float)$den;
            } else {
                $factor = (float)$rate;
            }
            $thirteenMonth = $totalEarnings * $factor;
        }

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
            'sss_loan' => round($sssLoan, 2),
            'calamity_loan' => round($calamityLoan, 2),
            'salary_advance' => round($salaryAdvance, 2),
            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
            'thirteenth_month' => round($thirteenMonth, 2),
        ];
    }

    /**
     * Apply approved leave and overtime requests to raw data
     */
    public function applyRequests(int $employeeId, string $start, string $end, array &$rawData): void
    {
        // Leaves (absent days)
        $leaves = $this->requestModel
            ->where('requested_by', $employeeId)
            ->where('status', 'approved')
            ->where('request_type', 'leave')
            ->where('start_date >=', $start)
            ->where('end_date <=', $end)
            ->findAll();

        $totalAbsentDays = 0;
        foreach ($leaves as $leave) {
            $totalAbsentDays += $leave['duration'];
        }
        $dailyRate = (float)($rawData['daily_rate'] ?? 0);
        $rawData['absent_days'] = ($rawData['absent_days'] ?? 0) + $totalAbsentDays;
        $rawData['absent_amount'] = $rawData['absent_days'] * $dailyRate;

        // Overtime requests
        $otRequests = $this->requestModel
            ->where('requested_by', $employeeId)
            ->where('status', 'approved')
            ->where('request_type', 'overtime')
            ->where('start_date >=', $start)
            ->where('end_date <=', $end)
            ->findAll();

        $hourlyRate = (float)($rawData['hourly_rate'] ?? 0);
        $regRate = (float)$this->settings->getSetting('ot_regular_rate', 1.25);
        $restRate = (float)$this->settings->getSetting('ot_rest_rate', 1.30);
        $holidayRate = (float)$this->settings->getSetting('ot_holiday_rate', 2.00);

        foreach ($otRequests as $ot) {
            $hours = $ot['duration'];
            // Determine day type – simplified: you can enhance with holiday check
            $isHoliday = false; // implement holiday table logic
            $isRestDay = (date('N', strtotime($ot['start_date'])) == 7); // Sunday
            if ($isHoliday) {
                $rawData['holiday_ot_pay'] = ($rawData['holiday_ot_pay'] ?? 0) + ($hours * $hourlyRate * $holidayRate);
                $rawData['holiday_ot_hours'] = ($rawData['holiday_ot_hours'] ?? 0) + $hours;
            } elseif ($isRestDay) {
                $rawData['rest_ot_pay'] = ($rawData['rest_ot_pay'] ?? 0) + ($hours * $hourlyRate * $restRate);
                $rawData['rest_ot_hours'] = ($rawData['rest_ot_hours'] ?? 0) + $hours;
            } else {
                $rawData['reg_ot_pay'] = ($rawData['reg_ot_pay'] ?? 0) + ($hours * $hourlyRate * $regRate);
                $rawData['reg_ot_hours'] = ($rawData['reg_ot_hours'] ?? 0) + $hours;
            }
        }
    }

    // ========== Contribution Calculators ==========
    protected function calculateSSS(float $monthlySalary): float
    {
        $sssJson = $this->settings->getSetting('sss_table');
        $brackets = json_decode($sssJson, true);
        if (!is_array($brackets)) {
            return 0;
        }
        foreach ($brackets as $bracket) {
            if ($monthlySalary >= $bracket['range_start'] && $monthlySalary <= $bracket['range_end']) {
                return (float)$bracket['ee'];
            }
        }
        return 0;
    }

    protected function calculatePhilHealth(float $monthlySalary): float
    {
        $rate = (float)$this->settings->getSetting('phic_rate', 0.03);
        $max = (float)$this->settings->getSetting('phic_max', 1200);
        $contribution = $monthlySalary * $rate;
        return min($contribution, $max);
    }

    protected function calculatePagIbig(float $monthlySalary): float
    {
        $rate = (float)$this->settings->getSetting('hdmf_rate', 0.02);
        $max = (float)$this->settings->getSetting('hdmf_max', 100);
        $contribution = $monthlySalary * $rate;
        return min($contribution, $max);
    }

    protected function calculateWithholdingTax(float $monthlySalary, float $periodTaxableIncome): float
    {
        // Annualize monthly salary (or use actual annual income)
        $annualTaxable = $monthlySalary * 12;
        $taxTable = [
            ['min' => 0, 'max' => 250000, 'fixed' => 0, 'percent' => 0],
            ['min' => 250000, 'max' => 400000, 'fixed' => 0, 'percent' => 0.20],
            ['min' => 400000, 'max' => 800000, 'fixed' => 30000, 'percent' => 0.25],
            ['min' => 800000, 'max' => 2000000, 'fixed' => 130000, 'percent' => 0.30],
            ['min' => 2000000, 'max' => 8000000, 'fixed' => 490000, 'percent' => 0.32],
            ['min' => 8000000, 'max' => null, 'fixed' => 2410000, 'percent' => 0.35],
        ];
        $annualTax = 0;
        foreach ($taxTable as $bracket) {
            if ($annualTaxable > $bracket['min']) {
                $excess = min($annualTaxable, $bracket['max'] ?? PHP_INT_MAX) - $bracket['min'];
                $annualTax = $bracket['fixed'] + ($excess * $bracket['percent']);
            }
        }
        $monthlyTax = $annualTax / 12;
        // For semi-monthly payroll, divide by 2 if the period is half-month
        // Here we assume $periodTaxableIncome is the semi-monthly taxable, but we use monthly tax/2
        return max(0, $monthlyTax / 2);
    }
}