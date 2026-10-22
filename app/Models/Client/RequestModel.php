<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class RequestModel extends Model
{
    protected $table      = 'requests';
    protected $primaryKey = 'id';
    protected $allowedFields = ['requested_by', 'request_type', 'leave_category', 'start_date', 'end_date', 'ot_start_time', 'ot_end_time', 'duration', 'approval_code', 'reason', 'status', 'is_paid_leave', 'processed_by', 'review_message', 'created_at', 'updated_at'];
    protected $useTimestamps = false;
}