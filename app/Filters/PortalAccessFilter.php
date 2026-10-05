<?php
namespace App\Filters;

use App\Libraries\PortalAccess;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class PortalAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getRoutePath(), '/');
        if (!preg_match('~^(?:client_portal(?:/|$)|settings(?:/|$)|register$)~i', $path)) return null;
        $user = auth()->user();
        $required = PortalAccess::requirement($path);
        if ($required === 'register') {
            if (strtolower($request->getMethod()) !== 'post') return null;
            $role = (string) ($request->getPost('role') ?? 'employee');
            if ($role === 'employee' || ($user && PortalAccess::can('roles', $user))) return null;
            return $this->denied($request);
        }
        // Authentication still runs for every protected request, including unknown routes.
        if (!$user) return redirect()->to(site_url('login'));
        $allowed = match ($required) {
            'self' => true,
            'edit_employee' => PortalAccess::canEditEmployee((int) basename($path)),
            'attendance_roster' => PortalAccess::reviewsAttendance($user),
            'review_attendance' => PortalAccess::canReviewEmployee((int) $request->getGet('employee_id'), $user),
            'review_request' => PortalAccess::canReviewRequest((int) basename($path), $user),
            'access_control' => PortalAccess::canManageAccessControl($user),
            'deny' => false,
            default => PortalAccess::can($required, $user),
        };
        if (!$allowed) return $this->denied($request);
        return null;
    }

    private function denied(RequestInterface $request): ResponseInterface
    {
        $message  = 'You do not have permission to access this resource.';
        $response = service('response')->setStatusCode(403)->noCache();
        $accept = strtolower($request->getHeaderLine('Accept'));
        $isGet  = strtolower($request->getMethod()) === 'get';
        // A real browser navigation (link, typed URL, or a plain <form> post —
        // not a fetch/XHR call, which never asks for text/html first).
        $isNavigation = !$request->isAJAX() && !str_contains($accept, 'json') && str_contains($accept, 'text/html');

        if (!$isNavigation) {
            // Background call: 'message' as well as 'error', since most pages
            // toast data.message (and toast.js toasts any 403 JSON itself).
            return $response->setJSON(['success'=>false, 'error'=>$message, 'message'=>$message]);
        }

        // Browser navigation: send the person back to where they were, or
        // their dashboard, with a toast — instead of dropping them on a
        // dead-end page or raw JSON. Never redirect to the denied page itself.
        if ($isNavigation) {
            $current = trim($request->getUri()->getRoutePath(), '/');
            $target  = site_url('client_portal/dashboard');
            $referer = $request->getHeaderLine('Referer');
            if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $request->getUri()->getHost()) {
                $refPath = trim((string) parse_url($referer, PHP_URL_PATH), '/');
                if ($refPath !== '' && !str_ends_with($refPath, $current)) $target = $referer;
            }
            if (!str_ends_with(trim((string) parse_url($target, PHP_URL_PATH), '/'), $current)) {
                // The toast is triggered by a URL flag that toast.js shows and strips,
                // not by session flash data: flash is dropped if any other request
                // (a background poll) touches the session before the page loads.
                $flag   = $isGet ? 'page' : 'action';
                $target = preg_replace('/([?&])access_denied=[^&#]*&?/', '$1', $target);
                $target = rtrim($target, '?&');
                $hash   = '';
                if (str_contains($target, '#')) [$target, $hash] = explode('#', $target, 2) + [1 => ''];
                return redirect()->to($target . (str_contains($target, '?') ? '&' : '?') . 'access_denied=' . $flag . ($hash !== '' ? '#' . $hash : ''));
            }
        }
        return $response->setBody(view('errors/permission_denied'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
