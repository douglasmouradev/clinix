<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Lgpd;

final class ComplianceController
{
    public function index(): void
    {
        Auth::requireRole(['admin']);
        $lgpd = new Lgpd();
        View::render('compliance/index', [
            'policy' => $lgpd->retentionPolicy(),
        ]);
    }

    public function savePolicy(): void
    {
        Auth::requireRole(['admin']);
        $retentionDays = (int) ($_POST['retention_days'] ?? LGPD_RETENTION_DAYS_DEFAULT);
        $autoAnonymize = !empty($_POST['auto_anonymize']);
        $retentionDays = max(30, min(3650, $retentionDays));

        (new Lgpd())->saveRetentionPolicy($retentionDays, $autoAnonymize, (int) (Auth::user()['id'] ?? 0));
        auditLog('lgpd.retention.policy.save', 'Política atualizada: ' . $retentionDays . ' dias');
        flash('success', 'Política de retenção atualizada.');
        redirect('/?route=compliance');
    }

    public function runRetention(): void
    {
        Auth::requireRole(['admin']);
        $affected = (new Lgpd())->runRetentionAnonymization((int) (Auth::user()['id'] ?? 0));
        flash('success', 'Processo executado. Pacientes anonimizados: ' . $affected . '.');
        redirect('/?route=compliance');
    }
}

