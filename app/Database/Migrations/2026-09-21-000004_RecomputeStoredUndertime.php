<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Undertime used to be measured against the whole scheduled window, so a full
 * day with the scheduled break was stored as 1h undertime. Re-stamp the stored
 * undertime hours and status on completed rows that were flagged that way.
 */
class RecomputeStoredUndertime extends Migration
{
    public function up()
    {
        $rows = $this->db->table('attendance')
            ->whereIn('attendance_status', ['approved_undertime', 'unapproved_undertime'])
            ->where('time_out IS NOT NULL', null, false)
            ->where('scheduled_start IS NOT NULL', null, false)
            ->get()->getResultArray();

        $service = new \App\Services\Client\AttendanceComputationService();

        foreach ($rows as $row) {
            try {
                $ut = $service->analyzeClockOut((int) $row['user_id'], $row['time_out'], $row)['undertime'];
            } catch (\Throwable $e) {
                continue;
            }

            if ($ut['approved_ut'] > 0) {
                $status = 'approved_undertime';
            } elseif ($ut['unapproved_ut'] > 0) {
                $status = 'unapproved_undertime';
            } elseif ((float) ($row['approved_ot_hours'] ?? 0) > 0) {
                $status = 'approved_overtime';
            } elseif ((float) ($row['unapproved_ot_hours'] ?? 0) > 0) {
                $status = 'unapproved_overtime';
            } else {
                $status = 'present';
            }

            $this->db->table('attendance')->where('id', $row['id'])->update([
                'approved_ut_hours'   => $ut['approved_ut'],
                'unapproved_ut_hours' => $ut['unapproved_ut'],
                'attendance_status'   => $status,
            ]);
        }
    }

    public function down()
    {
    }
}
