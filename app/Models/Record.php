<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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
}

