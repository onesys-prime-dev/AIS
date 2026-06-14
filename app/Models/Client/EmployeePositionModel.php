<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class EmployeePositionModel extends Model
{
    protected $table = 'employee_positions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'employee_id', 'department', 'position', 'employment_type',
        'start_time', 'end_time', 'working_day_type', 'working_days',
        'monthly_salary', 'de_minimis_benefit', 'de_minimis_breakdown', 'allowances',
    ];
}