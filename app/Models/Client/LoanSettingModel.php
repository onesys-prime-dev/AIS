<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class LoanSettingModel extends Model
{
    protected $table = 'employee_loan_settings';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [

        'loan_category',
        'loan_type',
        'interest_rate',
        'max_salary_multiplier',
        'salary_multiplier',
        'max_installments',
        'deduction_frequency',
        'grace_period_days',
        'penalty_rate',
        'allow_multiple_active_loans',
        'is_active'

    ];

    protected $useTimestamps = true;
}