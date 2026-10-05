<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMissedClockoutReasonToAttendance extends Migration
{
    public function up()
    {
        // Free-text reason for clocking out on a later calendar day than the shift
        // started (e.g. forgot to clock out) — kept separate from unapproved_ut_reason
        // since this isn't an undertime case, it's an overdue/missed clock-out.
        $this->forge->addColumn('attendance', [
            'missed_clockout_reason' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'unapproved_ut_reason',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('attendance', 'missed_clockout_reason');
    }
}
