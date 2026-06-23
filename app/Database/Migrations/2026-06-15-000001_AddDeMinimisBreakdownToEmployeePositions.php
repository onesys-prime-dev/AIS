<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeMinimisBreakdownToEmployeePositions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employee_positions', [
            'de_minimis_breakdown' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'de_minimis_benefit',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', 'de_minimis_breakdown');
    }
}
