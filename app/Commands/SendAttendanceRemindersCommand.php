<?php

namespace App\Commands;

use App\Services\Client\AttendanceReminderDelivery;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Sends the attendance reminders (clock in, clock out) that are due and the Team Head / admin escalation.
 * Run every 5 minutes by Windows Task Scheduler (XAMPP has no cron) or by cron on the server:
 *
 *   C:\xampp\php\php.exe C:\xampp\htdocs\AIS\spark attendance:send-reminders
 *   *\/5 * * * *  cd /path/to/AIS && php8.5 spark attendance:send-reminders
 *
 * Safe to run more than once, or two at the same time: each reminder is claimed before it is sent, so it can only go
 * out once. Reminders that are past their stop time are never sent late.
 *
 * --dry-run     lists who would be reminded and what would be sent; sends and writes nothing.
 * --now "2026-11-23 07:40"   pretends it is that moment (Asia/Manila), handy with --dry-run.
 */
final class SendAttendanceRemindersCommand extends BaseCommand
{
    protected $group       = 'AIS';
    protected $name        = 'attendance:send-reminders';
    protected $description = 'Sends the clock-in / clock-out reminders that are due, and the Team Head / admin escalation.';
    protected $usage       = 'attendance:send-reminders [--dry-run] [--now "YYYY-MM-DD HH:MM"]';
    protected $options     = [
        '--dry-run' => 'List what would be sent without sending or recording anything.',
        '--now'     => 'Pretend it is this moment (Asia/Manila), e.g. "2026-11-23 07:40".',
    ];

    public function run(array $params)
    {
        $tz     = new DateTimeZone('Asia/Manila');
        $dryRun = CLI::getOption('dry-run') !== null || in_array('--dry-run', $params, true);
        $nowOpt = CLI::getOption('now');

        try {
            $now = $nowOpt ? new DateTimeImmutable((string) $nowOpt, $tz) : new DateTimeImmutable('now', $tz);
        } catch (\Throwable $e) {
            CLI::error('Could not read --now. Use a format like "2026-11-23 07:40".');
            return EXIT_USER_INPUT;
        }

        CLI::write('Attendance reminders - ' . $now->format('Y-m-d H:i:s') . ($dryRun ? ' (dry run)' : ''));
        if (! \App\Services\Client\ReminderPushSender::configured()) {
            CLI::write('Browser push is not set up (no VAPID keys in .env). Run: php spark attendance:push-keys', 'yellow');
        }

        // Browser push too, when VAPID keys are set and the library is installed.
        $push   = new \App\Services\Client\ReminderPushSender();
        $pusher = $push->available() ? static fn (array $item): string => $push->sendToUser((int) $item['user_id'], $item) : null;
        $delivery = new AttendanceReminderDelivery(null, null, null, $pusher);
        if (! $dryRun && ! $delivery->canSendMail()) {
            CLI::write('Note: no From address is configured (email.fromEmail), so emails cannot be sent. Pop-ups still work.', 'yellow');
        }

        $report = $delivery->run($now, $dryRun);
        if (! $dryRun) {
            // The admin page shows when this last ran, so a stopped scheduler is noticed.
            try { (new \App\Models\Client\PayrollSettingsModel())->setSetting(\App\Controllers\Client\ReminderAdminController::LAST_RUN_KEY, date('Y-m-d H:i:s')); } catch (\Throwable $e) { /* not fatal */ }
        }
        foreach ($report['lines'] as $line) CLI::write('  ' . $line);

        CLI::write(sprintf(
            'Due now: %d | emails sent: %d, failed: %d, no address: %d, no sender: %d | escalation emails: %d | already handled: %d',
            $report['due'], $report['emails_sent'], $report['emails_failed'], $report['no_email'], $report['no_sender'], $report['escalations_sent'], $report['skipped']
        ));
        return $report['emails_failed'] > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
