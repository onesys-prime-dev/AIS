<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class EmployeeGovernmentIdModel extends Model
{
    protected $table = 'employee_government_ids';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'employee_id',
        'sss',
        'tin',
        'pagibig',
        'philhealth'
    ];
}