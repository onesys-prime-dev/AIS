<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class EmployeeLoanModel extends Model
{
    protected $table = 'employee_loans';
    protected $primaryKey = 'id';
    protected $allowedFields = ['employee_id', 'sss_loan', 'calamity_loan', 'salary_advance'];
    protected $useTimestamps = true;
}