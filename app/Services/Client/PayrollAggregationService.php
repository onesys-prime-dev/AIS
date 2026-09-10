<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\PayrollCutoffModel;
use App\Models\Client\PayrollCutoffSummaryModel;
use App\Models\Client\RequestModel;
use App\Models\Client\HolidayModel;

class PayrollAggregationService
{
    protected AttendanceModel $attendanceModel;
    protected RequestModel $requestModel;
    protected PayrollCutoffSummaryModel $summaryModel;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->requestModel = new RequestModel();
        $this->summaryModel = new PayrollCutoffSummaryModel();
    }

    public function aggregateCutoff(int $cutoffId): void
    {
        $cutoffModel = new PayrollCutoffModel();
        $cutoff = $cutoffModel->find($cutoffId);
        if (!$cutoff) return;

        $start = $cutoff['start_date'];
        $end   = $cutoff['end_date'];
        $totalWorkingDays = $this->getTotalWorkingDays($start, $end);

        $employeeModel = new \App\Models\Client\EmployeeModel();
        // Fetch both employee id and user_id so attendance/requests can be queried correctly
        $employees = $employeeModel->select('id, user_id')->findAll();
        $posModel  = new \App\Models\Client\EmployeePositionModel();

        foreach ($employees as $emp) {
            $position    = $posModel->where('employee_id', $emp['id'])->first();
            $workingType = $position['working_day_type'] ?? null;
            $schedStart  = ($workingType !== 'flexible' && !empty($position['start_time']))
                           ? $position['start_time'] : null;

            $summary = $this->summaryModel->getOrCreate($emp['id'], $cutoffId);
            $this->aggregateForEmployee($summary['id'], (int) $emp['user_id'], $start, $end, $totalWorkingDays, $schedStart);
            $this->applyRequestsToSummary($summary['id'], (int) $emp['user_id'], $start, $end);
        }
    }

    private function getTotalWorkingDays(string $startDate, string $endDate): int
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $end->modify('+1 day');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);
        
        $workingDays = 0;
        foreach ($period as $date) {
            $dayOfWeek = $date->format('N');
            if ($dayOfWeek < 6) {
                $workingDays++;
            }
        }
        return $workingDays;
    }

    private function aggregateForEmployee(int $summaryId, int $userId, string $start, string $end, int $totalWorkingDays, ?string $schedStart = null): void
    {
        $attendances = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('COALESCE(shift_date, DATE(time_in)) >=', $start)
            ->where('COALESCE(shift_date, DATE(time_in)) <=', $end)
            ->findAll();

        $attendedDates = [];
        $totalRegularHours = 0;
        $totalNightDiffHours = 0;
        $totalTardyMinutes = 0;
        $workedDays = 0;

        $regOtLe10 = 0;
        $regOtExcess = 0;
        $restOtLe10 = 0;
        $restOtExcess = 0;
        $holidayOtLe10 = 0;
        $holidayOtExcess = 0;

        foreach ($attendances as $att) {
            if (empty($att['time_in']) || empty($att['time_out'])) {
                continue;
            }

            // Prefer the saved shift_date (overnight-aware) over the raw time_in date
            $attDate = $att['shift_date'] ?? date('Y-m-d', strtotime($att['time_in']));
            $attendedDates[] = $attDate;
            
            $worked = (float) $att['total_hours'];
            $night = (float) $att['night_diff_hours'];
            $regularWorkHours = min($worked, 8);
            $otHours = max(0, $worked - 8);

            // Excess hours only get paid as OT if there's an approved overtime
            // request covering that date — otherwise they're not credited (e.g. a
            // missed clock-out that inflates total_hours, or unapproved late work).
            if (! $this->hasApprovedOvertimeRequest($userId, $attDate)) {
                $otHours = 0;
            }

            $dayOfWeek = date('N', strtotime($attDate));
            $isRestDay = ($dayOfWeek == 7);
            $isHoliday = $this->isHoliday($attDate);

            $first10 = min($otHours, 10);
            $excess = max(0, $otHours - 10);

            if ($isHoliday) {
                $holidayOtLe10 += $first10;
                $holidayOtExcess += $excess;
            } elseif ($isRestDay) {
                $restOtLe10 += $first10;
                $restOtExcess += $excess;
            } else {
                $regOtLe10 += $first10;
                $regOtExcess += $excess;
            }

            $totalRegularHours += $regularWorkHours;
            $totalNightDiffHours += $night;
            $totalTardyMinutes += $this->calculateTardyMinutes($att['time_in'], $schedStart);
            $workedDays++;
        }
        // Calculate absent days
        $absentDays = max(0, $totalWorkingDays - $workedDays);

        $this->summaryModel->update($summaryId, [
            'regular_hours'          => round($totalRegularHours, 2),
            'night_diff_hours'       => round($totalNightDiffHours, 2),
            'tardy_minutes'          => $totalTardyMinutes,
            'reg_ot_hours_le10'      => round($regOtLe10, 2),
            'reg_ot_hours_excess'    => round($regOtExcess, 2),
            'rest_ot_hours_le10'     => round($restOtLe10, 2),
            'rest_ot_hours_excess'   => round($restOtExcess, 2),
            'holiday_ot_hours_le10'  => round($holidayOtLe10, 2),
            'holiday_ot_hours_excess'=> round($holidayOtExcess, 2),
            'worked_days'            => $workedDays,
            'total_working_days'     => $totalWorkingDays,
            'absent_days'            => $absentDays,
        ]);
    }

    private function applyRequestsToSummary(int $summaryId, int $userId, string $start, string $end): void
    {
        $requests = $this->requestModel
            ->where('requested_by', $userId)
            ->where('status', 'approved')
            ->where(function($query) use ($start, $end) {
                $query->where('start_date >=', $start)
                      ->where('end_date <=', $end);
            })
            ->findAll();

        $paidLeaveDays = 0;
        $unpaidLeaveDays = 0;
        $undertimeHours = 0;
        $requestOtHours = 0;

        foreach ($requests as $req) {
            switch ($req['request_type']) {
                case 'leave':
                    $isPaid = $req['is_paid_leave'] ?? 0;
                    if ($isPaid) {
                        $paidLeaveDays += $req['duration'];
                    } else {
                        $unpaidLeaveDays += $req['duration'];
                    }
                    break;
                case 'overtime':
                    $requestOtHours += $req['duration'];
                    break;
                case 'undertime':
                    $undertimeHours += $req['duration'];
                    break;
            }
        }

        // Get current summary to adjust absence calculation
        $summary = $this->summaryModel->find($summaryId);
        if ($summary) {
            // Paid leaves should NOT be counted as absence
            $adjustedAbsentDays = max(0, ($summary['absent_days'] ?? 0) - $paidLeaveDays);
            
            $this->summaryModel->update($summaryId, [
                'paid_leave_days'       => $paidLeaveDays,
                'unpaid_leave_days'     => $unpaidLeaveDays + $adjustedAbsentDays,
                'undertime_hours'       => $undertimeHours,
                'request_overtime_hours'=> $requestOtHours,
            ]);
        } else {
            $this->summaryModel->update($summaryId, [
                'paid_leave_days'       => $paidLeaveDays,
                'unpaid_leave_days'     => $unpaidLeaveDays,
                'undertime_hours'       => $undertimeHours,
                'request_overtime_hours'=> $requestOtHours,
            ]);
        }
    }

    private function hasApprovedOvertimeRequest(int $userId, string $date): bool
    {
        $row = $this->requestModel
            ->where('requested_by', $userId)
            ->where('request_type', 'overtime')
            ->where('status', 'approved')
            ->where('start_date <=', $date)
            ->groupStart()
                ->where('end_date >=', $date)
                ->orWhere('end_date', null)
            ->groupEnd()
            ->first();

        return $row !== null;
    }

    private function calculateTardyMinutes(?string $timeIn, ?string $schedStart): int
    {
        if (! $timeIn || ! $schedStart) return 0;

        $timeOnly = date('H:i:s', strtotime($timeIn));
        if ($timeOnly <= $schedStart) return 0;

        $in    = new \DateTime($timeOnly);
        $start = new \DateTime($schedStart);
        $diff  = $start->diff($in);
        return ($diff->h * 60) + $diff->i;
    }

    private function isHoliday(string $date): bool
    {
        $model = new HolidayModel();
        return $model->isHoliday($date) !== null;
    }
}