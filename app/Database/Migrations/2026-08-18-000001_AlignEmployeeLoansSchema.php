<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Loans module (LoanModel/LoanService/LoanController) has always been
 * written against a richer employee_loans / employee_loan_settings schema
 * than what CreateEmployeeLoansTables / CreateEmployeeLoanSettingsTable
 * actually created. Both tables have 0 rows in production, so nothing has
 * ever been able to save through this feature — this brings the tables up
 * to match the code instead of the other way around.
 */
class AlignEmployeeLoansSchema extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------
        // employee_loans — add every column LoanModel already expects.
        // Existing sss_loan/calamity_loan/salary_advance columns are left
        // in place untouched (unused by current code, no data to lose).
        // ---------------------------------------------------------------
        $this->forge->addColumn('employee_loans', [
            'loan_category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'loan_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'principal_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
            ],
            'interest_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
                'default'    => 0,
            ],
            'total_interest' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
            ],
            'total_payable' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
            ],
            'deduction_per_payroll' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
            ],
            'deduction_frequency' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'Monthly',
            ],
            'installment_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
            ],
            'remaining_balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => false,
                'default'    => 0,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'estimated_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'loan_status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'completed', 'cancelled'],
                'null'       => false,
                'default'    => 'active',
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        // ---------------------------------------------------------------
        // employee_loan_settings — LoanController/LoanService read/write
        // 'loan_category', 'max_salary_multiplier' and
        // 'allow_multiple_active_loans'; the table has 'salary_multiplier'
        // and 'allow_multiple_loans' instead, and no 'loan_category',
        // 'grace_period_days' or 'penalty_rate' at all. saveLoanSettings()
        // filters on loan_category, so it currently errors on every save.
        // ---------------------------------------------------------------
        $this->forge->addColumn('employee_loan_settings', [
            'loan_category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'id',
            ],
            'grace_period_days' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
            ],
            'penalty_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
                'default'    => 0,
            ],
        ]);

        $this->forge->modifyColumn('employee_loan_settings', [
            'salary_multiplier' => [
                'name'       => 'max_salary_multiplier',
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
                'default'    => 1.00,
            ],
            'allow_multiple_loans' => [
                'name'       => 'allow_multiple_active_loans',
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('employee_loan_settings', [
            'max_salary_multiplier' => [
                'name'       => 'salary_multiplier',
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
                'default'    => 1.00,
            ],
            'allow_multiple_active_loans' => [
                'name'       => 'allow_multiple_loans',
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
        ]);

        $this->forge->dropColumn('employee_loan_settings', [
            'loan_category',
            'grace_period_days',
            'penalty_rate',
        ]);

        $this->forge->dropColumn('employee_loans', [
            'loan_category',
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
            'updated_by',
            'completed_at',
            'completed_by',
            'cancelled_at',
            'cancelled_by',
        ]);
    }
}
