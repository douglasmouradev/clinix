<?php

declare(strict_types=1);

namespace App\Core;

final class TenantSettings
{
    public const DEFAULT_PANEL_TOKEN = 'clinix-painel-2026';

    public static function panelHideNames(?int $tenantId = null): bool
    {
        $tenantId ??= tenantId();

        return self::get('panel_hide_names', $tenantId) === '1';
    }

    public static function setPanelHideNames(bool $hide, ?int $tenantId = null): void
    {
        $tenantId ??= tenantId();
        self::set('panel_hide_names', $tenantId, $hide ? '1' : '0');
    }

    public static function isDefaultPanelTokenInUse(?int $tenantId = null): bool
    {
        $token = DeviceTokens::panelToken($tenantId);

        return hash_equals(self::DEFAULT_PANEL_TOKEN, $token);
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
