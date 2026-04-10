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
    ];
}