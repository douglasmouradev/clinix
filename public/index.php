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

$route = $_GET['route'] ?? 'dashboard';
if ($route !== 'queue.panel') {
    \App\Core\Auth::enforceSessionSecurity();
}

$routes = [
    'login' => [App\Controllers\AuthController::class, 'loginForm', ['GET']],
    'login.submit' => [App\Controllers\AuthController::class, 'login', ['POST']],
    'onboarding' => [App\Controllers\OnboardingController::class, 'form', ['GET']],
    'onboarding.submit' => [App\Controllers\OnboardingController::class, 'submit', ['POST']],
    'logout' => [App\Controllers\AuthController::class, 'logout', ['POST']],
    'dashboard' => [App\Controllers\DashboardController::class, 'index', ['GET']],
    'billing' => [App\Controllers\BillingController::class, 'index', ['GET']],
    'billing.plan.change' => [App\Controllers\BillingController::class, 'changePlan', ['POST']],
    'compliance' => [App\Controllers\ComplianceController::class, 'index', ['GET']],
    'compliance.policy.save' => [App\Controllers\ComplianceController::class, 'savePolicy', ['POST']],
    'compliance.retention.run' => [App\Controllers\ComplianceController::class, 'runRetention', ['POST']],
    'appointments' => [App\Controllers\AppointmentController::class, 'index', ['GET']],
    'appointment.form' => [App\Controllers\AppointmentController::class, 'form', ['GET']],
    'appointment.save' => [App\Controllers\AppointmentController::class, 'save', ['POST']],
    'appointment.status' => [App\Controllers\AppointmentController::class, 'updateStatus', ['POST']],
    'admin.users' => [App\Controllers\AdminController::class, 'users', ['GET']],
    'admin.user.form' => [App\Controllers\AdminController::class, 'userForm', ['GET']],
    'admin.user.save' => [App\Controllers\AdminController::class, 'userSave', ['POST']],
    'admin.panel' => [App\Controllers\AdminController::class, 'panelSettings', ['GET']],
    'admin.panel.rotate' => [App\Controllers\AdminController::class, 'rotatePanelToken', ['POST']],
    'admin.clinic' => [App\Controllers\AdminController::class, 'clinicSlug', ['GET']],
    'admin.clinic.save' => [App\Controllers\AdminController::class, 'clinicSlugSave', ['POST']],
    'patients' => [App\Controllers\PatientController::class, 'index', ['GET']],
    'patient.form' => [App\Controllers\PatientController::class, 'form', ['GET']],
    'patient.save' => [App\Controllers\PatientController::class, 'save', ['POST']],
    'patient.document.delete' => [App\Controllers\PatientController::class, 'deleteDocument', ['POST']],
    'patient.history' => [App\Controllers\PatientController::class, 'history', ['GET']],
    'patient.history.report' => [App\Controllers\PatientController::class, 'historyReport', ['GET']],
    'patient.lgpd.export' => [App\Controllers\PatientController::class, 'exportLgpd', ['GET']],
    'patient.lgpd.anonymize' => [App\Controllers\PatientController::class, 'anonymizeLgpd', ['POST']],
    'reports.executive' => [App\Controllers\ReportsController::class, 'executive', ['GET']],
    'reports.executive.csv' => [App\Controllers\ReportsController::class, 'executiveCsv', ['GET']],
    'queue' => [App\Controllers\QueueController::class, 'index', ['GET']],
    'queue.generate' => [App\Controllers\QueueController::class, 'generate', ['POST']],
    'queue.call' => [App\Controllers\QueueController::class, 'call', ['POST']],
    'queue.done' => [App\Controllers\QueueController::class, 'done', ['POST']],
    'queue.panel' => [App\Controllers\QueueController::class, 'panel', ['GET']],
    'record.show' => [App\Controllers\RecordController::class, 'show', ['GET']],
    'record.add' => [App\Controllers\RecordController::class, 'add', ['POST']],
    'record.document.delete' => [App\Controllers\RecordController::class, 'deleteDocument', ['POST']],
];

if (!isset($routes[$route])) {
    http_response_code(404);
    echo 'Rota não encontrada.';
    exit;
}

[$controllerClass, $method, $allowedMethods] = $routes[$route];

if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods, true)) {
    http_response_code(405);
    echo 'Metodo não permitido.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

$controller = new $controllerClass();
$controller->$method();

