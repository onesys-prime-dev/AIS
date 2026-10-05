<?php
declare(strict_types=1);
namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Attendance timestamps are Philippine local datetimes, not time-only strings. */
final class AttendanceWindow
{
    public static function dateTime(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('Asia/Manila'));
        if (!$date || $date->format('Y-m-d H:i:s') !== $value) throw new InvalidArgumentException('Invalid attendance datetime.');
        return $date;
    }

    public static function breaks(array $schedule): array
    {
        $raw = $schedule['break_schedule'] ?? null;
        $items = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($items) || !$items) return [60];
        foreach ($items as $item) {
            if (filter_var($item, FILTER_VALIDATE_INT) === false || (int) $item < 1 || (int) $item > 480) {
                throw new InvalidArgumentException('Invalid break schedule. Ask HR to correct the employee schedule.');
            }
        }
        return array_map('intval', array_values($items));
    }

    /**
     * Break slots for an IN-PROGRESS attendance, preferring the employee's
     * CURRENTLY configured break schedule over the schedule_snapshot frozen
     * at clock-in. Unlike start/end time and required-hours math (which stay
     * frozen for historical/payroll accuracy — see requiredHours() below,
     * still snapshot-based), how many breaks someone gets and how long they
     * are is low-stakes enough to be live: an admin's mid-shift edit takes
     * effect immediately, so what the employee is shown on the Dashboard
     * always matches what starting/ending a break will actually do
     * server-side (both read through this same method).
     */
    public static function liveBreakSlots(array $attendance, array $currentPosition): array
    {
        $live = $currentPosition['break_schedule'] ?? null;
        $liveItems = is_array($live) ? $live : json_decode((string) $live, true);
        if (is_array($liveItems) && $liveItems) {
            return self::breaks(['break_schedule' => $liveItems]);
        }
        // No live schedule on file (e.g. position row missing) — fall back
        // to whatever the snapshot/legacy path would have used.
        return self::breaks(self::schedule($attendance, $currentPosition));
    }

    /**
     * Per-slot time-of-day policy, parallel to liveBreakSlots() by index.
     * Always reads the CURRENT employee_positions row (never the snapshot) —
     * same live-vs-snapshot rule as liveBreakSlots(): an admin's mid-shift
     * edit to a break's window takes effect immediately. A missing/short/
     * malformed entry means that slot is Flexible (no window), so this never
     * throws — callers can trust every index up to count(liveBreakSlots())
     * returns something, even if it's null.
     */
    public static function liveBreakWindows(array $currentPosition): array
    {
        $raw = $currentPosition['break_windows'] ?? null;
        $items = is_array($raw) ? $raw : json_decode((string) $raw, true);
        return is_array($items) ? array_values($items) : [];
    }

    /**
     * Whether `$now` (a full attendance datetime) falls inside the given
     * slot's window, resolved against the attendance's own shift date so an
     * overnight shift's "12:00"-"13:00" window means the calendar day the
     * shift actually runs on, same as bounds() does for start/end time.
     * A null/Flexible window is always allowed.
     */
    public static function withinBreakWindow(?array $window, array $attendance, string $now): bool
    {
        if (!$window || empty($window['start']) || empty($window['end'])) return true;
        $date = $attendance['shift_date'] ?? substr((string) $attendance['time_in'], 0, 10);
        $start = self::dateTime($date . ' ' . $window['start'] . ':00');
        $end = self::dateTime($date . ' ' . $window['end'] . ':00');
        if ($end <= $start) $end = $end->modify('+1 day');
        $moment = self::dateTime($now);
        return $moment >= $start && $moment <= $end;
    }

    /**
     * For a Strict slot, the latest minute the break may run to (the window's
     * own end) — the break is capped there even if the scheduled duration
     * would run longer. Slightly-strict/Flexible/no-window slots return null
     * (no cap; the scheduled duration always applies in full).
     */
    public static function breakWindowEnd(?array $window, array $attendance): ?DateTimeImmutable
    {
        if (!$window || ($window['mode'] ?? null) !== 'strict' || empty($window['end'])) return null;
        $date = $attendance['shift_date'] ?? substr((string) $attendance['time_in'], 0, 10);
        $start = !empty($window['start']) ? self::dateTime($date . ' ' . $window['start'] . ':00') : null;
        $end = self::dateTime($date . ' ' . $window['end'] . ':00');
        if ($start && $end <= $start) $end = $end->modify('+1 day');
        return $end;
    }

    /**
     * Paid hours a full attendance of the scheduled window should give: the window
     * length minus the scheduled break(s). Comparing against the whole window made
     * every full day with a break count as undertime (9h window vs 8h worked).
     */
    public static function requiredHours(float $windowHours, array $schedule): float
    {
        try {
            $breakMinutes = array_sum(self::breaks($schedule));
        } catch (InvalidArgumentException $e) {
            $breakMinutes = 0;
        }

        return max(0.0, round($windowHours - $breakMinutes / 60, 2));
    }

    public static function shiftDate(string $timeIn, array $schedule): string
    {
        $actual = self::dateTime($timeIn);
        $start = $schedule['start_time'] ?? null;
        $end = $schedule['end_time'] ?? null;
        if ($start && $end && $start > $end && $actual->format('H:i:s') < $end) $actual = $actual->modify('-1 day');
        return $actual->format('Y-m-d');
    }

    public static function schedule(array $attendance, ?array $current = null): array
    {
        $snapshot = json_decode((string) ($attendance['schedule_snapshot'] ?? ''), true);
        if (is_array($snapshot)) return $snapshot;
        $schedule = $current ?? [];
        foreach (['scheduled_start' => 'start_time', 'scheduled_end' => 'end_time'] as $column => $key) {
            if (!empty($attendance[$column])) $schedule[$key] = $attendance[$column];
        }
        return $schedule;
    }

    public static function bounds(array $attendance, ?array $current = null): array
    {
        $schedule = self::schedule($attendance, $current);
        if (empty($schedule['start_time']) || empty($schedule['end_time'])) return [null, null];
        $date = $attendance['shift_date'] ?? substr($attendance['time_in'], 0, 10);
        $start = self::dateTime($date . ' ' . $schedule['start_time']);
        $end = self::dateTime($date . ' ' . $schedule['end_time']);
        if ($end <= $start) $end = $end->modify('+1 day');
        return [$start, $end];
    }

    public static function elapsedHours(string $start, string $end, float $breakHours = 0): float
    {
        $seconds = self::dateTime($end)->getTimestamp() - self::dateTime($start)->getTimestamp();
        if ($seconds < 0) throw new InvalidArgumentException('Clock-out cannot be before clock-in.');
        return round(max(0, $seconds / 3600 - $breakHours), 2);
    }

    /** Gross night attendance; payable night differential remains a payroll calculation. */
    public static function nightHours(string $start, string $end): float
    {
        $from = self::dateTime($start); $to = self::dateTime($end);
        if ($to < $from) throw new InvalidArgumentException('Clock-out cannot be before clock-in.');
        $seconds = 0;
        for ($day = $from->setTime(0, 0)->modify('-1 day'); $day < $to; $day = $day->modify('+1 day')) {
            $nightStart = $day->setTime(22, 0); $nightEnd = $day->modify('+1 day')->setTime(6, 0);
            $seconds += max(0, min($to->getTimestamp(), $nightEnd->getTimestamp()) - max($from->getTimestamp(), $nightStart->getTimestamp()));
        }
        return round($seconds / 3600, 2);
    }
}
