<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class ApprovalCodeModel extends Model
{
    protected $table         = 'approval_codes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'code',
        'generated_by',
        'is_used',
        'used_by',
        'used_at',
        'created_at',
        'preset_data',   // nullable JSON: {duration}
    ];

    protected $useTimestamps  = false; // managed manually so we keep created_at only

    public function findActiveCode(string $code): ?array
    {
        return $this->where('code', $code)
                    ->where('is_used', 0)
                    ->first() ?: null;
    }

    /**
     * Mark a code as used.
     *
     * @param int $codeId   PK of the approval_codes row
     * @param int $usedBy   user_id of the employee consuming the code
     */
    public function markUsed(int $codeId, int $usedBy): bool
    {
        return (bool) $this->update($codeId, [
            'is_used' => 1,
            'used_by' => $usedBy,
            'used_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Atomically claims a code — unlike markUsed(), this only succeeds if the
     * code is still unused at the moment of the UPDATE itself (a single
     * WHERE-gated statement, not a separate check-then-write), so two
     * near-simultaneous redemptions of the same single-use code can't both
     * succeed. findActiveCode() alone can't prevent that: both requests can
     * pass its SELECT before either commits a write. Returns false if the
     * code was already claimed by the time this ran.
     */
    public function claim(int $codeId, int $usedBy): bool
    {
        $affected = $this->where('id', $codeId)
            ->where('is_used', 0)
            ->update(null, [
                'is_used' => 1,
                'used_by' => $usedBy,
                'used_at' => date('Y-m-d H:i:s'),
            ]);

        return $affected && $this->db->affectedRows() > 0;
    }
}