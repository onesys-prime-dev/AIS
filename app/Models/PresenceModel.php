<?php

namespace App\Models;

use CodeIgniter\Model;

class PresenceModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['online_status', 'last_active'];

    /*
        called on every heartbeat from the browser
        updates last_active timestamp and online_status
     */
    public function updatePresence(int $userId, string $status): void
    {
        $allowed = ['Online', 'Away', 'Offline'];
        $status  = in_array($status, $allowed) ? $status : 'Online';

        $this->update($userId, [
            'online_status' => $status,
            'last_active'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Called on every authenticated request via the TrackPresence filter —
     * marks the user Online from real activity anywhere in the portal, not
     * just the dedicated heartbeat ping. Throttled to at most once every 20
     * seconds so ordinary browsing doesn't write to the DB on every request.
     */
    public function touchActivity(int $userId): void
    {
        $db = \Config\Database::connect();
        $row = $db->table('users')->select('last_active')->where('id', $userId)->get()->getRowArray();
        $lastActive = $row['last_active'] ?? null;

        if ($lastActive && (time() - strtotime($lastActive)) < 20) {
            return;
        }

        $this->update($userId, [
            'online_status' => 'Online',
            'last_active'   => date('Y-m-d H:i:s'),
        ]);
    }

    /*
        last_active within 10 min  → trust the stored online_status (Online or Away)
        last_active 10–30 min ago  → Away (heartbeat stale)
        last_active 30+ min ago    → Offline
        never sent a heartbeat     → Offline
     */
    public function getAllPresenceStatuses(): array
    {
        $db = \Config\Database::connect();

        $rows = $db->table('users u')
            ->select('u.id as user_id, u.active as status, u.online_status, u.last_active, e.id as employee_id')
            ->join('employees e', 'e.user_id = u.id')
            ->where('u.active', 1)  
            ->get()
            ->getResultArray();
                log_message('debug', 'Presence rows: ' . json_encode($rows)); 

        $now     = time();
        $result  = [];

        foreach ($rows as $row) {
            $lastActive = $row['last_active'] ? strtotime($row['last_active']) : null;
            $diffMin    = $lastActive ? ($now - $lastActive) / 60 : PHP_INT_MAX;

            if ($diffMin <= 10) {
                $effective = $row['online_status'] ?? 'Online';
            } elseif ($diffMin <= 30) {
                $effective = 'Away';
            } else {
                $effective = 'Offline';
            }

            $result[$row['employee_id']] = $effective;
        }

        return $result;
    }

    // offline on logout
    public function setOffline(int $userId): void
    {
        $this->update($userId, ['online_status' => 'Offline']);
    }
}