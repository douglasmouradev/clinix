<?php

declare(strict_types=1);

namespace App\Core;

final class ApiRateLimiter
{
    private const MAX_PER_MINUTE = 120;

    public static function allow(string $tokenHash): bool
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache/api-rate';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $bucket = date('YmdHi');
        $file = $dir . '/' . substr(hash('sha256', $tokenHash), 0, 16) . '_' . $bucket . '.cnt';
        $count = 0;
        if (is_file($file)) {
            $count = (int) file_get_contents($file);
        }

        if ($count >= self::MAX_PER_MINUTE) {
            return false;
        }

        @file_put_contents($file, (string) ($count + 1), LOCK_EX);

        return true;
    }
}
