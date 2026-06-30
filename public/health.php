<?php

declare(strict_types=1);

require __DIR__ . '/../app/Config/config.php';
require __DIR__ . '/../app/Helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

header('Content-Type: application/json; charset=utf-8');

$pending = \App\Core\MigrationStatus::pending();

try {
    $pdo = \App\Core\Database::connection();
    $stmt = $pdo->query('SELECT 1');
    $ok = (int) $stmt->fetchColumn() === 1;
    http_response_code($ok && $pending === [] ? 200 : 503);
    echo json_encode([
        'status' => $ok && $pending === [] ? 'ok' : 'degraded',
        'app' => APP_NAME,
        'db' => $ok ? 'up' : 'down',
        'migrations_pending' => $pending,
        'time' => date('c'),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'app' => APP_NAME,
        'db' => 'down',
        'migrations_pending' => $pending,
        'message' => 'Database unavailable',
        'time' => date('c'),
    ], JSON_UNESCAPED_UNICODE);
}
