<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class PayrollModel extends Model
{
    protected $table = 'payroll';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'employee_id',
        'month_year',
        'phone',
        'daily_rate',
        'dst',
        'currency',
        'status'
    ];

    // Automatic timestamps
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}