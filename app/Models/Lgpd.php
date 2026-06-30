<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Lgpd
{
    public function logConsent(int $patientId, string $termVersion, ?int $collectedBy): void
    {
        $sql = 'INSERT INTO lgpd_consents (tenant_id, patient_id, term_version, consented_at, collected_by, ip_address, user_agent)
                VALUES (:tenant_id, :patient_id, :term_version, NOW(), :collected_by, :ip_address, :user_agent)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'patient_id' => $patientId,
            'term_version' => $termVersion,
            'collected_by' => $collectedBy,
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }

    public function consents(int $patientId): array
    {
        $sql = 'SELECT lc.*, u.name AS collected_by_name
                FROM lgpd_consents lc
                LEFT JOIN users u ON u.id = lc.collected_by
                WHERE lc.tenant_id = :tenant_id AND lc.patient_id = :patient_id
                ORDER BY lc.id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function logRequest(int $patientId, int $userId, string $type, string $notes = ''): void
    {
        $sql = 'INSERT INTO lgpd_data_requests (tenant_id, patient_id, requested_by, request_type, status, notes)
                VALUES (:tenant_id, :patient_id, :requested_by, :request_type, "completed", :notes)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'patient_id' => $patientId,
            'requested_by' => $userId,
            'request_type' => $type,
            'notes' => $notes,
        ]);
    }

    public function requests(int $patientId): array
    {
        $sql = 'SELECT lr.*, u.name AS requested_by_name
                FROM lgpd_data_requests lr
                INNER JOIN users u ON u.id = lr.requested_by
                WHERE lr.tenant_id = :tenant_id AND lr.patient_id = :patient_id
                ORDER BY lr.id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function retentionPolicy(): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tenant_retention_policies WHERE tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['tenant_id' => tenantId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveRetentionPolicy(int $retentionDays, bool $autoAnonymize, ?int $updatedBy): void
    {
        $sql = 'INSERT INTO tenant_retention_policies (tenant_id, retention_days, auto_anonymize, updated_by)
                VALUES (:tenant_id, :retention_days, :auto_anonymize, :updated_by)
                ON DUPLICATE KEY UPDATE
                    retention_days = VALUES(retention_days),
                    auto_anonymize = VALUES(auto_anonymize),
                    updated_by = VALUES(updated_by)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'retention_days' => $retentionDays,
            'auto_anonymize' => $autoAnonymize ? 1 : 0,
            'updated_by' => $updatedBy,
        ]);
    }

    public function runRetentionAnonymization(?int $requestedBy): int
    {
        $policy = $this->retentionPolicy();
        $days = (int) ($policy['retention_days'] ?? LGPD_RETENTION_DAYS_DEFAULT);
        $days = max(30, $days);

        $select = Database::connection()->prepare(
            'SELECT id FROM patients
             WHERE tenant_id = :tenant_id
               AND anonymized_at IS NULL
               AND updated_at < DATE_SUB(NOW(), INTERVAL :retention_days DAY)'
        );
        $select->bindValue(':tenant_id', tenantId(), \PDO::PARAM_INT);
        $select->bindValue(':retention_days', $days, \PDO::PARAM_INT);
        $select->execute();
        $ids = array_map('intval', $select->fetchAll(\PDO::FETCH_COLUMN));

        $patientModel = new Patient();
        foreach ($ids as $id) {
            $patientModel->anonymizeCascade($id);
        }

        $affected = count($ids);

        if ($affected > 0 && $requestedBy !== null) {
            $log = Database::connection()->prepare('INSERT INTO audit_logs (tenant_id, user_id, action, details, ip_address)
                    VALUES (:tenant_id, :user_id, :action, :details, :ip_address)');
            $log->execute([
                'tenant_id' => tenantId(),
                'user_id' => $requestedBy,
                'action' => 'lgpd.retention.run',
                'details' => 'Anonimizados ' . $affected . ' pacientes por política de retenção',
                'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ]);
        }

        return $affected;
    }
}

