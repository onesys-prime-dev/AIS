<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeLoansTables extends Migration
{
    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE LOANS
        |--------------------------------------------------------------------------
        */

        $this->forge->addField([

            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'employee_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],

            'loan_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],

            'principal_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'interest_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],

            'total_interest' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'total_payable' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'deduction_per_payroll' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'deduction_frequency' => [
                'type'       => 'ENUM',
                'constraint' => ['Semi-Monthly', 'Monthly'],
                'default'    => 'Semi-Monthly',
            ],

            'installment_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],

            'remaining_balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'start_date' => [
                'type' => 'DATE',
            ],

            'estimated_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],

            'loan_status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'active',
                    'completed',
                    'cancelled',
                    'suspended'
                ],
                'default' => 'active',
            ],

            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],

            'approved_by' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addKey('id', true);

        $this->forge->addKey('employee_id');

        $this->forge->createTable('employee_loans');



        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE LOAN SCHEDULE
        |--------------------------------------------------------------------------
        */

        $this->forge->addField([

            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'loan_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],

            'installment_no' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],

            'due_date' => [
                'type' => 'DATE',
            ],

            'amount_due' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'remaining_balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'schedule_status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'pending',
                    'paid',
                    'partial',
                    'missed',
                    'cancelled'
                ],
                'default' => 'pending',
            ],

            'payroll_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],

            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addKey('id', true);

        $this->forge->addKey('loan_id');

        $this->forge->createTable('employee_loan_schedule');



        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE LOAN PAYMENTS
        |--------------------------------------------------------------------------
        */

        $this->forge->addField([

            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'loan_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],

            'schedule_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],

            'employee_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],

            'payroll_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],

            'payment_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],

            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Payroll Deduction',
            ],

            'payment_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],

            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],

            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

        ]);

        $this->forge->addKey('id', true);

        $this->forge->addKey('loan_id');

        $this->forge->addKey('schedule_id');

        $this->forge->addKey('employee_id');

        $this->forge->createTable('employee_loan_payments');
    }

    public function down()
    {
        $this->forge->dropTable('employee_loan_payments');
        $this->forge->dropTable('employee_loan_schedule');
        $this->forge->dropTable('employee_loans');
    }
}
