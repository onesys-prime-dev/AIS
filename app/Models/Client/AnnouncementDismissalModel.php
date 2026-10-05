<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AnnouncementDismissalModel extends Model
{
    protected $table = 'announcement_dismissals';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'announcement_id',
        'user_id',
        'dismissed_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}