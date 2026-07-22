<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBreakScheduleSupport extends Migration
{
    public function up()
    {
        // Per-employee list of allowed breaks and their durations, e.g. [15,60,15]
        // (minutes). NULL means "not configured" — falls back to the legacy single
        // break behavior (one break, credited flat at 60 minutes).
        $this->forge->addColumn('employee_positions', [
            'break_schedule' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'end_time',
            ],
        ]);

        // How many of today's configured breaks this attendance record has already
        // used — determines which entry in break_schedule applies to the next one.
        $this->forge->addColumn('attendance', [
            'breaks_taken' => [
                'type'       => 'TINYINT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 0,
                'after'      => 'break_duration',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', 'break_schedule');
        $this->forge->dropColumn('attendance', 'breaks_taken');
    }
}
