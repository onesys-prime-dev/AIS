<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateThirteenthMonthAndAnnualization extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'              => ['type' => 'INT', 'unsigned' => true],
            'year'                     => ['type' => 'SMALLINT', 'unsigned' => true],
            'total_basic_earned'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'periods_count'            => ['type' => 'INT', 'default' => 0],
            'thirteenth_month_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'tax_exempt_amount'        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'taxable_amount'           => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'status'                   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'computed'],
            'computed_at'              => ['type' => 'DATETIME', 'null' => true],
            'created_at'               => ['type' => 'DATETIME', 'null' => true],
            'updated_at'               => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['employee_id', 'year']);
        $this->forge->createTable('thirteenth_month_records', true);

        $this->forge->addField([
            'id'                          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'                 => ['type' => 'INT', 'unsigned' => true],
            'year'                        => ['type' => 'SMALLINT', 'unsigned' => true],
            'total_taxable_compensation'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'thirteenth_month_taxable'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'annual_taxable_income'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'annual_tax_due'              => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'total_tax_withheld'          => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'year_end_adjustment'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'status'                      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'computed'],
            'computed_at'                 => ['type' => 'DATETIME', 'null' => true],
            'created_at'                  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'                  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['employee_id', 'year']);
        $this->forge->createTable('annualization_records', true);
    }

    public function down()
    {
        $this->forge->dropTable('thirteenth_month_records', true);
        $this->forge->dropTable('annualization_records', true);
    }
}
