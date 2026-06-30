<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\CpfValidator;
use App\Core\Database;

final class Patient
{
    public const WALK_IN_CPF = '00000000000';
    public const PRIORITY_CPF = '00000000001';

    public function all(?string $search = null): array
    {
        return $this->search($search, 500);
    }

    /** @return list<array<string, mixed>> */
    public function search(?string $search = null, int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));
        $connection = Database::connection();

        if ($search === null || trim($search) === '') {
            $stmt = $connection->prepare(
                'SELECT id, full_name, cpf, phone, birth_date
                 FROM patients
                 WHERE tenant_id = :tenant_id AND anonymized_at IS NULL
                 ORDER BY full_name
                 LIMIT ' . $limit
            );
            $stmt->execute(['tenant_id' => tenantId()]);

            return $stmt->fetchAll();
        }

        $sql = 'SELECT id, full_name, cpf, phone, birth_date
                FROM patients
                WHERE tenant_id = :tenant_id
                  AND anonymized_at IS NULL
                  AND (full_name LIKE :search OR cpf LIKE :search OR phone LIKE :search)
                ORDER BY full_name
                LIMIT ' . $limit;
        $stmt = $connection->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'search' => '%' . trim($search) . '%']);

        return $stmt->fetchAll();
    }

    public function findByCpfAndBirthDate(string $cpf, string $birthDate): ?array
    {
        $cpf = CpfValidator::normalize($cpf);
        if (strlen($cpf) !== 11 || trim($birthDate) === '') {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM patients
             WHERE cpf = :cpf AND birth_date = :birth_date AND tenant_id = :tenant_id AND anonymized_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([
            'cpf' => $cpf,
            'birth_date' => $birthDate,
            'tenant_id' => tenantId(),
        ]);
        $patient = $stmt->fetch();

        return $patient ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM patients WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
        $patient = $stmt->fetch();
        return $patient ?: null;
    }

    public function findByCpf(string $cpf): ?array
    {
        $cpf = CpfValidator::normalize($cpf);
        if (strlen($cpf) !== 11) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT * FROM patients WHERE cpf = :cpf AND tenant_id = :tenant_id AND anonymized_at IS NULL LIMIT 1'
        );
        $stmt->execute(['cpf' => $cpf, 'tenant_id' => tenantId()]);
        $patient = $stmt->fetch();

        return $patient ?: null;
    }

    public function isWalkInRecord(array $patient): bool
    {
        return (string) ($patient['cpf'] ?? '') === self::WALK_IN_CPF;
    }

    public function isPriorityRecord(array $patient): bool
    {
        return (string) ($patient['cpf'] ?? '') === self::PRIORITY_CPF;
    }

    /** Paciente genérico para senhas sem agendamento (totem). */
    public function walkInPatientId(): int
    {
        $existing = $this->findByCpf(self::WALK_IN_CPF);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return $this->create([
            'full_name' => 'Atendimento sem agendamento',
            'cpf' => self::WALK_IN_CPF,
            'birth_date' => '2000-01-01',
            'sex' => 'nao_informado',
            'phone' => null,
            'address' => null,
            'medical_history' => null,
            'lgpd_consent_at' => null,
            'lgpd_consent_version' => null,
        ]);
    }

    /** Paciente genérico para senhas prioritárias (totem). */
    public function priorityPatientId(): int
    {
        $existing = $this->findByCpf(self::PRIORITY_CPF);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return $this->create([
            'full_name' => 'Atendimento prioritário',
            'cpf' => self::PRIORITY_CPF,
            'birth_date' => '2000-01-01',
            'sex' => 'nao_informado',
            'phone' => null,
            'address' => null,
            'medical_history' => null,
            'lgpd_consent_at' => null,
            'lgpd_consent_version' => null,
        ]);
    }

    public function create(array $data): int
    {
        $connection = Database::connection();
        $sql = 'INSERT INTO patients (tenant_id, full_name, cpf, birth_date, sex, phone, address, medical_history, lgpd_consent_at, lgpd_consent_version) 
                VALUES (:tenant_id, :full_name, :cpf, :birth_date, :sex, :phone, :address, :medical_history, :lgpd_consent_at, :lgpd_consent_version)';
        $stmt = $connection->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()] + $data);

        return (int) $connection->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $sql = 'UPDATE patients 
                SET full_name = :full_name, cpf = :cpf, birth_date = :birth_date, sex = :sex, phone = :phone, 
                    address = :address, medical_history = :medical_history, lgpd_consent_at = :lgpd_consent_at, lgpd_consent_version = :lgpd_consent_version
                WHERE id = :id AND tenant_id = :tenant_id';
        $data['tenant_id'] = tenantId();
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);
    }

    public function addDocument(int $patientId, string $originalName, string $storedName, string $filePath, string $mimeType, int $fileSize): void
    {
        $sql = 'INSERT INTO patient_documents (tenant_id, patient_id, original_name, stored_name, file_path, mime_type, file_size)
                VALUES (:tenant_id, :patient_id, :original_name, :stored_name, :file_path, :mime_type, :file_size)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'patient_id' => $patientId,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);
    }

    public function documentsByPatientId(int $patientId): array
    {
        $sql = 'SELECT id, original_name, file_path, mime_type, file_size, created_at
                FROM patient_documents
                WHERE tenant_id = :tenant_id AND patient_id = :patient_id
                ORDER BY created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId(), 'patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function deleteDocument(int $documentId): ?array
    {
        $select = Database::connection()->prepare('SELECT id, file_path, patient_id FROM patient_documents WHERE id = :id AND tenant_id = :tenant_id');
        $select->execute(['id' => $documentId, 'tenant_id' => tenantId()]);
        $doc = $select->fetch();
        if (!$doc) {
            return null;
        }

        $delete = Database::connection()->prepare('DELETE FROM patient_documents WHERE id = :id AND tenant_id = :tenant_id');
        $delete->execute(['id' => $documentId, 'tenant_id' => tenantId()]);
        return $doc;
    }

    public function anonymize(int $id): void
    {
        $sql = 'UPDATE patients
                SET full_name = CONCAT("ANON-", id),
                    cpf = LPAD(id, 11, "0"),
                    birth_date = "1900-01-01",
                    phone = NULL,
                    address = NULL,
                    medical_history = "Dados anonimizados por LGPD.",
                    anonymized_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
    }
}
