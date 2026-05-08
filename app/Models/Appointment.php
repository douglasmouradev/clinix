<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Appointment
{
    public function all(?string $date = null, ?string $status = null): array
    {
        $conditions = ['a.tenant_id = :tenant_id'];
        $params = ['tenant_id' => tenantId()];

        if ($date !== null && trim($date) !== '') {
            $conditions[] = 'DATE(a.scheduled_at) = :date';
            $params['date'] = $date;
        }

        if ($status !== null && trim($status) !== '') {
            $conditions[] = 'a.status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT a.*, p.full_name AS patient_name, u.name AS professional_name
                FROM appointments a
                INNER JOIN patients p ON p.id = a.patient_id
                LEFT JOIN users u ON u.id = a.professional_id
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY a.scheduled_at ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT * FROM appointments WHERE id = :id AND tenant_id = :tenant_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $sql = 'INSERT INTO appointments (tenant_id, patient_id, professional_id, scheduled_at, status, reason, notes, created_by)
                VALUES (:tenant_id, :patient_id, :professional_id, :scheduled_at, :status, :reason, :notes, :created_by)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data + ['tenant_id' => tenantId()]);
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE appointments
                SET patient_id = :patient_id, professional_id = :professional_id, scheduled_at = :scheduled_at,
                    status = :status, reason = :reason, notes = :notes
                WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data + ['id' => $id, 'tenant_id' => tenantId()]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $sql = 'UPDATE appointments SET status = :status WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['status' => $status, 'id' => $id, 'tenant_id' => tenantId()]);
    }
}

