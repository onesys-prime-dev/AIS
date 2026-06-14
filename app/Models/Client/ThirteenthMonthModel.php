<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class ThirteenthMonthModel extends Model
{
    protected $table         = 'thirteenth_month_records';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'employee_id', 'year', 'total_basic_earned', 'total_gross_basic',
        'total_absent_undertime', 'periods_count',
        'thirteenth_month_amount', 'tax_exempt_amount', 'taxable_amount',
        'status', 'computed_at',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
}
