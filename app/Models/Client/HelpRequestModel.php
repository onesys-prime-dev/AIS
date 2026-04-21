<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class HelpRequestModel extends Model
{
    protected $table = 'help_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'category',
        'priority',
        'subject',
        'message',
        'status',
        'response',
        'responded_by',
        'responded_at',
        'created_at',
        'updated_at',
    ];
}