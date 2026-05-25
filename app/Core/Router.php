<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @return array<string, array{class: class-string, method: string, http: list<string>, roles?: list<string>, public?: bool}>
     */
    public static function routes(): array
    {
        return [
            'login' => ['class' => \App\Controllers\AuthController::class, 'method' => 'loginForm', 'http' => ['GET'], 'public' => true],
            'login.submit' => ['class' => \App\Controllers\AuthController::class, 'method' => 'login', 'http' => ['POST'], 'public' => true],
            'login.2fa' => ['class' => \App\Controllers\AuthController::class, 'method' => 'twoFactorForm', 'http' => ['GET'], 'public' => true],
            'login.2fa.submit' => ['class' => \App\Controllers\AuthController::class, 'method' => 'twoFactorVerify', 'http' => ['POST'], 'public' => true],
            'password.forgot' => ['class' => \App\Controllers\PasswordResetController::class, 'method' => 'forgotForm', 'http' => ['GET'], 'public' => true],
            'password.forgot.submit' => ['class' => \App\Controllers\PasswordResetController::class, 'method' => 'forgotSubmit', 'http' => ['POST'], 'public' => true],
            'password.reset' => ['class' => \App\Controllers\PasswordResetController::class, 'method' => 'resetForm', 'http' => ['GET'], 'public' => true],
            'password.reset.submit' => ['class' => \App\Controllers\PasswordResetController::class, 'method' => 'resetSubmit', 'http' => ['POST'], 'public' => true],
            'onboarding' => ['class' => \App\Controllers\OnboardingController::class, 'method' => 'form', 'http' => ['GET'], 'public' => true],
            'onboarding.submit' => ['class' => \App\Controllers\OnboardingController::class, 'method' => 'submit', 'http' => ['POST'], 'public' => true],
            'logout' => ['class' => \App\Controllers\AuthController::class, 'method' => 'logout', 'http' => ['POST'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'password.change' => ['class' => \App\Controllers\PasswordController::class, 'method' => 'form', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'password.change.submit' => ['class' => \App\Controllers\PasswordController::class, 'method' => 'submit', 'http' => ['POST'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'dashboard' => ['class' => \App\Controllers\DashboardController::class, 'method' => 'index', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'billing' => ['class' => \App\Controllers\BillingController::class, 'method' => 'index', 'http' => ['GET'], 'roles' => ['admin']],
            'billing.plan.change' => ['class' => \App\Controllers\BillingController::class, 'method' => 'changePlan', 'http' => ['POST'], 'roles' => ['admin']],
            'billing.checkout' => ['class' => \App\Controllers\BillingController::class, 'method' => 'checkout', 'http' => ['POST'], 'roles' => ['admin']],
            'billing.webhook' => ['class' => \App\Controllers\BillingController::class, 'method' => 'webhook', 'http' => ['POST'], 'public' => true],
            'compliance' => ['class' => \App\Controllers\ComplianceController::class, 'method' => 'index', 'http' => ['GET'], 'roles' => ['admin']],
            'compliance.policy.save' => ['class' => \App\Controllers\ComplianceController::class, 'method' => 'savePolicy', 'http' => ['POST'], 'roles' => ['admin']],
            'compliance.retention.run' => ['class' => \App\Controllers\ComplianceController::class, 'method' => 'runRetention', 'http' => ['POST'], 'roles' => ['admin']],
            'appointments' => ['class' => \App\Controllers\AppointmentController::class, 'method' => 'index', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'appointments.week' => ['class' => \App\Controllers\AppointmentController::class, 'method' => 'week', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'appointment.form' => ['class' => \App\Controllers\AppointmentController::class, 'method' => 'form', 'http' => ['GET'], 'roles' => ['admin', 'reception']],
            'appointment.save' => ['class' => \App\Controllers\AppointmentController::class, 'method' => 'save', 'http' => ['POST'], 'roles' => ['admin', 'reception']],
            'appointment.status' => ['class' => \App\Controllers\AppointmentController::class, 'method' => 'updateStatus', 'http' => ['POST'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'appointment.confirm' => ['class' => \App\Controllers\AppointmentController::class, 'method' => 'confirm', 'http' => ['GET'], 'public' => true],
            'admin.users' => ['class' => \App\Controllers\AdminController::class, 'method' => 'users', 'http' => ['GET'], 'roles' => ['admin']],
            'admin.user.form' => ['class' => \App\Controllers\AdminController::class, 'method' => 'userForm', 'http' => ['GET'], 'roles' => ['admin']],
            'admin.user.save' => ['class' => \App\Controllers\AdminController::class, 'method' => 'userSave', 'http' => ['POST'], 'roles' => ['admin']],
            'admin.panel' => ['class' => \App\Controllers\AdminController::class, 'method' => 'panelSettings', 'http' => ['GET'], 'roles' => ['admin']],
            'admin.panel.rotate' => ['class' => \App\Controllers\AdminController::class, 'method' => 'rotatePanelToken', 'http' => ['POST'], 'roles' => ['admin']],
            'admin.clinic' => ['class' => \App\Controllers\AdminController::class, 'method' => 'clinicSlug', 'http' => ['GET'], 'roles' => ['admin']],
            'admin.clinic.save' => ['class' => \App\Controllers\AdminController::class, 'method' => 'clinicSlugSave', 'http' => ['POST'], 'roles' => ['admin']],
            'admin.api' => ['class' => \App\Controllers\AdminController::class, 'method' => 'apiTokens', 'http' => ['GET'], 'roles' => ['admin']],
            'admin.api.create' => ['class' => \App\Controllers\AdminController::class, 'method' => 'apiTokenCreate', 'http' => ['POST'], 'roles' => ['admin']],
            'admin.api.revoke' => ['class' => \App\Controllers\AdminController::class, 'method' => 'apiTokenRevoke', 'http' => ['POST'], 'roles' => ['admin']],
            'admin.audit' => ['class' => \App\Controllers\AuditController::class, 'method' => 'index', 'http' => ['GET'], 'roles' => ['admin']],
            'admin.2fa' => ['class' => \App\Controllers\TwoFactorController::class, 'method' => 'settings', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'admin.2fa.enable' => ['class' => \App\Controllers\TwoFactorController::class, 'method' => 'enable', 'http' => ['POST'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'admin.2fa.disable' => ['class' => \App\Controllers\TwoFactorController::class, 'method' => 'disable', 'http' => ['POST'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'patients' => ['class' => \App\Controllers\PatientController::class, 'method' => 'index', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'patient.form' => ['class' => \App\Controllers\PatientController::class, 'method' => 'form', 'http' => ['GET'], 'roles' => ['admin', 'reception']],
            'patient.save' => ['class' => \App\Controllers\PatientController::class, 'method' => 'save', 'http' => ['POST'], 'roles' => ['admin', 'reception']],
            'patient.document' => ['class' => \App\Controllers\DocumentController::class, 'method' => 'patient', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'patient.document.delete' => ['class' => \App\Controllers\PatientController::class, 'method' => 'deleteDocument', 'http' => ['POST'], 'roles' => ['admin', 'reception']],
            'patient.history' => ['class' => \App\Controllers\PatientController::class, 'method' => 'history', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'patient.history.report' => ['class' => \App\Controllers\PatientController::class, 'method' => 'historyReport', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'patient.lgpd.export' => ['class' => \App\Controllers\PatientController::class, 'method' => 'exportLgpd', 'http' => ['GET'], 'roles' => ['admin']],
            'patient.lgpd.anonymize' => ['class' => \App\Controllers\PatientController::class, 'method' => 'anonymizeLgpd', 'http' => ['POST'], 'roles' => ['admin']],
            'reports.executive' => ['class' => \App\Controllers\ReportsController::class, 'method' => 'executive', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'reports.executive.csv' => ['class' => \App\Controllers\ReportsController::class, 'method' => 'executiveCsv', 'http' => ['GET'], 'roles' => ['admin']],
            'queue' => ['class' => \App\Controllers\QueueController::class, 'method' => 'index', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'queue.data' => ['class' => \App\Controllers\QueueController::class, 'method' => 'data', 'http' => ['GET'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'queue.generate' => ['class' => \App\Controllers\QueueController::class, 'method' => 'generate', 'http' => ['POST'], 'roles' => ['admin', 'reception']],
            'queue.ticket.print' => ['class' => \App\Controllers\QueueController::class, 'method' => 'ticketPrint', 'http' => ['GET'], 'roles' => ['admin', 'reception']],
            'queue.call' => ['class' => \App\Controllers\QueueController::class, 'method' => 'call', 'http' => ['POST'], 'roles' => ['admin', 'reception', 'nurse', 'doctor']],
            'queue.done' => ['class' => \App\Controllers\QueueController::class, 'method' => 'done', 'http' => ['POST'], 'roles' => ['admin', 'nurse', 'doctor']],
            'queue.kiosk' => ['class' => \App\Controllers\QueueController::class, 'method' => 'kiosk', 'http' => ['GET'], 'public' => true],
            'queue.kiosk.scheduled' => ['class' => \App\Controllers\QueueController::class, 'method' => 'kioskScheduled', 'http' => ['GET'], 'public' => true],
            'queue.kiosk.scheduled.submit' => ['class' => \App\Controllers\QueueController::class, 'method' => 'kioskScheduledSubmit', 'http' => ['POST'], 'public' => true],
            'queue.kiosk.walkin' => ['class' => \App\Controllers\QueueController::class, 'method' => 'kioskWalkIn', 'http' => ['POST'], 'public' => true],
            'queue.kiosk.print' => ['class' => \App\Controllers\QueueController::class, 'method' => 'kioskTicketPrint', 'http' => ['GET'], 'public' => true],
            'queue.panel' => ['class' => \App\Controllers\QueueController::class, 'method' => 'panel', 'http' => ['GET'], 'public' => true],
            'queue.panel.data' => ['class' => \App\Controllers\QueueController::class, 'method' => 'panelData', 'http' => ['GET'], 'public' => true],
            'queue.panel.stream' => ['class' => \App\Controllers\QueueController::class, 'method' => 'panelStream', 'http' => ['GET'], 'public' => true],
            'record.show' => ['class' => \App\Controllers\RecordController::class, 'method' => 'show', 'http' => ['GET'], 'roles' => ['admin', 'nurse', 'doctor']],
            'record.add' => ['class' => \App\Controllers\RecordController::class, 'method' => 'add', 'http' => ['POST'], 'roles' => ['admin', 'nurse', 'doctor']],
            'record.amend' => ['class' => \App\Controllers\RecordController::class, 'method' => 'amend', 'http' => ['POST'], 'roles' => ['admin', 'doctor']],
            'record.document' => ['class' => \App\Controllers\DocumentController::class, 'method' => 'record', 'http' => ['GET'], 'roles' => ['admin', 'nurse', 'doctor']],
            'record.document.delete' => ['class' => \App\Controllers\RecordController::class, 'method' => 'deleteDocument', 'http' => ['POST'], 'roles' => ['admin', 'nurse', 'doctor']],
            'cron.retention' => ['class' => \App\Controllers\CronController::class, 'method' => 'retention', 'http' => ['GET', 'POST'], 'public' => true],
            'api.v1.patients' => ['class' => \App\Controllers\ApiController::class, 'method' => 'patients', 'http' => ['GET'], 'public' => true],
            'api.v1.queue' => ['class' => \App\Controllers\ApiController::class, 'method' => 'queue', 'http' => ['GET'], 'public' => true],
        ];
    }

    public static function dispatch(string $route, string $httpMethod): void
    {
        $routes = self::routes();
        if (!isset($routes[$route])) {
            http_response_code(404);
            echo 'Rota não encontrada.';
            exit;
        }

        $def = $routes[$route];
        if (!in_array($httpMethod, $def['http'], true)) {
            http_response_code(405);
            echo 'Metodo não permitido.';
            exit;
        }

        $isPublic = (bool) ($def['public'] ?? false);
        if (!$isPublic) {
            if (isset($def['roles'])) {
                Auth::requireRole($def['roles']);
            } else {
                Auth::requireLogin();
            }
            Auth::enforcePasswordChange($route);
            BillingGate::assertActiveSubscription($route);
        }

        $controller = new $def['class']();
        $controller->{$def['method']}();
    }

    /** @return list<string> */
    public static function publicRoutes(): array
    {
        $public = [];
        foreach (self::routes() as $name => $def) {
            if (!empty($def['public'])) {
                $public[] = $name;
            }
        }

        return $public;
    }
}
