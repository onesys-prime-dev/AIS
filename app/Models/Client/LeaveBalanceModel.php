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

    /**
     * Rebuild the leave balance from the latest approved request of a given type.
     *
     * This is useful when a balance row exists but was not updated at the time
     * the request was approved. The total entitlement is preserved and the
     * used/remaining values are recalculated from the latest approved request
     * in the selected year.
     */
    public function syncFromApprovedRequests(int $employeeId, ?int $year = null, string $requestType = 'leave'): array
    {
        $year ??= (int) date('Y');

        $row = $this->getOrCreate($employeeId, 'leave', $year);

        $employee = db_connect()
            ->table('employees')
            ->select('user_id')
            ->where('id', $employeeId)
            ->get()
            ->getRowArray();

        if (! $employee || empty($employee['user_id'])) {
            return $row;
        }

        $latestRequest = db_connect()
            ->table('requests')
            ->select('duration')
            ->where('requested_by', (int) $employee['user_id'])
            ->where('request_type', $requestType)
            ->where('status', 'approved')
            ->where('YEAR(start_date)', $year, false)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        $used = $latestRequest !== null ? (float) ($latestRequest['duration'] ?? 0) : 0.0;
        $remaining = max(0.0, (float) $row['total_entitlement'] - $used);

        $this->update($row['id'], [
            'used'      => $used,
            'remaining' => $remaining,
        ]);

        return $this->getBalance($employeeId, 'leave', $year) ?? $row;
    }

    // =========================================================================
    // Entitlement management
    // =========================================================================

    /**
     * Set a new total entitlement for an employee.
     * Recalculates remaining as (new total - used), floored at 0.
     * Creates the row via getOrCreate if it doesn't exist yet.
     */
    public function setEntitlement(int $employeeId, float $total, ?int $year = null): void
    {
        $year ??= (int) date('Y');

        $row = $this->getOrCreate($employeeId, 'leave', $year);

        $newRemaining = max(0.0, $total - (float) $row['used']);

        $this->update($row['id'], [
            'total_entitlement' => $total,
            'remaining'         => $newRemaining,
        ]);
    }

    /**
     * Set entitlement for a specific leave type (vacation/sick/emergency).
     */
    public function setLeaveTypeEntitlement(int $employeeId, string $leaveType, float $total, ?int $year = null): void
    {
        $year ??= (int) date('Y');

        $row = $this->getOrCreate($employeeId, $leaveType, $year);

        $newRemaining = max(0.0, $total - (float) $row['used']);

        $this->update($row['id'], [
            'total_entitlement' => $total,
            'remaining'         => $newRemaining,
        ]);
    }

    /**
     * Get entitlement for a specific leave type, returns 0 if none.
     */
    public function getTypeEntitlement(int $employeeId, string $leaveType, ?int $year = null): float
    {
        $year ??= (int) date('Y');
        $row = $this->getBalance($employeeId, $leaveType, $year);
        return $row ? (float) $row['total_entitlement'] : 0.0;
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
     * Apply request status changes to leave balances.
     *
     * Approving a leave request deducts from the balance.
     * Rejecting an already approved leave request refunds it.
     * Rejected pending requests do not change the balance.
     *
     * Returns the deduction result when approval triggers a deduction,
     * null when no balance change is needed.
     */
    public function applyRequestStatusChange(
        int $userId,
        string $requestType,
        float $days,
        string $newStatus,
        ?string $oldStatus = null,
        ?int $year = null,
        string $leaveCategory = 'leave'
    ): ?bool {
        if ($requestType !== 'leave') {
            return null;
        }

        if ($newStatus === 'approved' && $oldStatus !== 'approved') {
            return $this->deduct($userId, $leaveCategory, $days, $year);
        }

        if ($newStatus === 'rejected' && $oldStatus === 'approved') {
            $this->refund($userId, $leaveCategory, $days, $year);
        }

        return null;
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
    // Year-boundary-aware deduction & refund
    //
    // Balances are stored one row per calendar year. A request spanning a year
    // boundary (e.g. Dec 28 - Jan 3) previously charged its whole duration
    // against the start date's year alone, even though part of it falls under
    // the new year's fresh entitlement. These split each day of the request
    // into its own calendar year before touching any balance row.
    // =========================================================================

    /**
     * Splits a request's stored `duration` across the calendar years its date
     * range touches, e.g. 2026-12-28..2027-01-03 (7 days) -> [2026 => 4, 2027 => 3].
     *
     * `duration` — not the calendar span — is authoritative for how many days
     * are charged, because that same column is what payroll pays out
     * (PayrollController::getAttendanceSummary()). Deriving the quantity from
     * the dates instead would let the balance and the payslip disagree whenever
     * the two differ: a legacy row with a NULL end_date but duration > 1, or a
     * fractional half-day duration. So the days-per-year ratio comes from the
     * dates, but the total always adds back up to exactly `duration`, with the
     * final year absorbing any rounding remainder.
     *
     * @return array<int,float> year => days charged to that year
     */
    private function splitDurationByYear(string $startDate, string $endDate, float $duration): array
    {
        $start = new \DateTime($startDate);
        $end   = new \DateTime($endDate !== '' ? $endDate : $startDate);
        if ($end < $start) $end = clone $start;

        $calendarDays = [];
        $cursor = clone $start;
        while ($cursor <= $end) {
            $y = (int) $cursor->format('Y');
            $calendarDays[$y] = ($calendarDays[$y] ?? 0) + 1;
            $cursor->modify('+1 day');
        }

        $totalCalendarDays = array_sum($calendarDays);
        if ($totalCalendarDays <= 0) {
            return [(int) $start->format('Y') => $duration];
        }

        // Single year (the overwhelmingly common case): charge it all there,
        // exactly as before, with no scaling arithmetic in the way.
        if (count($calendarDays) === 1) {
            return [array_key_first($calendarDays) => $duration];
        }

        $split     = [];
        $allocated = 0.0;
        $years     = array_keys($calendarDays);
        $lastYear  = end($years);

        foreach ($calendarDays as $year => $days) {
            if ($year === $lastYear) {
                // Absorbs the remainder so the parts sum to exactly $duration.
                $split[$year] = round($duration - $allocated, 2);
                continue;
            }
            $share = round($duration * ($days / $totalCalendarDays), 2);
            $split[$year] = $share;
            $allocated += $share;
        }

        return $split;
    }

    /**
     * Checks whether an employee has enough remaining balance for a request,
     * across every calendar year it touches, WITHOUT writing anything — no
     * deduction, and no balance rows created as a side effect (this runs
     * before the caller's transaction opens). A year with no row yet is
     * treated as a full default entitlement, which is what getOrCreate()
     * would materialise at deduction time anyway.
     */
    public function checkSufficientBalance(int $userId, string $leaveType, string $startDate, string $endDate, float $duration): array
    {
        $employeeId = $this->resolveEmployeeId($userId);
        if ($employeeId === null) {
            return ['sufficient' => true, 'shortfalls' => []];
        }

        $shortfalls = [];
        foreach ($this->splitDurationByYear($startDate, $endDate, $duration) as $year => $days) {
            if ($days <= 0) continue;

            $row       = $this->getBalance($employeeId, $leaveType, $year);
            $remaining = $row !== null ? (float) $row['remaining'] : (float) self::DEFAULT_ENTITLEMENT;

            if ($remaining < $days) {
                $shortfalls[] = "{$days} day(s) needed in {$year}, only {$remaining} remaining";
            }
        }
        return ['sufficient' => $shortfalls === [], 'shortfalls' => $shortfalls];
    }

    /**
     * Deducts a request's duration from the employee's balance, split across
     * every calendar year its date range touches.
     *
     * Each year is deducted with a single conditional UPDATE gated on
     * `remaining >= days`, so the balance can't be overdrawn even if two
     * approvals for the same employee are processed at the same instant:
     * checkSufficientBalance() runs before the caller's transaction opens, so
     * on its own it leaves a window where both could pass. Returns false if any
     * year's deduction didn't apply, which means the balance was consumed in
     * between — the caller should roll back rather than approve. A plain
     * read-modify-write with a max(0, …) floor would instead silently
     * under-deduct and hide the overdraw.
     */
    public function deductAcrossYears(int $userId, string $leaveType, string $startDate, string $endDate, float $duration): bool
    {
        $employeeId = $this->resolveEmployeeId($userId);
        if ($employeeId === null) return true;

        foreach ($this->splitDurationByYear($startDate, $endDate, $duration) as $year => $days) {
            if ($days <= 0) continue;

            $row = $this->getOrCreate($employeeId, $leaveType, $year);

            // Raw builder rather than Model::update(): the conditional
            // `remaining >= ?` guard plus the self-referential `used + n`
            // arithmetic can't be expressed through the Model's data-array
            // update path (which also rejects an empty dataset outright).
            // The unescaped fragments are float-cast numerics, not input.
            $this->db->table($this->table)
                 ->where('id', $row['id'])
                 ->where('remaining >=', $days)
                 ->set('used', 'used + ' . (float) $days, false)
                 ->set('remaining', 'remaining - ' . (float) $days, false)
                 ->set($this->updatedField, date('Y-m-d H:i:s'))
                 ->update();

            if ($this->db->affectedRows() < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Refunds a previously-deducted request's duration, split exactly the same
     * way deductAcrossYears() charged it — used when an approved leave request
     * is rejected/cancelled after the fact.
     */
    public function refundAcrossYears(int $userId, string $leaveType, string $startDate, string $endDate, float $duration): void
    {
        $employeeId = $this->resolveEmployeeId($userId);
        if ($employeeId === null) return;

        foreach ($this->splitDurationByYear($startDate, $endDate, $duration) as $year => $days) {
            if ($days <= 0) continue;

            $row = $this->getBalance($employeeId, $leaveType, $year);
            if ($row === null) continue;

            $newUsed      = max(0.0, (float) $row['used'] - $days);
            $newRemaining = min((float) $row['total_entitlement'], (float) $row['remaining'] + $days);
            $this->update($row['id'], ['used' => $newUsed, 'remaining' => $newRemaining]);
        }
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