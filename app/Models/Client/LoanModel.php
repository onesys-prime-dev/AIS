<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class LoanModel extends Model
{
    protected $table = 'employee_loans';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [

        'employee_id',

        'loan_type',

        'principal_amount',

        'interest_rate',

        'total_interest',

        'total_payable',

        'deduction_per_payroll',

        'deduction_frequency',

        'installment_count',

        'remaining_balance',

        'start_date',

        'estimated_end_date',

        'loan_status',

        'remarks',

        'created_by',

        'approved_by',

        'approved_at',

        /*
        |--------------------------------------------------------------------------
        | NEW AUDIT FIELDS
        |--------------------------------------------------------------------------
        */

        'updated_by',

        'completed_at',

        'completed_by',

        'cancelled_at',

        'cancelled_by',
    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';

    protected $updatedField = 'updated_at';
}