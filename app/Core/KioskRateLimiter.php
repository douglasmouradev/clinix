<?php

declare(strict_types=1);

namespace App\Core;

final class KioskRateLimiter
{
    private const MAX_PER_MINUTE = 15;

    public static function allow(string $action): bool
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/kiosk-rate';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = tenantId() . '-' . $action . '-' . sha1($ip);
        $file = $dir . '/' . $key . '.json';
        $now = time();
        $window = $now - 60;
        $hits = [];

        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                foreach ($decoded as $ts) {
                    if (is_int($ts) && $ts >= $window) {
                        $hits[] = $ts;
                    }
                }
            }
        }

        if (count($hits) >= self::MAX_PER_MINUTE) {
            return false;
        }

        $hits[] = $now;
        file_put_contents($file, json_encode($hits), LOCK_EX);

        return true;
    }

    public static function denyAndExit(): void
    {
        http_response_code(429);
        echo 'Muitas senhas emitidas em pouco tempo. Aguarde um minuto e tente novamente.';
        exit;
    }
}
