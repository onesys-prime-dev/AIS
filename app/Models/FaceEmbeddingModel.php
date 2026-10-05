<?php

namespace App\Models;

use CodeIgniter\Model;

class FaceEmbeddingModel extends Model
{
    protected $table = 'face_embeddings';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'embedding',
        'model_version',
        'quality_score',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * All enrolled embeddings, across all users, with `embedding` JSON-decoded
     * back into a float array. Used by FaceRecognitionService::recognize() for
     * the linear cosine-similarity scan.
     */
    public function getAll(): array
    {
        $rows = $this->findAll();

        foreach ($rows as &$row) {
            $row['embedding'] = json_decode((string) $row['embedding'], true) ?: [];
        }
        unset($row);

        return $rows;
    }

    public function getForUser(int $userId): array
    {
        $rows = $this->where('user_id', $userId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        foreach ($rows as &$row) {
            $row['embedding'] = json_decode((string) $row['embedding'], true) ?: [];
        }
        unset($row);

        return $rows;
    }

    public function countForUser(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }

    /**
     * Evicts the single oldest enrollment row for a user (FIFO cap enforcement).
     */
    public function deleteOldestForUser(int $userId): bool
    {
        $oldest = $this->where('user_id', $userId)
            ->orderBy('created_at', 'ASC')
            ->first();

        if (!$oldest) {
            return false;
        }

        return $this->delete($oldest['id']);
    }

    /**
     * Enrollment metadata only — never the raw embedding floats, which have
     * no reason to ever reach the browser.
     */
    public function getMetaForUser(int $userId): array
    {
        return $this->select('id, model_version, quality_score, created_at')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Wipes all enrolled embeddings for a user (the "Replace Face" flow —
     * clear everything, then capture fresh).
     */
    public function deleteAllForUser(int $userId): bool
    {
        return $this->where('user_id', $userId)->delete();
    }

    /**
     * Enrollment counts/latest-date for every user with at least one
     * embedding, keyed by user_id — one aggregate query for building an
     * admin employee list instead of N per-user lookups.
     *
     * @return array<int, array{count: int, latest_at: string}>
     */
    public function getSummaryForAllUsers(): array
    {
        $rows = $this->select('user_id, COUNT(*) AS count, MAX(created_at) AS latest_at')
            ->groupBy('user_id')
            ->findAll();

        $summary = [];
        foreach ($rows as $row) {
            $summary[(int) $row['user_id']] = [
                'count' => (int) $row['count'],
                'latest_at' => $row['latest_at'],
            ];
        }

        return $summary;
    }
}
