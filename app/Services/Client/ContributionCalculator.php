<?php

namespace App\Services\Client;

use App\Models\Client\SssBracketModel;
use App\Models\Client\WtaxBracketModel;

class ContributionCalculator
{
    public function getSssEmployee(float $monthlySalary): float
    {
        $model = new SssBracketModel();
        $bracket = $model->where('range_start <=', $monthlySalary)
            ->orderBy('range_start', 'DESC')
            ->first();
        return $bracket ? (float) $bracket['total_ee'] : 0;
    }

    public function getPhicEmployee(float $monthlySalary): float
    {
        // PhilHealth: (monthlySalary * 0.05) / 2, capped at (1200/2)
        $premium = $monthlySalary * 0.05;
        $share = $premium / 2;
        $max = 600; // max employee share (1200 total)
        return min($share, $max);
    }

    public function getPagIbigEmployee(float $monthlySalary): float
    {
        // Pag-IBIG: 2% of monthly salary, capped at 100
        $share = $monthlySalary * 0.02;
        return min($share, 100);
    }

    public function computeWithholdingTax(float $monthlyTaxableIncome): float
    {
        $model = new WtaxBracketModel();
        $bracket = $model->where('min_amount <=', $monthlyTaxableIncome)
            ->orderBy('min_amount', 'DESC')
            ->first();
        if (!$bracket) return 0;
        $excess = $monthlyTaxableIncome - $bracket['min_amount'];
        $tax = $bracket['fixed_tax'] + ($excess * $bracket['percent_over']);
        return max(0, $tax);
    }
}