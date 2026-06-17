<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    public static function send(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if (requestIsHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $csp = "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data: https://api.qrserver.com; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'";
        header('Content-Security-Policy: ' . $csp);
    }
}
