<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class PayrollSettingsModel extends Model
{
    protected $table            = 'payroll_settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false; // ← not auto-incremented
    protected $allowedFields    = ['id', 'client_id', 'setting_key', 'setting_value'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Get a setting value for the current client.
     */
    public function getSetting(string $key, $default = null)
    {
        $clientId = session()->get('client_id');

        $row = $this->where('client_id', $clientId)
                    ->where('setting_key', $key)
                    ->first();

        return $row ? $row['setting_value'] : $default;
    }

    /**
     * Insert or update a setting for the current client.
     */
    public function setSetting(string $key, $value): void
    {
        $clientId = session()->get('client_id');

        $existing = $this->where('client_id', $clientId)
                         ->where('setting_key', $key)
                         ->first();

        if ($existing) {
            $this->update($existing['id'], ['setting_value' => $value]);
        } else {
            // Generate id manually since there's no auto-increment
            $nextId = $this->selectMax('id')->get()->getRow()->id + 1;

            $this->insert([
                'id'            => $nextId,
                'client_id'     => $clientId,
                'setting_key'   => $key,
                'setting_value' => $value,
            ]);
        }
    }
}