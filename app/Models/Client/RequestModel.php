<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class RequestModel extends Model
{
    protected $table      = 'requests';
    protected $primaryKey = 'id';
    protected $allowedFields = ['requested_by', 'request_type', 'start_date', 'end_date', 'duration', 'approval_code', 'reason', 'status', 'processed_by', 'review_message', 'created_at', 'updated_at'];
    protected $useTimestamps = false;
}