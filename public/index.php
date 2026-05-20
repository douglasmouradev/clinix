<?php

declare(strict_types=1);

require __DIR__ . '/../app/Config/config.php';
require __DIR__ . '/../app/Helpers.php';

$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/app.log');
error_reporting(E_ALL);

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

session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$route = (string) ($_GET['route'] ?? 'dashboard');
$httpMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!in_array($route, \App\Core\Router::publicRoutes(), true)) {
    \App\Core\Auth::enforceSessionSecurity();
}

$csrfExempt = ['billing.webhook', 'cron.retention'];
if ($httpMethod === 'POST' && !in_array($route, $csrfExempt, true)) {
    verifyCsrf();
}

\App\Core\Router::dispatch($route, $httpMethod);
