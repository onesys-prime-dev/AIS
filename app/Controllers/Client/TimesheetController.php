<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\TimesheetsService;
use App\Models\Client\AttendanceModel;
use App\Models\Client\EmployeeModel;

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
            'isPayrollAdmin'    => $this->isPayrollAdmin(),
        ]);
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

    // AJAX endpoint: list every task logged against one attendance session,
    // plus the clock-in/out window they must fit within.
    public function listTasks(int $attendanceId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        return $this->response->setJSON(
            $this->timesheetsService->listTasksForAttendance($attendanceId, (int) $user->id)
        );
    }

    // AJAX endpoint: log a new task against an attendance session.
    public function createTask(int $attendanceId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $data = $this->request->getJSON(true) ?? [];

        return $this->response->setJSON(
            $this->timesheetsService->createTask($attendanceId, (int) $user->id, $data)
        );
    }

    // AJAX endpoint: edit an existing task (looked up by its own id).
    public function updateTask(int $taskId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $data = $this->request->getJSON(true) ?? [];

        return $this->response->setJSON(
            $this->timesheetsService->updateByUserId($taskId, (int) $user->id, $data)
        );
    }

    // AJAX endpoint: delete a task (looked up by its own id).
    public function deleteTask(int $taskId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $deleted = $this->timesheetsService->deleteByUserId($taskId, (int) $user->id);

        return $this->response->setJSON([
            'success' => $deleted,
            'message' => $deleted ? null : 'Task not found',
        ]);
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
        $start = new \DateTime("$date $timeIn");
        $end   = new \DateTime("$date $timeOut");
        if ($end < $start) $end->modify('+1 day');

        $nightStartDt = new \DateTime("$date $nightStart");
        $nightEndDt   = new \DateTime("$date $nightEnd");
        if ($nightEndDt < $nightStartDt) $nightEndDt->modify('+1 day');

        $overlapStart = max($start, $nightStartDt);
        $overlapEnd   = min($end, $nightEndDt);
        if ($overlapStart >= $overlapEnd) return 0.0;

        $diff = $overlapStart->diff($overlapEnd);
        return round(($diff->h * 60 + $diff->i) / 60, 2);
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

        $this->streamTimesheetCsv($data['daily'], "Timesheet_{$start}_to_{$end}.csv");
    }

    private function streamTimesheetCsv(array $rows, string $filename): void
    {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Date',
            'Time In',
            'Time Out',
            'Regular Hours',
            'OT Hours',
            'Unapproved OT Hours',
            'RDOT Hours',
            'Tardy Minutes',
            'Undertime Hours',
            'Night Diff',
            'Remarks',
            'Client',
            'Task Performed'
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['date'] ?? '',
                $row['in'] ?? '',
                $row['out'] ?? '',
                $row['regular_hours'] ?? '',
                $row['ot_hours'] ?? '',
                $row['unapproved_ot_hours'] ?? '',
                $row['rdot_hours'] ?? '',
                $row['tardy_minutes'] ?? '',
                $row['undertime_hours'] ?? '',
                $row['night_diff'] ?? '',
                $row['remarks'] ?? '',
                $row['client'] ?? '',
                $row['task_performed'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    // ================= ADMIN: REVIEW AN EMPLOYEE'S TIMESHEET =================

    private function isPayrollAdmin(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->inGroup('client_payroll_staff') || $user->inGroup('superadmin') || $user->inGroup('admin');
    }

    public function adminEmployeeTimesheetHistory()
    {
        if (!$this->isPayrollAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        if (!$employeeId) {
            return $this->response->setJSON(['error' => 'Missing employee_id']);
        }

        try {
            $employee = (new EmployeeModel())->find($employeeId);
            if (!$employee) {
                return $this->response->setJSON(['error' => 'Employee not found']);
            }

            $userId = (int) $employee['user_id'];

            return $this->response->setJSON([
                'employee_name'  => trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')),
                'current_period' => $this->resolveCutoffByDate(date('Y-m-d')),
                'history'        => $this->timesheetsService->listCutoffHistoryByUserId($userId),
            ]);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to load employee timesheet history.');
        }
    }

    public function adminEmployeeTimesheet()
    {
        if (!$this->isPayrollAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        $start      = $this->request->getGet('start');
        $end        = $this->request->getGet('end');

        if (!$employeeId || !$start || !$end) {
            return $this->response->setJSON(['error' => 'Missing employee_id, start, or end']);
        }

        try {
            $employee = (new EmployeeModel())->find($employeeId);
            if (!$employee) {
                return $this->response->setJSON(['error' => 'Employee not found']);
            }

            $data = $this->timesheetsService->getDetailedTimesheet((int) $employee['user_id'], $start, $end);
            $data['employee_name'] = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));

            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to load employee timesheet.');
        }
    }

    public function adminExportEmployeeTimesheet()
    {
        if (!$this->isPayrollAdmin()) {
            return $this->response->setStatusCode(403)->setBody('Unauthorized');
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        $start      = $this->request->getGet('start');
        $end        = $this->request->getGet('end');

        if (!$employeeId || !$start || !$end) {
            return $this->response->setStatusCode(400)->setBody('Missing employee_id, start, or end');
        }

        $employee = (new EmployeeModel())->find($employeeId);
        if (!$employee) {
            return $this->response->setStatusCode(404)->setBody('Employee not found');
        }

        $data = $this->timesheetsService->getDetailedTimesheet((int) $employee['user_id'], $start, $end);
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim(($employee['first_name'] ?? '') . '_' . ($employee['last_name'] ?? '')));

        $this->streamTimesheetCsv($data['daily'], "Timesheet_{$slug}_{$start}_to_{$end}.csv");
    }
}