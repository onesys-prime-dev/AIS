<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table      = 'attendance';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'date', 'time_in', 'break_start', 'break_end', 'break_duration', 'time_out', 'total_hours'];
    protected $useTimestamps = false;
}