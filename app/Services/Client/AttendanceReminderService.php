<?php

namespace App\Services\Client;

use App\Libraries\AttendanceWindow;
use App\Libraries\ReminderConfig;
use App\Models\Client\PayrollSettingsModel;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * The rule engine for attendance reminders. Given a moment ("now"), it works out who should be reminded about what.
 * It reads schedules, leave, holidays, overtime requests and the real attendance records, and it sends nothing and
 * writes nothing, so every rule can be tested with a pretend clock.
 *
 * Only the CURRENT step of a sequence is ever returned: after a scheduler outage the missed earlier steps are skipped
 * rather than sent late, and a sequence stops after its stop time.
 *
 * An item is one reminder for one employee:
 *   key       "2026-11-23|clock_in|10|0" = shift date | type | step | break slot (unique per employee)
 *   type      clock_in | clock_out | break_window | break_end
 *   channels  popup / email / push (which channels this step uses, before the employee's own preferences are applied)
 *   escalate  true on the clock-in step that also tells the Team Head and admins
 */
final class AttendanceReminderService
{
    public const CONFIG_KEY = 'attendance_reminders_config';

    /** An open shift that nobody closed keeps reminding (once a day) for this many days after it should have ended. */
    private const STALE_DAYS = 14;

    private BaseConnection $db;
    private array $config;
    private DateTimeZone $tz;

    public function __construct(?BaseConnection $db = null, ?array $config = null)
    {
        $this->db     = $db ?? \Config\Database::connect();
        $this->config = $config ?? self::loadConfig();
        $this->tz     = new DateTimeZone('Asia/Manila');
    }

    public static function loadConfig(): array
    {
        try {
            return ReminderConfig::fromStored((new PayrollSettingsModel())->getSetting(self::CONFIG_KEY));
        } catch (Throwable $e) {
            return ReminderConfig::defaults();
        }
    }

    public function config(): array
    {
        return $this->config;
    }

    /**
     * Every reminder that is due right now.
     *
     * @return list<array<string, mixed>>
     */
    public function due(DateTimeImmutable $now, ?int $onlyUserId = null): array
    {
        if (empty($this->config['enabled'])) return [];

        $now    = $now->setTimezone($this->tz);
        $people = $this->people($onlyUserId);
        if (! $people) return [];

        $items = array_merge(
            $this->clockInItems($now, $people),
            $this->sessionItems($now, $people)
        );

        $out = [];
        foreach ($items as $item) {
            $p = $people[$item['user_id']];
            if (! $p['prefs']['reminders_enabled']) continue;          // opting out stops everything
            $item['channels']['popup'] = $item['channels']['popup'] && ! empty($this->config['popup']['enabled']);
            $item['channels']['email'] = $item['channels']['email'] && ! empty($this->config['email']['enabled']) && $p['prefs']['email_enabled'];
            $item['channels']['push']  = $item['channels']['push'] && $p['prefs']['push_enabled'];
            $item['sound'] = $p['prefs']['sound_enabled'];
            $out[] = $item;
        }
        return $out;
    }

    /** The one item with this key for this employee, or null when it is no longer due (e.g. they already acted). */
    public function find(DateTimeImmutable $now, int $userId, string $key): ?array
    {
        foreach ($this->due($now, $userId) as $item) {
            if ($item['key'] === $key) return $item;
        }
        return null;
    }

    /* ----------------------------------------------------------------------------------------------- people */

    /** @return array<int, array<string, mixed>> keyed by user id: employees that have a schedule and an active account */
    private function people(?int $onlyUserId): array
    {
        $q = $this->db->table('employees e')
            ->select('e.id AS employee_id, e.user_id, e.first_name, e.last_name, p.start_time, p.end_time, p.working_days, p.wfh_days, p.break_schedule, p.break_windows, p.department_id, p.tardiness_grace_minutes, i.secret AS email, pr.reminders_enabled, pr.email_enabled, pr.push_enabled, pr.sound_enabled, ov.config AS override_config', false)
            ->join('employee_positions p', 'p.employee_id = e.id')
            ->join('users u', 'u.id = e.user_id')
            ->join('auth_identities i', "i.user_id = u.id AND i.type = 'email_password'", 'left')
            ->join('attendance_reminder_prefs pr', 'pr.user_id = u.id', 'left')
            ->join('attendance_reminder_overrides ov', 'ov.employee_id = e.id', 'left')
            ->where('u.active', 1)
            ->where('u.deleted_at', null)
            ->where('e.user_id IS NOT NULL', null, false);
        if ($onlyUserId !== null) $q->where('e.user_id', $onlyUserId);

        $out = [];
        foreach ($q->get()->getResultArray() as $r) {
            $uid = (int) $r['user_id'];
            $out[$uid] = [
                'user_id'      => $uid,
                'employee_id'  => (int) $r['employee_id'],
                'name'         => trim($r['first_name'] . ' ' . $r['last_name']),
                'email'        => ($r['email'] !== null && filter_var($r['email'], FILTER_VALIDATE_EMAIL)) ? $r['email'] : null,
                'department_id' => $r['department_id'] !== null ? (int) $r['department_id'] : null,
                'position'     => $r,
                'working_days' => $this->jsonList($r['working_days']),
                'wfh_days'     => $this->jsonList($r['wfh_days']),
                'has_schedule' => ! empty($r['start_time']) && ! empty($r['end_time']) && $this->jsonList($r['working_days']) !== [],
                'cfg'          => ReminderConfig::forEmployee($this->config, $r['override_config']),
                'prefs'        => [
                    'reminders_enabled' => $r['reminders_enabled'] === null ? true : (bool) (int) $r['reminders_enabled'],
                    'email_enabled'     => $r['email_enabled'] === null ? true : (bool) (int) $r['email_enabled'],
                    'push_enabled'      => $r['push_enabled'] === null ? true : (bool) (int) $r['push_enabled'],
                    'sound_enabled'     => $r['sound_enabled'] === null ? true : (bool) (int) $r['sound_enabled'],
                ],
            ];
        }
        return $out;
    }

    private function jsonList($raw): array
    {
        $v = is_array($raw) ? $raw : json_decode((string) $raw, true);
        return is_array($v) ? array_values(array_filter($v, static fn ($x) => $x !== null && $x !== '')) : [];
    }

    /* ------------------------------------------------------------------------------------------- clock in */

    private function clockInItems(DateTimeImmutable $now, array $people): array
    {
        $days = [$now->setTime(0, 0)->modify('-1 day'), $now->setTime(0, 0)];
        $dates = array_map(static fn ($d) => $d->format('Y-m-d'), $days);
        $holidays = $this->holidays($dates);
        $leave    = $this->approvedLeave($dates[0], $dates[1], array_keys($people));
        [$clocked, $open] = $this->attendanceState($dates, array_keys($people));
        $network  = $this->networkLine();

        $items = [];
        foreach ($people as $uid => $p) {
            $cfg = $p['cfg']['clock_in'];
            if (! $p['has_schedule'] || empty($cfg['enabled']) || isset($open[$uid])) continue;

            foreach ($days as $day) {
                $date = $day->format('Y-m-d');
                if (! in_array(strtolower($day->format('D')), $p['working_days'], true)) continue;
                if (isset($holidays[$date]) || isset($leave[$uid][$date]) || isset($clocked[$uid][$date])) continue;

                $start = $this->at($date, $p['position']['start_time']);
                $stop  = $start->modify('+' . (int) $cfg['stop_after'] . ' minutes');
                $current = $this->currentStep($start, $cfg['steps'], $now);
                if ($now > $stop) continue;

                // The Team Head / admin copy no longer depends on the employee's own minutes: it is due once the escalation
                // minute has passed (and stays due until the reminders stop), so a late scheduler run or a custom list of
                // minutes without that exact minute cannot skip it.
                $escalate = ! empty($this->config['escalation']['enabled'])
                    && $now >= $start->modify('+' . (int) $cfg['escalate_step'] . ' minutes');
                if ($current === null && ! $escalate) continue;

                if ($current === null) {
                    // No reminder of their own is due right now, but the escalation is: a silent item that only carries it.
                    $silent = $this->item($p, 'clock_in', 'esc', 0, $date, $start->modify('+' . (int) $cfg['escalate_step'] . ' minutes'), '', '', 'info',
                        ['popup' => false, 'email' => false, 'push' => false], true);
                    $silent['shift_start'] = $start->format('Y-m-d H:i:s');
                    $items[] = $silent;
                    continue;
                }

                $isWfh = in_array(strtolower($day->format('D')), $p['wfh_days'], true);
                $late  = (int) round(($now->getTimestamp() - $start->getTimestamp()) / 60);
                [$title, $body, $severity] = $this->clockInMessage($current, $start, $late, $isWfh ? '' : $network);
                $item = $this->item($p, 'clock_in', (string) $current, 0, $date, $start->modify($current . ' minutes'), $title, $body, $severity, [
                    'popup' => true,
                    'email' => $current >= 0 || ! empty($this->config['email']['before']),
                    'push'  => true,
                ], $escalate);
                $item['shift_start'] = $start->format('Y-m-d H:i:s');
                $items[] = $item;
            }
        }
        return $items;
    }

    /* ---------------------------------------------------------------------- clock out + breaks (open sessions) */

    private function sessionItems(DateTimeImmutable $now, array $people): array
    {
        $rows = $this->db->table('attendance a')
            ->select('a.*')
            ->where('a.time_out', null)
            ->whereIn('a.user_id', array_keys($people))
            ->get()->getResultArray();
        if (! $rows) return [];

        $ot = $this->approvedOvertime(array_column($rows, 'user_id'), $now);
        $items = [];
        foreach ($rows as $a) {
            $uid = (int) $a['user_id'];
            $p = $people[$uid] ?? null;
            if (! $p) continue;

            try {
                [$start, $end] = AttendanceWindow::bounds($a, $p['position']);
            } catch (Throwable $e) {
                continue;
            }
            if (! $start || ! $end) continue;
            $date = $a['shift_date'] ?: substr((string) $a['time_in'], 0, 10);

            $items = array_merge($items, $this->clockOutItems($now, $p, $a, $date, $end, $ot[$uid] ?? []));
            $items = array_merge($items, $this->breakItems($now, $p, $a, $date));
        }
        return $items;
    }

    private function clockOutItems(DateTimeImmutable $now, array $p, array $a, string $date, DateTimeImmutable $end, array $otEnds): array
    {
        $cfg = $p['cfg']['clock_out'];
        if (empty($cfg['enabled'])) return [];

        // Approved overtime with a time span pushes the reminder to after the overtime ends.
        [$anchor, $label] = $this->clockOutAnchor($date, $end, $otEnds);

        $stop = $anchor->modify('+' . (int) $cfg['stop_after'] . ' minutes');
        if ($now > $stop) return $this->staleItems($now, $p, $date, $anchor, $stop);
        $current = $this->currentStep($anchor, $cfg['steps'], $now);
        if ($current === null) return [];

        $after = (int) round(($now->getTimestamp() - $anchor->getTimestamp()) / 60);
        [$title, $body, $severity] = $this->clockOutMessage($current, $anchor, $after, $label);
        return [$this->item($p, 'clock_out', (string) $current, 0, $date, $anchor->modify($current . ' minutes'), $title, $body, $severity, [
            'popup' => true,
            'email' => $current >= 0 || ! empty($this->config['email']['before']),
            'push'  => true,
        ], false)];
    }

    /**
     * When the clock-out reminders are measured from: the shift end, or the end of approved overtime that has a time span
     * (a span that crosses midnight, or belongs to a night shift, ends on the next calendar day).
     *
     * @return array{0: DateTimeImmutable, 1: string}
     */
    private function clockOutAnchor(string $date, DateTimeImmutable $end, array $otEnds): array
    {
        $anchor = $end;
        $label  = 'Your shift';
        foreach ($otEnds as $range) {
            if ($date < $range[0] || $date > $range[1]) continue;
            $otEnd = $this->at($date, $range[2]);
            if (! empty($range[3]) && $otEnd <= $this->at($date, $range[3])) $otEnd = $otEnd->modify('+1 day');
            if ($end->format('Y-m-d') > $date && $otEnd <= $end) $otEnd = $otEnd->modify('+1 day');
            if ($otEnd > $anchor) { $anchor = $otEnd; $label = 'Your approved overtime'; }
        }
        return [$anchor, $label];
    }

    /**
     * A shift that was never closed: the normal clock-out reminders have stopped, but the employee still has an open
     * attendance (so they cannot clock in again). One reminder a day, for STALE_DAYS days, until they or an admin fix it.
     */
    private function staleItems(DateTimeImmutable $now, array $p, string $date, DateTimeImmutable $anchor, DateTimeImmutable $stop): array
    {
        if ($now > $anchor->modify('+' . self::STALE_DAYS . ' days')) return [];
        $body = 'Your shift on ' . $anchor->format('F j') . ' (it ended at ' . $anchor->format('g:i A') . ') was never clocked out. Clock out now, or ask an admin to correct it. You cannot clock in again until it is closed.';
        return [$this->item($p, 'clock_out', 'stale' . $now->format('md'), 0, $date, $stop, 'You still have an open shift', $body, 'urgent', ['popup' => true, 'email' => true, 'push' => true], false)];
    }

    private function breakItems(DateTimeImmutable $now, array $p, array $a, string $date): array
    {
        $cfg = $p['cfg']['break'];
        if (empty($cfg['enabled'])) return [];

        try {
            $slots   = AttendanceWindow::liveBreakSlots($a, $p['position']);
            $windows = AttendanceWindow::liveBreakWindows($p['position']);
        } catch (Throwable $e) {
            return [];
        }
        $taken = (int) ($a['breaks_taken'] ?? 0);
        $channels = ['popup' => true, 'email' => false, 'push' => true];

        // A break is running: warn before it ends, and when it is over.
        if (! empty($a['break_start'])) {
            $bs      = AttendanceWindow::dateTime((string) $a['break_start']);
            $minutes = $slots[$taken] ?? end($slots);
            $endAt   = $bs->modify('+' . (int) $minutes . ' minutes');
            $cap     = AttendanceWindow::breakWindowEnd($windows[$taken] ?? null, $a);
            if ($cap && $cap < $endAt) $endAt = $cap;

            if ($now >= $endAt && $now <= $endAt->modify('+30 minutes')) {
                return [$this->item($p, 'break_end', 'over', $taken, $date, $endAt, 'Your break is over', 'Your break time is up (it ended at ' . $endAt->format('g:i A') . '). Please end your break and get back to work.', 'urgent', $channels, false)];
            }
            $pre = (int) $cfg['end_before'];
            if ($pre > 0 && $now >= $endAt->modify('-' . $pre . ' minutes') && $now < $endAt) {
                return [$this->item($p, 'break_end', 'pre', $taken, $date, $endAt->modify('-' . $pre . ' minutes'), 'Break ending soon', 'Your break ends at ' . $endAt->format('g:i A') . '.', 'info', $channels, false)];
            }
            return [];
        }

        // No break running and this slot is still to be taken: only slots with a time window get window reminders.
        if ($taken >= count($slots)) return [];
        $w = $windows[$taken] ?? null;
        if (! is_array($w) || empty($w['start']) || empty($w['end'])) return [];

        $ws = AttendanceWindow::dateTime($date . ' ' . $w['start'] . ':00');
        $we = AttendanceWindow::dateTime($date . ' ' . $w['end'] . ':00');
        if ($we <= $ws) $we = $we->modify('+1 day');
        if ($now < $ws || $now >= $we) return [];

        $range = $ws->format('g:i A') . ' – ' . $we->format('g:i A');
        $close = (int) $cfg['window_close_before'];
        if ($close > 0 && $now >= $we->modify('-' . $close . ' minutes')) {
            return [$this->item($p, 'break_window', 'close', $taken, $date, $we->modify('-' . $close . ' minutes'), 'Break window closing', 'Your break window (' . $range . ') closes at ' . $we->format('g:i A') . '. Start your break soon or you may miss it.', 'warning', $channels, false)];
        }
        return [$this->item($p, 'break_window', 'open', $taken, $date, $ws, 'Break time', 'Your break window (' . $range . ') is open. Start your break when you are ready.', 'info', $channels, false)];
    }

    /* --------------------------------------------------------------------------------------------- helpers */

    /** The latest step (minutes from the anchor) whose time has come, or null if the first one hasn't. */
    private function currentStep(DateTimeImmutable $anchor, array $steps, DateTimeImmutable $now): ?int
    {
        $current = null;
        foreach ($steps as $s) {
            if ($anchor->modify(((int) $s >= 0 ? '+' : '') . (int) $s . ' minutes') <= $now) $current = (int) $s;
        }
        return $current;
    }

    private function at(string $date, string $time): DateTimeImmutable
    {
        return new DateTimeImmutable($date . ' ' . substr($time, 0, 8), $this->tz);
    }

    private function item(array $p, string $type, string $step, int $slot, string $date, DateTimeImmutable $dueAt, string $title, string $body, string $severity, array $channels, bool $escalate): array
    {
        return [
            'key'           => $date . '|' . $type . '|' . $step . '|' . $slot,
            'user_id'       => $p['user_id'],
            'employee_id'   => $p['employee_id'],
            'name'          => $p['name'],
            'email'         => $p['email'],
            'department_id' => $p['department_id'],
            'type'          => $type,
            'step'          => $step,
            'slot'          => $slot,
            'shift_date'    => $date,
            'due_at'        => $dueAt->format('Y-m-d H:i:s'),
            'title'         => $title,
            'body'          => $body,
            'severity'      => $severity,
            'channels'      => $channels,
            'escalate'      => $escalate,
        ];
    }

    private function clockInMessage(int $step, DateTimeImmutable $start, int $late, string $network): array
    {
        $time = $start->format('g:i A');
        $net  = $network !== '' ? ' ' . $network : '';
        if ($step < 0)  return ['Shift starting soon', "Your shift starts at {$time}. Don't forget to clock in." . $net, 'info'];
        if ($step === 0) return ["It's time to clock in", "Your shift starts now ({$time}). Please clock in." . $net, 'warning'];
        $severity = $step >= 30 ? 'urgent' : 'warning';
        return ["You haven't clocked in yet", "Your shift started at {$time}" . ($late > 0 ? " ({$late} minutes ago)" : '') . '. Please clock in now.' . $net, $severity];
    }

    private function clockOutMessage(int $step, DateTimeImmutable $anchor, int $after, string $label): array
    {
        $time  = $anchor->format('g:i A');
        $isOt  = $label !== 'Your shift';
        $extra = $isOt ? '' : ' If you worked overtime, file an overtime request.';

        if ($step < 0) {
            if ($after >= 0) {
                return ['Shift ended', "{$label} ended at {$time}. Log your tasks and clock out." . $extra, 'warning'];
            }
            return ['Shift ending soon', "{$label} ends at {$time}. Log your tasks and clock out." . ($isOt ? '' : ' If you are staying on for overtime, make sure it has been requested.'), 'info'];
        }
        $ago = $after > 0 ? " ({$after} minutes ago)" : '';
        return ["You're still clocked in", "{$label} ended at {$time}{$ago}. Clock out if you are done." . $extra, $step >= 120 ? 'urgent' : 'warning'];
    }
    /** Extra line for on-site employees when the office-network rule is switched on. */
    private function networkLine(): string
    {
        try {
            $s = new PayrollSettingsModel();
            if ((string) ($s->getSetting('attendance_network_enabled') ?? '1') === '0') return '';
            if (trim((string) ($s->getSetting('attendance_allowed_ips') ?? '')) === '') return '';
            return 'Clock in from the office network.';
        } catch (Throwable $e) {
            return '';
        }
    }

    /** @return array<string, true> */
    private function holidays(array $dates): array
    {
        $out = [];
        try {
            $md = array_map(static fn ($d) => substr($d, 5), $dates);
            $rows = $this->db->table('client_holidays')
                ->groupStart()->whereIn('holiday_date', $dates)
                ->orGroupStart()->where('is_recurring', 1)->whereIn("DATE_FORMAT(holiday_date,'%m-%d')", $md)->groupEnd()
                ->groupEnd()->get()->getResultArray();
            foreach ($rows as $r) {
                foreach ($dates as $d) {
                    if ($r['holiday_date'] === $d || ((int) ($r['is_recurring'] ?? 0) === 1 && substr($r['holiday_date'], 5) === substr($d, 5))) $out[$d] = true;
                }
            }
        } catch (Throwable $e) {
            // No holidays table: nothing to skip.
        }
        return $out;
    }

    /** @return array<int, array<string, true>> user id => dates on approved leave */
    private function approvedLeave(string $from, string $to, array $userIds): array
    {
        if (! $userIds) return [];
        $rows = $this->db->table('requests')
            ->select('requested_by, start_date, COALESCE(end_date, start_date) AS end_date', false)
            ->where('request_type', 'leave')->where('status', 'approved')
            ->where('start_date <=', $to)->where('COALESCE(end_date, start_date) >=', $from, false)
            ->whereIn('requested_by', $userIds)
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            for ($d = new DateTimeImmutable($r['start_date']); $d->format('Y-m-d') <= $r['end_date'] && $d->format('Y-m-d') <= $to; $d = $d->modify('+1 day')) {
                if ($d->format('Y-m-d') >= $from) $out[(int) $r['requested_by']][$d->format('Y-m-d')] = true;
            }
        }
        return $out;
    }

    /**
     * @return array{0: array<int, array<string, true>>, 1: array<int, true>} dates already clocked in per user, and
     *         users with a session still open (a shift that is under way)
     */
    private function attendanceState(array $dates, array $userIds): array
    {
        $rows = $this->db->table('attendance')
            ->select('user_id, shift_date, DATE(time_in) AS d, time_out', false)
            ->whereIn('user_id', $userIds)
            ->groupStart()->whereIn('shift_date', $dates)->orWhereIn('DATE(time_in)', $dates)->orWhere('time_out', null)->groupEnd()
            ->get()->getResultArray();
        $clocked = $open = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            // The shift date is the day the shift belongs to (a night shift clocked in after midnight still belongs to the
            // day before); only fall back to the calendar day of the clock-in when no shift date was recorded.
            $clocked[$uid][$r['shift_date'] ?: $r['d']] = true;
            if ($r['time_out'] === null) $open[$uid] = true;
        }
        return [$clocked, $open];
    }

    /** @return array<int, list<array{0: string, 1: string, 2: string, 3: ?string}>> user id => [start date, end date, ot end time, ot start time] */
    private function approvedOvertime(array $userIds, DateTimeImmutable $now): array
    {
        $from = $now->modify('-2 days')->format('Y-m-d');
        $rows = $this->db->table('requests')
            ->select('requested_by, start_date, COALESCE(end_date, start_date) AS end_date, ot_end_time, ot_start_time', false)
            ->where('request_type', 'overtime')->where('status', 'approved')
            ->where('ot_end_time IS NOT NULL', null, false)
            ->where('COALESCE(end_date, start_date) >=', $from, false)
            ->whereIn('requested_by', array_map('intval', array_unique($userIds)))
            ->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) $out[(int) $r['requested_by']][] = [$r['start_date'], $r['end_date'], $r['ot_end_time'], $r['ot_start_time']];
        return $out;
    }
}
