<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class RawPayrollDataModel extends Model
{
    protected $table = 'raw_payroll_data';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'employee_id', 'period_start', 'period_end', 'daily_rate', 'basic_pay',
        'absent_days', 'absent_amount', 'undertime_hours', 'undertime_amount',
        'night_diff_pay', 'reg_ot_hours', 'reg_ot_pay', 'rest_ot_hours', 'rest_ot_pay',
        'holiday_ot_hours', 'holiday_ot_pay', 'de_minimis', 'other_taxable', 'other_non_taxable',
        'sss_ee', 'phic_ee', 'hdmf_ee', 'withholding_tax', 'sss_loan', 'calamity_loan',
        'salary_advance', 'other_deductions', 'net_pay', 'status'
    ];
    protected $useTimestamps = false;
}