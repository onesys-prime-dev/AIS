<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;

class AttendanceComputationService{

    protected $db;
    protected AttendanceModel $attendanceModel;

    public function __construct()
    {
        $this->db = db_connect();
        $this->attendanceModel = new AttendanceModel();
    }

    public function getEmployeeSchedule(
        int $userId
    ): ?array
    {
        $db = db_connect();

        $employee = $db->table('employees')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$employee) {
            return null;
        }

        return $db->table('employee_positions')
            ->where('employee_id', $employee['id'])
            ->get()
            ->getRowArray();
    }

    public function getApprovedRequest(
        int $userId,
        string $date,
        string $type
    ): ?array
    {
        $db = db_connect();

       $query = $db->table('requests')
        ->where('requested_by', $userId)
        ->where('request_type', $type)
        ->where('status', 'approved')
        ->where('start_date <=', $date)
        ->groupStart()
            ->where('end_date >=', $date)
            ->orWhere('end_date', null)
        ->groupEnd();

        return $query->get()->getRowArray();
    }

    public function calculateLate(
        string $timeIn,
        string $scheduledStart
    ): int
    {
        $actual = strtotime($timeIn);
        $start = strtotime($scheduledStart);

        if ($actual <= $start) {
            return 0;
        }

        return floor(
            ($actual - $start) / 60
        );
    }

    public function calculateOvertime(
        int $userId,
        string $date,
        string $actualOut,
        string $scheduledEnd
    ): array
    {
        $result = [
            'approved_ot'   => 0,
            'unapproved_ot' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | No OT if employee did not exceed scheduled end
        |--------------------------------------------------------------------------
        */

        if (
            strtotime($actualOut)
            <=
            strtotime($scheduledEnd)
        ) {
            return $result;
        }

        /*
        |--------------------------------------------------------------------------
        | Get today's attendance
        |--------------------------------------------------------------------------
        */

        $attendance = $this->db
            ->table('attendance')
            ->where('user_id', $userId)
            ->where('DATE(time_in)', $date)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (! $attendance) {
            return $result;
        }

        /*
        |--------------------------------------------------------------------------
        | Compute total worked hours
        |--------------------------------------------------------------------------
        */

        $breakDuration =
            (float) ($attendance['break_duration'] ?? 0);

        $totalHoursWorked =
            (
                strtotime($actualOut)
                -
                strtotime($attendance['time_in'])
            )
            / 3600;

        $totalHoursWorked -= $breakDuration;

        $totalHoursWorked =
            max(0, $totalHoursWorked);

        /*
        |--------------------------------------------------------------------------
        | Compute required hours dynamically
        |--------------------------------------------------------------------------
        */

        $schedule =
            $this->getEmployeeSchedule(
                $userId
            );

        if (! $schedule) {
            return $result;
        }

        $scheduledStart =
            $date
            . ' '
            . $schedule['start_time'];

        $requiredHours =
            (
                strtotime($scheduledEnd)
                -
                strtotime($scheduledStart)
            )
            / 3600;

        /*
        |--------------------------------------------------------------------------
        | Employee must complete required hours first
        |--------------------------------------------------------------------------
        */

        if (
            $totalHoursWorked
            <
            $requiredHours
        ) {
            return $result;
        }

        /*
        |--------------------------------------------------------------------------
        | Compute OT hours
        |--------------------------------------------------------------------------
        */

        $hours =
            (
                strtotime($actualOut)
                -
                strtotime($scheduledEnd)
            )
            / 3600;

        $request =
            $this->getApprovedRequest(
                $userId,
                $date,
                'overtime'
            );

        if ($request) {
            $result['approved_ot'] =
                round($hours, 2);
        } else {
            $result['unapproved_ot'] =
                round($hours, 2);
        }

        return $result;
    }

    public function calculateUndertime(
        int $userId,
        string $date,
        string $actualOut,
        string $scheduledEnd
    ): array
    {
        $result = [
            'approved_ut' => 0,
            'unapproved_ut' => 0
        ];

        if (
            strtotime($actualOut)
            >=
            strtotime($scheduledEnd)
        ) {
            return $result;
        }

        $hours =
            (
                strtotime($scheduledEnd)
                -
                strtotime($actualOut)
            )
            /
            3600;

        $request =
            $this->getApprovedRequest(
                $userId,
                $date,
                'undertime'
            );

        if ($request) {
            $result['approved_ut'] = round($hours, 2);
        } else {
            $result['unapproved_ut'] = round($hours, 2);
        }

        return $result;
    }
    
    public function analyzeClockOut(
        int $userId,
        string $actualOut
    ): array
    {
        $schedule = $this->getEmployeeSchedule($userId);

        if (! $schedule) {
            return [
                'overtime' => [
                    'approved_ot'   => 0,
                    'unapproved_ot' => 0,
                ],
                'undertime' => [
                    'approved_ut'   => 0,
                    'unapproved_ut' => 0,
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Get the currently open attendance record (still clocked in). Looked up by
        | "still open", not "started today" — a shift that started yesterday and is
        | only being clocked out now (missed clock-out) must still resolve here.
        |--------------------------------------------------------------------------
        */

        $attendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->orderBy('id', 'DESC')
            ->first();

        if (! $attendance) {
            return [
                'overtime' => [
                    'approved_ot'   => 0,
                    'unapproved_ot' => 0,
                ],
                'undertime' => [
                    'approved_ut'   => 0,
                    'unapproved_ut' => 0,
                ],
            ];
        }

        // Use the shift's own date (from time_in), not today's date, so the
        // scheduled start/end and approved-request lookup line up with the shift
        // that's actually being clocked out of, even across a midnight rollover.
        $date = date('Y-m-d', strtotime($attendance['time_in']));

        /*
        |--------------------------------------------------------------------------
        | Required working hours
        |--------------------------------------------------------------------------
        */

        $scheduledStart = $date . ' ' . $schedule['start_time'];
        $scheduledEnd = $date . ' ' . $schedule['end_time'];
        $requiredHours = (strtotime($scheduledEnd) - strtotime($scheduledStart)) / 3600;

        /*
        |--------------------------------------------------------------------------
        | Actual rendered hours
        |--------------------------------------------------------------------------
        */

        $workedHours =
            (
                strtotime($actualOut)
                -
                strtotime($attendance['time_in'])
            ) / 3600;

        $workedHours -=
            (float) ($attendance['break_duration'] ?? 0);

        $workedHours = max(0, $workedHours);

        /*
        |--------------------------------------------------------------------------
        | Difference
        |--------------------------------------------------------------------------
        */

        $difference =
            round(
                $workedHours - $requiredHours,
                2
            );

        $approvedOT = 0;
        $unapprovedOT = 0;
        $approvedUT = 0;
        $unapprovedUT = 0;

        /*
        |--------------------------------------------------------------------------
        | OVERTIME
        |--------------------------------------------------------------------------
        */

        if ($difference > 0) {

            $request =
                $this->getApprovedRequest(
                    $userId,
                    $date,
                    'overtime'
                );

            if ($request) {
                $approvedOT = $difference;
            } else {
                $unapprovedOT = $difference;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UNDERTIME
        |--------------------------------------------------------------------------
        */

        if ($difference < 0) {

            $hours =
                abs($difference);

            $request =
                $this->getApprovedRequest(
                    $userId,
                    $date,
                    'undertime'
                );

            if ($request) {
                $approvedUT = $hours;
            } else {
                $unapprovedUT = $hours;
            }
        }

        return [
            'overtime' => [
                'approved_ot'   => $approvedOT,
                'unapproved_ot' => $unapprovedOT,
            ],
            'undertime' => [
                'approved_ut'   => $approvedUT,
                'unapproved_ut' => $unapprovedUT,
            ],
        ];
    }
}