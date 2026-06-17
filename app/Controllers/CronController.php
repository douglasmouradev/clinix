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

        jsonResponse([
            'ok' => true,
            'retention_processed' => $processed,
            'notifications_sent' => $notifications,
            'time' => date('c'),
        ]);
    }
}
