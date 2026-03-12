<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class RequestModel extends Model
{
    protected $table      = 'requests';
    protected $primaryKey = 'id';
    protected $allowedFields = ['requested_by', 'request_type', 'date', 'reason', 'status', 'processed_by', 'created_at', 'updated_at'];
    protected $useTimestamps = false;
}