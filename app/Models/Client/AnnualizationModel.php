<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class AnnualizationModel extends Model
{
    protected $table         = 'annualization_records';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'employee_id', 'year', 'total_taxable_compensation', 'thirteenth_month_taxable',
        'annual_taxable_income', 'annual_tax_due', 'total_tax_withheld',
        'year_end_adjustment', 'status', 'computed_at',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
}
