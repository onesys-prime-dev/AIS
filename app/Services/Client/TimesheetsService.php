<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\TasksModel;

class TimesheetsService
{
    protected TasksModel $tasksModel;
    protected AttendanceModel $attendanceModel;

    public function __construct()
    {
        $this->tasksModel      = new TasksModel();
        $this->attendanceModel = new AttendanceModel();
    }

    public function listByUserId(int $userId): array
    {
        return $this->tasksModel
            ->select('attendance.date, tasks.start_time, tasks.end_time, employee_positions.department, tasks.task_performed, tasks.id, tasks.user_id, tasks.attendance_id')
            ->join('attendance', 'attendance.id = tasks.attendance_id')
            ->join('employees', 'employees.user_id = tasks.user_id')
            ->join('employee_positions', 'employee_positions.employee_id = employees.id')
            ->where('tasks.user_id', $userId)
            ->orderBy('attendance.date', 'DESC')
            ->orderBy('tasks.start_time', 'ASC')
            ->findAll();
    }

    public function updateByUserId(int $taskId, int $userId, array $payload): bool
    {
        $task = $this->tasksModel
            ->where('id', $taskId)
            ->where('user_id', $userId)
            ->first();

        if (!$task) {
            return false;
        }

        $taskUpdateData = [];

        if (isset($payload['start_time'])) {
            $taskUpdateData['start_time'] = $payload['start_time'];
        }

        if (isset($payload['end_time'])) {
            $taskUpdateData['end_time'] = $payload['end_time'];
        }

        if (isset($payload['task_performed'])) {
            $taskUpdateData['task_performed'] = $payload['task_performed'];
        }

        $ok = true;

        if (!empty($taskUpdateData)) {
            // Capture old values before updating
            $old = [
                'start_time'     => $task['start_time'],
                'end_time'       => $task['end_time'],
                'task_performed' => $task['task_performed'],
            ];

            $ok = $this->tasksModel->update($taskId, $taskUpdateData);

            if ($ok) {
                service('activityLogger')->taskUpdated($taskId, $old, $taskUpdateData);
            }
        }

        if (isset($payload['date']) && $payload['date'] !== '') {
            $ok = $ok && $this->attendanceModel
                ->where('id', (int) $task['attendance_id'])
                ->where('user_id', $userId)
                ->set('date', $payload['date'])
                ->update();
        }

        return $ok;
    }

    public function deleteByUserId(int $taskId, int $userId): bool
    {
        $task = $this->tasksModel
            ->where('id', $taskId)
            ->where('user_id', $userId)
            ->first();

        if (!$task) {
            return false;
        }

        $deleted = $this->tasksModel->delete($taskId);

        if ($deleted) {
            service('activityLogger')->taskDeleted($taskId, $task);
        }

        return $deleted;
    }
}
