<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Payslip release workflow: a saved payslip is a draft (invisible to the
 * employee) until it's sent from Payroll Output → Payslip — immediately
 * ("Send Now") or on a scheduled date by the payroll:send-scheduled-payslips
 * command.
 *
 * Backfill (reversed by down()):
 *  - rows already sent                      → send_status 'sent'
 *  - never-sent rows whose period has ended → marked sent, sent_by 'Backfill',
 *    so employees keep seeing exactly the past payslips they could see before
 *    (every saved row used to be visible to them)
 *  - never-sent current/future-period rows  → 'needs_review': they were saved
 *    before net_pay_before_loans existed, so the release flow won't guess
 *    whether their stored net pay already had the Loans-module deduction taken
 *    out — they must be saved once more from Generate Payslip before sending.
 */
class AddReleaseTrackingToEmployeePayslips extends Migration
{
    private const BACKFILL_SENDER = 'Backfill';

    public function up()
    {
        $this->forge->addColumn('employee_payslips', [
            'send_status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'draft', 'after' => 'net_pay'],
            'scheduled_send_date'  => ['type' => 'DATE', 'null' => true, 'default' => null, 'after' => 'send_status'],
            'scheduled_by'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null, 'after' => 'scheduled_send_date'],
            'scheduled_at'         => ['type' => 'DATETIME', 'null' => true, 'default' => null, 'after' => 'scheduled_by'],
            'send_attempts'        => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'sent_by'],
            'last_send_error'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null, 'after' => 'send_attempts'],
            'last_attempt_at'      => ['type' => 'DATETIME', 'null' => true, 'default' => null, 'after' => 'last_send_error'],
            'review_reason'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null, 'after' => 'last_attempt_at'],
            'needs_resend'         => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'review_reason'],
            'paid_at'              => ['type' => 'DATETIME', 'null' => true, 'default' => null, 'after' => 'needs_resend'],
            'paid_by'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null, 'after' => 'paid_at'],
            'net_pay_before_loans' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true, 'default' => null, 'after' => 'paid_by'],
            'source_fingerprint'   => ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'default' => null, 'after' => 'net_pay_before_loans'],
        ]);

        $this->db->query('CREATE INDEX idx_employee_payslips_release ON employee_payslips (send_status, scheduled_send_date)');

        $table = $this->db->prefixTable('employee_payslips');

        $this->db->query("UPDATE {$table} SET send_status = 'sent' WHERE sent_at IS NOT NULL");

        $this->db->query(
            "UPDATE {$table}
                SET sent_at = COALESCE(created_at, NOW()), sent_by = ?, send_status = 'sent'
              WHERE sent_at IS NULL AND period_end < CURDATE()",
            [self::BACKFILL_SENDER]
        );

        $this->db->query(
            "UPDATE {$table}
                SET send_status = 'needs_review',
                    review_reason = 'Saved before the release update - open it in Generate Payslip and save it again before sending.'
              WHERE sent_at IS NULL"
        );
    }

    public function down()
    {
        $table = $this->db->prefixTable('employee_payslips');

        $this->db->query("UPDATE {$table} SET sent_at = NULL, sent_by = NULL WHERE sent_by = ?", [self::BACKFILL_SENDER]);

        $this->db->query('DROP INDEX idx_employee_payslips_release ON employee_payslips');

        $this->forge->dropColumn('employee_payslips', [
            'send_status', 'scheduled_send_date', 'scheduled_by', 'scheduled_at',
            'send_attempts', 'last_send_error', 'last_attempt_at', 'review_reason',
            'needs_resend', 'paid_at', 'paid_by', 'net_pay_before_loans', 'source_fingerprint',
        ]);
    }
}
