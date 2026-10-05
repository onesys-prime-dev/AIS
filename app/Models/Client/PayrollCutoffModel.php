<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class PayrollCutoffModel extends Model
{
    protected $table = 'payroll_cutoffs';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'start_date', 'end_date', 'is_active'];
    protected $useTimestamps = true;
    protected $useSoftDeletes = false;

    public function getActiveCutoff(): ?array
    {
        return $this->where('is_active', 1)->first();
    }

    public function setActiveCutoff(int $cutoffId): bool
    {
        // Deactivate all others first
        $this->where('is_active', 1)->set(['is_active' => 0])->update();
        return (bool) $this->update($cutoffId, ['is_active' => 1]);
    }
}