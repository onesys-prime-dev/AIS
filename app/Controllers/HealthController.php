<?php

namespace App\Controllers;

use Throwable;

/**
 * Unauthenticated deploy-verification endpoint — GET /health. Proves the app
 * booted and can actually reach its database, not just that nginx/PHP-FPM
 * answered. Used by .github/workflows/deploy-reusable.yml's post-deploy
 * health check (retries this a few times before failing the deploy) — keep
 * this cheap and side-effect-free.
 */
class HealthController extends BaseController
{
    public function index()
    {
        try {
            \Config\Database::connect()->query('SELECT 1');
        } catch (Throwable $e) {
            return $this->response->setStatusCode(503)->setJSON([
                'status' => 'error',
                'message' => 'Database unreachable.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
        ]);
    }
}
