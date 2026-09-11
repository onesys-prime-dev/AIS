<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class PayrollTransactionModel extends Model
{
    protected $table = 'payroll_transactions';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [

        'employee_id',
        'period_start',
        'period_end',
        'basic_pay',
        'overtime_pay',
        'night_diff_pay',
        'holiday_pay',
        'de_minimis',
        'other_earnings_taxable',
        'other_earnings_non_taxable',
        'total_earnings',
        'sss_ee',
        'phic_ee',
        'hdmf_ee',
        'withholding_tax',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
        'computed_by',
        'computed_at'
    ];

    protected $useTimestamps = false;
}