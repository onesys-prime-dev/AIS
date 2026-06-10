<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSentByToEmployeePayslips extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employee_payslips', [
            'sent_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'sent_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_payslips', 'sent_by');
    }
}
