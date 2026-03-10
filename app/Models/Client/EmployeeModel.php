<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'employee_number',
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'birthdate'
    ];
}
