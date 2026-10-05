<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marks which of an employee's scheduled working_days are WFH rather than
 * onsite — same JSON-array-of-day-codes format as working_days (e.g.
 * '["mon","wed"]'), but always a subset of it: a day can't be WFH without
 * also being a working day.
 *
 * Display-only for now — not read by attendance/payroll (schedule_snapshot,
 * PayrollAggregationService, LoanController day-rate calc, etc. are
 * untouched), so no existing computation changes.
 */
class AddWfhDaysToEmployeePositions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('employee_positions', [
            'wfh_days' => [
                'type' => 'TEXT', 'null' => true, 'after' => 'working_days',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', ['wfh_days']);
    }
}
