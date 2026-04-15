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

        $companyId = $this->resolveActiveCompanyId();
        $branding  = $this->getOrCreateBranding($companyId);

        // Load this user's saved color preferences (may override company defaults in the view)
        $userId       = (int) $user->id;
        $userSettings = $this->userSettingsModel->getSettingsArray($userId);

        // Merge: user prefs win over company branding for color fields
        $colorKeys = ['sidebar_color', 'header_color', 'background_color', 'sidebar_icon_mode'];
        foreach ($colorKeys as $key) {
            if (! empty($userSettings[$key])) {
                $branding[$key] = $userSettings[$key];
            }
        }

        $data = [
            'title'         => 'Customization',
            'branding'      => $branding,
            'logoUrl'       => ! empty($branding['logo_path']) ? base_url((string) $branding['logo_path']) : null,
            'canManageLogo' => auth()->user()->inGroup('client_payroll_staff'),
        ];

        return view('settings/customization', $data);
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
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userId = (int) $user->id;

        // --- Save color/icon preferences per user ---
        $userSettings = [
            'sidebar_color'     => (string) $this->request->getPost('sidebar_color'),
            'header_color'      => (string) $this->request->getPost('header_color'),
            'background_color'  => (string) $this->request->getPost('background_color'),
            'sidebar_icon_mode' => (string) $this->request->getPost('sidebar_icon_mode'),
        ];

        if (! $this->userSettingsModel->saveSettings($userId, $userSettings)) {
            return redirect()->back()->withInput()->with('error', 'Unable to save your color preferences.');
        }

        // --- Logo upload: only for client_payroll_staff ---
        if (auth()->user()->inGroup('client_payroll_staff')) {
            $logoFile = $this->request->getFile('company_logo');

            if ($logoFile && $logoFile->isValid() && ! $logoFile->hasMoved()) {
                $allowedMime = ['image/png', 'image/jpeg', 'image/svg+xml'];
                $mimeType    = (string) $logoFile->getClientMimeType();

                if (! in_array($mimeType, $allowedMime, true)) {
                    return redirect()->back()->withInput()->with('error', 'Logo must be PNG, JPG, or SVG.');
                }

                if ($logoFile->getSizeByUnit('mb') > 3) {
                    return redirect()->back()->withInput()->with('error', 'Logo must be 3MB or below.');
                }

                $companyId = $this->resolveActiveCompanyId();
                $branding  = $this->getOrCreateBranding($companyId);
                $uploadDir = ROOTPATH . 'public/uploads/company_logos/company_' . $companyId;

                if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0775, true) && ! is_dir($uploadDir)) {
                    return redirect()->back()->withInput()->with('error', 'Unable to create logo upload directory.');
                }

                $extension = strtolower((string) $logoFile->getExtension());
                $newName   = 'logo_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

                if (! $logoFile->move($uploadDir, $newName, true)) {
                    return redirect()->back()->withInput()->with('error', 'Unable to upload company logo.');
                }

                $newPath = 'uploads/company_logos/company_' . $companyId . '/' . $newName;

                if (! empty($branding['logo_path'])) {
                    $oldPath = ROOTPATH . 'public/' . ltrim((string) $branding['logo_path'], '/');
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $logoData = [
                    'logo_path'          => $newPath,
                    'logo_original_name' => (string) $logoFile->getClientName(),
                    'logo_mime_type'     => $mimeType,
                    'logo_size'          => (int) $logoFile->getSize(),
                    'updated_by'         => $userId,
                ];

                if (! empty($branding['id'])) {
                    $this->brandingModel->update((int) $branding['id'], $logoData);
                } else {
                    $this->brandingModel->insert(array_merge($logoData, ['company_id' => $companyId]));
                }
            }
        }

        return redirect()->to(site_url('settings/customization'))->with('success', 'Customization updated successfully.');
    }

    private function getOrCreateBranding(int $companyId): array
    {
        $branding = $this->brandingModel->where('company_id', $companyId)->first();

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
}