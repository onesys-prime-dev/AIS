<?php

namespace App\Models;

use CodeIgniter\Model;

class TrustedDeviceModel extends Model
{
    protected $table = 'trusted_devices';

    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'token',
        'device_name',
        'ip_address',
        'last_used_at',
        'expires_at',
    ];

    protected $useTimestamps = true;
}