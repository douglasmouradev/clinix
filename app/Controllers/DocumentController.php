<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DocumentStorage;

final class DocumentController
{
    public function patient(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $documentId = (int) ($_GET['id'] ?? 0);
        $stmt = Database::connection()->prepare(
            'SELECT pd.*, pd.patient_id FROM patient_documents pd
             WHERE pd.id = :id AND pd.tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['id' => $documentId, 'tenant_id' => tenantId()]);
        $doc = $stmt->fetch();
        if (!$doc) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }

        $this->stream((string) $doc['file_path'], (string) $doc['mime_type'], (string) $doc['original_name']);
    }

    public function record(): void
    {
        Auth::requireRole(['admin', 'nurse', 'doctor']);
        $documentId = (int) ($_GET['id'] ?? 0);
        $stmt = Database::connection()->prepare(
            'SELECT rd.* FROM record_documents rd
             WHERE rd.id = :id AND rd.tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['id' => $documentId, 'tenant_id' => tenantId()]);
        $doc = $stmt->fetch();
        if (!$doc) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }

        $this->stream((string) $doc['file_path'], (string) $doc['mime_type'], (string) $doc['original_name']);
    }

    private function stream(string $filePath, string $mimeType, string $downloadName): void
    {
        $absolute = DocumentStorage::absolutePath($filePath);
        if (!is_file($absolute)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }

        header('Content-Type: ' . ($mimeType !== '' ? $mimeType : 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"');
        header('Content-Length: ' . (string) filesize($absolute));
        readfile($absolute);
        exit;
    }
}
