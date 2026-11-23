<?php

namespace App\Services\Client;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use Throwable;

/**
 * Sends the reminders the rule engine says are due: the emails (and, in the push phase, browser pushes) plus the
 * Team Head / admin escalation. The portal pop-ups need no sending: the page reads the same engine live.
 *
 * Safe to run twice at once and to re-run: a unique row per employee/day/type/step/slot is claimed ("sending") before
 * anything goes out, the engine is asked again just before sending (they may have clocked in a second ago), and a
 * failed send is retried up to MAX_ATTEMPTS while that step is still the current one.
 */
final class AttendanceReminderDelivery
{
    public const MAX_ATTEMPTS = 3;

    private BaseConnection $db;
    private AttendanceReminderService $service;
    /** @var callable(string, string, string): array{0: bool, 1: ?string} (to, subject, html) => [ok, error] */
    private $mailer;
    /** @var callable(array): string|null (item) => push status */
    private $pusher;
    private bool $customMailer;

    public function __construct(?BaseConnection $db = null, ?AttendanceReminderService $service = null, ?callable $mailer = null, ?callable $pusher = null)
    {
        $this->db           = $db ?? \Config\Database::connect();
        $this->service      = $service ?? new AttendanceReminderService($this->db);
        $this->customMailer = $mailer !== null;
        $this->mailer       = $mailer ?? [$this, 'smtpSend'];
        $this->pusher       = $pusher;
    }

    /** True when real mail can go out (a From address is configured), or a test mailer was supplied. */
    public function canSendMail(): bool
    {
        return $this->customMailer || trim((string) config('Email')->fromEmail) !== '';
    }

    /**
     * @return array<string, mixed> counts and a line per reminder, for the command to print
     */
    public function run(DateTimeImmutable $now, bool $dryRun = false): array
    {
        $report = ['now' => $now->format('Y-m-d H:i:s'), 'dry_run' => $dryRun, 'due' => 0, 'emails_sent' => 0, 'emails_failed' => 0,
            'no_email' => 0, 'no_sender' => 0, 'pushed' => 0, 'skipped' => 0, 'escalations_sent' => 0, 'lines' => []];

        if (! $dryRun) $this->releaseStuck();

        $items = $this->service->due($now);
        $report['due'] = count($items);

        foreach ($items as $item) {
            $wantsMail = $item['channels']['email'];
            $wantsPush = $item['channels']['push'] && $this->pusher !== null;
            $label = '[' . $item['type'] . ' ' . $item['step'] . '] ' . $item['name'];
            if (! $wantsMail && ! $wantsPush) {
                $report['lines'][] = $label . ' - pop-up only';
                continue;
            }

            $channels = array_values(array_filter([$wantsMail ? 'email' : null, $wantsPush ? 'push' : null]));
            if ($dryRun) {
                $note = '';
                if ($wantsMail && ! $item['email']) $note = ' (no email address)';
                elseif ($wantsMail && ! $this->canSendMail()) $note = ' (no sender configured)';
                $report['lines'][] = $label . ' - would send: ' . implode(', ', $channels) . ' + pop-up' . $note;
                continue;
            }

            $row = $this->claim($item);
            if ($row === null) { $report['skipped']++; continue; }

            // They may have acted since the list was built.
            if ($this->service->find($now, (int) $item['user_id'], $item['key']) === null) {
                $this->finish($row, 'skipped', null, null, null);
                $report['skipped']++;
                continue;
            }

            // A retry (the email failed last time) must not repeat what already went out.
            $emailStatus = $wantsMail ? ($row['email_status'] ?? null) : null;
            $pushStatus  = $wantsPush ? ($row['push_status'] ?? null) : null;
            $error = null;
            if ($wantsMail && $emailStatus !== 'sent') {
                $emailStatus = null;
                if (! $item['email']) { $emailStatus = 'no_email'; $report['no_email']++; }
                elseif (! $this->canSendMail()) { $emailStatus = 'no_sender'; $report['no_sender']++; }
                else {
                    [$ok, $err] = ($this->mailer)($item['email'], $item['title'], $this->render('emails/attendance_reminder', [
                        'title' => $item['title'], 'name' => $item['name'], 'body' => $item['body'],
                        'link' => site_url('client_portal/dashboard'), 'sentAt' => $now->format('M j, g:i A'),
                    ]));
                    $emailStatus = $ok ? 'sent' : 'failed';
                    $error = $ok ? null : $err;
                    $report[$ok ? 'emails_sent' : 'emails_failed']++;
                }
            }
            if ($wantsPush && $pushStatus !== 'sent') {
                $pushStatus = null;
                try { $pushStatus = ($this->pusher)($item); } catch (Throwable $e) { $pushStatus = 'failed'; $error = $error ?? $e->getMessage(); }
                if ($pushStatus === 'sent') $report['pushed']++;
            }

            // A failed email is retried on the next run while this step is current; anything else is final.
            $this->finish($row, $emailStatus === 'failed' ? 'failed' : 'sent', $emailStatus, $pushStatus, $error);
            $report['lines'][] = $label . ' - ' . ($emailStatus ?? 'no email') . ($pushStatus ? ', push ' . $pushStatus : '');
        }

        $this->escalate($now, $items, $report, $dryRun);
        return $report;
    }

    /** A test reminder to one employee's own address (the "send me a test" button). Returns a status string. */
    public function sendTest(int $userId, string $name, ?string $email, DateTimeImmutable $now): string
    {
        if (! $email) return 'no_email';
        if (! $this->canSendMail()) return 'no_sender';
        [$ok, $err] = ($this->mailer)($email, 'Test reminder', $this->render('emails/attendance_reminder', [
            'title' => 'Test reminder', 'name' => $name,
            'body' => 'This is a test. Your attendance reminders will look like this.',
            'link' => site_url('client_portal/dashboard'), 'sentAt' => $now->format('M j, g:i A'),
        ]));
        return $ok ? 'sent' : 'failed';
    }

    /* ------------------------------------------------------------------------------------------- escalation */

    /**
     * Once the escalation minute has passed: one grouped email per recipient (the employee's Team Head and every admin /
     * super admin) listing everyone still missing. Never sent to the employee, and never for an employee who opted out
     * (the engine already left those out).
     *
     * Every (employee, recipient) pair has its own log row, so when one recipient's email fails only that recipient is
     * retried, and the others are never sent the same email twice.
     */
    private function escalate(DateTimeImmutable $now, array $items, array &$report, bool $dryRun): void
    {
        $esc = array_values(array_filter($items, static fn ($i) => $i['escalate']));
        if (! $esc) return;

        $byRecipient = [];   // email => ['name', 'rows' => [[item, claimed row]]]
        foreach ($esc as $item) {
            if (! $dryRun && $this->service->find($now, (int) $item['user_id'], $item['key']) === null) continue;   // they just clocked in

            $people = $this->recipients((int) $item['user_id'], $item['department_id']);
            if (! $people) {
                if ($dryRun) { $report['lines'][] = '[escalation] ' . $item['name'] . ' - nobody to tell (no Team Head or admin with an email)'; continue; }
                $row = $this->claim(['user_id' => $item['user_id'], 'shift_date' => $item['shift_date'], 'type' => 'escalation', 'step' => 'none', 'slot' => 0, 'due_at' => $item['due_at']]);
                if ($row) {
                    $this->finish($row, 'sent', 'no_email', null, null);
                    $report['lines'][] = '[escalation] ' . $item['name'] . ' - nobody to tell (no Team Head or admin with an email)';
                }
                continue;
            }
            foreach ($people as $r) {
                $row = null;
                if (! $dryRun) {
                    $row = $this->claim(['user_id' => $item['user_id'], 'shift_date' => $item['shift_date'], 'type' => 'escalation', 'step' => 'u' . $r['id'], 'slot' => 0, 'due_at' => $item['due_at']]);
                    if ($row === null) continue;   // this recipient was already told about this employee
                }
                $byRecipient[$r['email']]['name'] = $r['name'];
                $byRecipient[$r['email']]['rows'][] = ['item' => $item, 'row' => $row];
            }
        }

        foreach ($byRecipient as $email => $g) {
            $rows = array_map(static function ($x) use ($now) {
                $startTs = strtotime($x['item']['shift_start'] ?? $x['item']['due_at']);
                return ['name' => $x['item']['name'], 'start' => date('g:i A', $startTs), 'late' => max(0, (int) round(($now->getTimestamp() - $startTs) / 60))];
            }, $g['rows']);

            if ($dryRun) {
                $report['lines'][] = '[escalation] would email ' . $g['name'] . ' <' . $email . '> about: ' . implode(', ', array_column($rows, 'name'));
                continue;
            }
            if (! $this->canSendMail()) {
                $report['no_sender']++;
                foreach ($g['rows'] as $x) if ($x['row']) $this->finish($x['row'], 'sent', 'no_sender', null, null);
                continue;
            }
            [$ok, $err] = ($this->mailer)($email, count($rows) === 1 ? $rows[0]['name'] . ' has not clocked in' : count($rows) . ' employees have not clocked in', $this->render('emails/attendance_escalation', [
                'rows' => $rows, 'link' => site_url('client_portal/dashboard'), 'sentAt' => $now->format('M j, g:i A'),
            ]));
            foreach ($g['rows'] as $x) {
                if ($x['row']) $this->finish($x['row'], $ok ? 'sent' : 'failed', $ok ? 'sent' : 'failed', null, $ok ? null : $err);
            }
            if ($ok) { $report['escalations_sent']++; $report['lines'][] = '[escalation] emailed ' . $g['name'] . ' about ' . count($rows) . ' employee(s)'; }
            else { $report['emails_failed']++; }
        }
    }

    /** @return list<array{id: int, name: string, email: string}> */
    private function recipients(int $employeeUserId, ?int $departmentId): array
    {
        $ids = [];
        if ($departmentId) {
            foreach ($this->db->table('department_team_heads')->select('team_head_user_id')->where('department_id', $departmentId)->get()->getResultArray() as $r) {
                $ids[(int) $r['team_head_user_id']] = true;
            }
        }
        foreach ($this->db->table('auth_groups_users')->select('user_id')->whereIn('group', ['admin', 'superadmin'])->get()->getResultArray() as $r) {
            $ids[(int) $r['user_id']] = true;
        }
        unset($ids[$employeeUserId]);
        if (! $ids) return [];

        $rows = $this->db->table('users u')
            ->select("u.id, i.secret AS email, COALESCE(NULLIF(TRIM(CONCAT(e.first_name, ' ', e.last_name)), ''), u.username) AS name", false)
            ->join('auth_identities i', "i.user_id = u.id AND i.type = 'email_password'")
            ->join('employees e', 'e.user_id = u.id', 'left')
            ->whereIn('u.id', array_keys($ids))->where('u.active', 1)->where('u.deleted_at', null)
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            if (filter_var($r['email'], FILTER_VALIDATE_EMAIL)) $out[strtolower($r['email'])] = ['id' => (int) $r['id'], 'name' => $r['name'], 'email' => $r['email']];
        }
        return array_values($out);
    }

    /* --------------------------------------------------------------------------------------------- the log */

    /**
     * Creates (or finds) the log row and claims it for sending. Null when it was already sent/skipped, is being sent by
     * another run, or has used up its attempts.
     *
     * @return array{id: int, email_status: ?string, push_status: ?string}|null
     */
    private function claim(array $item): ?array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->query(
            "INSERT IGNORE INTO attendance_reminders (user_id, shift_date, type, step, slot, due_at, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)",
            [(int) $item['user_id'], $item['shift_date'], $item['type'], (string) $item['step'], (int) $item['slot'], $item['due_at'], $now]
        );
        $row = $this->db->table('attendance_reminders')
            ->where('user_id', (int) $item['user_id'])->where('shift_date', $item['shift_date'])
            ->where('type', $item['type'])->where('step', (string) $item['step'])->where('slot', (int) $item['slot'])
            ->get()->getRowArray();
        if (! $row || ! in_array($row['status'], ['pending', 'failed'], true) || (int) $row['attempts'] >= self::MAX_ATTEMPTS) return null;

        $this->db->query("UPDATE attendance_reminders SET status = 'sending', claimed_at = ?, attempts = attempts + 1 WHERE id = ? AND status IN ('pending', 'failed')", [$now, (int) $row['id']]);
        return $this->db->affectedRows() === 1 ? ['id' => (int) $row['id'], 'email_status' => $row['email_status'], 'push_status' => $row['push_status']] : null;
    }

    private function finish(array $row, string $status, ?string $emailStatus, ?string $pushStatus, ?string $error): void
    {
        $this->db->table('attendance_reminders')->where('id', $row['id'])->update([
            'status'       => $status,
            'email_status' => $emailStatus,
            'push_status'  => $pushStatus,
            'last_error'   => $error !== null ? mb_substr($error, 0, 255) : null,
            'sent_at'      => in_array($status, ['sent', 'failed'], true) ? date('Y-m-d H:i:s') : null,
        ]);
    }

    /** A run that died mid-send leaves "sending" behind: put those back so they can be retried (attempts still count). */
    private function releaseStuck(): void
    {
        // Judged by when the row was claimed, not when it was created: a pop-up dismissal can create the row long before it is sent.
        $this->db->query("UPDATE attendance_reminders SET status = 'failed', last_error = 'interrupted' WHERE status = 'sending' AND COALESCE(claimed_at, created_at) < ?", [date('Y-m-d H:i:s', time() - 1800)]);
    }

    private function render(string $view, array $data): string
    {
        // debug => false: no development "view start/end" comments inside an email
        return view($view, $data, ['debug' => false]);
    }

    /** The real mail transport (the same SMTP setup password reset and payslips use). */
    private function smtpSend(string $to, string $subject, string $html): array
    {
        $mail = service('email');
        $mail->clear(true);
        $mail->setMailType('html');
        $mail->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setMessage($html);
        if ($mail->send(false)) return [true, null];
        log_message('error', 'Attendance reminder email to {to} failed.', ['to' => $to]);
        return [false, 'send failed'];
    }
}
