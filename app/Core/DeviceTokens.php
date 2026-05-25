<?php

declare(strict_types=1);

namespace App\Core;

final class DeviceTokens
{
    public static function panelToken(?int $tenantId = null): string
    {
        $tenantId ??= tenantId();
        $token = self::get('panel_access_token', $tenantId);

        return $token !== '' ? $token : PANEL_ACCESS_TOKEN;
    }

    public static function kioskToken(?int $tenantId = null): string
    {
        $tenantId ??= tenantId();
        $token = self::get('kiosk_access_token', $tenantId);
        if ($token !== '') {
            return $token;
        }

        $token = bin2hex(random_bytes(16));
        self::set('kiosk_access_token', $tenantId, $token);

        return $token;
    }

    public static function rotate(string $key, ?int $tenantId = null): string
    {
        $tenantId ??= tenantId();
        $token = bin2hex(random_bytes(16));
        self::set($key, $tenantId, $token);

        return $token;
    }

    private static function get(string $key, int $tenantId): string
    {
        $stmt = Database::connection()->prepare(
            'SELECT `value` FROM app_settings WHERE `key` = :key AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['key' => $key, 'tenant_id' => $tenantId]);

        return (string) ($stmt->fetchColumn() ?: '');
    }

    private static function set(string $key, int $tenantId, string $value): void
    {
        $sql = 'INSERT INTO app_settings (tenant_id, `key`, `value`) VALUES (:tenant_id, :key, :value)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId, 'key' => $key, 'value' => $value]);
    }
}
