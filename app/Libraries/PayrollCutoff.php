<?php

namespace App\Libraries;

use App\Models\Client\PayrollSettingsModel;

/**
 * Resolves the payroll cutoff period a calendar date falls in, using the
 * cutoffs configured in Payroll Settings (simple "first half ends on day N",
 * or the cross-month half1/half2 definition) — the same rules as
 * payroll_management.php's resolvePeriodDates()/buildPeriodDate().
 */
final class PayrollCutoff
{
    private static ?array $settings = null;

    /** @return array{start:string,end:string,half:string,salary_month:string} */
    public static function forDate(string $date): array
    {
        $s    = self::settings();
        $base = new \DateTime($date);

        // A date can belong to the previous, current, or next salary month
        // (cross-month cutoffs such as Aug 26 - Sep 10).
        foreach ([0, 1, -1] as $shift) {
            $m = (clone $base)->modify('first day of this month')->modify(($shift >= 0 ? '+' : '') . $shift . ' months');
            $halves = self::halves((int) $m->format('Y'), (int) $m->format('n'), $s);
            foreach ($halves as $period) {
                if ($period['start'] <= $date && $date <= $period['end']) {
                    return $period;
                }
            }
        }

        // Gap in a custom definition: fall back to the simple two-half month.
        $halves = self::halves((int) $base->format('Y'), (int) $base->format('n'), ['first_half_cutoff' => 15]);
        return (int) $base->format('j') <= 15 ? $halves[0] : $halves[1];
    }

    private static function settings(): array
    {
        if (self::$settings !== null) {
            return self::$settings;
        }
        $model = new PayrollSettingsModel();
        $keys  = ['first_half_cutoff', 'half1_start_day', 'half1_start_offset', 'half1_end_day', 'half1_end_offset',
                  'half2_start_day', 'half2_start_offset', 'half2_end_day', 'half2_end_offset',
                  'half1_salary_day', 'half1_salary_offset', 'half2_salary_day', 'half2_salary_offset',
                  'monthly_start_day', 'monthly_start_offset', 'monthly_end_day', 'monthly_end_offset',
                  'monthly_salary_day', 'monthly_salary_offset',
                  'salary_day_adjust'];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $model->getSetting($key);
        }
        return self::$settings = $out;
    }

    /**
     * The configured salary day for the cutoff a payslip belongs to (found from
     * its period end), for the Payslip tab's Schedule default and for
     * Automatic release mode. Salary Day is set per half in Payroll Settings as
     * a day of the salary month (+ month offset, 0 = last day), the same shape
     * as the Start/End Day fields. A weekend/holiday is moved per
     * salary_day_adjust ('before' default, 'after', or 'keep').
     *
     * Returns null when no salary day is configured for that half — callers then
     * leave the payslip as a draft rather than guessing a date.
     *
     * The "Monthly" cutoff (its own start/end day, independently configurable
     * from half1/half2 — see monthlyRange()) is detected from $periodStart/
     * $periodEnd via matchMonthlyPeriod() and resolved using monthly_salary_day,
     * instead of going through forDate(), which only knows about
     * first_half/second_half windows: under a cross-month custom cutoff (e.g.
     * half2 ends the 25th, half1 starts the 26th) a Monthly period's end date
     * doesn't fall in either half of its own month, and forDate() would
     * attribute it to next month's half1 instead.
     */
    public static function salaryDayFor(string $periodEnd, ?string $periodStart = null): ?string
    {
        $s = self::settings();

        $monthly = $periodStart !== null ? self::matchMonthlyPeriod($periodStart, $periodEnd) : null;
        if ($monthly !== null) {
            $day = $s['monthly_salary_day'] ?? null;
            if ($day === null || $day === '' || !is_numeric($day)) {
                return null;
            }
            [$year, $month] = array_map('intval', explode('-', $monthly['salary_month']));
            $date = self::build($year, $month, (int) $day, (int) ($s['monthly_salary_offset'] ?? 0));

            return self::adjustForNonWorkingDay($date, (string) ($s['salary_day_adjust'] ?? 'before'));
        }

        $period = self::forDate($periodEnd);
        $prefix = ($period['half'] ?? 'first_half') === 'first_half' ? 'half1' : 'half2';
        $day    = $s[$prefix . '_salary_day'] ?? null;

        if ($day === null || $day === '' || !is_numeric($day)) {
            return null;
        }

        [$year, $month] = array_map('intval', explode('-', $period['salary_month']));
        $date = self::build($year, $month, (int) $day, (int) ($s[$prefix . '_salary_offset'] ?? 0));

        return self::adjustForNonWorkingDay($date, (string) ($s['salary_day_adjust'] ?? 'before'));
    }

    /**
     * The configured "Monthly" cutoff range for a given salary month —
     * independent of half1/half2, its own start/end day + month offset
     * (monthly_start_day/monthly_end_day, 0 = last day). Defaults to the full
     * calendar month (day 1 → last day) when left unset, matching the legacy
     * behavior and payroll_management.php's fallback ranges.
     *
     * @return array{start:string,end:string,salary_month:string}
     */
    private static function monthlyRange(int $year, int $month, array $s): array
    {
        $startDay = $s['monthly_start_day'] !== null && $s['monthly_start_day'] !== '' ? (int) $s['monthly_start_day'] : 1;
        $startOff = (int) ($s['monthly_start_offset'] ?? 0);
        $endDay   = $s['monthly_end_day'] !== null && $s['monthly_end_day'] !== '' ? (int) $s['monthly_end_day'] : 0;
        $endOff   = (int) ($s['monthly_end_offset'] ?? 0);

        return [
            'start'        => self::build($year, $month, $startDay, $startOff),
            'end'          => self::build($year, $month, $endDay, $endOff),
            'salary_month' => sprintf('%04d-%02d', $year, $month),
        ];
    }

    /**
     * Whether [$start, $end] is the configured Monthly cutoff for some salary
     * month — checked against the salary month implied by $start plus the
     * month either side, the same cross-month search forDate() does.
     *
     * @return array{start:string,end:string,salary_month:string}|null
     */
    public static function matchMonthlyPeriod(string $start, string $end): ?array
    {
        $s    = self::settings();
        $base = new \DateTime($start);

        foreach ([0, 1, -1] as $shift) {
            $m     = (clone $base)->modify('first day of this month')->modify(($shift >= 0 ? '+' : '') . $shift . ' months');
            $range = self::monthlyRange((int) $m->format('Y'), (int) $m->format('n'), $s);
            if ($range['start'] === $start && $range['end'] === $end) {
                return $range;
            }
        }

        return null;
    }

    /**
     * The configured Monthly cutoff's [start, end] for a given salary month
     * ('YYYY-MM'), for building an exact-match query/display — e.g. the
     * Payroll Status page, which used to assume Monthly always fell within
     * its own calendar month and could join on DATE_FORMAT(period_start,
     * '%Y-%m') alone. That breaks once the Monthly cutoff can start in the
     * previous month (a negative start offset), same as half1/half2.
     *
     * @return array{start:string,end:string,salary_month:string}
     */
    public static function monthlyPeriodForSalaryMonth(string $salaryMonth): array
    {
        [$year, $month] = array_map('intval', explode('-', $salaryMonth));

        return self::monthlyRange($year, $month, self::settings());
    }

    /** Moves a weekend/holiday date to the working day before or after it ('keep' leaves it). */
    public static function adjustForNonWorkingDay(string $date, string $rule): string
    {
        if (!in_array($rule, ['before', 'after'], true)) {
            return $date;
        }

        $step = $rule === 'before' ? '-1 day' : '+1 day';
        $d    = new \DateTime($date);

        // Bounded — a run of 14 consecutive non-working days would mean the
        // holiday table is misconfigured; fall back to the original date then.
        for ($i = 0; $i < 14; $i++) {
            if (!self::isNonWorkingDay($d->format('Y-m-d'))) {
                return $d->format('Y-m-d');
            }
            $d->modify($step);
        }

        return $date;
    }

    /** Saturday, Sunday, or a date in client_holidays (recurring ones match on month-day). */
    public static function isNonWorkingDay(string $date): bool
    {
        $weekday = (int) (new \DateTime($date))->format('N');
        if ($weekday >= 6) {
            return true;
        }

        $db = db_connect();
        if (!$db->tableExists('client_holidays')) {
            return false;
        }

        $monthDay = substr($date, 5, 5);

        return $db->table('client_holidays')
            ->groupStart()
                ->where('holiday_date', $date)
                ->orGroupStart()
                    ->where('is_recurring', 1)
                    ->where("DATE_FORMAT(holiday_date, '%m-%d') = " . $db->escape($monthDay), null, false)
                ->groupEnd()
            ->groupEnd()
            ->countAllResults() > 0;
    }

    /** @return array{0:array{start:string,end:string},1:array{start:string,end:string}} */
    private static function halves(int $year, int $month, array $s): array
    {
        $ym = sprintf('%04d-%02d', $year, $month);
        if (!empty($s['half1_start_day'])) {
            return [
                ['start' => self::build($year, $month, (int) $s['half1_start_day'], (int) ($s['half1_start_offset'] ?? 0)),
                 'end'   => self::build($year, $month, (int) $s['half1_end_day'],   (int) ($s['half1_end_offset'] ?? 0)),
                 'half' => 'first_half', 'salary_month' => $ym],
                ['start' => self::build($year, $month, (int) $s['half2_start_day'], (int) ($s['half2_start_offset'] ?? 0)),
                 'end'   => self::build($year, $month, (int) $s['half2_end_day'],   (int) ($s['half2_end_offset'] ?? 0)),
                 'half' => 'second_half', 'salary_month' => $ym],
            ];
        }

        $cutoff  = (int) (($s['first_half_cutoff'] ?? 15) ?: 15);
        $ym      = sprintf('%04d-%02d', $year, $month);
        $lastDay = (int) (new \DateTime($ym . '-01'))->format('t');

        return [
            ['start' => "$ym-01", 'end' => sprintf('%s-%02d', $ym, $cutoff), 'half' => 'first_half', 'salary_month' => $ym],
            ['start' => sprintf('%s-%02d', $ym, $cutoff + 1), 'end' => sprintf('%s-%02d', $ym, $lastDay), 'half' => 'second_half', 'salary_month' => $ym],
        ];
    }

    /** day 0 = last day of the target month. */
    private static function build(int $year, int $month, int $day, int $offset): string
    {
        $base = new \DateTime(sprintf('%04d-01-01', $year));
        $base->modify(($month + $offset - 1) . ' months');
        $last = (int) $base->format('t');
        $d    = $day === 0 ? $last : min(max($day, 1), $last);

        return sprintf('%04d-%02d-%02d', (int) $base->format('Y'), (int) $base->format('m'), $d);
    }
}
