<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AnnouncementAttachmentModel extends Model
{
    protected $table = 'announcement_attachments';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'announcement_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
        'created_at',
    ];

    protected $useTimestamps = false;
}
