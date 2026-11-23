<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\AttendanceReminderDelivery;
use App\Services\Client\AttendanceReminderService;
use DateTimeImmutable;
use DateTimeZone;

/**
 * What the signed-in employee sees of the attendance reminders: the pop-ups that are due right now, dismissing one,
 * and their own switches. Everything is about the signed-in user only.
 */
class ReminderController extends BaseController
{
    public const MAX_DEVICES = 10;
    /** "Send me a test" emails per person per hour (it uses the company's mail quota). */
    public const TEST_LIMIT_PER_HOUR = 5;

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    }

    /** The pop-ups due right now (built live, so they vanish the moment the employee clocks in / out / ends the break). */
    public function pending()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }

        $service = new AttendanceReminderService();
        $items   = array_values(array_filter(
            $service->due($this->now(), (int) $user->id),
            static fn (array $i) => $i['channels']['popup']
        ));

        $dismissed = [];
        if ($items) {
            $rows = $this->db()->table('attendance_reminders')
                ->select('shift_date, type, step, slot')
                ->where('user_id', (int) $user->id)->where('dismissed_at IS NOT NULL', null, false)
                ->whereIn('shift_date', array_unique(array_column($items, 'shift_date')))
                ->get()->getResultArray();
            foreach ($rows as $r) $dismissed[$r['shift_date'] . '|' . $r['type'] . '|' . $r['step'] . '|' . (int) $r['slot']] = true;
        }

        $out = [];
        $sound = true;
        foreach ($items as $i) {
            if (isset($dismissed[$i['key']])) continue;
            $sound = $sound && $i['sound'];
            $out[] = ['key' => $i['key'], 'type' => $i['type'], 'step' => $i['step'], 'title' => $i['title'], 'body' => $i['body'], 'severity' => $i['severity']];
        }

        return $this->response->setJSON(['items' => $out, 'sound' => $sound, 'dashboard_url' => site_url('client_portal/dashboard')]);
    }

    /** Hide one pop-up for the day (later steps still show). Only a reminder that is actually due can be dismissed. */
    public function dismiss()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        $key = $this->request->getPost('key');
        if (! is_string($key) || ! preg_match('/^(\d{4}-\d{2}-\d{2})\|([a-z_]+)\|(-?[0-9a-z]+)\|(\d+)$/', $key, $m)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid reminder.']);
        }

        $item = (new AttendanceReminderService())->find($this->now(), (int) $user->id, $key);
        if ($item) {
            $now = date('Y-m-d H:i:s');
            $this->db()->query(
                "INSERT IGNORE INTO attendance_reminders (user_id, shift_date, type, step, slot, due_at, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)",
                [(int) $user->id, $m[1], $m[2], $m[3], (int) $m[4], $item['due_at'], $now]
            );
            $this->db()->table('attendance_reminders')
                ->where('user_id', (int) $user->id)->where('shift_date', $m[1])->where('type', $m[2])->where('step', $m[3])->where('slot', (int) $m[4])
                ->update(['dismissed_at' => $now]);
        }
        return $this->response->setJSON(['success' => true]);
    }

    /** The employee's own switches (no row = everything on), plus whether email and push can reach them. */
    public function preferences()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        return $this->response->setJSON($this->preferenceState((int) $user->id));
    }

    public function savePreferences()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }

        $row = ['user_id' => (int) $user->id, 'updated_at' => date('Y-m-d H:i:s')];
        foreach (['reminders_enabled', 'email_enabled', 'push_enabled', 'sound_enabled'] as $field) {
            $v = $this->request->getPost($field);
            if ($v !== null) $row[$field] = in_array((string) $v, ['1', 'true', 'on'], true) ? 1 : 0;
        }
        $exists = $this->db()->table('attendance_reminder_prefs')->where('user_id', (int) $user->id)->countAllResults();
        if ($exists) $this->db()->table('attendance_reminder_prefs')->where('user_id', (int) $user->id)->update($row);
        else $this->db()->table('attendance_reminder_prefs')->insert($row);

        return $this->response->setJSON(['success' => true, 'preferences' => $this->preferenceState((int) $user->id)]);
    }

    /**
     * Registers this browser for push. The endpoint is where OUR server will POST, so only the real push services are
     * accepted (a signed-in employee must not be able to make the server call an arbitrary address).
     */
    public function pushSubscribe()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        $endpoint = $this->request->getPost('endpoint');
        $p256dh   = $this->request->getPost('p256dh');
        $authKey  = $this->request->getPost('auth');
        if (! is_string($endpoint) || ! is_string($p256dh) || ! is_string($authKey)
            || ! self::isPushEndpoint($endpoint)
            || ! preg_match('/^[A-Za-z0-9_-]{20,200}$/', $p256dh) || ! preg_match('/^[A-Za-z0-9_-]{10,100}$/', $authKey)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'That device could not be registered.']);
        }

        $hash = hash('sha256', $endpoint);
        $row  = ['user_id' => (int) $user->id, 'endpoint' => $endpoint, 'p256dh' => $p256dh, 'auth' => $authKey, 'user_agent' => mb_substr((string) $this->request->getUserAgent(), 0, 255), 'failed_count' => 0];
        $db = $this->db();
        if ($db->table('push_subscriptions')->where('endpoint_hash', $hash)->countAllResults()) {
            $db->table('push_subscriptions')->where('endpoint_hash', $hash)->update($row);   // same browser, possibly a different account now
        } else {
            // A handful of devices per person is plenty (every device costs a request per reminder): the oldest make room.
            $ids = array_column($db->table('push_subscriptions')->select('id')->where('user_id', (int) $user->id)->orderBy('id', 'DESC')->get()->getResultArray(), 'id');
            if (count($ids) >= self::MAX_DEVICES) {
                $db->table('push_subscriptions')->whereIn('id', array_slice($ids, self::MAX_DEVICES - 1))->delete();
            }
            $db->table('push_subscriptions')->insert($row + ['endpoint_hash' => $hash, 'created_at' => date('Y-m-d H:i:s')]);
        }
        return $this->response->setJSON(['success' => true, 'preferences' => $this->preferenceState((int) $user->id)]);
    }

    /**
     * The page asks, for the browser's current push subscription, whether it still belongs to the signed-in person. On a
     * shared computer the next person to sign in must not keep receiving the previous person's reminders: a device that is
     * registered to someone else is forgotten (and the page then drops the browser's subscription).
     */
    public function pushCheck()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        $endpoint = $this->request->getPost('endpoint');
        if (! is_string($endpoint) || $endpoint === '') {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }
        $row = $this->db()->table('push_subscriptions')->select('id, user_id')->where('endpoint_hash', hash('sha256', $endpoint))->get()->getRowArray();
        if ($row && (int) $row['user_id'] !== (int) $user->id) {
            $this->db()->table('push_subscriptions')->where('id', $row['id'])->delete();
            return $this->response->setJSON(['success' => true, 'mine' => false, 'drop' => true]);
        }
        return $this->response->setJSON(['success' => true, 'mine' => (bool) $row, 'drop' => ! $row]);
    }

    public function pushUnsubscribe()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        $endpoint = $this->request->getPost('endpoint');
        if (is_string($endpoint) && $endpoint !== '') {
            $this->db()->table('push_subscriptions')->where('user_id', (int) $user->id)->where('endpoint_hash', hash('sha256', $endpoint))->delete();
        }
        return $this->response->setJSON(['success' => true, 'preferences' => $this->preferenceState((int) $user->id)]);
    }

    /** Only the browsers' own push services. */
    public static function isPushEndpoint(string $endpoint): bool
    {
        if (strlen($endpoint) > 1000 || ! preg_match('~^https://[^/@\s]+/~', $endpoint)) return false;
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));
        foreach (['fcm.googleapis.com', 'push.services.mozilla.com', 'push.apple.com', 'notify.windows.com'] as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) return true;
        }
        return false;
    }

    /** "Send me a test": one email to the employee's own address. */
    public function test()
    {
        $user = auth()->user();
        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        $state = $this->preferenceState((int) $user->id);

        $cacheKey = 'rem_test_' . (int) $user->id;
        $used = (int) (cache($cacheKey) ?? 0);
        if ($used >= self::TEST_LIMIT_PER_HOUR) {
            return $this->response->setStatusCode(429)->setJSON(['success' => false, 'status' => 'limited', 'message' => 'You have sent several test emails already. Please try again in an hour.']);
        }
        $status = (new AttendanceReminderDelivery())->sendTest((int) $user->id, $state['name'], $state['email'], $this->now());
        if (in_array($status, ['sent', 'failed'], true)) {
            cache()->save($cacheKey, $used + 1, 3600);   // only real attempts count
        }
        $messages = [
            'sent'      => 'A test reminder was sent to ' . ($state['email'] ?? '') . '.',
            'no_email'  => 'Your account has no email address, so email reminders cannot reach you.',
            'no_sender' => 'The portal has no sending address configured yet. Ask an administrator.',
            'failed'    => 'The test email could not be sent. Please try again later.',
        ];
        return $this->response->setJSON(['success' => $status === 'sent', 'status' => $status, 'message' => $messages[$status] ?? 'Done.']);
    }

    private function preferenceState(int $userId): array
    {
        $db = $this->db();
        $p = $db->table('attendance_reminder_prefs')->where('user_id', $userId)->get()->getRowArray();
        $identity = $db->table('auth_identities')->select('secret')->where('user_id', $userId)->where('type', 'email_password')->get()->getRowArray();
        $emp = $db->table('employees')->select("TRIM(CONCAT(first_name, ' ', last_name)) AS name", false)->where('user_id', $userId)->get()->getRowArray();
        $devices = $db->tableExists('push_subscriptions') ? $db->table('push_subscriptions')->where('user_id', $userId)->countAllResults() : 0;

        return [
            'reminders_enabled' => $p ? (bool) (int) $p['reminders_enabled'] : true,
            'email_enabled'     => $p ? (bool) (int) $p['email_enabled'] : true,
            'push_enabled'      => $p ? (bool) (int) $p['push_enabled'] : true,
            'sound_enabled'     => $p ? (bool) (int) $p['sound_enabled'] : true,
            'email'             => $identity['secret'] ?? null,
            'name'              => ($emp['name'] ?? '') !== '' ? $emp['name'] : (string) (auth()->user()->username ?? ''),
            'can_send_mail'     => trim((string) config('Email')->fromEmail) !== '',
            'push_devices'      => $devices,
            'push_available'    => \App\Services\Client\ReminderPushSender::configured() && class_exists(\Minishlink\WebPush\WebPush::class),
            'push_public_key'   => \App\Services\Client\ReminderPushSender::publicKey(),
            'feature_enabled'   => ! empty(AttendanceReminderService::loadConfig()['enabled']),
        ];
    }

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }
}
