<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class DashboardStats
{
    /** @return array<string, int> */
    public function forRole(string $role): array
    {
        $tid = tenantId();
        $today = date('Y-m-d');
        $conn = Database::connection();

        $stmt = $conn->prepare('SELECT COUNT(*) FROM patients WHERE tenant_id = :t AND anonymized_at IS NULL');
        $stmt->execute(['t' => $tid]);
        $patients = (int) $stmt->fetchColumn();

        $stmt = $conn->prepare('SELECT COUNT(*) FROM appointments WHERE tenant_id = :t AND DATE(scheduled_at) = :d');
        $stmt->execute(['t' => $tid, 'd' => $today]);
        $appointmentsToday = (int) $stmt->fetchColumn();

        $stmt = $conn->prepare('SELECT COUNT(*) FROM queue_tickets WHERE tenant_id = :t AND status = "waiting" AND DATE(created_at) = :d');
        $stmt->execute(['t' => $tid, 'd' => $today]);
        $queueWaiting = (int) $stmt->fetchColumn();

        $stmt = $conn->prepare('SELECT COUNT(*) FROM patient_records WHERE tenant_id = :t AND DATE(created_at) = :d');
        $stmt->execute(['t' => $tid, 'd' => $today]);
        $recordsToday = (int) $stmt->fetchColumn();

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
            'open_invoices' => $openInvoices,
        ];
    }
}
