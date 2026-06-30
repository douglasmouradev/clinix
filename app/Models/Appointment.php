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

    public function create(array $data): int
    {
        $sql = 'INSERT INTO appointments (tenant_id, patient_id, professional_id, scheduled_at, status, reason, notes, confirm_token, created_by)
                VALUES (:tenant_id, :patient_id, :professional_id, :scheduled_at, :status, :reason, :notes, :confirm_token, :created_by)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data + [
            'tenant_id' => tenantId(),
            'confirm_token' => bin2hex(random_bytes(16)),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function week(string $startDate): array
    {
        $end = date('Y-m-d', strtotime($startDate . ' +6 days'));
        $sql = 'SELECT a.*, p.full_name AS patient_name, u.name AS professional_name
                FROM appointments a
                INNER JOIN patients p ON p.id = a.patient_id
                LEFT JOIN users u ON u.id = a.professional_id
                WHERE a.tenant_id = :tenant_id
                  AND DATE(a.scheduled_at) BETWEEN :start AND :end
                ORDER BY a.scheduled_at ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'start' => $startDate, 'end' => $end]);
        return $stmt->fetchAll();
    }

    public function findByConfirmToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $stmt = Database::connection()->prepare(
            'SELECT * FROM appointments WHERE confirm_token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
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

    public function findTodayActiveForPatient(int $patientId): ?array
    {
        $sql = 'SELECT a.*, p.full_name AS patient_name, u.name AS professional_name
                FROM appointments a
                INNER JOIN patients p ON p.id = a.patient_id
                LEFT JOIN users u ON u.id = a.professional_id
                WHERE a.tenant_id = :tenant_id
                  AND a.patient_id = :patient_id
                  AND DATE(a.scheduled_at) = CURDATE()
                  AND a.status IN ("scheduled", "checked_in")
                ORDER BY a.scheduled_at ASC
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findTodayForPatient(int $patientId): ?array
    {
        $sql = 'SELECT a.*, p.full_name AS patient_name, u.name AS professional_name
                FROM appointments a
                INNER JOIN patients p ON p.id = a.patient_id
                LEFT JOIN users u ON u.id = a.professional_id
                WHERE a.tenant_id = :tenant_id
                  AND a.patient_id = :patient_id
                  AND DATE(a.scheduled_at) = CURDATE()
                  AND a.status NOT IN ("cancelled", "completed")
                ORDER BY a.scheduled_at ASC
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findForPatient(int $appointmentId, int $patientId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, u.name AS professional_name
             FROM appointments a
             LEFT JOIN users u ON u.id = a.professional_id
             WHERE a.id = :id AND a.patient_id = :patient_id AND a.tenant_id = :tenant_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $appointmentId,
            'patient_id' => $patientId,
            'tenant_id' => tenantId(),
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function hasConflict(?int $professionalId, string $scheduledAt, ?int $ignoreId = null): bool
    {
        if ($professionalId === null || $professionalId <= 0) {
            return false;
        }

        $start = date('Y-m-d H:i:s', strtotime($scheduledAt . ' -29 minutes'));
        $end = date('Y-m-d H:i:s', strtotime($scheduledAt . ' +29 minutes'));
        $sql = 'SELECT COUNT(*) FROM appointments
                WHERE tenant_id = :tenant_id AND professional_id = :professional_id
                  AND status NOT IN ("cancelled", "completed")
                  AND scheduled_at BETWEEN :start AND :end';
        $params = [
            'tenant_id' => tenantId(),
            'professional_id' => $professionalId,
            'start' => $start,
            'end' => $end,
        ];
        if ($ignoreId !== null && $ignoreId > 0) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return list<array<string, mixed>> */
    public function upcomingForPatient(int $patientId, int $limit = 20): array
    {
        $sql = 'SELECT a.*, p.full_name AS patient_name, u.name AS professional_name
                FROM appointments a
                INNER JOIN patients p ON p.id = a.patient_id
                LEFT JOIN users u ON u.id = a.professional_id
                WHERE a.tenant_id = :tenant_id
                  AND a.patient_id = :patient_id
                  AND a.scheduled_at >= CURDATE()
                  AND a.status IN ("scheduled", "checked_in")
                ORDER BY a.scheduled_at ASC
                LIMIT ' . max(1, min($limit, 50));
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'patient_id' => $patientId]);

        return $stmt->fetchAll();
    }
}

