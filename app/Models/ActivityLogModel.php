<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'username',
        'action',
        'module',
        'record_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    // Let the service handle timestamps manually so we use DATETIME not INT
    protected $useTimestamps = false;

    protected $validationRules = [
        'user_id'     => 'required|integer',
        'username'    => 'required|max_length[30]',
        'action'      => 'required|in_list[created,updated,deleted]',
        'module'      => 'required|max_length[50]',
        'description' => 'required|max_length[255]',
    ];

    // -------------------------------------------------------------------------
    // Query helpers for the log viewer
    // -------------------------------------------------------------------------

    /**
     * Filter logs by one or more modules.
     *
     * @param  string|string[] $modules
     */
    public function byModule(string|array $modules): static
    {
        if (is_array($modules)) {
            $this->whereIn('module', $modules);
        } else {
            $this->where('module', $modules);
        }

        return $this;
    }

    /**
     * Filter logs to a specific user.
     */
    public function byUser(int $userId): static
    {
        return $this->where('user_id', $userId);
    }

    /**
     * Filter logs by action type.
     *
     * @param  string $action  'created' | 'updated' | 'deleted'
     */
    public function byAction(string $action): static
    {
        return $this->where('action', $action);
    }

    /**
     * Filter logs to a specific record within a module.
     */
    public function byRecord(string $module, int $recordId): static
    {
        return $this->where('module', $module)->where('record_id', $recordId);
    }

    /**
     * Filter logs within a date range (inclusive).
     *
     * @param  string $from  'Y-m-d' or 'Y-m-d H:i:s'
     * @param  string $to    'Y-m-d' or 'Y-m-d H:i:s'
     */
    public function betweenDates(string $from, string $to): static
    {
        return $this->where('created_at >=', $from)
                    ->where('created_at <=', $to);
    }

    /**
     * Return logs newest first.
     */
    public function latest(): static
    {
        return $this->orderBy('created_at', 'DESC');
    }

    /**
     * Paginated result with filters already applied via chained methods.
     *
     * Example:
     *   $model->byModule('requests')->latest()->paginate(20)
     */
}
