<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmployeeScheduleFields extends Migration
{
    public function up()
    {
        $fields = [
            'working_day_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
                'after' => 'employment_type',
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => true,
                'after' => 'working_day_type',
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => true,
                'after' => 'start_time',
            ],
            'working_days' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'end_time',
            ],
        ];

        $this->forge->addColumn('employee_positions', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', ['working_days', 'end_time', 'start_time', 'working_day_type']);
    }
}