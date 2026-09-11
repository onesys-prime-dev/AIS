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
