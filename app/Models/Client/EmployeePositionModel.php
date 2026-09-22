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
        'employee_id', 'department', 'department_id', 'position', 'employment_type',
        'start_time', 'end_time', 'working_day_type', 'working_days', 'break_schedule', 'break_windows',
        'monthly_salary', 'de_minimis_benefit', 'de_minimis_breakdown', 'allowances',
        'additional_de_minimis_benefit', 'additional_de_minimis_breakdown',
    ];
}