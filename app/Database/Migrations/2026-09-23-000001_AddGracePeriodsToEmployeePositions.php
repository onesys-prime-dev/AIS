<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-employee grace periods, configured in Employee Management's Work
 * Schedule section alongside start/end time — not a global setting, since
 * this lives with the rest of that employee's schedule and is meant to
 * freeze into schedule_snapshot at clock-in the same way start_time/end_time
 * already do (payroll-stable: a mid-shift edit doesn't retroactively change
 * an in-progress shift's tardy/OT math).
 *
 * tardiness_grace_minutes: clocking in this many minutes late still counts
 * as exactly on time (0 tardy minutes) — crossing it does NOT get a
 * discount, the full lateness (from scheduled start) counts.
 *
 * ot_grace_minutes: applied independently to each edge of the scheduled
 * window — up to this many minutes early (before start) or late (after
 * end) isn't counted as excess/overtime.
 *
 * Both default to 0 (today's exact-minute behavior) so no existing employee
 * changes until an admin sets one.
 */
class AddGracePeriodsToEmployeePositions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employee_positions', [
            'tardiness_grace_minutes' => [
                'type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true,
                'null' => false, 'default' => 0, 'after' => 'end_time',
            ],
            'ot_grace_minutes' => [
                'type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true,
                'null' => false, 'default' => 0, 'after' => 'tardiness_grace_minutes',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', ['tardiness_grace_minutes', 'ot_grace_minutes']);
    }
}
