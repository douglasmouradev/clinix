<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\ExecutiveReport;

final class ReportsController
{
    public function executive(): void
    {
        Auth::requireRole(['admin']);
        $dateFrom = trim((string) ($_GET['date_from'] ?? date('Y-m-01')));
        $dateTo = trim((string) ($_GET['date_to'] ?? date('Y-m-d')));

        $model = new ExecutiveReport();
        $kpis = cacheRemember('reports_kpis_' . tenantId() . '_' . $dateFrom . '_' . $dateTo, 60, static fn (): array => $model->kpis($dateFrom, $dateTo));
        $appointmentsByStatus = cacheRemember('reports_appt_status_' . tenantId() . '_' . $dateFrom . '_' . $dateTo, 60, static fn (): array => $model->appointmentsByStatus($dateFrom, $dateTo));

        View::render('reports/executive', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'kpis' => $kpis,
            'appointmentsByStatus' => $appointmentsByStatus,
        ]);
    }

    public function executiveCsv(): void
    {
        Auth::requireRole(['admin']);
        $dateFrom = trim((string) ($_GET['date_from'] ?? date('Y-m-01')));
        $dateTo = trim((string) ($_GET['date_to'] ?? date('Y-m-d')));

        $model = new ExecutiveReport();
        $kpis = $model->kpis($dateFrom, $dateTo);
        $appointmentsByStatus = $model->appointmentsByStatus($dateFrom, $dateTo);

        $output = csvBeginDownload('relatorio_executivo_' . $dateFrom . '_' . $dateTo . '.csv');

        csvWriteRow($output, ['Clinix', 'Relatorio Executivo']);
        csvWriteRow($output, ['Periodo', $dateFrom . ' a ' . $dateTo]);
        csvWriteRow($output, []);
        csvWriteRow($output, ['KPI', 'Valor']);
        csvWriteRow($output, ['Pacientes ativos', (string) ($kpis['active_patients'] ?? 0)]);
        csvWriteRow($output, ['Agendamentos', (string) ($kpis['appointments_total'] ?? 0)]);
        csvWriteRow($output, ['Senhas geradas', (string) ($kpis['queue_total'] ?? 0)]);
        csvWriteRow($output, ['Registros clinicos', (string) ($kpis['records_total'] ?? 0)]);
        csvWriteRow($output, ['Usuarios ativos', (string) ($kpis['active_users'] ?? 0)]);
        csvWriteRow($output, []);
        csvWriteRow($output, ['Status do agendamento', 'Total']);
        foreach ($appointmentsByStatus as $row) {
            csvWriteRow($output, [(string) $row['status'], (string) $row['total']]);
        }

        fclose($output);
        exit;
    }
}
