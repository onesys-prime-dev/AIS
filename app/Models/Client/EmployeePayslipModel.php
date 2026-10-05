<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class EmployeePayslipModel extends Model
{
    protected $table = 'employee_payslips';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'employee_id', 'period_start', 'period_end', 'net_pay', 'sent_at', 'sent_by', 'file_path', 'generated_data',
        // Payslip release workflow (2026-09-28 migration). Anything not
        // listed here is silently dropped by insert()/update().
        'send_status', 'scheduled_send_date', 'scheduled_by', 'scheduled_at',
        'send_attempts', 'last_send_error', 'last_attempt_at', 'review_reason',
        'needs_resend', 'paid_at', 'paid_by', 'net_pay_before_loans', 'source_fingerprint',
    ];

    public const STATUS_DRAFT        = 'draft';
    public const STATUS_SCHEDULED    = 'scheduled';
    public const STATUS_SENDING      = 'sending';
    public const STATUS_SENT         = 'sent';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_NEEDS_REVIEW = 'needs_review';

    /** Automatic retries for a failed scheduled send before it waits for staff. */
    public const MAX_AUTO_ATTEMPTS = 3;
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
}