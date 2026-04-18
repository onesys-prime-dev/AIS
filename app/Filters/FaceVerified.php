<?php

namespace App\Filters;

use Config\CompreFace;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FaceVerified implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $faceConfig = config(CompreFace::class);
        if (! $faceConfig->enabled) {
            return null;
        }

        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return null;
        }

        if ((bool) session('face_verified')) {
            return null;
        }

        return redirect()->to('/face-verify');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
