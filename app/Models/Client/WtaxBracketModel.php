<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class WtaxBracketModel extends Model
{
    protected $table = 'wtax_brackets_monthly';
    protected $primaryKey = 'id';
    protected $allowedFields = ['min_amount', 'max_amount', 'fixed_tax', 'percent_over'];
    protected $useTimestamps = false;
}