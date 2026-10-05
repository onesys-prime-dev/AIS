<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class LoanPaymentModel extends Model
{
    protected $table = 'employee_loan_payments';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [

        'loan_id',

        'schedule_id',

        'employee_id',

        'payroll_id',

        'payment_amount',

        'payment_method',

        'payment_reference',

        'remarks',
    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';
}