<?php

namespace App\Services\Client;

use App\Models\AttendanceModel;
use App\Models\Client\PayrollSettingsModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\PayrollModel;
use DateTime;
use DateInterval;
use DatePeriod;

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

    public function computePayroll($employeeId, $startDate, $endDate, $adjustments = [])
    {
        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->find($employeeId);
        if (!$employee) throw new \Exception("Employee not found");

        $periodMonth = date('Y-m', strtotime($startDate));
        $payrollRecord = $this->payrollModel
            ->where('employee_id', $employeeId)
            ->where('month_year', $periodMonth)
            ->first();

        if (!$payrollRecord) {
            throw new \Exception("No payroll record found for employee ID $employeeId in month $periodMonth. Please create a payroll entry first in Payroll Status tab.");
        }

        $dailyRate = floatval($payrollRecord['daily_rate']);
        if ($dailyRate <= 0) {
            throw new \Exception("Daily rate is not set or is zero for employee ID $employeeId in month $periodMonth. Please set daily rate in Payroll Status.");
        }

        $workingDaysInMonth = $this->getWorkingDaysInPeriod($startDate, $endDate);
        $monthlySalary = $dailyRate * $workingDaysInMonth;
        $hourlyRate = $monthlySalary / max($this->settings->getSetting('monthly_hours'), 1);

        $attendance = $this->attendanceModel
            ->where('user_id', $employee['user_id'])
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->findAll();

        $presentDays = $this->countPresentDays($attendance);
        $basicPay = $presentDays * $dailyRate;

        $otPay = $this->computeOvertimePay($attendance, $hourlyRate);
        $nightDiffPay = $this->computeNightDifferential($attendance, $hourlyRate);
        $holidayPay = 0;

        $deMinimis = $adjustments['de_minimis'] ?? 0;
        $otherTaxable = $adjustments['other_taxable'] ?? 0;
        $otherNonTaxable = $adjustments['other_non_taxable'] ?? 0;

        $totalEarnings = $basicPay + $otPay + $nightDiffPay + $holidayPay + $deMinimis + $otherTaxable + $otherNonTaxable;

        $sss = $this->computeSSS($monthlySalary);
        $phic = $this->computePhilHealth($monthlySalary);
        $hdmf = $this->computePagIbig($monthlySalary);

        $taxableIncome = $basicPay + $otPay + $nightDiffPay + $holidayPay + $otherTaxable;
        $withholdingTax = $this->computeWithholdingTax($taxableIncome, $monthlySalary);

        $sssLoan = $adjustments['sss_loan'] ?? 0;
        $calamityLoan = $adjustments['calamity_loan'] ?? 0;
        $salaryAdvance = $adjustments['salary_advance'] ?? 0;
        $otherDeductions = $adjustments['other_deductions'] ?? 0;

        $totalDeductions = $sss + $phic + $hdmf + $withholdingTax + $sssLoan + $calamityLoan + $salaryAdvance + $otherDeductions;
        $netPay = $totalEarnings - $totalDeductions;

        return [
            'employee_id'   => $employeeId,
            'period_start'  => $startDate,
            'period_end'    => $endDate,
            'basic_pay'     => round($basicPay, 2),
            'daily_rate'    => round($dailyRate, 2),
            'monthly_salary' => round($monthlySalary, 2),
            'present_days'  => $presentDays,
            'overtime_pay'  => round($otPay, 2),
            'night_diff_pay' => round($nightDiffPay, 2),
            'holiday_pay'   => round($holidayPay, 2),
            'de_minimis'    => round($deMinimis, 2),
            'other_earnings_taxable' => round($otherTaxable, 2),
            'other_earnings_non_taxable' => round($otherNonTaxable, 2),
            'total_earnings' => round($totalEarnings, 2),
            'sss_ee'        => round($sss, 2),
            'phic_ee'       => round($phic, 2),
            'hdmf_ee'       => round($hdmf, 2),
            'withholding_tax' => round($withholdingTax, 2),
            'sss_loan'      => round($sssLoan, 2),
            'calamity_loan' => round($calamityLoan, 2),
            'salary_advance' => round($salaryAdvance, 2),
            'other_deductions' => round($otherDeductions, 2),
            'total_deductions' => round($totalDeductions, 2),
            'net_pay'       => round($netPay, 2),
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
                if ($rec['date'] == $date && !empty($rec['time_in'])) {
                    $present++;
                    break;
                }
            }
        }
        return $present;
    }

    protected function computeOvertimePay($attendance, $hourlyRate)
    {
        $otRate = $this->settings->getSetting('ot_regular_rate');
        $totalOtHours = 0;
        foreach ($attendance as $day) {
            $totalHours = floatval($day['total_hours'] ?? 0);
            if ($totalHours > 8) $totalOtHours += ($totalHours - 8);
        }
        return $totalOtHours * $hourlyRate * $otRate;
    }

    protected function computeNightDifferential($attendance, $hourlyRate)
    {
        $ndStart = $this->settings->getSetting('night_diff_start');
        $ndEnd = $this->settings->getSetting('night_diff_end');
        $ndRate = $this->settings->getSetting('night_diff_rate');

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

    protected function computeSSS($monthlySalary)
    {
        $sssTable = json_decode($this->settings->getSetting('sss_table'), true);
        if (empty($sssTable)) {
            // Fallback simplified table
            $sssTable = [
                ['range' => '0-3250', 'ee' => 135],
                ['range' => '3250.01-3750', 'ee' => 157.5],
                ['range' => '3750.01-4250', 'ee' => 180],
                ['range' => '4250.01-4750', 'ee' => 202.5],
                ['range' => '4750.01-5250', 'ee' => 225],
                ['range' => '5250.01-5750', 'ee' => 247.5],
                ['range' => '5750.01-6250', 'ee' => 270],
                ['range' => '6250.01-6750', 'ee' => 292.5],
                ['range' => '6750.01-7250', 'ee' => 315],
                ['range' => '7250.01-7750', 'ee' => 337.5],
                ['range' => '7750.01-8250', 'ee' => 360],
                ['range' => '8250.01-8750', 'ee' => 382.5],
                ['range' => '8750.01-9250', 'ee' => 405],
                ['range' => '9250.01-9750', 'ee' => 427.5],
                ['range' => '9750.01-10250', 'ee' => 450],
                ['range' => '10250.01-10750', 'ee' => 472.5],
                ['range' => '10750.01-11250', 'ee' => 495],
                ['range' => '11250.01-11750', 'ee' => 517.5],
                ['range' => '11750.01-12250', 'ee' => 540],
                ['range' => '12250.01-12750', 'ee' => 562.5],
                ['range' => '12750.01-13250', 'ee' => 585],
                ['range' => '13250.01-13750', 'ee' => 607.5],
                ['range' => '13750.01-14250', 'ee' => 630],
                ['range' => '14250.01-14750', 'ee' => 652.5],
                ['range' => '14750.01-15250', 'ee' => 675],
                ['range' => '15250.01-15750', 'ee' => 697.5],
                ['range' => '15750.01-16250', 'ee' => 720],
                ['range' => '16250.01-16750', 'ee' => 742.5],
                ['range' => '16750.01-17250', 'ee' => 765],
                ['range' => '17250.01-17750', 'ee' => 787.5],
                ['range' => '17750.01-18250', 'ee' => 810],
                ['range' => '18250.01-18750', 'ee' => 832.5],
                ['range' => '18750.01-19250', 'ee' => 855],
                ['range' => '19250.01-19750', 'ee' => 877.5],
                ['range' => '19750.01-20250', 'ee' => 900],
                ['range' => '20250.01-20750', 'ee' => 922.5],
                ['range' => '20750.01-21250', 'ee' => 945],
                ['range' => '21250.01-21750', 'ee' => 967.5],
                ['range' => '21750.01-22250', 'ee' => 990],
                ['range' => '22250.01-22750', 'ee' => 1012.5],
                ['range' => '22750.01-23250', 'ee' => 1035],
                ['range' => '23250.01-23750', 'ee' => 1057.5],
                ['range' => '23750.01-24250', 'ee' => 1080],
                ['range' => '24250.01-24750', 'ee' => 1102.5],
                ['range' => '24750.01-25250', 'ee' => 1125],
                ['range' => '25250.01-25750', 'ee' => 1147.5],
                ['range' => '25750.01-26250', 'ee' => 1170],
                ['range' => '26250.01-26750', 'ee' => 1192.5],
                ['range' => '26750.01-27250', 'ee' => 1215],
                ['range' => '27250.01-27750', 'ee' => 1237.5],
                ['range' => '27750.01-28250', 'ee' => 1260],
                ['range' => '28250.01-28750', 'ee' => 1282.5],
                ['range' => '28750.01-29250', 'ee' => 1305],
                ['range' => '29250.01-29750', 'ee' => 1327.5],
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
        return max(0, $monthlyTax / 2);
    }
}