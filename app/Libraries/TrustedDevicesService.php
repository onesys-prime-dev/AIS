<?php

namespace App\Libraries;

use App\Models\TrustedDeviceModel;

class TrustedDeviceService
{
    private TrustedDeviceModel $model;

    public function __construct()
    {
        helper('cookie');
        $this->model = new TrustedDeviceModel();
    }

    public function isTrusted(int $userId): bool
    {
        $token = service('request')
            ->getCookie('trusted_device');

        if (! $token) {
            return false;
        }

        $record = $this->model
            ->where('user_id', $userId)
            ->where('token', hash('sha256', $token))
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->first();

        if (! $record) {
            return false;
        }

        $this->model->update($record['id'], [
            'last_used_at' => date('Y-m-d H:i:s'),
            'expires_at'   => date(
                'Y-m-d H:i:s',
                time() + (30 * DAY)
            ),
        ]);

        set_cookie([
            'name'     => 'trusted_device',
            'value'    => $token,
            'expire'   => 30 * DAY,
            'path'     => '/',
            'secure'   => ENVIRONMENT !== 'development',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return true;
    }

    public function trustCurrentDevice(int $userId): void
    {

        if ($this->isTrusted($userId)) {
            return;
        }

        $token = bin2hex(random_bytes(64));

        set_cookie([
            'name'     => 'trusted_device',
            'value'    => $token,
            'expire'   => 30 * DAY,
            'path'     => '/',
            'secure'   => ENVIRONMENT !== 'development',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $this->model->insert([
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'device_name' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => service('request')->getIPAddress(),
            'last_used_at' => date('Y-m-d H:i:s'),
            'expires_at' => date(
                'Y-m-d H:i:s',
                time() + (30 * DAY)
            ),
        ]);
    }

    public function forgetCurrentDevice(int $userId): void
    {
        $token = service('request')
            ->getCookie('trusted_device');

        if (! $token) {
            return;
        }

        $this->model
            ->where('user_id', $userId)
            ->where('token', hash('sha256', $token))
            ->delete();

        delete_cookie('trusted_device');
    }
}