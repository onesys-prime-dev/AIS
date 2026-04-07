<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class CompanyBrandingModel extends Model
{
    protected $table = 'company_branding';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useAutoIncrement = true;

    protected $protectFields = true;
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'company_id',
        'logo_path',
        'logo_original_name',
        'logo_mime_type',
        'logo_size',
        'sidebar_color',
        'header_color',
        'background_color',
        'sidebar_icon_mode',
        'updated_by',
        'created_at',
        'updated_at',
    ];
}
