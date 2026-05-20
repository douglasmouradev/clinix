<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ExecutiveReport
{
    public function kpis(string $dateFrom, string $dateTo): array
    {
        $tenantId = tenantId();
        $conn = Database::connection();

        $patientsStmt = $conn->prepare('SELECT COUNT(*) FROM patients WHERE tenant_id = :tenant_id AND anonymized_at IS NULL');
        $patientsStmt->execute(['tenant_id' => $tenantId]);

        $appointmentsStmt = $conn->prepare('SELECT COUNT(*) FROM appointments WHERE tenant_id = :tenant_id AND DATE(scheduled_at) BETWEEN :date_from AND :date_to');
        $appointmentsStmt->execute(['tenant_id' => $tenantId, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

        $queueStmt = $conn->prepare('SELECT COUNT(*) FROM queue_tickets WHERE tenant_id = :tenant_id AND DATE(created_at) BETWEEN :date_from AND :date_to');
        $queueStmt->execute(['tenant_id' => $tenantId, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

        $recordsStmt = $conn->prepare('SELECT COUNT(*) FROM patient_records WHERE tenant_id = :tenant_id AND DATE(created_at) BETWEEN :date_from AND :date_to');
        $recordsStmt->execute(['tenant_id' => $tenantId, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

        $activeUsersStmt = $conn->prepare('SELECT COUNT(*) FROM users WHERE tenant_id = :tenant_id AND is_active = 1');
        $activeUsersStmt->execute(['tenant_id' => $tenantId]);

        $avgWaitStmt = $conn->prepare(
            'SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) FROM queue_tickets
             WHERE tenant_id = :tenant_id AND status IN ("called", "done")
               AND called_at IS NOT NULL AND DATE(created_at) BETWEEN :date_from AND :date_to'
        );
        $avgWaitStmt->execute(['tenant_id' => $tenantId, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
        $avgWait = $avgWaitStmt->fetchColumn();

        return [
            'active_patients' => (int) $patientsStmt->fetchColumn(),
            'appointments_total' => (int) $appointmentsStmt->fetchColumn(),
            'queue_total' => (int) $queueStmt->fetchColumn(),
            'records_total' => (int) $recordsStmt->fetchColumn(),
            'active_users' => (int) $activeUsersStmt->fetchColumn(),
            'queue_avg_wait_minutes' => $avgWait !== null ? round((float) $avgWait, 1) : 0.0,
        ];
    }

    public function appointmentsByStatus(string $dateFrom, string $dateTo): array
    {
        $sql = 'SELECT status, COUNT(*) AS total
                FROM appointments
                WHERE tenant_id = :tenant_id AND DATE(scheduled_at) BETWEEN :date_from AND :date_to
                GROUP BY status
                ORDER BY total DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'date_from' => $dateFrom, 'date_to' => $dateTo]);
        return $stmt->fetchAll();
    }
}

