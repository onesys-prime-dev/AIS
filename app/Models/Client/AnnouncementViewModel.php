<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AnnouncementViewModel extends Model
{
    protected $table = 'announcement_views';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'announcement_id',
        'user_id',
        'viewed_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
