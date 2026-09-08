<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class LoanScheduleModel extends Model
{
    protected $table = 'employee_loan_schedule';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [

        'loan_id',

        'installment_no',

        'due_date',

        'amount_due',

        'remaining_balance',

        'schedule_status',

        'payroll_id',

        'paid_at',
    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';
}