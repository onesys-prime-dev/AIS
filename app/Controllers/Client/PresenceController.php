<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\PresenceModel;
use CodeIgniter\HTTP\ResponseInterface;

class PresenceController extends BaseController
{
    protected PresenceModel $presenceModel; 

    public function __construct()
    {
        $this->presenceModel = new PresenceModel();
    }

    /*
        POST client_portal/presence/heartbeat
        Called every 5 minutes by the logged-in user's browser.
        Body: { "status": "Online" | "Away" }
     */
    public function heartbeat(): ResponseInterface
    {
        $user = auth()->user();

        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }

        // getJSON(true) is null for a missing/invalid body — don't index into it.
        $body = $this->request->getJSON(true);
        $status = is_array($body) ? ($body['status'] ?? 'Online') : 'Online';
        $this->presenceModel->updatePresence((int) $user->id, $status);

        return $this->response->setJSON(['ok' => true]);
    }

    /*
        GET client_portal/presence/statuses
        Called every 30 seconds by the employee management / profile pages.
        Returns a map of employee_id => effective status.
     */
    public function statuses(): ResponseInterface
    {
        $user = auth()->user();

        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }

        $statuses = $this->presenceModel->getAllPresenceStatuses();

        if (!\App\Libraries\PortalAccess::can('employees')) {
            $ids = db_connect()->table('employees')->select('id')->whereIn('user_id', [(int) $user->id, ...\App\Libraries\PortalAccess::teamUserIds($user)])->get()->getResultArray();
            $statuses = array_intersect_key($statuses, array_flip(array_column($ids, 'id')));
        }
        return $this->response->setJSON($statuses);
    }
}
