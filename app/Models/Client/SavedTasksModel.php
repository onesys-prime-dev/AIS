<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class SavedTasksModel extends Model
{
    protected $table      = 'saved_tasks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'task_performed', 'saved_at'];
    protected $useTimestamps = false;
}