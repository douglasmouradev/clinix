<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class DashboardStats
{
    /** @return array<string, int> */
    public function forRole(string $role): array
    {
        return cacheRemember('dashboard_stats_' . tenantId() . '_' . $role, 45, function () use ($role): array {
            return $this->computeForRole($role);
        });
    }

    /** @return array<string, int> */
    private function computeForRole(string $role): array
    {
        $tid = tenantId();
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        $conn = Database::connection();

        try {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM patients WHERE tenant_id = :t AND anonymized_at IS NULL');
            $stmt->execute(['t' => $tid]);
            $patients = (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM patients WHERE tenant_id = :t');
            $stmt->execute(['t' => $tid]);
            $patients = (int) $stmt->fetchColumn();
        }

        $stmt = $conn->prepare(
            'SELECT COUNT(*) FROM appointments WHERE tenant_id = :t AND scheduled_at >= :start AND scheduled_at <= :end'
        );
        $stmt->execute(['t' => $tid, 'start' => $todayStart, 'end' => $todayEnd]);
        $appointmentsToday = (int) $stmt->fetchColumn();

        $stmt = $conn->prepare(
            'SELECT COUNT(*) FROM queue_tickets WHERE tenant_id = :t AND status = "waiting" AND created_at >= :start AND created_at <= :end'
        );
        $stmt->execute(['t' => $tid, 'start' => $todayStart, 'end' => $todayEnd]);
        $queueWaiting = (int) $stmt->fetchColumn();

        $stmt = $conn->prepare(
            'SELECT COUNT(*) FROM patient_records WHERE tenant_id = :t AND created_at >= :start AND created_at <= :end'
        );
        $stmt->execute(['t' => $tid, 'start' => $todayStart, 'end' => $todayEnd]);
        $recordsToday = (int) $stmt->fetchColumn();

        $returnsOverdue = 0;
        $returnsPending = 0;
        try {
            $stmt = $conn->prepare(
                'SELECT COUNT(*) FROM patient_returns WHERE tenant_id = :t AND status = \'pending\' AND return_due_date < CURDATE()'
            );
            $stmt->execute(['t' => $tid]);
            $returnsOverdue = (int) $stmt->fetchColumn();

            $stmt = $conn->prepare('SELECT COUNT(*) FROM patient_returns WHERE tenant_id = :t AND status = \'pending\'');
            $stmt->execute(['t' => $tid]);
            $returnsPending = (int) $stmt->fetchColumn();
        } catch (\Throwable) {
        }

        $openInvoices = 0;
        if ($role === 'admin') {
            $stmt = $conn->prepare('SELECT COUNT(*) FROM invoices WHERE tenant_id = :t AND status = "open"');
            $stmt->execute(['t' => $tid]);
            $openInvoices = (int) $stmt->fetchColumn();
        }

        return [
            'patients' => $patients,
            'appointments_today' => $appointmentsToday,
            'queue_waiting' => $queueWaiting,
            'records_today' => $recordsToday,
            'returns_overdue' => $returnsOverdue,
            'returns_pending' => $returnsPending,
            'open_invoices' => $openInvoices,
        ];
    }
}
