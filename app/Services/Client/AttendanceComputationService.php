<?php

namespace App\Services\Client;

class AttendanceComputationService{

    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
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
        $date = date('Y-m-d');

        $schedule =
            $this->getEmployeeSchedule(
                $userId
            );

        if (!$schedule) {
            return [
                'overtime' => [
                    'approved_ot' => 0,
                    'unapproved_ot' => 0,
                ],
                'undertime' => [
                    'approved_ut' => 0,
                    'unapproved_ut' => 0,
                ],
            ];
        }

        $scheduledEnd =
            $date . ' ' .
            $schedule['end_time'];

        return [
            'overtime' =>
                $this->calculateOvertime(
                    $userId,
                    $date,
                    $actualOut,
                    $scheduledEnd
                ),

            'undertime' =>
                $this->calculateUndertime(
                    $userId,
                    $date,
                    $actualOut,
                    $scheduledEnd
                )
        ];
    }
}