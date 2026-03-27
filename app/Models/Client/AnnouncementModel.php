<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table = 'announcements';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'title',
        'category',
        'audience',
        'department',
        'priority',
        'publish_at',
        'expires_at',
        'summary',
        'details',
        'require_acknowledgement',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
