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
                  'half2_start_day', 'half2_start_offset', 'half2_end_day', 'half2_end_offset'];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $model->getSetting($key);
        }
        return self::$settings = $out;
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
