<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DocumentStorage;
use App\Core\View;
use App\Models\Patient;
use App\Models\Record;

final class RecordController
{
    public function show(): void
    {
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

    public function amend(): void
    {
        $recordId = (int) ($_POST['record_id'] ?? 0);
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $content = trim((string) ($_POST['content'] ?? ''));
        $reason = trim((string) ($_POST['change_reason'] ?? ''));

        if ($recordId > 0 && $content !== '' && $reason !== '') {
            (new Record())->amendEntry($recordId, $content, $reason, (int) Auth::user()['id']);
            auditLog('record.amend', 'Retificação do registro ID ' . $recordId);
            flash('success', 'Registro retificado com versionamento.');
        } else {
            flash('error', 'Informe conteúdo e motivo da retificação.');
        }

        redirect('/?route=record.show&patient_id=' . $patientId);
    }

    private function handleDocumentUpload(Record $recordModel, int $recordId): void
    {
        if (!isset($_FILES['document'])) {
            return;
        }

        $stored = DocumentStorage::storeUploaded($_FILES['document'], 'records');
        if ($stored === null) {
            flash('error', 'Falha ao anexar documento (formato ou tamanho inválido).');
            return;
        }

        $recordModel->addDocument(
            $recordId,
            $stored['original_name'],
            $stored['stored_name'],
            $stored['file_path'],
            $stored['mime_type'],
            $stored['file_size']
        );
    }

    public function deleteDocument(): void
    {
        $documentId = (int) ($_POST['document_id'] ?? 0);
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        if ($documentId > 0 && $patientId > 0) {
            $doc = (new Record())->deleteDocument($documentId);
            if ($doc) {
                DocumentStorage::delete((string) $doc['file_path']);
                auditLog('record.document.delete', 'Anexo ' . $documentId . ' removido');
                flash('success', 'Anexo removido com sucesso.');
            }
        }
        redirect('/?route=record.show&patient_id=' . $patientId);
    }
}
