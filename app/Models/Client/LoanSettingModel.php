<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class LoanSettingModel extends Model
{
    protected $table = 'employee_loan_settings';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [

        'loan_type',

        'interest_rate',

        'max_salary_multiplier',

        'max_installments',

        'deduction_frequency',

        'allow_multiple_loans',

        'grace_period_days',

        'penalty_rate',

        'is_active',
    ];

    protected $useTimestamps = true;
}