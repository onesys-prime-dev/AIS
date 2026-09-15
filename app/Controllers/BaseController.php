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

    /**
     * Log the full exception server-side and return a JSON error response.
     * In production, $publicMessage is shown to the client instead of the raw
     * exception text so internals (queries, file paths, stack info) never leak.
     */
    protected function jsonError(Throwable $e, string $publicMessage = 'Something went wrong. Please try again.', int $statusCode = 500)
    {
        log_message('error', static::class . ': ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

        return $this->response->setStatusCode($statusCode)->setJSON(['error' => $this->safeErrorMessage($e, $publicMessage)]);
    }

    /**
     * Log the full exception server-side and redirect back with a sanitized
     * flash error message (same rationale as jsonError()).
     */
    protected function flashError(Throwable $e, string $publicMessage = 'Something went wrong. Please try again.')
    {
        log_message('error', static::class . ': ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

        return redirect()->back()->with('error', $this->safeErrorMessage($e, $publicMessage));
    }

    /**
     * ValidationException messages are always author-written and safe to show
     * (e.g. "Employee not found"). Anything else may contain internals (SQL,
     * file paths, stack info), so it's only shown outside production.
     */
    protected function safeErrorMessage(Throwable $e, string $publicMessage): string
    {
        if ($e instanceof \App\Exceptions\ValidationException || ENVIRONMENT !== 'production') {
            return $e->getMessage();
        }

        return $publicMessage;
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
                'font_family'      => 'default',
                'font_size'        => 'medium',
            ];

            $branding['logo_url'] = ! empty($branding['logo_path'])
                ? base_url((string) $branding['logo_path'])
                : null;

            // font_family/font_size are user_settings-only preferences, not
            // company_branding columns, so a real DB row won't carry them.
            $branding['font_family'] ??= 'default';
            $branding['font_size']   ??= 'medium';

                log_message('debug', 'logo_path: {p} | logo_url: {u}', [
    'p' => $branding['logo_path'] ?? 'null',
    'u' => $branding['logo_url']  ?? 'null',
]);

            // Layer user-specific settings on top of company branding.
            // User preferences override colors/icon mode; logo stays company-level.
            $userId       = (int) auth()->id();
            $userSettings = (new UserSettingsModel())->getSettingsArray($userId);

            $overridableKeys = ['sidebar_color', 'header_color', 'background_color', 'sidebar_icon_mode', 'font_family', 'font_size'];
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