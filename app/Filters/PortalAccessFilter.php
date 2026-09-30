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
        if ($request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'json') || strtolower($request->getMethod()) !== 'get') {
            // 'message' as well as 'error': most pages toast data.message.
            return $response->setJSON(['success'=>false, 'error'=>$message, 'message'=>$message]);
        }

        // A normal page visit (browser navigation): send the person back to
        // where they were, or their dashboard, with a toast — instead of
        // dropping them on a dead-end page. Never redirect to the denied
        // page itself, and keep the plain page for anything that isn't a
        // real navigation (e.g. a script fetching HTML).
        if (str_contains(strtolower($request->getHeaderLine('Accept')), 'text/html')) {
            $current = trim($request->getUri()->getRoutePath(), '/');
            $target  = site_url('client_portal/dashboard');
            $referer = $request->getHeaderLine('Referer');
            if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $request->getUri()->getHost()) {
                $refPath = trim((string) parse_url($referer, PHP_URL_PATH), '/');
                if ($refPath !== '' && !str_ends_with($refPath, $current)) $target = $referer;
            }
            if (!str_ends_with(trim((string) parse_url($target, PHP_URL_PATH), '/'), $current)) {
                return redirect()->to($target)->with('error', "You don't have access to that page.");
            }
        }
        return $response->setBody(view('errors/permission_denied'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
