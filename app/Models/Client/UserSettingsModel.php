<?php

namespace App\Models\Client;

use CodeIgniter\Model;

class UserSettingsModel extends Model
{
    protected $table         = 'user_settings';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'settings'];

    public function getByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    public function getSettingsArray(int $userId): array
    {
        $row = $this->getByUserId($userId);
        if (! $row || empty($row['settings'])) {
            return [];
        }

        $decoded = is_string($row['settings'])
            ? json_decode($row['settings'], true)
            : $row['settings'];

        return is_array($decoded) ? $decoded : [];
    }

    public function saveSettings(int $userId, array $settings): bool
    {
        $row = $this->getByUserId($userId);

        if ($row) {
            return (bool) $this->update($row['id'], [
                'settings' => json_encode($settings),
            ]);
        }

        return (bool) $this->insert([
            'user_id'  => $userId,
            'settings' => json_encode($settings),
        ]);
    }
}