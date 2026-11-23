<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Libraries\PortalAccess;
use App\Libraries\ReminderConfig;
use App\Models\Client\PayrollSettingsModel;
use App\Services\Client\AttendanceReminderService;

/**
 * Admin side of the attendance reminders (Access Control -> Reminders): the global settings and a report of who the
 * reminders cannot reach. Admin and Super Admin only (the access-control routes are gated by PortalAccess already,
 * and every method checks again).
 */
class ReminderAdminController extends BaseController
{
    public const LAST_RUN_KEY = 'attendance_reminders_last_run';

    private function guard()
    {
        if (! PortalAccess::canManageAccessControl()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Only Admin and Super Admin can manage reminders.']);
        }
        return null;
    }

    /** Current settings, the defaults (for "reset"), and a health line: can email go out, and is the scheduler running. */
    public function settings()
    {
        if ($denied = $this->guard()) return $denied;

        $settings = new PayrollSettingsModel();
        $lastRun  = $settings->getSetting(self::LAST_RUN_KEY);

        return $this->response->setJSON([
            'config'        => AttendanceReminderService::loadConfig(),
            'defaults'      => ReminderConfig::defaults(),
            'can_send_mail' => trim((string) config('Email')->fromEmail) !== '',
            'push_available' => \App\Services\Client\ReminderPushSender::configured() && class_exists(\Minishlink\WebPush\WebPush::class),
            'push_devices'   => \Config\Database::connect()->tableExists('push_subscriptions') ? \Config\Database::connect()->table('push_subscriptions')->countAllResults() : 0,
            'last_run'      => $lastRun ?: null,
            'last_run_ago_minutes' => $lastRun ? (int) floor((time() - strtotime((string) $lastRun)) / 60) : null,
        ]);
    }

    public function save()
    {
        if ($denied = $this->guard()) return $denied;

        $post = $this->request->getPost();
        try {
            $new = ReminderConfig::fromAdminInput(is_array($post) ? $post : []);
        } catch (\DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }

        $old = AttendanceReminderService::loadConfig();
        (new PayrollSettingsModel())->setSetting(AttendanceReminderService::CONFIG_KEY, json_encode($new, JSON_THROW_ON_ERROR));
        service('activityLogger')->log('updated', 'attendance_reminders', 'Attendance reminder settings updated', null, $old, $new);

        return $this->response->setJSON(['success' => true, 'message' => 'Reminder settings saved.', 'config' => $new]);
    }

    /**
     * Who the reminders cannot reach or who switched them off: so the gaps can be fixed. One row per employee with a
     * schedule, only those with something to report.
     */
    public function report()
    {
        if ($denied = $this->guard()) return $denied;

        $db = \Config\Database::connect();
        $rows = $db->table('employees e')
            ->select("e.user_id, TRIM(CONCAT(e.first_name, ' ', e.last_name)) AS name, d.name AS department, p.start_time, p.end_time, p.working_days, i.secret AS email, pr.reminders_enabled, pr.email_enabled, pr.push_enabled, (SELECT COUNT(*) FROM push_subscriptions ps WHERE ps.user_id = e.user_id) AS devices, (SELECT COUNT(*) FROM attendance_reminders ar WHERE ar.user_id = e.user_id AND ar.email_status = 'failed' AND ar.shift_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS failed_emails", false)
            ->join('employee_positions p', 'p.employee_id = e.id')
            ->join('users u', 'u.id = e.user_id')
            ->join('departments d', 'd.id = p.department_id', 'left')
            ->join('auth_identities i', "i.user_id = u.id AND i.type = 'email_password'", 'left')
            ->join('attendance_reminder_prefs pr', 'pr.user_id = u.id', 'left')
            ->where('u.active', 1)->where('u.deleted_at', null)->where('e.user_id IS NOT NULL', null, false)
            ->orderBy('e.first_name', 'ASC')
            ->get()->getResultArray();

        $out = ['total' => 0, 'rows' => []];
        foreach ($rows as $r) {
            $days = json_decode((string) $r['working_days'], true);
            $hasSchedule = ! empty($r['start_time']) && ! empty($r['end_time']) && is_array($days) && $days !== [];
            $issues = [];
            if (! $hasSchedule) $issues[] = 'No schedule';
            if ($r['reminders_enabled'] !== null && (int) $r['reminders_enabled'] === 0) $issues[] = 'Opted out';
            if (! $r['email'] || ! filter_var($r['email'], FILTER_VALIDATE_EMAIL)) $issues[] = 'No email address';
            elseif ($r['email_enabled'] !== null && (int) $r['email_enabled'] === 0) $issues[] = 'Email turned off';
            if ((int) $r['failed_emails'] > 0) $issues[] = $r['failed_emails'] . ' email(s) failed this week';
            $out['total']++;
            if ($issues) $out['rows'][] = ['user_id' => (int) $r['user_id'], 'name' => $r['name'], 'department' => $r['department'] ?? '', 'issues' => $issues, 'push_devices' => (int) $r['devices']];
        }
        return $this->response->setJSON($out);
    }
}
