<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class LeaveBalanceModel extends Model
{
    protected $table         = 'leave_balances';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id',
        'leave_type',
        'year',
        'total_entitlement',
        'used',
        'remaining',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    // Default entitlement (days) when auto-creating a balance row
    const DEFAULT_ENTITLEMENT = 15.00;

    // =========================================================================
    // Core read helpers
    // =========================================================================

    /**
     * Find the balance row for a given employee, leave type, and year.
     * Returns null if no row exists yet.
     */
    public function getBalance(int $employeeId, string $leaveType = 'leave', ?int $year = null): ?array
    {
        $year ??= (int) date('Y');

        return $this->where('employee_id', $employeeId)
                    ->where('leave_type',  $leaveType)
                    ->where('year',        $year)
                    ->first();
    }

    /**
     * Return all balance rows for an employee (all types, all years).
     * Ordered most-recent year first.
     */
    public function getAllBalances(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
                    ->orderBy('year', 'DESC')
                    ->orderBy('leave_type', 'ASC')
                    ->findAll();
    }

    // =========================================================================
    // Auto-initialisation
    // =========================================================================

    /**
     * Return an existing balance row or create a fresh one with the default
     * entitlement.  Never returns null.
     */
    public function getOrCreate(int $employeeId, string $leaveType = 'leave', ?int $year = null): array
    {
        $year ??= (int) date('Y');

        $row = $this->getBalance($employeeId, $leaveType, $year);

        if ($row !== null) {
            return $row;
        }

        $this->insert([
            'employee_id'       => $employeeId,
            'leave_type'        => $leaveType,
            'year'              => $year,
            'total_entitlement' => self::DEFAULT_ENTITLEMENT,
            'used'              => 0.00,
            'remaining'         => self::DEFAULT_ENTITLEMENT,
        ]);

        return $this->getBalance($employeeId, $leaveType, $year);
    }

    // =========================================================================
    // Deduction & refund
    // =========================================================================

    /**
     * Deduct days from an employee's balance when a leave request is approved.
     *
     * Returns true on success, false if there is insufficient balance.
     * Throws if the employee has no record in the employees table.
     *
     * @param  int    $userId    users.id of the requesting employee
     * @param  string $leaveType request_type value (e.g. 'leave')
     * @param  float  $days      number of days to deduct (request.duration)
     * @param  int|null $year    defaults to the year of today
     */
    public function deduct(int $userId, string $leaveType = 'leave', float $days = 1.0, ?int $year = null): bool
    {
        // Only deduct for leave-type requests; overtime/undertime don't touch balances
        if ($leaveType !== 'leave') {
            return true;
        }

        $year ??= (int) date('Y');

        $employeeId = $this->resolveEmployeeId($userId);
        if ($employeeId === null) {
            // User has no employee record — skip silently (e.g. payroll staff)
            return true;
        }

        $row = $this->getOrCreate($employeeId, $leaveType, $year);

        if ((float) $row['remaining'] < $days) {
            return false; // insufficient balance — caller decides how to handle
        }

        $this->update($row['id'], [
            'used'      => (float) $row['used']      + $days,
            'remaining' => (float) $row['remaining'] - $days,
        ]);

        return true;
    }

    /**
     * Refund days back to an employee's balance when a previously approved
     * leave request is rejected or cancelled.
     *
     * @param  int    $userId
     * @param  string $leaveType
     * @param  float  $days
     * @param  int|null $year
     */
    public function refund(int $userId, string $leaveType = 'leave', float $days = 1.0, ?int $year = null): void
    {
        if ($leaveType !== 'leave') {
            return;
        }

        $year ??= (int) date('Y');

        $employeeId = $this->resolveEmployeeId($userId);
        if ($employeeId === null) {
            return;
        }

        $row = $this->getBalance($employeeId, $leaveType, $year);

        if ($row === null) {
            // Nothing to refund — balance row doesn't exist
            return;
        }

        $newUsed      = max(0.0, (float) $row['used']      - $days);
        $newRemaining = min(
            (float) $row['total_entitlement'],
            (float) $row['remaining'] + $days
        );

        $this->update($row['id'], [
            'used'      => $newUsed,
            'remaining' => $newRemaining,
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Resolve a user_id to an employee_id via the employees table.
     * Returns null if the user has no employee record.
     */
    private function resolveEmployeeId(int $userId): ?int
    {
        $row = db_connect()
            ->table('employees')
            ->select('id')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        return $row ? (int) $row['id'] : null;
    }
}
