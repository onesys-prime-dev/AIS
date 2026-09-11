<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class ImportBatchModel extends Model
{
    protected $table         = 'import_batches';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'type', 'filename', 'file_hash', 'imported_by',
        'total_rows', 'ready_count', 'duplicate_count', 'error_count', 'other_count',
        'summary', 'created_at',
    ];
    protected $useTimestamps = false;

    /**
     * Records one completed import — timesheet or employee — for the history
     * view. Called after the import itself has already committed; a failure
     * to record history should never be why an otherwise-successful import
     * reports as failed, so callers should not roll back on this alone.
     */
    public function record(
        string $type,
        ?string $filename,
        ?string $fileHash,
        ?int $importedBy,
        int $totalRows,
        int $readyCount,
        int $duplicateCount,
        int $errorCount,
        int $otherCount = 0,
        array $summary = []
    ): void {
        $this->insert([
            'type'            => $type,
            'filename'        => $filename,
            'file_hash'       => $fileHash,
            'imported_by'     => $importedBy,
            'total_rows'      => $totalRows,
            'ready_count'     => $readyCount,
            'duplicate_count' => $duplicateCount,
            'error_count'     => $errorCount,
            'other_count'     => $otherCount,
            'summary'         => $summary ? json_encode($summary, JSON_THROW_ON_ERROR) : null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Recent import history, newest first, joined to the importing user's
     * display name where available.
     */
    public function recent(?string $type = null, int $limit = 50): array
    {
        $builder = $this->select("
                import_batches.*,
                COALESCE(NULLIF(CONCAT(e.last_name, ', ', e.first_name), ', '), u.username) AS imported_by_name
            ", false)
            ->join('users u', 'u.id = import_batches.imported_by', 'left')
            ->join('employees e', 'e.user_id = u.id', 'left');

        if ($type !== null) $builder->where('import_batches.type', $type);

        return $builder->orderBy('import_batches.id', 'DESC')->limit($limit)->findAll();
    }
}
