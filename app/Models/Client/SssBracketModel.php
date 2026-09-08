<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class SssBracketModel extends Model
{
    protected $table = 'sss_brackets';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'range_start', 'range_end', 'monthly_salary_credit',
        'ee_regular', 'ee_wisp', 'er_regular', 'er_wisp'
    ];
    protected $useTimestamps = false;
}