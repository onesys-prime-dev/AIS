<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table         = 'departments';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'is_active'];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
}
