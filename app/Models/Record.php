<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\DocumentStorage;

final class Record
{
    public function timeline(int $patientId, array $filters = []): array
    {
        $conditions = ['pr.patient_id = :patient_id', 'pr.tenant_id = :tenant_id'];
        $params = ['patient_id' => $patientId, 'tenant_id' => tenantId()];

        $entryType = trim((string) ($filters['entry_type'] ?? ''));
        if ($entryType !== '') {
            $conditions[] = 'pr.entry_type = :entry_type';
            $params['entry_type'] = $entryType;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $conditions[] = 'DATE(pr.created_at) >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $conditions[] = 'DATE(pr.created_at) <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $sql = 'SELECT pr.*, u.name AS professional_name, u.role
                FROM patient_records pr
                INNER JOIN users u ON u.id = pr.professional_id
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY pr.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addEntry(int $patientId, int $professionalId, string $entryType, string $content, ?array $structuredData = null): int
    {
        $connection = Database::connection();
        $sql = 'INSERT INTO patient_records (tenant_id, patient_id, professional_id, entry_type, content, structured_data) 
                VALUES (:tenant_id, :patient_id, :professional_id, :entry_type, :content, :structured_data)';
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'entry_type' => $entryType,
            'content' => $content,
            'structured_data' => $structuredData !== null ? json_encode($structuredData, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) $connection->lastInsertId();
    }

    public function addDocument(int $recordId, string $originalName, string $storedName, string $filePath, string $mimeType, int $fileSize): void
    {
        $sql = 'INSERT INTO record_documents (tenant_id, record_id, original_name, stored_name, file_path, mime_type, file_size)
                VALUES (:tenant_id, :record_id, :original_name, :stored_name, :file_path, :mime_type, :file_size)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'record_id' => $recordId,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);
    }

    public function documentsByRecordIds(array $recordIds): array
    {
        if (empty($recordIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
        $sql = "SELECT id, record_id, original_name, file_path, mime_type, file_size, created_at
                FROM record_documents
                WHERE tenant_id = ? AND record_id IN ($placeholders)
                ORDER BY id ASC";

        $params = array_merge([tenantId()], $recordIds);
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['record_id']][] = $row;
        }

        return $grouped;
    }

    public function deleteDocument(int $documentId): ?array
    {
        $select = Database::connection()->prepare('SELECT id, file_path, record_id FROM record_documents WHERE id = :id AND tenant_id = :tenant_id');
        $select->execute(['id' => $documentId, 'tenant_id' => tenantId()]);
        $doc = $select->fetch();
        if (!$doc) {
            return null;
        }

        $delete = Database::connection()->prepare('DELETE FROM record_documents WHERE id = :id AND tenant_id = :tenant_id');
        $delete->execute(['id' => $documentId, 'tenant_id' => tenantId()]);

        return $doc;
    }

    public function findEntry(int $recordId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM patient_records WHERE id = :id AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['id' => $recordId, 'tenant_id' => tenantId()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function amendEntry(int $recordId, string $content, string $reason, int $changedBy): void
    {
        $entry = $this->findEntry($recordId);
        if (!$entry) {
            return;
        }

        $conn = Database::connection();
        $versionStmt = $conn->prepare(
            'SELECT COALESCE(MAX(version_no), 0) + 1 FROM patient_record_versions WHERE record_id = :record_id AND tenant_id = :tenant_id'
        );
        $versionStmt->execute(['record_id' => $recordId, 'tenant_id' => tenantId()]);
        $versionNo = (int) $versionStmt->fetchColumn();

        $insert = $conn->prepare(
            'INSERT INTO patient_record_versions (tenant_id, record_id, patient_id, professional_id, entry_type, content, structured_data, version_no, change_reason, changed_by)
             VALUES (:tenant_id, :record_id, :patient_id, :professional_id, :entry_type, :content, :structured_data, :version_no, :change_reason, :changed_by)'
        );
        $insert->execute([
            'tenant_id' => tenantId(),
            'record_id' => $recordId,
            'patient_id' => (int) $entry['patient_id'],
            'professional_id' => (int) $entry['professional_id'],
            'entry_type' => (string) $entry['entry_type'],
            'content' => (string) $entry['content'],
            'structured_data' => $entry['structured_data'],
            'version_no' => $versionNo,
            'change_reason' => $reason,
            'changed_by' => $changedBy,
        ]);

        $update = $conn->prepare(
            'UPDATE patient_records SET content = :content WHERE id = :id AND tenant_id = :tenant_id'
        );
        $update->execute(['content' => $content, 'id' => $recordId, 'tenant_id' => tenantId()]);
    }

    /** @return list<array<string, mixed>> */
    public function versions(int $recordId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT v.*, u.name AS changed_by_name FROM patient_record_versions v
             INNER JOIN users u ON u.id = v.changed_by
             WHERE v.record_id = :record_id AND v.tenant_id = :tenant_id
             ORDER BY v.version_no DESC'
        );
        $stmt->execute(['record_id' => $recordId, 'tenant_id' => tenantId()]);
        return $stmt->fetchAll();
    }

    public function purgePatientClinicalData(int $patientId): void
    {
        $connection = Database::connection();
        $docsStmt = $connection->prepare(
            'SELECT rd.file_path
             FROM record_documents rd
             INNER JOIN patient_records pr ON pr.id = rd.record_id
             WHERE pr.patient_id = :patient_id AND pr.tenant_id = :tenant_id'
        );
        $docsStmt->execute(['patient_id' => $patientId, 'tenant_id' => tenantId()]);
        foreach ($docsStmt->fetchAll() as $doc) {
            DocumentStorage::delete((string) $doc['file_path']);
        }

        $connection->prepare(
            'DELETE rd FROM record_documents rd
             INNER JOIN patient_records pr ON pr.id = rd.record_id
             WHERE pr.patient_id = :patient_id AND pr.tenant_id = :tenant_id'
        )->execute(['patient_id' => $patientId, 'tenant_id' => tenantId()]);

        $connection->prepare(
            'UPDATE patient_records
             SET content = "[Dados clínicos anonimizados por LGPD]", structured_data = NULL
             WHERE patient_id = :patient_id AND tenant_id = :tenant_id'
        )->execute(['patient_id' => $patientId, 'tenant_id' => tenantId()]);
    }
}

