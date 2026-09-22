<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds an optional per-break-slot time-of-day policy, stored as a JSON array
 * parallel (by index) to break_schedule. Each entry is either null (Flexible
 * — no window, today's behavior) or an object:
 *   {"mode":"strict"|"slightly_strict","start":"HH:MM","end":"HH:MM"}
 *
 * - strict: Start Break only works inside the window, and the break is
 *   capped to whatever time remains until the window's end (it does not
 *   extend the scheduled duration past the window).
 * - slightly_strict: Start Break only works inside the window, but once
 *   started the employee always gets the full scheduled duration measured
 *   from their own click, even if that runs past the window's end.
 *
 * Purely additive — nothing that reads break_schedule needs to change, and a
 * missing/empty break_windows means every slot behaves exactly as it does
 * today (Flexible).
 */
class AddBreakWindowsToEmployeePositions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employee_positions', [
            'break_windows' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'break_schedule',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', 'break_windows');
    }
}
