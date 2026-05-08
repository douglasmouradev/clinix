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

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_executivo_' . $dateFrom . '_' . $dateTo . '.csv"');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fputcsv($output, ['Clinix', 'Relatorio Executivo']);
        fputcsv($output, ['Período', $dateFrom . ' a ' . $dateTo]);
        fputcsv($output, []);
        fputcsv($output, ['KPI', 'Valor']);
        fputcsv($output, ['Pacientes ativos', $kpis['active_patients'] ?? 0]);
        fputcsv($output, ['Agendamentos', $kpis['appointments_total'] ?? 0]);
        fputcsv($output, ['Senhas geradas', $kpis['queue_total'] ?? 0]);
        fputcsv($output, ['Registros clinicos', $kpis['records_total'] ?? 0]);
        fputcsv($output, ['Usuários ativos', $kpis['active_users'] ?? 0]);
        fputcsv($output, []);
        fputcsv($output, ['Status do agendamento', 'Total']);
        foreach ($appointmentsByStatus as $row) {
            fputcsv($output, [$row['status'], $row['total']]);
        }
        fclose($output);
        exit;
    }
}

