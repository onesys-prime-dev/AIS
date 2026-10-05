<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table      = 'attendance';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'shift_date', 'schedule_snapshot', 'break_log', 'state_version', 'breaks_taken',
        'date',
        'time_in',
        'break_start',
        'break_end',
        'break_duration',
        'time_out',
        'total_hours',
        'night_diff_hours',

        'scheduled_start',
        'scheduled_end',
        'late_minutes',

        'approved_ot_hours',
        'unapproved_ot_hours',

        'approved_ut_hours',
        'unapproved_ut_hours',

        'unapproved_ut_reason',
        'missed_clockout_reason',
        'attendance_status',

        'time_in_dt',
        'time_out_dt',
        'break_start_dt',
        'break_end_dt',
    ];
    protected $useTimestamps = false;
}