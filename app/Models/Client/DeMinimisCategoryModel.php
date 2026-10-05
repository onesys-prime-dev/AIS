<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class DeMinimisCategoryModel extends Model
{
    protected $table = 'de_minimis_categories';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'category_key',
        'label',
        'note',
        'cap_amount',
        'cap_period',
        'is_active',
    ];

    protected $useTimestamps = true;

    /**
     * Categories BIR recognizes as de minimis beyond the 4 already handled
     * elsewhere in this app (rice, uniform, laundry, medical). Ceilings match
     * RR No. 29-2025 (further amending RR No. 2-98's Section 2.78.1(A)(3)),
     * effective 2026-01-06. Two RR items aren't expressible as a flat peso
     * cap — meal allowance is 30% of the regional basic minimum wage, and
     * monetized VL is a 12-day/year cap on unused leave credits — so those
     * stay at cap_amount 0 with the rule spelled out in the label; every
     * value here stays editable in the De Minimis Categories panel rather
     * than hardcoded into computation, since BIR revises them periodically.
     */
    public function seedBirDefaults(): void
    {
        $defaults = [
            ['category_key' => 'medical_cash_dependents', 'label' => 'Medical Cash Allowance to Dependents', 'note' => null, 'cap_amount' => 333, 'cap_period' => 'monthly'],
            ['category_key' => 'actual_medical_assistance', 'label' => 'Actual Medical Assistance', 'note' => null, 'cap_amount' => 12000, 'cap_period' => 'annual'],
            ['category_key' => 'achievement_awards', 'label' => 'Employee Achievement Awards', 'note' => null, 'cap_amount' => 12000, 'cap_period' => 'annual'],
            ['category_key' => 'christmas_anniversary_gifts', 'label' => 'Christmas / Anniversary Gifts', 'note' => null, 'cap_amount' => 6000, 'cap_period' => 'annual'],
            ['category_key' => 'meal_allowance_ot_night', 'label' => 'Meal Allowance (OT / Night Shift)', 'note' => '30% of the regional minimum wage — no fixed peso cap', 'cap_amount' => 0, 'cap_period' => 'per_payroll'],
            ['category_key' => 'monetized_unused_vl', 'label' => 'Monetized Unused Vacation Leave', 'note' => 'Max 12 days/year — no fixed peso cap', 'cap_amount' => 0, 'cap_period' => 'annual'],
            ['category_key' => 'cba_productivity_incentive', 'label' => 'CBA & Productivity Incentives', 'note' => null, 'cap_amount' => 12000, 'cap_period' => 'annual'],
        ];

        foreach ($defaults as $row) {
            $existing = $this->where('category_key', $row['category_key'])->first();
            if ($existing) {
                $this->update($existing['id'], $row);
            } else {
                $this->insert($row);
            }
        }
    }
}
