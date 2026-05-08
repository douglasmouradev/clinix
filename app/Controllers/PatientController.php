<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Lgpd;
use App\Models\Patient;
use App\Models\Record;

final class PatientController
{
    public function index(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $search = trim((string) ($_GET['q'] ?? ''));
        $patients = (new Patient())->all($search);
        View::render('patients/index', ['patients' => $patients, 'search' => $search]);
    }

    public function form(): void
    {
        Auth::requireRole(['admin', 'reception']);
        $id = (int) ($_GET['id'] ?? 0);
        $patientModel = new Patient();
        $patient = $id > 0 ? $patientModel->find($id) : null;
        $documents = $id > 0 ? $patientModel->documentsByPatientId($id) : [];
        View::render('patients/form', ['patient' => $patient, 'documents' => $documents]);
    }

    public function save(): void
    {
        Auth::requireRole(['admin', 'reception']);

        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'cpf' => preg_replace('/\D+/', '', $_POST['cpf'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? '',
            'sex' => $_POST['sex'] ?? '',
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'medical_history' => trim($_POST['medical_history'] ?? ''),
            'lgpd_consent_at' => !empty($_POST['lgpd_consent']) ? date('Y-m-d H:i:s') : null,
            'lgpd_consent_version' => !empty($_POST['lgpd_consent']) ? 'v1.0' : null,
        ];

        if ($data['full_name'] === '' || strlen($data['cpf']) !== 11) {
            View::render('patients/form', ['patient' => array_merge(['id' => $id], $data), 'error' => 'Nome e CPF válido sao obrigatorios.']);
            return;
        }

        $patientModel = new Patient();
        $existing = $id > 0 ? $patientModel->find($id) : null;
        if ($id > 0) {
            $patientModel->update($id, $data);
            $this->handlePatientDocumentUpload($patientModel, $id);
            if (!empty($_POST['lgpd_consent']) && empty($existing['lgpd_consent_at'])) {
                (new Lgpd())->logConsent($id, LGPD_TERM_VERSION, (int) (Auth::user()['id'] ?? 0));
            }
            auditLog('patient.update', 'Paciente ID ' . $id . ' atualizado');
            flash('success', 'Paciente atualizado com sucesso.');
            redirect('/?route=patient.history&id=' . $id);
        } else {
            $newId = $patientModel->create($data);
            $this->handlePatientDocumentUpload($patientModel, $newId);
            if (!empty($_POST['lgpd_consent'])) {
                (new Lgpd())->logConsent($newId, LGPD_TERM_VERSION, (int) (Auth::user()['id'] ?? 0));
            }
            auditLog('patient.create', 'Paciente ID ' . $newId . ' criado');
            flash('success', 'Paciente cadastrado com sucesso.');
            redirect('/?route=patient.history&id=' . $newId);
        }
    }

    public function history(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);

        $patientId = (int) ($_GET['id'] ?? 0);
        $patient = (new Patient())->find($patientId);

        if (!$patient) {
            redirect('/?route=patients');
        }

        $role = Auth::user()['role'];
        $filters = [
            'entry_type' => trim((string) ($_GET['entry_type'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
        $timeline = (new Record())->timeline($patientId, $filters);
        $documentsByRecord = (new Record())->documentsByRecordIds(array_map(static fn (array $row): int => (int) $row['id'], $timeline));
        $lgpdRequests = (new Lgpd())->requests($patientId);
        $consents = (new Lgpd())->consents($patientId);

        View::render('patients/history', [
            'patient' => $patient,
            'timeline' => $timeline,
            'documentsByRecord' => $documentsByRecord,
            'canViewClinicalContent' => in_array($role, ['admin', 'nurse', 'doctor'], true),
            'role' => $role,
            'filters' => $filters,
            'lgpdRequests' => $lgpdRequests,
            'consents' => $consents,
        ]);
    }

    public function historyReport(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);

        $patientId = (int) ($_GET['id'] ?? 0);
        $patient = (new Patient())->find($patientId);
        if (!$patient) {
            redirect('/?route=patients');
        }

        $role = Auth::user()['role'];
        $filters = [
            'entry_type' => trim((string) ($_GET['entry_type'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];
        $timeline = (new Record())->timeline($patientId, $filters);
        $canViewClinicalContent = in_array($role, ['admin', 'nurse', 'doctor'], true);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="historico_paciente_' . $patientId . '.csv"');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fputcsv($output, ['Paciente', $patient['full_name']]);
        fputcsv($output, ['CPF', $patient['cpf']]);
        fputcsv($output, ['Nascimento', $patient['birth_date']]);
        fputcsv($output, []);
        fputcsv($output, ['Tipo', 'Profissional', 'Perfil', 'Data', 'Conteudo']);

        foreach ($timeline as $item) {
            fputcsv($output, [
                $item['entry_type'],
                $item['professional_name'],
                roleLabel($item['role']),
                formatDateTimeBr($item['created_at']),
                $canViewClinicalContent ? $item['content'] : 'Conteudo clinico restrito para este perfil.',
            ]);
        }

        fclose($output);
        exit;
    }

    private function handlePatientDocumentUpload(Patient $patientModel, int $patientId): void
    {
        if (!isset($_FILES['document']) || (int) ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $file = $_FILES['document'];
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Falha ao anexar documento do paciente.');
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
            flash('error', 'Formato não permitido para anexo do paciente.');
            return;
        }

        if ($fileSize > 5 * 1024 * 1024) {
            flash('error', 'Anexo do paciente excede o limite de 5MB.');
            return;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/patients/' . tenantId();
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            flash('error', 'Não foi possivel criar diretorio de anexos do paciente.');
            return;
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . strtolower($extension) : '');
        $targetPath = $uploadDir . '/' . $safeName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            flash('error', 'Falha ao salvar anexo do paciente.');
            return;
        }

        $publicPath = 'uploads/patients/' . tenantId() . '/' . $safeName;
        $patientModel->addDocument($patientId, $originalName, $safeName, $publicPath, $mimeType, $fileSize);
    }

    public function deleteDocument(): void
    {
        Auth::requireRole(['admin', 'reception']);
        $documentId = (int) ($_POST['document_id'] ?? 0);
        $patientId = (int) ($_POST['patient_id'] ?? 0);

        if ($documentId > 0 && $patientId > 0) {
            $doc = (new Patient())->deleteDocument($documentId);
            if ($doc) {
                $path = __DIR__ . '/../../public/' . ltrim((string) $doc['file_path'], '/');
                if (is_file($path)) {
                    @unlink($path);
                }
                auditLog('patient.document.delete', 'Documento de paciente ID ' . $documentId . ' removido');
                flash('success', 'Documento removido.');
            }
        }

        redirect('/?route=patient.form&id=' . $patientId);
    }

    public function exportLgpd(): void
    {
        Auth::requireRole(['admin']);
        $patientId = (int) ($_GET['id'] ?? 0);
        $patient = (new Patient())->find($patientId);
        if (!$patient) {
            redirect('/?route=patients');
        }

        $timeline = (new Record())->timeline($patientId);
        (new Lgpd())->logRequest($patientId, (int) (Auth::user()['id'] ?? 0), 'export', 'Exportacao LGPD');
        auditLog('lgpd.export', 'Exportacao de dados do paciente ID ' . $patientId);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="lgpd_paciente_' . $patientId . '.json"');
        echo json_encode(['patient' => $patient, 'records' => $timeline], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function anonymizeLgpd(): void
    {
        Auth::requireRole(['admin']);
        $patientId = (int) ($_POST['id'] ?? 0);
        if ($patientId > 0) {
            (new Patient())->anonymize($patientId);
            (new Lgpd())->logRequest($patientId, (int) (Auth::user()['id'] ?? 0), 'anonymize', 'Anonimização LGPD');
            auditLog('lgpd.anonymize', 'Anonimização de dados do paciente ID ' . $patientId);
            flash('success', 'Paciente anonimizado conforme LGPD.');
        }
        redirect('/?route=patients');
    }
}

