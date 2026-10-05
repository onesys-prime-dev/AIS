<?php

namespace App\Filters;

use Config\FaceRecognition;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\AccountSettings;
use App\Libraries\TrustedDevicesService;

class FaceVerified implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return null;
        }

        // Per-account override (admin-managed, Face Recognition page) wins
        // over the global switch either way: an account explicitly marked
        // "Always Require" still gets gated even if the feature is globally
        // off, and one marked "Exempt" is skipped even if it's globally on.
        // No override (the default for every account) falls back to the
        // global switch, so nothing changes until an admin sets one.
        $userId = (int) auth()->id();
        $override = AccountSettings::getFaceRequirement($userId);
        $required = $override ?? config(FaceRecognition::class)->enabled;
        if (! $required) {
            return null;
        }

        if ((bool) session('face_verified')) {
            return null;
        }

        $trusted = new TrustedDevicesService();

        if ($trusted->isTrusted($userId)) {
            session()->set('face_verified', true);
            return null;
        }

        return redirect()->to('/face-verify');

    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
