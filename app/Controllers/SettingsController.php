<?php

namespace App\Controllers;

use App\Models\Client\CompanyBrandingModel;

class SettingsController extends BaseController
{
    protected CompanyBrandingModel $brandingModel;

    public function __construct()
    {
        $this->brandingModel = new CompanyBrandingModel();
    }

    public function customization()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $companyId = $this->resolveActiveCompanyId();
        $branding = $this->getOrCreateBranding($companyId);

        $data = [
            'title' => 'Customization',
            'branding' => $branding,
            'logoUrl' => ! empty($branding['logo_path']) ? base_url((string) $branding['logo_path']) : null,
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
            'sidebar_color' => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'header_color' => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'background_color' => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'sidebar_icon_mode' => 'required|in_list[auto,light,dark,original]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $companyId = $this->resolveActiveCompanyId();
        $branding = $this->getOrCreateBranding($companyId);

        $updateData = [
            'sidebar_color' => (string) $this->request->getPost('sidebar_color'),
            'header_color' => (string) $this->request->getPost('header_color'),
            'background_color' => (string) $this->request->getPost('background_color'),
            'sidebar_icon_mode' => (string) $this->request->getPost('sidebar_icon_mode'),
            'updated_by' => (int) $user->id,
        ];

        $logoFile = $this->request->getFile('company_logo');

        if ($logoFile && $logoFile->isValid() && ! $logoFile->hasMoved()) {
            $allowedMime = ['image/png', 'image/jpeg', 'image/svg+xml'];
            $mimeType = (string) $logoFile->getClientMimeType();

            if (! in_array($mimeType, $allowedMime, true)) {
                return redirect()->back()->withInput()->with('error', 'Logo must be PNG, JPG, or SVG.');
            }

            if ($logoFile->getSizeByUnit('mb') > 3) {
                return redirect()->back()->withInput()->with('error', 'Logo must be 3MB or below.');
            }

            $uploadDir = ROOTPATH . 'public/uploads/company_logos/company_' . $companyId;
            if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0775, true) && ! is_dir($uploadDir)) {
                return redirect()->back()->withInput()->with('error', 'Unable to create logo upload directory.');
            }

            $extension = strtolower((string) $logoFile->getExtension());
            $newName = 'logo_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

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

            $updateData['logo_path'] = $newPath;
            $updateData['logo_original_name'] = (string) $logoFile->getClientName();
            $updateData['logo_mime_type'] = $mimeType;
            $updateData['logo_size'] = (int) $logoFile->getSize();
        }

        $saved = false;

        if (! empty($branding['id'])) {
            $saved = (bool) $this->brandingModel->update((int) $branding['id'], $updateData);
        } else {
            $saved = (bool) $this->brandingModel->insert(array_merge($updateData, [
                'company_id' => $companyId,
            ]));
        }

        if (! $saved) {
            $errors = $this->brandingModel->errors();
            $message = ! empty($errors) ? implode(' ', $errors) : 'Unable to save customization changes.';

            return redirect()->back()->withInput()->with('error', $message);
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
            'id' => null,
            'company_id' => $companyId,
            'logo_path' => null,
            'logo_original_name' => null,
            'logo_mime_type' => null,
            'logo_size' => null,
            'sidebar_color' => '#1e1e1e',
            'header_color' => '#ffffff',
            'background_color' => '#f5f6fa',
            'sidebar_icon_mode' => 'auto',
        ];
    }
}