<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPeriodTypeToPayroll extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payroll', [
            'period_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'monthly',
                'after'      => 'month_year',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payroll', 'period_type');
    }
}
