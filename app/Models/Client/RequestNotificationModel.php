<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class RequestNotificationModel extends Model
{
    protected $table = 'request_notifications';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'request_id',
        'recipient_user_id',
        'actor_user_id',
        'status',
        'title',
        'details',
        'viewed_at',
        'dismissed_at',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
