<?php

declare(strict_types=1);

$envFile = dirname(__DIR__, 2) . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if ($key !== '') {
                $_ENV[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }
}

function envValue(string $key, string $default): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

define('APP_NAME', envValue('APP_NAME', 'Clinix'));
define('APP_URL', envValue('APP_URL', 'http://localhost:8000'));
define('DB_HOST', envValue('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) envValue('DB_PORT', '3306'));
define('DB_NAME', envValue('DB_NAME', 'clinix'));
define('DB_USER', envValue('DB_USER', 'root'));
define('DB_PASS', envValue('DB_PASS', ''));
define('SESSION_NAME', envValue('SESSION_NAME', 'clinix_session'));
define('PANEL_ACCESS_TOKEN', envValue('PANEL_ACCESS_TOKEN', 'clinix-painel-2026'));
define('DEFAULT_TENANT_ID', (int) envValue('DEFAULT_TENANT_ID', '1'));
define('LGPD_TERM_VERSION', envValue('LGPD_TERM_VERSION', 'v1.0'));
define('LGPD_RETENTION_DAYS_DEFAULT', (int) envValue('LGPD_RETENTION_DAYS_DEFAULT', '1825'));
define('STRIPE_SECRET_KEY', envValue('STRIPE_SECRET_KEY', ''));
define('STRIPE_WEBHOOK_SECRET', envValue('STRIPE_WEBHOOK_SECRET', ''));
define('CRON_SECRET', envValue('CRON_SECRET', ''));
define('APP_ENV', envValue('APP_ENV', 'local'));
define('MAIL_FROM', envValue('MAIL_FROM', 'noreply@clinix.local'));
define('SMTP_HOST', envValue('SMTP_HOST', ''));
define('SMTP_PORT', (int) envValue('SMTP_PORT', '587'));
define('SMTP_USER', envValue('SMTP_USER', ''));
define('SMTP_PASS', envValue('SMTP_PASS', ''));

