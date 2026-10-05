<?php

namespace App\Libraries;

/**
 * Thin reader/writer for the generic per-user `user_settings.settings` JSON
 * blob (the same store EmployeeAccountController already uses for the
 * payslip PDF password). Kept here as a shared helper since this key needs
 * to be read from a request filter (FaceVerified), not just a controller.
 */
final class AccountSettings
{
    private static function db()
    {
        return \Config\Database::connect();
    }

    private static function read(int $userId): array
    {
        $row = self::db()->table('user_settings')->where('user_id', $userId)->get()->getRowArray();
        if (!$row || empty($row['settings'])) return [];
        $decoded = json_decode((string) $row['settings'], true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function write(int $userId, array $settings): void
    {
        $db = self::db();
        $now = date('Y-m-d H:i:s');
        $exists = $db->table('user_settings')->where('user_id', $userId)->countAllResults() > 0;
        if ($exists) {
            $db->table('user_settings')->where('user_id', $userId)->update([
                'settings' => json_encode($settings), 'updated_at' => $now,
            ]);
        } else {
            $db->table('user_settings')->insert([
                'user_id' => $userId, 'settings' => json_encode($settings),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    /**
     * Per-account override for whether face verification is required at
     * login: true (Always Require), false (Exempt), or null (System
     * Default — follow the global FaceRecognition.enabled switch). Absent
     * for every account until an admin explicitly sets one, so this is a
     * no-op for the whole system until used.
     */
    public static function getFaceRequirement(int $userId): ?bool
    {
        $value = self::read($userId)['face_recognition_required'] ?? null;
        return $value === null ? null : (bool) $value;
    }

    /** $value: true (required), false (exempt), or null (reset to System Default). */
    public static function setFaceRequirement(int $userId, ?bool $value): void
    {
        $settings = self::read($userId);
        if ($value === null) {
            unset($settings['face_recognition_required']);
        } else {
            $settings['face_recognition_required'] = $value;
        }
        self::write($userId, $settings);
    }
}
