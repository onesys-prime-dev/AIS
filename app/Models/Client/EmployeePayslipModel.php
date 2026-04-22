<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class EmployeePayslipModel extends Model
{
    protected $table = 'employee_payslips';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'employee_id', 'period_start', 'period_end', 'net_pay', 'file_path', 'generated_data'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
}