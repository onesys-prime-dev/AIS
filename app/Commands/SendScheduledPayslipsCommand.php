<?php

namespace App\Commands;

use App\Models\Client\EmployeePayslipModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sends every payslip scheduled on Payroll Output → Payslip whose date has
 * arrived. Run once a day by Windows Task Scheduler (XAMPP has no cron):
 *
 *   C:\xampp\php\php.exe C:\xampp\htdocs\AIS\spark payroll:send-scheduled-payslips
 *
 * Safe to run more than once a day or twice at the same time: each payslip is
 * claimed (status 'sending') before it's emailed, so it can only go out once.
 *
 * Also:
 *  - retries a failed scheduled send, up to EmployeePayslipModel::MAX_AUTO_ATTEMPTS
 *    attempts in total, then leaves it Failed for staff to retry by hand;
 *  - releases payslips stuck in 'sending' for 30+ minutes (e.g. the server
 *    stopped mid-send) back to Failed so they can be retried.
 *
 * --dry-run  lists what would be sent, sends nothing.
 */
final class SendScheduledPayslipsCommand extends BaseCommand
{
    protected $group = 'AIS';
    protected $name = 'payroll:send-scheduled-payslips';
    protected $description = 'Sends payslips whose scheduled send date (Payroll Output → Payslip) has arrived.';
    protected $usage = 'payroll:send-scheduled-payslips [--dry-run]';
    protected $options = ['--dry-run' => 'List what would be sent without sending anything.'];

    public function run(array $params)
    {
        $db     = db_connect();
        $today  = date('Y-m-d');
        $dryRun = CLI::getOption('dry-run') !== null || in_array('--dry-run', $params, true);

        CLI::write('Scheduled payslip send — ' . date('Y-m-d H:i:s') . ($dryRun ? ' (dry run)' : ''));

        // Stuck sends (process died mid-send) → Failed, so they're retried.
        if (!$dryRun) {
            $stuck = $db->table('employee_payslips')
                ->where('send_status', EmployeePayslipModel::STATUS_SENDING)
                ->where('last_attempt_at <', date('Y-m-d H:i:s', strtotime('-30 minutes')))
                ->update([
                    'send_status'     => EmployeePayslipModel::STATUS_FAILED,
                    'last_send_error' => 'Interrupted while sending (server stopped?) — will retry.',
                ]);
            $released = $db->affectedRows();
            if ($stuck && $released > 0) {
                CLI::write("Released {$released} payslip(s) stuck in 'sending'.", 'yellow');
            }
        }

        $due = $db->table('employee_payslips p')
            ->select("p.id, p.employee_id, p.period_start, p.period_end, p.send_status, p.scheduled_send_date, p.send_attempts,
                      CONCAT(e.first_name, ' ', e.last_name) AS employee_name", false)
            ->join('employees e', 'e.id = p.employee_id', 'left')
            ->where('p.sent_at', null)
            ->where('p.scheduled_send_date IS NOT NULL', null, false)
            ->where('p.scheduled_send_date <=', $today)
            ->groupStart()
                ->where('p.send_status', EmployeePayslipModel::STATUS_SCHEDULED)
                ->orGroupStart()
                    ->where('p.send_status', EmployeePayslipModel::STATUS_FAILED)
                    ->where('p.send_attempts <', EmployeePayslipModel::MAX_AUTO_ATTEMPTS)
                ->groupEnd()
            ->groupEnd()
            ->orderBy('p.scheduled_send_date', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->get()->getResultArray();

        if (!$due) {
            CLI::write('Nothing due.');
            return EXIT_SUCCESS;
        }

        CLI::write(count($due) . ' payslip(s) due.');

        if ($dryRun) {
            foreach ($due as $p) {
                CLI::write(sprintf('  would send #%d %s (%s – %s), scheduled %s%s',
                    $p['id'], $p['employee_name'] ?? ('employee ' . $p['employee_id']), $p['period_start'], $p['period_end'],
                    $p['scheduled_send_date'], $p['send_status'] === 'failed' ? ' — retry, attempt ' . ((int) $p['send_attempts'] + 1) : ''));
            }
            return EXIT_SUCCESS;
        }

        $controller = new \App\Controllers\Client\PayrollController();
        // Loads the controller's helpers. deliverPayslip() itself never reads
        // $this->request / $this->response.
        $controller->initController(service('request'), service('response'), service('logger'));

        $sent = $held = $failed = 0;
        foreach ($due as $p) {
            $label = sprintf('#%d %s (%s – %s)', $p['id'], $p['employee_name'] ?? ('employee ' . $p['employee_id']), $p['period_start'], $p['period_end']);
            $result = $controller->deliverPayslip((int) $p['id'], 'Scheduled send', 'scheduled', false);

            if (!empty($result['success'])) {
                $sent++;
                CLI::write('  sent    ' . $label . (!empty($result['warning']) ? ' — ' . $result['warning'] : ''), 'green');
            } elseif (($result['status'] ?? '') === EmployeePayslipModel::STATUS_NEEDS_REVIEW) {
                $held++;
                CLI::write('  held    ' . $label . ' — ' . ($result['error'] ?? ''), 'yellow');
            } else {
                $failed++;
                CLI::write('  FAILED  ' . $label . ' — ' . ($result['error'] ?? 'unknown error'), 'red');
            }
        }

        CLI::write("Done. Sent {$sent}, held for review {$held}, failed {$failed}.");

        return $failed > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
