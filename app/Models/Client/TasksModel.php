<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class TasksModel extends Model
{
    protected $table      = 'tasks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['attendance_id', 'user_id', 'start_time', 'end_time', 'task_performed', 'client', 'client_id', 'saved_at'];
    protected $useTimestamps = false;
}