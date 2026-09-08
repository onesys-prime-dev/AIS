<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class PayrollCutoffSummaryModel extends Model
{
    protected $table = 'payroll_cutoff_summary';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'employee_id', 'cutoff_id', 'regular_hours', 'night_diff_hours', 'tardy_minutes',
        'reg_ot_hours', 'rest_day_ot_hours', 'holiday_ot_hours',
        'paid_leave_days', 'unpaid_leave_days', 'undertime_hours', 'request_overtime_hours',
        'de_minimis_json', 'other_taxable', 'other_non_taxable',
        'sss_loan', 'calamity_loan', 'salary_advance',
        'basic_pay', 'night_diff_pay', 'overtime_pay', 'absent_deduction', 'undertime_deduction',
        'gross_pay', 'sss_ee', 'phic_ee', 'hdmf_ee', 'withholding_tax', 'total_deductions', 'net_pay',
        'status', 'computed_at',
        'worked_days', 'total_working_days', 'absent_days',
        'reg_ot_hours_le10', 'reg_ot_hours_excess',
        'rest_ot_hours_le10', 'rest_ot_hours_excess',
        'holiday_ot_hours_le10', 'holiday_ot_hours_excess'
    ];
    protected $useTimestamps = true;

    public function getOrCreate(int $employeeId, int $cutoffId): array
    {
        $summary = $this->where('employee_id', $employeeId)
            ->where('cutoff_id', $cutoffId)
            ->first();
        if ($summary) return $summary;

        $this->insert([
            'employee_id' => $employeeId,
            'cutoff_id'   => $cutoffId,
            'status'      => 'draft',
        ]);
        return $this->find($this->getInsertID());
    }
}