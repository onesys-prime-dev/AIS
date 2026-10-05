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

    /**
     * Returns the company's branding row, or an in-memory default (unsaved,
     * id: null) if none exists yet. Single source of truth for the default
     * branding shape, used by both the settings and account controllers.
     */
    public function getOrCreateDefault(int $companyId): array
    {
        $branding = $this->where('company_id', $companyId)->first();

        if ($branding) {
            return $branding;
        }

        return [
            'id'                 => null,
            'company_id'         => $companyId,
            'logo_path'          => null,
            'logo_original_name' => null,
            'logo_mime_type'     => null,
            'logo_size'          => null,
            'sidebar_color'      => '#1e1e1e',
            'header_color'       => '#ffffff',
            'background_color'   => '#f5f6fa',
            'sidebar_icon_mode'  => 'auto',
        ];
    }

    /**
     * Applies a user's per-account overrides (color/font preferences from
     * UserSettingsModel) on top of the company branding defaults.
     */
    public static function applyUserOverrides(array $branding, array $userSettings): array
    {
        foreach (['sidebar_color', 'header_color', 'background_color', 'sidebar_icon_mode', 'font_family', 'font_size'] as $key) {
            if (! empty($userSettings[$key])) {
                $branding[$key] = $userSettings[$key];
            }
        }

        $branding['font_family'] ??= 'default';
        $branding['font_size']   ??= 'medium';

        return $branding;
    }
}
