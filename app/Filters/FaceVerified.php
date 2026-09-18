<?php

namespace App\Filters;

use Config\FaceRecognition;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\TrustedDevicesService;

class FaceVerified implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $faceConfig = config(FaceRecognition::class);
        if (! $faceConfig->enabled) {
            return null;
        }

        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return null;
        }

        if ((bool) session('face_verified')) {
            return null;
        }

        $userId = (int) auth()->id();

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
