<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\Lgpd;
use App\Models\Notification;

final class CronController
{
    public function retention(): void
    {
        $secret = CRON_SECRET;
        $provided = trim((string) ($_SERVER['HTTP_X_CRON_SECRET'] ?? ''));
        if ($provided === '' && APP_ENV !== 'production') {
            $provided = trim((string) ($_GET['secret'] ?? ''));
        }
        if ($secret === '' || !hash_equals($secret, $provided)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $tenants = Database::connection()->query('SELECT id FROM tenants WHERE is_active = 1')->fetchAll();
        $lgpd = new Lgpd();
        $processed = 0;

        foreach ($tenants as $tenant) {
            $_SESSION['tenant_context_id'] = (int) $tenant['id'];
            $policy = $lgpd->retentionPolicy();
            if ((int) ($policy['auto_anonymize'] ?? 0) === 1) {
                $processed += $lgpd->runRetentionAnonymization(null);
            }
        }

        $notifications = (new Notification())->processDue();
        $returnReminders = $this->processReturnReminders();

        jsonResponse([
            'ok' => true,
            'retention_processed' => $processed,
            'notifications_sent' => $notifications,
            'return_reminders' => $returnReminders,
            'time' => date('c'),
        ]);
    }

    private function processReturnReminders(): int
    {
        if (!\App\Models\ReturnVisit::tableExists()) {
            return 0;
        }

        $sent = 0;
        $notifier = new Notification();
        $returnModel = new \App\Models\ReturnVisit();

        foreach ($returnModel->overdueNeedingReminder() as $row) {
            $_SESSION['tenant_context_id'] = (int) $row['tenant_id'];
            $phone = trim((string) ($row['patient_phone'] ?? ''));
            $body = 'Clinix: retorno previsto para ' . formatDateBr((string) $row['return_due_date'])
                . '. Entre em contato para agendar.';
            if ($phone !== '') {
                $notifier->schedule('log', $phone, 'Retorno pendente', $body, date('Y-m-d H:i:s'));
            }
            $returnModel->markReminderSent((int) $row['id']);
            $sent++;
        }

        return $sent;
    }
}
