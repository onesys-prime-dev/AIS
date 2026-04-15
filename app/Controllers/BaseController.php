<?php

namespace App\Controllers;

use App\Models\Client\CompanyBrandingModel;
use App\Models\Client\UserSettingsModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class BaseController extends Controller
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->bootstrapActiveCompanyBranding();
    }

    protected function resolveActiveCompanyId(): int
    {
        return (int) (session('company_id') ?? 1);
    }

    private function bootstrapActiveCompanyBranding(): void
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return;
        }

        try {
            $companyId    = $this->resolveActiveCompanyId();
            $brandingModel = new CompanyBrandingModel();

            $branding = $brandingModel->where('company_id', $companyId)->first() ?? [
                'company_id'       => $companyId,
                'logo_path'        => null,
                'sidebar_color'    => '#1e1e1e',
                'header_color'     => '#ffffff',
                'background_color' => '#f5f6fa',
                'sidebar_icon_mode'=> 'auto',
            ];

            $branding['logo_url'] = ! empty($branding['logo_path'])
                ? base_url((string) $branding['logo_path'])
                : null;

                log_message('debug', 'logo_path: {p} | logo_url: {u}', [
    'p' => $branding['logo_path'] ?? 'null',
    'u' => $branding['logo_url']  ?? 'null',
]);

            // Layer user-specific settings on top of company branding.
            // User preferences override colors/icon mode; logo stays company-level.
            $userId       = (int) auth()->id();
            $userSettings = (new UserSettingsModel())->getSettingsArray($userId);

            $overridableKeys = ['sidebar_color', 'header_color', 'background_color', 'sidebar_icon_mode'];
            foreach ($overridableKeys as $key) {
                if (! empty($userSettings[$key])) {
                    $branding[$key] = $userSettings[$key];
                }
            }

            service('renderer')->setVar('activeCompanyBranding', $branding);
        } catch (Throwable $e) {
            log_message('debug', 'Branding bootstrap skipped: {message}', ['message' => $e->getMessage()]);
        }
    }
}