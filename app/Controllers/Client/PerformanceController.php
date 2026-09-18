<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\EmployeeModel;
use App\Models\Client\AttendanceModel;
use App\Models\Client\PayrollSettingsModel;

class PerformanceController extends BaseController
{
    /**
     * Self-service only: punctuality, attendance, average hours and OT
     * frequency for the logged-in employee, trended over recent semi-monthly
     * cutoffs. Never exposes another employee's data — the employee_id is
     * always resolved from the authenticated user, never taken from input.
     */
    public function mySummary()
    {
        $user = auth()->user();
        $employee = (new EmployeeModel())->where('user_id', $user->id)->first();
        if (!$employee) {
            return $this->response->setJSON(['error' => 'No employee record linked to this account.']);
        }

        $periodsRequested = (int) ($this->request->getGet('periods') ?? 6);
        $periodsRequested = max(1, min(12, $periodsRequested));

        $attendanceModel = new AttendanceModel();
        $periods = $this->recentCutoffs($periodsRequested);

        $trend = [];
        $latestRows = [];
        foreach ($periods as $p) {
            $rows = $attendanceModel
                ->where('user_id', $user->id)
                ->where('COALESCE(shift_date, DATE(time_in)) >=', $p['start'])
                ->where('COALESCE(shift_date, DATE(time_in)) <=', $p['end'])
                ->where('time_in IS NOT NULL')
                ->where('time_out IS NOT NULL')
                ->findAll();

            $trend[] = $this->summarizePeriod($p['label'], $p['start'], $p['end'], $rows);
            $latestRows = $rows; // overwritten each loop, ends up holding the last (most recent) period's rows
        }

        return $this->response->setJSON([
            'employee_name' => trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')),
            'latest'        => end($trend) ?: null,
            'trend'         => $trend,
            'daily'         => $this->buildDailyBreakdown($latestRows),
        ]);
    }

    /**
     * Cutoffs as actually configured in Payroll Settings — the same
     * first_half_cutoff day (or, if set, the cross-month half1/half2 start
     * /end day+offset period definition) that payroll_management.php uses
     * to resolve "1st Half" / "2nd Half" for a salary month. Most recent
     * (including the current, possibly in-progress, one) last.
     */
    private function recentCutoffs(int $count): array
    {
        $psModel  = new PayrollSettingsModel();
        $settings = [
            'first_half_cutoff' => $psModel->getSetting('first_half_cutoff'),
            'half1_start_day'   => $psModel->getSetting('half1_start_day'),
            'half1_start_offset'=> $psModel->getSetting('half1_start_offset'),
            'half1_end_day'     => $psModel->getSetting('half1_end_day'),
            'half1_end_offset'  => $psModel->getSetting('half1_end_offset'),
            'half2_start_day'   => $psModel->getSetting('half2_start_day'),
            'half2_start_offset'=> $psModel->getSetting('half2_start_offset'),
            'half2_end_day'     => $psModel->getSetting('half2_end_day'),
            'half2_end_offset'  => $psModel->getSetting('half2_end_offset'),
        ];

        $periods = [];
        $cursor  = new \DateTime('first day of this month');
        // Generous buffer of extra months so the loop always reaches the
        // current month regardless of $count, then gets trimmed below.
        $cursor->modify('-' . (intdiv($count, 2) + 2) . ' months');

        $currentMonthStart = new \DateTime('first day of this month');
        while ($cursor <= $currentMonthStart) {
            $year  = (int) $cursor->format('Y');
            $month = (int) $cursor->format('m');
            $ranges = $this->resolveHalves($year, $month, $settings);

            $periods[] = ['label' => $cursor->format('M') . ' 1st Half'] + $ranges['first_half'];
            $periods[] = ['label' => $cursor->format('M') . ' 2nd Half'] + $ranges['second_half'];

            $cursor->modify('+1 month');
        }

        // Keep the most recent ones (array is built oldest-first) and drop
        // any cutoff that hasn't started yet.
        $today = date('Y-m-d');
        $periods = array_values(array_filter($periods, fn ($p) => $p['start'] <= $today));

        return array_slice($periods, -$count);
    }

    /** Mirrors resolvePeriodDates()/buildPeriodDate() in payroll_management.php exactly,
     *  so a company's actual configured cutoff (simple day, or cross-month
     *  half1/half2 definition) is honored here instead of assuming 1-15/16-end. */
    private function resolveHalves(int $salaryYear, int $salaryMonth, array $s): array
    {
        if (!empty($s['half1_start_day'])) {
            return [
                'first_half' => [
                    'start' => $this->buildPeriodDate($salaryYear, $salaryMonth, (int) $s['half1_start_day'], (int) ($s['half1_start_offset'] ?? 0)),
                    'end'   => $this->buildPeriodDate($salaryYear, $salaryMonth, (int) $s['half1_end_day'], (int) ($s['half1_end_offset'] ?? 0)),
                ],
                'second_half' => [
                    'start' => $this->buildPeriodDate($salaryYear, $salaryMonth, (int) $s['half2_start_day'], (int) ($s['half2_start_offset'] ?? 0)),
                    'end'   => $this->buildPeriodDate($salaryYear, $salaryMonth, (int) $s['half2_end_day'], (int) ($s['half2_end_offset'] ?? 0)),
                ],
            ];
        }

        $cutoff  = (int) ($s['first_half_cutoff'] ?: 15);
        $lastDay = (int) (new \DateTime(sprintf('%04d-%02d-01', $salaryYear, $salaryMonth)))->format('t');
        $ym      = sprintf('%04d-%02d', $salaryYear, $salaryMonth);

        return [
            'first_half'  => ['start' => "$ym-01", 'end' => $ym . '-' . str_pad((string) $cutoff, 2, '0', STR_PAD_LEFT)],
            'second_half' => ['start' => $ym . '-' . str_pad((string) ($cutoff + 1), 2, '0', STR_PAD_LEFT), 'end' => $ym . '-' . str_pad((string) $lastDay, 2, '0', STR_PAD_LEFT)],
        ];
    }

    /** day=0 means "last day of the target month", matching buildPeriodDate() in JS. */
    private function buildPeriodDate(int $salaryYear, int $salaryMonth, int $day, int $offset): string
    {
        $rawMonth = $salaryMonth + $offset;
        $base = new \DateTime(sprintf('%04d-01-01', $salaryYear));
        $base->modify(($rawMonth - 1) . ' months');
        $targetYear  = (int) $base->format('Y');
        $targetMonth = (int) $base->format('m');
        $lastDay     = (int) $base->format('t');

        $d = $day === 0 ? $lastDay : min(max($day, 1), $lastDay);

        return sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, $d);
    }

    private function summarizePeriod(string $label, string $start, string $end, array $rows): array
    {
        $workingDays = $this->countWeekdays($start, min($end, date('Y-m-d')));

        $workedDates = [];
        $lateDays    = 0;
        $otDays      = 0;
        $totalHours  = 0.0;

        foreach ($rows as $r) {
            $date = $r['shift_date'] ?? date('Y-m-d', strtotime($r['time_in']));
            $workedDates[$date] = true;
            if ((float) ($r['late_minutes'] ?? 0) > 0) {
                $lateDays++;
            }
            if ((float) ($r['approved_ot_hours'] ?? 0) > 0) {
                $otDays++;
            }
            $totalHours += (float) ($r['total_hours'] ?? 0);
        }

        $workedDays = count($workedDates);

        return [
            'label'             => $label,
            'attendance_rate'   => $workingDays > 0 ? round(min($workedDays, $workingDays) / $workingDays * 100, 1) : 0,
            'punctuality_rate'  => $workedDays > 0 ? round(($workedDays - $lateDays) / $workedDays * 100, 1) : null,
            'avg_hours_per_day' => $workedDays > 0 ? round($totalHours / $workedDays, 2) : 0,
            'ot_days'           => $otDays,
            'worked_days'       => $workedDays,
            'working_days'      => $workingDays,
        ];
    }

    /** Per-day rows for the "show per day in the cutoff" toggle — one row
     *  per calendar date, since an employee can have multiple clock-ins on
     *  the same date (e.g. after a missed clock-out correction). */
    private function buildDailyBreakdown(array $rows): array
    {
        $byDate = [];
        foreach ($rows as $r) {
            $date = $r['shift_date'] ?? date('Y-m-d', strtotime($r['time_in']));
            if (!isset($byDate[$date])) {
                $byDate[$date] = [
                    'date'       => $date,
                    'time_in'    => $r['time_in'],
                    'time_out'   => $r['time_out'],
                    'hours'      => 0.0,
                    'late_minutes' => 0.0,
                    'ot_hours'   => 0.0,
                ];
            }
            $byDate[$date]['time_in']  = min($byDate[$date]['time_in'], $r['time_in']);
            $byDate[$date]['time_out'] = max($byDate[$date]['time_out'], $r['time_out']);
            $byDate[$date]['hours']       += (float) ($r['total_hours'] ?? 0);
            $byDate[$date]['late_minutes'] = max($byDate[$date]['late_minutes'], (float) ($r['late_minutes'] ?? 0));
            $byDate[$date]['ot_hours']    += (float) ($r['approved_ot_hours'] ?? 0);
        }

        ksort($byDate);

        return array_map(function ($d) {
            $d['hours']    = round($d['hours'], 2);
            $d['ot_hours'] = round($d['ot_hours'], 2);
            $d['time_in']  = date('h:i A', strtotime($d['time_in']));
            $d['time_out'] = date('h:i A', strtotime($d['time_out']));
            return $d;
        }, array_values($byDate));
    }

    /** Simple Mon-Fri business-day count; doesn't account for a per-employee
     *  custom work week (e.g. 6-day schedules) or approved leave — a
     *  deliberate simplification for a self-service trend view. */
    private function countWeekdays(string $start, string $end): int
    {
        if ($start > $end) {
            return 0;
        }
        $count = 0;
        $cursor = new \DateTime($start);
        $endDt  = new \DateTime($end);
        while ($cursor <= $endDt) {
            if ((int) $cursor->format('N') < 6) {
                $count++;
            }
            $cursor->modify('+1 day');
        }
        return $count;
    }
}
