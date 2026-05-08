<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Patient;
use App\Models\Record;

final class RecordController
{
    public function show(): void
    {
        Auth::requireRole(['admin', 'nurse', 'doctor']);
        $patientId = (int) ($_GET['patient_id'] ?? 0);
        $patient = (new Patient())->find($patientId);
        if (!$patient) {
            redirect('/?route=patients');
        }

        $recordModel = new Record();
        $timeline = $recordModel->timeline($patientId);
        $documentsByRecord = $recordModel->documentsByRecordIds(array_map(static fn (array $row): int => (int) $row['id'], $timeline));

        View::render('records/show', [
            'patient' => $patient,
            'timeline' => $timeline,
            'documentsByRecord' => $documentsByRecord,
            'role' => Auth::user()['role'],
        ]);
    }

    public function add(): void
    {
        Auth::requireRole(['admin', 'nurse', 'doctor']);
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $entryType = $_POST['entry_type'] ?? '';

        $allowed = in_array(Auth::user()['role'], ['doctor', 'admin'], true)
            ? ['consultation', 'diagnosis', 'prescription', 'medical_note']
            : ['triage'];

        $structuredData = null;
        if ($entryType === 'triage') {
            $structuredData = [
                'blood_pressure' => trim((string) ($_POST['blood_pressure'] ?? '')),
                'heart_rate' => trim((string) ($_POST['heart_rate'] ?? '')),
                'temperature' => trim((string) ($_POST['temperature'] ?? '')),
                'spo2' => trim((string) ($_POST['spo2'] ?? '')),
                'glucose' => trim((string) ($_POST['glucose'] ?? '')),
                'pain_scale' => trim((string) ($_POST['pain_scale'] ?? '')),
            ];
            $structuredData = array_filter($structuredData, static fn (string $value): bool => $value !== '');
            if ($content === '' && !empty($structuredData)) {
                $lines = [];
                foreach ($structuredData as $key => $value) {
                    $lines[] = $key . ': ' . $value;
                }
                $content = implode(PHP_EOL, $lines);
            }
        }

        if ($patientId > 0 && $content !== '' && in_array($entryType, $allowed, true)) {
            $recordModel = new Record();
            $recordId = $recordModel->addEntry($patientId, (int) Auth::user()['id'], $entryType, $content, $structuredData);
            $this->handleDocumentUpload($recordModel, $recordId);
            auditLog('record.add', 'Registro ' . $entryType . ' no paciente ID ' . $patientId);
            flash('success', 'Registro clinico salvo.');
        }

        redirect('/?route=record.show&patient_id=' . $patientId);
    }

    private function handleDocumentUpload(Record $recordModel, int $recordId): void
    {
        if (!isset($_FILES['document']) || (int) ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $file = $_FILES['document'];
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Falha ao anexar documento.');
            return;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? '');
        $fileSize = (int) ($file['size'] ?? 0);
        $mimeType = (string) mime_content_type($tmpName);

        $allowedMime = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (!in_array($mimeType, $allowedMime, true)) {
            flash('error', 'Formato de arquivo não permitido. Use PDF, JPG, PNG, DOC ou DOCX.');
            return;
        }

        if ($fileSize > 5 * 1024 * 1024) {
            flash('error', 'Arquivo excede o limite de 5MB.');
            return;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/records/' . tenantId();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            flash('error', 'Não foi possivel criar diretorio de anexos.');
            return;
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . strtolower($extension) : '');
        $targetPath = $uploadDir . '/' . $safeName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            flash('error', 'Falha ao salvar o documento anexado.');
            return;
        }

        $publicPath = 'uploads/records/' . tenantId() . '/' . $safeName;
        $recordModel->addDocument($recordId, $originalName, $safeName, $publicPath, $mimeType, $fileSize);
    }

    public function deleteDocument(): void
    {
        Auth::requireRole(['admin', 'nurse', 'doctor']);
        $documentId = (int) ($_POST['document_id'] ?? 0);
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        if ($documentId > 0 && $patientId > 0) {
            $doc = (new Record())->deleteDocument($documentId);
            if ($doc) {
                $path = __DIR__ . '/../../public/' . ltrim((string) $doc['file_path'], '/');
                if (is_file($path)) {
                    @unlink($path);
                }
                auditLog('record.document.delete', 'Anexo ' . $documentId . ' removido');
                flash('success', 'Anexo removido com sucesso.');
            }
        }
        redirect('/?route=record.show&patient_id=' . $patientId);
    }
}

