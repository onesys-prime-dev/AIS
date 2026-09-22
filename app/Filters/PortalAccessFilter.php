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
        $response = service('response')->setStatusCode(403)->noCache();
        if ($request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'json') || strtolower($request->getMethod()) !== 'get') {
            return $response->setJSON(['success'=>false, 'error'=>'You do not have permission to access this resource.']);
        }
        return $response->setBody(view('errors/permission_denied'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
