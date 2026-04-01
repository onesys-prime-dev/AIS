<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\TasksModel;
use DateTime;

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

    public function listCurrentCutoffByUserId(int $userId): array
	{
		$now = date('Y-m-d');
		$period = $this->resolveCutoffByDate($now);

		return $this->baseListByUserIdBuilder($userId)
			->where('attendance.date >=', $period['start'])
			->where('attendance.date <=', $period['end'])
			->orderBy('attendance.date', 'DESC')
			->orderBy('tasks.start_time', 'ASC')
			->findAll();
	}

	public function listCutoffHistoryByUserId(int $userId): array
	{
		$rows = $this->listByUserId($userId);
		$currentKey = $this->cutoffKeyByDate(date('Y-m-d'));
		$groups = [];

		foreach ($rows as $row) {
			$date = (string) ($row['date'] ?? '');
			if ($date === '') {
				continue;
			}

			$period = $this->resolveCutoffByDate($date);
			$key = $period['start'] . '|' . $period['end'];

			// History is view-only and excludes the active/current cutoff period.
			if ($key === $currentKey) {
				continue;
			}

			if (! isset($groups[$key])) {
				$groups[$key] = [
					'period_start' => $period['start'],
					'period_end' => $period['end'],
					'total_minutes' => 0,
					'entries_count' => 0,
					'days' => [],
				];
			}

			$groups[$key]['total_minutes'] += $this->durationMinutes(
				(string) ($row['start_time'] ?? ''),
				(string) ($row['end_time'] ?? '')
			);
			$groups[$key]['entries_count']++;
			$groups[$key]['days'][$date] = true;
		}

		krsort($groups);
		$result = [];

		foreach ($groups as $group) {
			$totalMinutes = (int) $group['total_minutes'];
			$result[] = [
				'period_start' => $group['period_start'],
				'period_end' => $group['period_end'],
				'total_minutes' => $totalMinutes,
				'total_hours' => number_format($totalMinutes / 60, 2, '.', ''),
				'entries_count' => (int) $group['entries_count'],
				'days_covered' => count($group['days']),
			];
		}

		return $result;
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

		private function baseListByUserIdBuilder(int $userId): TasksModel
	{
		return $this->tasksModel
			->select('attendance.date, tasks.start_time, tasks.end_time, employee_positions.department, tasks.task_performed, tasks.id, tasks.user_id, tasks.attendance_id')
			->join('attendance', 'attendance.id = tasks.attendance_id')
			->join('employees', 'employees.user_id = tasks.user_id')
			->join('employee_positions', 'employee_positions.employee_id = employees.id')
			->where('tasks.user_id', $userId);
	}

	private function resolveCutoffByDate(string $date): array
	{
		$base = new DateTime($date);
		$day = (int) $base->format('d');

		if ($day <= 15) {
			$start = $base->format('Y-m-01');
			$end = $base->format('Y-m-15');

			return [
				'start' => $start,
				'end' => $end,
			];
		}

		$start = $base->format('Y-m-16');
		$end = $base->format('Y-m-t');

		return [
			'start' => $start,
			'end' => $end,
		];
	}

	private function cutoffKeyByDate(string $date): string
	{
		$period = $this->resolveCutoffByDate($date);

		return $period['start'] . '|' . $period['end'];
	}

	private function durationMinutes(string $startTime, string $endTime): int
	{
		if ($startTime === '' || $endTime === '') {
			return 0;
		}

		$startStamp = strtotime($startTime);
		$endStamp = strtotime($endTime);

		if ($startStamp === false || $endStamp === false || $endStamp <= $startStamp) {
			return 0;
		}

		return (int) floor(($endStamp - $startStamp) / 60);
	}
}
