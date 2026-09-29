<?php

namespace App\Controllers;

use App\Models\Client\CompanyBrandingModel;
use App\Models\Client\UserSettingsModel;

class SettingsController extends BaseController
{
    protected CompanyBrandingModel $brandingModel;
    protected UserSettingsModel    $userSettingsModel;

    public function __construct()
    {
        $this->brandingModel      = new CompanyBrandingModel();
        $this->userSettingsModel  = new UserSettingsModel();
    }

    public function customization()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        return redirect()->to(site_url('client_portal/profile') . '?tab=appearance');
    }

    public function saveCustomization()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'sidebar_color'     => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'header_color'      => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'background_color'  => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'sidebar_icon_mode' => 'required|in_list[auto,light,dark,original]',
            'font_family'       => 'required|in_list[default,classic,serif,rounded,mono]',
            'font_size'         => 'required|in_list[small,medium,large,xlarge]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // Validate the optional logo before saving the other preferences. This
        // prevents a rejected upload from producing a misleading partial save.
        $canManageLogo = \App\Libraries\PortalAccess::can('branding');
        $logoFile      = $canManageLogo ? $this->request->getFile('company_logo') : null;
        $logoMimeType  = null;
        $removeLogo    = $canManageLogo && $this->request->getPost('remove_company_logo') === '1';
        $allowedLogoTypes = [
            'image/png'     => 'png',
            'image/jpeg'    => 'jpg',
            'image/svg+xml' => 'svg',
        ];

        if (! $removeLogo && $logoFile && $logoFile->getError() !== UPLOAD_ERR_NO_FILE) {
            if (! $logoFile->isValid()) {
                return redirect()->back()->withInput()->with('error', 'Logo upload failed: ' . $logoFile->getErrorString());
            }

            $logoMimeType = (string) $logoFile->getMimeType();

            if (! isset($allowedLogoTypes[$logoMimeType])) {
                return redirect()->back()->withInput()->with('error', 'Logo must be PNG, JPG, or SVG.');
            }

            if ($logoFile->getSize() > 3 * 1024 * 1024) {
                return redirect()->back()->withInput()->with('error', 'Logo must be 3MB or below.');
            }
        }

        $userId = (int) $user->id;

        // Load existing settings first so we don't overwrite unrelated keys
        $existingSettings = $this->userSettingsModel->getSettingsArray($userId);

        // Merge only the color/icon keys, preserving everything else (e.g. payslip_password)
        $userSettings = array_merge($existingSettings, [
            'sidebar_color'     => (string) $this->request->getPost('sidebar_color'),
            'header_color'      => (string) $this->request->getPost('header_color'),
            'background_color'  => (string) $this->request->getPost('background_color'),
            'sidebar_icon_mode' => (string) $this->request->getPost('sidebar_icon_mode'),
            'font_family'       => (string) $this->request->getPost('font_family'),
            'font_size'         => (string) $this->request->getPost('font_size'),
        ]);

        if (! $this->userSettingsModel->saveSettings($userId, $userSettings)) {
            return redirect()->back()->withInput()->with('error', 'Unable to save your color preferences.');
        }

        // --- Logo upload: only for client_payroll_staff ---
        if ($canManageLogo) {
            if ($removeLogo) {
                $companyId = $this->resolveActiveCompanyId();
                $branding  = $this->getOrCreateBranding($companyId);

                if (! empty($branding['id'])) {
                    $logoRemoved = $this->brandingModel->update((int) $branding['id'], [
                        'logo_path'          => null,
                        'logo_original_name' => null,
                        'logo_mime_type'     => null,
                        'logo_size'          => null,
                        'updated_by'         => $userId,
                    ]);

                    if (! $logoRemoved) {
                        return redirect()->back()->withInput()->with('error', 'Unable to remove the company logo.');
                    }
                }

                if (! empty($branding['logo_path'])) {
                    $oldPath = ROOTPATH . 'public/' . ltrim((string) $branding['logo_path'], '/');
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            } elseif ($logoFile && $logoFile->isValid() && ! $logoFile->hasMoved()) {
                $companyId = $this->resolveActiveCompanyId();
                $branding  = $this->getOrCreateBranding($companyId);
                $uploadDir = ROOTPATH . 'public/uploads/company_logos/company_' . $companyId;

                if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0775, true) && ! is_dir($uploadDir)) {
                    return redirect()->back()->withInput()->with('error', 'Unable to create logo upload directory.');
                }

                $extension = $allowedLogoTypes[$logoMimeType];
                $newName   = 'logo_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

                if (! $logoFile->move($uploadDir, $newName, true)) {
                    return redirect()->back()->withInput()->with('error', 'Unable to upload company logo.');
                }

                $newPath = 'uploads/company_logos/company_' . $companyId . '/' . $newName;

                $logoData = [
                    'logo_path'          => $newPath,
                    'logo_original_name' => (string) $logoFile->getClientName(),
                    'logo_mime_type'     => $logoMimeType,
                    'logo_size'          => (int) $logoFile->getSize(),
                    'updated_by'         => $userId,
                ];

                $logoSaved = ! empty($branding['id'])
                    ? $this->brandingModel->update((int) $branding['id'], $logoData)
                    : $this->brandingModel->insert(array_merge($logoData, ['company_id' => $companyId])) !== false;

                if (! $logoSaved) {
                    @unlink($uploadDir . DIRECTORY_SEPARATOR . $newName);
                    return redirect()->back()->withInput()->with('error', 'Unable to save the company logo.');
                }

                if (! empty($branding['logo_path'])) {
                    $oldPath = ROOTPATH . 'public/' . ltrim((string) $branding['logo_path'], '/');
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }
        }

        $redirectTo = trim((string) $this->request->getPost('redirect_to'));
        $isSafeLocalRedirect = $redirectTo !== ''
            && preg_match('#^/(?!/)#', $redirectTo) === 1
            && ! str_contains($redirectTo, "\r")
            && ! str_contains($redirectTo, "\n");

        return redirect()
            ->to($isSafeLocalRedirect ? $redirectTo : site_url('settings/customization'))
            ->with('success', 'Customization updated successfully.');
    }

    private function getOrCreateBranding(int $companyId): array
    {
        return $this->brandingModel->getOrCreateDefault($companyId);
    }
}
