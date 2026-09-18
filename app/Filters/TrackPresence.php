<?php

namespace App\Filters;

use App\Models\PresenceModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Marks the logged-in user Online on every authenticated request — a real
 * page load or action anywhere in the portal, not just the dedicated
 * heartbeat ping presence.js sends every 60s. This catches genuine activity
 * between heartbeat cycles (or if the browser tab is throttled/backgrounded
 * and JS timers get delayed) so "last active" reflects real usage, not just
 * whether the heartbeat happened to fire recently.
 */
final class TrackPresence implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth()->user();
        if ($user) {
            (new PresenceModel())->touchActivity((int) $user->id);
        }
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
