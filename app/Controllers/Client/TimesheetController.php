<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\TimesheetsService;
use App\Models\Client\AttendanceModel;

class TimesheetController extends BaseController
{
    protected TimesheetsService $timesheetsService;

    public function __construct()
    {
        $this->timesheetsService = new TimesheetsService();
    }

    public function index()
    {
        $user = auth()->user();
        if (!$user) return redirect()->to('/login');

        $userId = (int) $user->id;
        $now = date('Y-m-d');
        $period = $this->resolveCutoffByDate($now);

        // Detailed timesheet for the current cutoff
        $detailed = $this->timesheetsService->getDetailedTimesheet($userId, $period['start'], $period['end']);

        // Existing data for other parts of the view
        $timesheet = $this->timesheetsService->listCurrentCutoffByUserId($userId);
        $timesheetHistory = $this->timesheetsService->listCutoffHistoryByUserId($userId);
        $dailyTimeLog = $this->timesheetsService->listDailyTimeLogByUserId($userId);

        return view('client_portal/timesheet', [
            'timesheet'         => $timesheet,
            'timesheetHistory'  => $timesheetHistory,
            'dailyTimeLog'      => $dailyTimeLog,
            'currentUserId'     => $userId,
            // New data for Excel‑style table
            'dailyTimesheet'    => $detailed['daily'],
            'totals'            => $detailed['totals'],
            'period_start'      => $period['start'],
            'period_end'        => $period['end'],
        ]);
    }

    public function update(int $id)
    {
        $user = auth()->user();
        if (!$user) return redirect()->to('/login');

        $date = trim($this->request->getPost('date'));
        $startTime = trim($this->request->getPost('start_time'));
        $endTime = trim($this->request->getPost('end_time'));
        $taskPerformed = trim($this->request->getPost('task_performed'));

        if ($taskPerformed === '') {
            return redirect()->back()->with('error', 'Task Performed is required.');
        }
        // ... validation as before ...

        $updated = $this->timesheetsService->updateByUserId($id, (int) $user->id, [
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'task_performed' => $taskPerformed,
        ]);

        if (!$updated) {
            return redirect()->back()->with('error', 'Unable to update timesheet.');
        }
        return redirect()->back()->with('success', 'Timesheet updated successfully.');
    }

    public function delete(int $id)
    {
        $user = auth()->user();
        if (!$user) return redirect()->to('/login');

        $deleted = $this->timesheetsService->deleteByUserId($id, (int) $user->id);
        if (!$deleted) {
            return redirect()->back()->with('error', 'Unable to delete timesheet.');
        }
        return redirect()->back()->with('success', 'Timesheet deleted successfully.');
    }

    public function dailyTimeLog()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated.']);
        }
        $dailyTimeLog = $this->timesheetsService->listDailyTimeLogByUserId((int) $user->id);
        return $this->response->setJSON($dailyTimeLog);
    }

    // AJAX endpoint to update the task log (task_performed) for a day
    public function updateTaskLog(int $attendanceId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $data          = $this->request->getJSON(true);
        $taskPerformed = trim($data['task_performed'] ?? '');
        $client        = trim($data['client']         ?? '');

        $attModel   = new AttendanceModel();
        $attendance = $attModel->where('id', $attendanceId)->where('user_id', $user->id)->first();
        if (!$attendance) {
            return $this->response->setJSON(['success' => false, 'message' => 'Attendance record not found']);
        }

        $tasksModel = new \App\Models\Client\TasksModel();
        $existing   = $tasksModel->where('attendance_id', $attendanceId)->where('user_id', $user->id)->first();

        $payload = [
            'task_performed' => $taskPerformed,
            'client'         => $client,
        ];

        if ($existing) {
            $tasksModel->update($existing['id'], $payload);
        } else {
            $tasksModel->insert(array_merge($payload, [
                'attendance_id' => $attendanceId,
                'user_id'       => $user->id,
                'saved_at'      => date('Y-m-d H:i:s'),
            ]));
        }

        return $this->response->setJSON(['success' => true]);
    }

    // AJAX endpoint to update IN/OUT times (admin use)
    public function updateAttendance(int $attendanceId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $data = $this->request->getJSON(true);
        $allowedFields = ['time_in', 'time_out'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $data[$field])) {
                    return $this->response->setJSON(['success' => false, 'message' => "Invalid $field format. Use HH:MM:SS"]);
                }
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid fields to update']);
        }

        $attModel = new AttendanceModel();
        $attendance = $attModel->where('id', $attendanceId)->where('user_id', $user->id)->first();
        if (!$attendance) {
            return $this->response->setJSON(['success' => false, 'message' => 'Attendance record not found']);
        }

        $updated = $attModel->update($attendanceId, $updateData);
        if ($updated) {
            $this->recalculateAttendanceTotals($attendanceId);
            return $this->response->setJSON(['success' => true]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Update failed']);
    }

    private function recalculateAttendanceTotals(int $attendanceId)
    {
        $attModel = new AttendanceModel();
        $att = $attModel->find($attendanceId);
        if (!$att || !$att['time_in'] || !$att['time_out']) return;

        $start = new \DateTime($att['time_in']);
        $end   = new \DateTime($att['time_out']);
        if ($end < $start) $end->modify('+1 day');
        $diff = $end->diff($start);
        $totalMinutes = $diff->h * 60 + $diff->i;
        $breakMinutes = (float)($att['break_duration'] ?? 0) * 60;
        $totalMinutes = max(0, $totalMinutes - $breakMinutes);
        $totalHours = round($totalMinutes / 60, 2);

        $nightDiff =
        $this->computeNightDiffForAttendance(
            date('Y-m-d', strtotime($att['time_in'])),
            date('H:i:s', strtotime($att['time_in'])),
            date('H:i:s', strtotime($att['time_out']))
        );
        $attModel->update($attendanceId, [
            'total_hours' => $totalHours,
            'night_diff_hours' => $nightDiff,
        ]);
    }

    private function computeNightDiffForAttendance(string $date, string $timeIn, string $timeOut): float
    {
        $nightStart = '22:00:00';
        $nightEnd   = '06:00:00';
        $startTs = strtotime("$date $timeIn");
        $endTs   = strtotime("$date $timeOut");
        if ($endTs < $startTs) $endTs += 86400;

        $nightStartTs = strtotime("$date $nightStart");
        $nightEndTs   = strtotime("$date $nightEnd");
        if ($nightEndTs < $nightStartTs) $nightEndTs += 86400;

        $nightMinutes = 0;
        for ($t = $startTs; $t < $endTs; $t++) {
            if ($t >= $nightStartTs && $t < $nightEndTs) $nightMinutes++;
        }
        return round($nightMinutes / 60, 2);
    }

    private function resolveCutoffByDate(string $date): array
    {
        $day = (int)date('d', strtotime($date));
        if ($day <= 15) {
            return ['start' => date('Y-m-01', strtotime($date)), 'end' => date('Y-m-15', strtotime($date))];
        } else {
            return ['start' => date('Y-m-16', strtotime($date)), 'end' => date('Y-m-t', strtotime($date))];
        }
    }

    public function historyDetails()
    {
        $user = auth()->user();

        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $data =
            $this->timesheetsService
                ->getDetailedTimesheet(
                    $user->id,
                    $start,
                    $end
                );

        return $this->response->setJSON($data);
    }

    public function exportHistory()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $data = $this->timesheetsService
            ->getDetailedTimesheet(
                $user->id,
                $start,
                $end
            );

        $filename = "Timesheet_{$start}_to_{$end}.csv";

        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, [
            'Date',
            'Time In',
            'Time Out',
            'Regular Hours',
            'OT Hours',
            'RDOT Hours',
            'Tardy Minutes',
            'Night Diff',
            'Remarks',
            'Client',
            'Task Performed'
        ]);

        // Data rows
        foreach ($data['daily'] as $row) {
            fputcsv($output, [
                $row['date'] ?? '',
                $row['in'] ?? '',
                $row['out'] ?? '',
                $row['regular_hours'] ?? '',
                $row['ot_hours'] ?? '',
                $row['rdot_hours'] ?? '',
                $row['tardy_minutes'] ?? '',
                $row['night_diff'] ?? '',
                $row['remarks'] ?? '',
                $row['client'] ?? '',
                $row['task_performed'] ?? '',
            ]);
        }

        fclose($output);
        exit;
}
}