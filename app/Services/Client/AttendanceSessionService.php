<?php
declare(strict_types=1);
namespace App\Services\Client;

use App\Libraries\AttendanceWindow;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

final class AttendanceSessionService
{
    private BaseConnection $db;
    private \Closure $clock;
    private \Closure $audit;

    public function __construct(?BaseConnection $db = null, ?callable $clock = null, ?callable $audit = null)
    {
        $this->db = $db ?? db_connect();
        $this->clock = \Closure::fromCallable($clock ?? static fn() => date('Y-m-d H:i:s'));
        $this->audit = \Closure::fromCallable($audit ?? static fn($description, $id, $before, $after) => service('activityLogger')->log($before ? 'updated' : 'created', 'attendance', $description, $id, $before ?: null, $after));
    }

    /** Lock the stable user row even before their first attendance row exists. */
    private function write(int $userId, callable $work): array
    {
        $this->db->transBegin();
        try {
            $user = $this->db->query('SELECT id FROM users WHERE id = ? AND active = 1 AND deleted_at IS NULL FOR UPDATE', [$userId])->getRowArray();
            if (!$user) throw new DomainException('Active account required.');
            $result = $work();
            if (!$this->db->transStatus()) throw new RuntimeException('Attendance could not be saved.');
            if (!$this->db->transCommit()) throw new RuntimeException('Attendance commit failed.');
            return $result;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function now(): string
    {
        $now = ($this->clock)(); AttendanceWindow::dateTime($now); return $now;
    }

    private function currentSchedule(int $userId): array
    {
        // Not reusing AttendanceComputationService::getEmployeeSchedule() here: it
        // returns null both when the employee profile is missing and when only the
        // position/schedule row is missing, and this method needs to tell those
        // two cases apart (only the former should block clocking in).
        $employee = $this->db->table('employees')->where('user_id', $userId)->get()->getRowArray();
        if (!$employee) throw new DomainException('Your account needs an employee profile before you can clock in.');
        $row = $this->db->table('employee_positions')->where('employee_id', $employee['id'])->get()->getRowArray() ?? [];
        return array_intersect_key($row, array_flip(['start_time', 'end_time', 'break_schedule', 'working_days', 'working_day_type']));
    }

    private function owned(int $userId, int $id): array
    {
        $row = $this->db->query('SELECT * FROM attendance WHERE id = ? AND user_id = ? FOR UPDATE', [$id, $userId])->getRowArray();
        if (!$row) throw new DomainException('Attendance record not found.');
        return $row;
    }

    private function version(array $row, int $expected): void
    {
        if ((int) $row['state_version'] !== $expected) throw new DomainException('Attendance changed in another request. Reload the dashboard and try again.');
    }

    private function save(array $before, array $changes, string $description): array
    {
        $changes['state_version'] = (int) $before['state_version'] + 1;
        if (!$this->db->table('attendance')->where('id', $before['id'])->update($changes)) throw new RuntimeException('Attendance update failed.');
        if (!(($this->audit)($description, (int) $before['id'], $before, $changes))) throw new RuntimeException('Attendance audit could not be saved.');
        return array_replace($before, $changes);
    }

    public function clockIn(int $userId): array
    {
        return $this->write($userId, function() use ($userId) {
            if ($this->db->table('attendance')->where('user_id', $userId)->where('time_out', null)->countAllResults()) throw new DomainException('Already clocked in. Clock out of your open attendance first.');
            $now = $this->now(); $schedule = $this->currentSchedule($userId);
            $schedule['break_schedule'] = AttendanceWindow::breaks($schedule);
            $date = AttendanceWindow::shiftDate($now, $schedule);
            if ($this->db->table('attendance')->where('user_id', $userId)->where('COALESCE(shift_date, DATE(time_in))', $date)->countAllResults()) throw new DomainException('Attendance has already been recorded for this shift date.');
            $row = ['user_id' => $userId, 'time_in' => $now, 'time_in_dt' => $now, 'shift_date' => $date,
                'schedule_snapshot' => json_encode($schedule, JSON_THROW_ON_ERROR), 'break_log' => '[]', 'breaks_taken' => 0,
                'break_duration' => 0, 'state_version' => 0, 'scheduled_start' => $schedule['start_time'] ?? null,
                'scheduled_end' => $schedule['end_time'] ?? null, 'attendance_status' => 'present'];
            [$start] = AttendanceWindow::bounds($row);
            $row['late_minutes'] = $start ? max(0, (int) floor((AttendanceWindow::dateTime($now)->getTimestamp() - $start->getTimestamp()) / 60)) : 0;
            if (!$this->db->table('attendance')->insert($row)) throw new RuntimeException('Clock-in could not be saved.');
            $row['id'] = (int) $this->db->insertID();
            if (!(($this->audit)('Clocked in', $row['id'], [], $row))) throw new RuntimeException('Clock-in audit could not be saved.');
            return $row;
        });
    }

    private function endBreak(array $row, string $now): array
    {
        AttendanceWindow::elapsedHours($row['break_start'], $now);
        $schedule = AttendanceWindow::schedule($row, $this->currentSchedule((int) $row['user_id']));
        $slots = AttendanceWindow::breaks($schedule); $taken = (int) $row['breaks_taken'];
        $minutes = $slots[$taken] ?? $slots[array_key_last($slots)];
        $log = json_decode((string) ($row['break_log'] ?? '[]'), true) ?: [];
        $log[] = ['start' => $row['break_start'], 'end' => $now, 'deducted_minutes' => $minutes, 'policy' => 'preset'];
        return ['break_start' => null, 'break_start_dt' => null, 'break_end' => $now, 'break_end_dt' => $now,
            'break_duration' => round((float) ($row['break_duration'] ?? 0) + $minutes / 60, 2),
            'breaks_taken' => $taken + 1, 'break_log' => json_encode($log, JSON_THROW_ON_ERROR)];
    }

    public function changeBreak(int $userId, int $id, string $action, int $expected): array
    {
        return $this->write($userId, function() use ($userId, $id, $action, $expected) {
            $row = $this->owned($userId, $id); $this->version($row, $expected);
            if ($row['time_out'] !== null) throw new DomainException('This attendance is already clocked out.');
            $now = $this->now(); AttendanceWindow::elapsedHours($row['time_in'], $now);
            if ($action === 'start' && empty($row['break_start'])) {
                $slots = AttendanceWindow::breaks(AttendanceWindow::schedule($row, $this->currentSchedule($userId)));
                if ((int) $row['breaks_taken'] >= count($slots)) throw new DomainException('All scheduled breaks have been used.');
                return $this->save($row, ['break_start' => $now, 'break_start_dt' => $now], 'Break started');
            }
            if ($action === 'end' && !empty($row['break_start'])) return $this->save($row, $this->endBreak($row, $now), 'Break ended');
            throw new DomainException('Break state has changed. Reload the dashboard.');
        });
    }

    public function clockOut(int $userId, int $id, int $expected, string $utReason = '', string $missedReason = ''): array
    {
        return $this->write($userId, function() use ($userId, $id, $expected, $utReason, $missedReason) {
            $row = $this->owned($userId, $id);
            if ($row['time_out'] !== null) {
                // Retrying cannot move an already recorded clock-out, but the caller
                // (e.g. a resubmitted request after a lost response) still needs to
                // know whether it was late, so the missed-clockout prompt isn't lost.
                // No need for currentSchedule() here (and no risk of it throwing if
                // the employee profile is ever missing by the time of a retry): any
                // row that can reach this branch was written by clockIn() or the
                // importer, both of which always save a schedule_snapshot, which
                // AttendanceWindow::schedule() always prefers over the $current arg.
                [, $end] = AttendanceWindow::bounds($row);
                $shiftEndDate = $end ? $end->format('Y-m-d') : substr($row['time_in'], 0, 10);
                $row['missed_clockout'] = substr($row['time_out'], 0, 10) > $shiftEndDate;
                return $row;
            }
            $this->version($row, $expected); $now = $this->now();
            $changes = !empty($row['break_start']) ? $this->endBreak($row, $now) : [];
            $effective = array_replace($row, $changes);
            $analysis = (new AttendanceComputationService())->analyzeClockOut($userId, $now, $effective);
            $ot = $analysis['overtime']; $ut = $analysis['undertime'];
            [, $end] = AttendanceWindow::bounds($effective, $this->currentSchedule($userId));
            $shiftEndDate = $end ? $end->format('Y-m-d') : substr($row['time_in'], 0, 10);
            $missed = substr($now, 0, 10) > $shiftEndDate;
            if (mb_strlen($utReason) > 255 || mb_strlen($missedReason) > 2000) throw new DomainException('Please shorten the clock-out reason.');
            $status = $ut['approved_ut'] > 0 ? 'approved_undertime' : ($ut['unapproved_ut'] > 0 ? 'unapproved_undertime' : ($ot['approved_ot'] > 0 ? 'approved_overtime' : ($ot['unapproved_ot'] > 0 ? 'unapproved_overtime' : 'present')));
            $changes += ['time_out' => $now, 'time_out_dt' => $now,
                'total_hours' => AttendanceWindow::elapsedHours($row['time_in'], $now, (float) ($effective['break_duration'] ?? 0)),
                'night_diff_hours' => AttendanceWindow::nightHours($row['time_in'], $now),
                'approved_ot_hours' => $ot['approved_ot'], 'unapproved_ot_hours' => $ot['unapproved_ot'],
                'approved_ut_hours' => $ut['approved_ut'], 'unapproved_ut_hours' => $ut['unapproved_ut'],
                'unapproved_ut_reason' => $ut['unapproved_ut'] > 0 ? $utReason : null,
                'missed_clockout_reason' => $missed ? $missedReason : null, 'attendance_status' => $status];
            $result = $this->save($row, $changes, empty($row['break_start']) ? 'Clocked out' : 'Clocked out and ended active break');
            $result['missed_clockout'] = $missed;
            return $result;
        });
    }
}
