<?php

namespace App\Services\Client;

use CodeIgniter\Database\BaseConnection;
use Throwable;

/**
 * Browser push for attendance reminders (Web Push with VAPID). Used by AttendanceReminderDelivery; the employee's
 * devices are the rows in push_subscriptions, added from their profile.
 *
 * Needs the minishlink/web-push library and VAPID keys in .env (generate with `php spark attendance:push-keys`):
 *   push.vapidPublic  = ...
 *   push.vapidPrivate = ...
 *   push.subject      = mailto:someone@your-company.example
 *
 * Without them push is simply "not configured": nothing is sent and the profile page does not offer it.
 */
final class ReminderPushSender
{
    private BaseConnection $db;
    /** @var callable|null test double: (array $subscription, string $payload) => array{ok: bool, gone: bool, error: ?string} */
    private $transport;

    public function __construct(?BaseConnection $db = null, ?callable $transport = null)
    {
        $this->db        = $db ?? \Config\Database::connect();
        $this->transport = $transport;
    }

    public static function publicKey(): string
    {
        return trim((string) env('push.vapidPublic', ''));
    }

    public static function configured(): bool
    {
        return self::publicKey() !== '' && trim((string) env('push.vapidPrivate', '')) !== '';
    }

    /** Can push actually go out (keys set and the library installed, or a test transport supplied). */
    public function available(): bool
    {
        return $this->transport !== null || (self::configured() && class_exists(\Minishlink\WebPush\WebPush::class));
    }

    /**
     * Pushes one reminder to every device the employee registered.
     *
     * @return string sent | no_device | failed | off  (off = push is not set up)
     */
    public function sendToUser(int $userId, array $item): string
    {
        if (! $this->available()) return 'off';
        if (! $this->db->tableExists('push_subscriptions')) return 'off';

        $subs = $this->db->table('push_subscriptions')->where('user_id', $userId)->get()->getResultArray();
        if (! $subs) return 'no_device';

        $payload = json_encode([
            'title' => $item['title'],
            'body'  => $item['body'],
            'tag'   => $item['key'],
            'group' => implode('|', array_filter([explode('|', (string) $item['key'])[0] ?? '', explode('|', (string) $item['key'])[1] ?? '', explode('|', (string) $item['key'])[3] ?? ''], static fn ($x) => $x !== '')),
            'url'   => site_url('client_portal/dashboard'),
        ], JSON_UNESCAPED_UNICODE);

        $delivered = 0;
        foreach ($subs as $s) {
            $r = $this->push($s, $payload);
            if ($r['ok']) {
                $delivered++;
                $this->db->table('push_subscriptions')->where('id', $s['id'])->update(['failed_count' => 0, 'last_used_at' => date('Y-m-d H:i:s')]);
            } elseif ($r['gone']) {
                // The browser says this device is gone (uninstalled, permission removed): forget it.
                $this->db->table('push_subscriptions')->where('id', $s['id'])->delete();
            } else {
                $failed = (int) $s['failed_count'] + 1;
                if ($failed >= 5) $this->db->table('push_subscriptions')->where('id', $s['id'])->delete();
                else $this->db->table('push_subscriptions')->where('id', $s['id'])->update(['failed_count' => $failed]);
            }
        }
        return $delivered > 0 ? 'sent' : 'failed';
    }

    /** @return array{ok: bool, gone: bool, error: ?string} */
    private function push(array $sub, string $payload): array
    {
        if ($this->transport !== null) return ($this->transport)($sub, $payload);

        try {
            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject'    => (string) env('push.subject', 'mailto:admin@example.com'),
                    'publicKey'  => self::publicKey(),
                    'privateKey' => (string) env('push.vapidPrivate', ''),
                ],
            ], ['TTL' => 1800, 'urgency' => 'high']);

            $report = $webPush->sendOneNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint'  => $sub['endpoint'],
                    'publicKey' => $sub['p256dh'],
                    'authToken' => $sub['auth'],
                ]),
                $payload
            );
            return [
                'ok'    => $report->isSuccess(),
                'gone'  => $report->isSubscriptionExpired(),
                'error' => $report->isSuccess() ? null : $report->getReason(),
            ];
        } catch (Throwable $e) {
            log_message('error', 'Attendance reminder push failed: {m}', ['m' => $e->getMessage()]);
            return ['ok' => false, 'gone' => false, 'error' => $e->getMessage()];
        }
    }
}
