<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeLoanSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],

            'loan_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],

            'interest_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.00,
            ],

            'salary_multiplier' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 1.00,
            ],

            'max_installments' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 12,
            ],

            'deduction_frequency' => [
                'type' => 'ENUM',
                'constraint' => [
                    'Semi-Monthly',
                    'Monthly'
                ],
                'default' => 'Semi-Monthly',
            ],

            'allow_multiple_loans' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],

            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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

        $this->forge->createTable(
            'employee_loan_settings'
        );
    }

    public function down()
    {
        $this->forge->dropTable(
            'employee_loan_settings'
        );
    }
}