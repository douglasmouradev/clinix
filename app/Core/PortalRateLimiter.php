<?php

declare(strict_types=1);

namespace App\Core;

final class PortalRateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 900;

    public static function isLocked(string $tenantSlug, string $cpf): bool
    {
        $file = self::filePath($tenantSlug, $cpf);
        if (!is_file($file)) {
            return false;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            return false;
        }

        $lockedUntil = (int) ($data['locked_until'] ?? 0);

        return $lockedUntil > time();
    }

    public static function registerFailure(string $tenantSlug, string $cpf): void
    {
        $file = self::filePath($tenantSlug, $cpf);
        $data = ['attempts' => 0, 'locked_until' => 0];
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $data['attempts'] = (int) ($data['attempts'] ?? 0) + 1;
        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $data['locked_until'] = time() + self::LOCK_SECONDS;
            $data['attempts'] = 0;
        }

        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public static function clear(string $tenantSlug, string $cpf): void
    {
        $file = self::filePath($tenantSlug, $cpf);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function filePath(string $tenantSlug, string $cpf): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/portal-rate';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = sha1($tenantSlug . '|' . $cpf . '|' . $ip);

        return $dir . '/' . $key . '.json';
    }
}
