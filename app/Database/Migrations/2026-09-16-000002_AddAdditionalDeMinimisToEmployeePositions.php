<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * New, separate columns for the additional BIR de minimis categories —
 * deliberately not merged into the existing de_minimis_benefit /
 * de_minimis_breakdown columns, so that field and everything that reads it
 * (payroll computation included) is completely unaffected.
 */
class AddAdditionalDeMinimisToEmployeePositions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employee_positions', [
            'additional_de_minimis_benefit' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
                'after'      => 'de_minimis_breakdown',
            ],
            'additional_de_minimis_breakdown' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'additional_de_minimis_benefit',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', ['additional_de_minimis_benefit', 'additional_de_minimis_breakdown']);
    }
}
