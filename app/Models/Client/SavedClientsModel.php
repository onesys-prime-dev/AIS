<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class SavedClientsModel extends Model
{
    protected $table         = 'saved_clients';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['user_id', 'client', 'saved_at'];
    protected $useTimestamps = false;
}
