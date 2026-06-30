<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ReturnVisit
{
    /** @return list<array<string, mixed>> */
    public function list(?string $filter = null, ?string $search = null, ?string $from = null, ?string $to = null): array
    {
        $conditions = ['r.tenant_id = :tenant_id'];
        $params = ['tenant_id' => tenantId()];

        $filter = trim((string) $filter);
        if ($filter === 'overdue') {
            $conditions[] = "r.status = 'pending' AND r.return_due_date < CURDATE()";
        } elseif ($filter === 'pending') {
            $conditions[] = "r.status = 'pending'";
        } elseif ($filter !== '' && in_array($filter, ['scheduled', 'completed', 'cancelled'], true)) {
            $conditions[] = 'r.status = :status';
            $params['status'] = $filter;
        }

        if ($search !== null && trim($search) !== '') {
            $conditions[] = '(p.full_name LIKE :search OR p.cpf LIKE :search OR p.phone LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        if ($from !== null && trim($from) !== '') {
            $conditions[] = 'r.return_due_date >= :from';
            $params['from'] = $from;
        }

        if ($to !== null && trim($to) !== '') {
            $conditions[] = 'r.return_due_date <= :to';
            $params['to'] = $to;
        }

        $sql = 'SELECT r.*,
                       p.full_name AS patient_name,
                       p.phone AS patient_phone,
                       u.name AS professional_name,
                       a.scheduled_at AS appointment_scheduled_at,
                       CASE
                           WHEN r.status = \'pending\' AND r.return_due_date < CURDATE() THEN \'overdue\'
                           ELSE r.status
                       END AS effective_status
                FROM patient_returns r
                INNER JOIN patients p ON p.id = r.patient_id
                LEFT JOIN users u ON u.id = r.professional_id
                LEFT JOIN appointments a ON a.id = r.appointment_id
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY
                    CASE WHEN r.status = \'pending\' AND r.return_due_date < CURDATE() THEN 0 ELSE 1 END,
                    r.return_due_date ASC,
                    r.id DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT r.*,
                       p.full_name AS patient_name,
                       p.phone AS patient_phone,
                       u.name AS professional_name,
                       a.scheduled_at AS appointment_scheduled_at,
                       CASE
                           WHEN r.status = \'pending\' AND r.return_due_date < CURDATE() THEN \'overdue\'
                           ELSE r.status
                       END AS effective_status
                FROM patient_returns r
                INNER JOIN patients p ON p.id = r.patient_id
                LEFT JOIN users u ON u.id = r.professional_id
                LEFT JOIN appointments a ON a.id = r.appointment_id
                WHERE r.id = :id AND r.tenant_id = :tenant_id
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO patient_returns
                (tenant_id, patient_id, professional_id, source_appointment_id, return_due_date, status, reason, notes, created_by)
                VALUES
                (:tenant_id, :patient_id, :professional_id, :source_appointment_id, :return_due_date, :status, :reason, :notes, :created_by)';
        $connection = Database::connection();
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'patient_id' => (int) $data['patient_id'],
            'professional_id' => $data['professional_id'] ?? null,
            'source_appointment_id' => $data['source_appointment_id'] ?? null,
            'return_due_date' => $data['return_due_date'],
            'status' => $data['status'] ?? 'pending',
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => (int) $data['created_by'],
        ]);

        return (int) $connection->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE patient_returns
                SET patient_id = :patient_id,
                    professional_id = :professional_id,
                    return_due_date = :return_due_date,
                    reason = :reason,
                    notes = :notes
                WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'patient_id' => (int) $data['patient_id'],
            'professional_id' => $data['professional_id'] ?? null,
            'return_due_date' => $data['return_due_date'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'id' => $id,
            'tenant_id' => tenantId(),
        ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $sql = 'UPDATE patient_returns SET status = :status WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['status' => $status, 'id' => $id, 'tenant_id' => tenantId()]);
    }

    public function linkAppointment(int $id, int $appointmentId): void
    {
        $sql = 'UPDATE patient_returns
                SET status = \'scheduled\', appointment_id = :appointment_id
                WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'appointment_id' => $appointmentId,
            'id' => $id,
            'tenant_id' => tenantId(),
        ]);
    }

    public function countOverdue(): int
    {
        $sql = 'SELECT COUNT(*) FROM patient_returns
                WHERE tenant_id = :tenant_id AND status = \'pending\' AND return_due_date < CURDATE()';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);

        return (int) $stmt->fetchColumn();
    }

    public function countPending(): int
    {
        $sql = 'SELECT COUNT(*) FROM patient_returns
                WHERE tenant_id = :tenant_id AND status = \'pending\'';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);

        return (int) $stmt->fetchColumn();
    }
}
