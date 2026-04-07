<?php

namespace App\Controllers;

use App\Models\Client\CompanyBrandingModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
        $this->bootstrapActiveCompanyBranding();
    }

    protected function resolveActiveCompanyId(): int
    {
        // Temporary client-end scope: one active company context from session fallback.
        return (int) (session('company_id') ?? 1);
    }

    private function bootstrapActiveCompanyBranding(): void
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return;
        }

        try {
            $brandingModel = new CompanyBrandingModel();
            $companyId = $this->resolveActiveCompanyId();

            $branding = $brandingModel->where('company_id', $companyId)->first();

            if (! $branding) {
                $branding = [
                    'company_id' => $companyId,
                    'logo_path' => null,
                    'sidebar_color' => '#1e1e1e',
                    'header_color' => '#ffffff',
                    'background_color' => '#f5f6fa',
                    'sidebar_icon_mode' => 'auto',
                ];
            }

            $branding['logo_url'] = ! empty($branding['logo_path'])
                ? base_url((string) $branding['logo_path'])
                : null;

            service('renderer')->setVar('activeCompanyBranding', $branding);
        } catch (Throwable $e) {
            // Keep app resilient before/while migrations are being applied.
            log_message('debug', 'Branding bootstrap skipped: {message}', ['message' => $e->getMessage()]);
        }
    }
}
