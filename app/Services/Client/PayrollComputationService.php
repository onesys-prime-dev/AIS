<?php

namespace App\Services\Client;

use App\Models\Client\PayrollSettingsModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\AttendanceModel;
use App\Models\Client\PayrollModel;
use DateTime;
use DateInterval;
use DatePeriod;
use Exception;

class PayrollComputationService
{
    protected $settings;
    protected $attendanceModel;
    protected $payrollModel;

    public function __construct()
    {
        $this->settings = new PayrollSettingsModel();
        $this->attendanceModel = new AttendanceModel();
        $this->payrollModel = new PayrollModel();
    }

    /**
     * Main payroll computation for an employee for a given period
     * @param int $employeeId
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @param array $manualOverrides Optional manual values for adjustments (de minimis, loans, etc.)
     * @return array Complete payroll breakdown
     * @throws Exception
     */
    public function computePayroll($employeeId, $startDate, $endDate, $manualOverrides = [])
    {
        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->find($employeeId);
        if (!$employee) throw new Exception("Employee not found");

        // Get daily rate from payroll table for this month
        $periodMonth = date('Y-m', strtotime($startDate));
        $payrollRecord = $this->payrollModel
            ->where('employee_id', $employeeId)
            ->where('month_year', $periodMonth)
            ->first();
        if (!$payrollRecord || $payrollRecord['daily_rate'] <= 0) {
            throw new Exception("Daily rate not set for $periodMonth. Please create payroll entry first.");
        }
        $dailyRate = (float) $payrollRecord['daily_rate'];

        // Working days in the period (Mon-Fri)
        $workingDaysInPeriod = $this->getWorkingDaysInPeriod($startDate, $endDate);
        $monthlySalary = $dailyRate * $workingDaysInPeriod;
        $hourlyRate = $monthlySalary / max($this->settings->getSetting('monthly_hours'), 1);

        // Fetch attendance records
        $attendance = $this->attendanceModel
            ->where('user_id', $employee['user_id'])
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->findAll();

        // --- Basic Pay & Absences ---
        $presentDays = $this->countPresentDays($attendance);
        $absentDays = max(0, $workingDaysInPeriod - $presentDays);
        $absentAmount = $absentDays * $dailyRate;

        // --- Overtime (Regular, Rest day, Holiday) ---
        $otRegularAmount = 0;
        $otRestAmount = 0;
        $otHolidayAmount = 0;
        $otRateRegular = (float) $this->settings->getSetting('ot_regular_rate', 1.25);
        $otRateRest = (float) $this->settings->getSetting('ot_rest_rate', 1.30);
        $otRateHoliday = (float) $this->settings->getSetting('ot_holiday_rate', 2.00);
        foreach ($attendance as $day) {
            $totalHours = (float)($day['total_hours'] ?? 0);
            if ($totalHours <= 8) continue;
            $otHours = $totalHours - 8;
            $isSunday = (date('N', strtotime($day['date'])) == 7);
            $isHoliday = $this->isHoliday($day['date']); // stub – implement with holiday table if needed
            if ($isHoliday) {
                $otHolidayAmount += $otHours * $hourlyRate * $otRateHoliday;
            } elseif ($isSunday) {
                $otRestAmount += $otHours * $hourlyRate * $otRateRest;
            } else {
                $otRegularAmount += $otHours * $hourlyRate * $otRateRegular;
            }
        }

        // --- Night Differential (10pm-6am) ---
        $ndStart = $this->settings->getSetting('night_diff_start', '22:00:00');
        $ndEnd = $this->settings->getSetting('night_diff_end', '06:00:00');
        $ndRate = (float) $this->settings->getSetting('night_diff_rate', 0.10);
        $nightDiffAmount = $this->computeNightDifferential($attendance, $hourlyRate, $ndStart, $ndEnd, $ndRate);

        // --- Undertime (based on recorded total_hours < 8) ---
        $undertimeHours = 0;
        foreach ($attendance as $day) {
            $totalHours = (float)($day['total_hours'] ?? 0);
            if ($totalHours > 0 && $totalHours < 8) {
                $undertimeHours += (8 - $totalHours);
            }
        }
        $undertimeAmount = $undertimeHours * $hourlyRate;

        // --- Holiday Pay (if any) ---
        $holidayPayAmount = 0; // can be computed via separate function if needed

        // --- Basic Pay adjustments ---
        $basicPay = $presentDays * $dailyRate;
        $netBasicPay = $basicPay - $absentAmount - $undertimeAmount + $nightDiffAmount;

        // --- Other earnings (from manual overrides) ---
        $deMinimis = $manualOverrides['de_minimis'] ?? 0;
        $otherTaxable = $manualOverrides['other_taxable'] ?? 0;
        $otherNonTaxable = $manualOverrides['other_non_taxable'] ?? 0;

        $totalOvertime = $otRegularAmount + $otRestAmount + $otHolidayAmount;
        $totalEarnings = $netBasicPay + $totalOvertime + $holidayPayAmount + $deMinimis + $otherTaxable + $otherNonTaxable;

        // --- Government contributions ---
        $sss = $this->computeSSS($monthlySalary);
        $phic = $this->computePhilHealth($monthlySalary);
        $hdmf = $this->computePagIbig($monthlySalary);

        // --- Withholding Tax (semi-monthly) ---
        $taxableIncome = $netBasicPay + $totalOvertime + $holidayPayAmount + $otherTaxable;
        $withholdingTax = $this->computeWithholdingTax($taxableIncome, $monthlySalary);

        // --- Loans & Advances (from manual overrides) ---
        $sssLoan = $manualOverrides['sss_loan'] ?? 0;
        $calamityLoan = $manualOverrides['calamity_loan'] ?? 0;
        $salaryAdvance = $manualOverrides['salary_advance'] ?? 0;
        $calamityAdvance = $manualOverrides['calamity_advance'] ?? 0;
        $otherDeductions = $manualOverrides['other_deductions'] ?? 0;

        $totalDeductions = $sss + $phic + $hdmf + $withholdingTax + $sssLoan + $calamityLoan + $salaryAdvance + $calamityAdvance + $otherDeductions;
        $netPay = $totalEarnings - $totalDeductions;

        // Return full array with all breakdown fields (matches Excel raw sheet)
        return [
            'employee_id' => $employeeId,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'basic_pay' => round($basicPay, 2),
            'daily_rate' => round($dailyRate, 2),
            'monthly_salary' => round($monthlySalary, 2),
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'absent_amount' => round($absentAmount, 2),
            'undertime_hours' => round($undertimeHours, 2),
            'undertime_amount' => round($undertimeAmount, 2),
            'night_diff_pay' => round($nightDiffAmount, 2),
            'ot_regular_pay' => round($otRegularAmount, 2),
            'ot_rest_pay' => round($otRestAmount, 2),
            'ot_holiday_pay' => round($otHolidayAmount, 2),
            'overtime_pay' => round($totalOvertime, 2),
            'holiday_pay' => round($holidayPayAmount, 2),
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
            'calamity_advance' => round($calamityAdvance, 2),
            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
            'net_basic_pay' => round($netBasicPay, 2),
            'rate_per_hour' => round($hourlyRate, 2),
            'hours_worked' => round($presentDays * 8, 2) // approximate
        ];
    }

    protected function getWorkingDaysInPeriod($start, $end)
    {
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $endDate->modify('+1 day');
        $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);
        $workingDays = 0;
        foreach ($period as $date) {
            if ($date->format('N') < 6) $workingDays++;
        }
        return $workingDays;
    }

    protected function countPresentDays($attendance)
    {
        $dates = array_unique(array_column($attendance, 'date'));
        $present = 0;
        foreach ($dates as $date) {
            foreach ($attendance as $rec) {
                if ($rec['date'] == $date && !empty($rec['time_in']) && !empty($rec['time_out']) && (float)($rec['total_hours'] ?? 0) >= 4) {
                    $present++;
                    break;
                }
            }
        }
        return $present;
    }

    protected function computeNightDifferential($attendance, $hourlyRate, $ndStart, $ndEnd, $ndRate)
    {
        $totalNdHours = 0;
        foreach ($attendance as $day) {
            if (empty($day['time_in']) || empty($day['time_out'])) continue;
            $timeIn = new DateTime($day['date'] . ' ' . $day['time_in']);
            $timeOut = new DateTime($day['date'] . ' ' . $day['time_out']);
            if ($timeOut < $timeIn) $timeOut->modify('+1 day');

            $startNd = new DateTime($day['date'] . ' ' . $ndStart);
            $endNd = new DateTime($day['date'] . ' ' . $ndEnd);
            if ($endNd <= $startNd) $endNd->modify('+1 day');

            $overlapStart = max($timeIn, $startNd);
            $overlapEnd = min($timeOut, $endNd);
            if ($overlapStart < $overlapEnd) {
                $diff = $overlapStart->diff($overlapEnd);
                $hours = $diff->h + $diff->i / 60;
                $totalNdHours += $hours;
            }
        }
        return $totalNdHours * $hourlyRate * $ndRate;
    }

    protected function isHoliday($date)
    {
        // Stub: implement with your holiday table logic
        return false;
    }

    protected function computeSSS($monthlySalary)
    {
        $sssTable = json_decode($this->settings->getSetting('sss_table'), true);
        if (empty($sssTable)) {
            $sssTable = [
                ['range' => '0-3250', 'ee' => 135],
                ['range' => '3250.01-3750', 'ee' => 157.5],
                ['range' => '29750.01-30250', 'ee' => 1350],
            ];
        }
        foreach ($sssTable as $bracket) {
            $parts = explode('-', $bracket['range']);
            $min = floatval($parts[0]);
            $max = isset($parts[1]) ? floatval($parts[1]) : PHP_FLOAT_MAX;
            if ($monthlySalary >= $min && $monthlySalary <= $max) {
                return $bracket['ee'];
            }
        }
        return 0;
    }

    protected function computePhilHealth($monthlySalary)
    {
        $rate = $this->settings->getSetting('phic_rate');
        return min($monthlySalary * $rate, 1200);
    }

    protected function computePagIbig($monthlySalary)
    {
        $rate = $this->settings->getSetting('hdmf_rate');
        return min($monthlySalary * $rate, 100);
    }

    protected function computeWithholdingTax($periodTaxableIncome, $monthlySalary)
    {
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
        return max(0, $monthlyTax);
    }
}