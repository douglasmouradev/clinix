<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CpfValidator;
use App\Core\DocumentStorage;
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
        $page = max(1, (int) ($_GET['page'] ?? 1));
        try {
            $result = (new Patient())->paginate($search !== '' ? $search : null, $page, 25);
        } catch (\Throwable) {
            flash('error', 'Não foi possível carregar a lista de pacientes. Verifique se as migrations foram aplicadas.');
            $result = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 25];
        }
        View::render('patients/index', [
            'patients' => $result['items'],
            'search' => $search,
            'pagination' => $result,
        ]);
    }

    public function search(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $search = trim((string) ($_GET['q'] ?? ''));
        try {
            $data = (new Patient())->search($search !== '' ? $search : null, 20);
        } catch (\Throwable $exception) {
            jsonResponse(['ok' => false, 'error' => 'Não foi possível buscar pacientes.', 'data' => []], 500);
        }

        jsonResponse(['ok' => true, 'data' => $data]);
    }

    public function cepLookup(): void
    {
        Auth::requireRole(['admin', 'reception']);

        $cep = preg_replace('/\D+/', '', (string) ($_GET['cep'] ?? '')) ?? '';
        if (strlen($cep) !== 8) {
            jsonResponse(['ok' => false, 'error' => 'CEP inválido.'], 400);
        }

        $data = lookupCepFromViaCep($cep);
        if ($data === null) {
            jsonResponse(['ok' => false, 'error' => 'CEP não encontrado.'], 404);
        }

        jsonResponse(['ok' => true, 'data' => $data]);
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
        $addressData = buildPatientAddressFromRequest($_POST);
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'cpf' => preg_replace('/\D+/', '', $_POST['cpf'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? '',
            'sex' => $_POST['sex'] ?? '',
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? '') ?: null,
            'cep' => $addressData['cep'],
            'address' => $addressData['address'],
            'medical_history' => trim($_POST['medical_history'] ?? ''),
            'lgpd_consent_at' => !empty($_POST['lgpd_consent']) ? date('Y-m-d H:i:s') : null,
            'lgpd_consent_version' => !empty($_POST['lgpd_consent']) ? 'v1.0' : null,
        ];

        $data['cpf'] = CpfValidator::normalize($data['cpf']);
        if ($data['full_name'] === '' || !CpfValidator::isValid($data['cpf'])) {
            View::render('patients/form', [
                'patient' => array_merge(['id' => $id], $data, patientAddressFieldsFromRequest($_POST)),
                'error' => 'Nome e CPF válido são obrigatórios.',
            ]);
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
            if (!(new \App\Models\Billing())->canCreatePatient(tenantId())) {
                View::render('patients/form', [
                    'patient' => array_merge(['id' => 0], $data, patientAddressFieldsFromRequest($_POST)),
                    'error' => 'Limite de pacientes do plano atingido ou assinatura irregular. Verifique Billing.',
                ]);
                return;
            }
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

        $output = csvBeginDownload('historico_paciente_' . $patientId . '.csv');

        csvWriteRow($output, ['Paciente', (string) $patient['full_name']]);
        csvWriteRow($output, ['CPF', (string) ($patient['cpf'] ?? '')]);
        csvWriteRow($output, ['Nascimento', (string) ($patient['birth_date'] ?? '')]);
        csvWriteRow($output, []);
        csvWriteRow($output, ['Tipo', 'Profissional', 'Perfil', 'Data', 'Conteudo']);

        foreach ($timeline as $item) {
            csvWriteRow($output, [
                (string) $item['entry_type'],
                (string) $item['professional_name'],
                roleLabel((string) $item['role']),
                formatDateTimeBr((string) $item['created_at']),
                $canViewClinicalContent ? (string) $item['content'] : 'Conteudo clinico restrito para este perfil.',
            ]);
        }

        fclose($output);
        exit;
    }

    private function handlePatientDocumentUpload(Patient $patientModel, int $patientId): void
    {
        if (!isset($_FILES['document'])) {
            return;
        }

        $stored = DocumentStorage::storeUploaded($_FILES['document'], 'patients');
        if ($stored === null) {
            flash('error', 'Falha ao anexar documento do paciente (formato ou tamanho inválido).');
            return;
        }

        $patientModel->addDocument(
            $patientId,
            $stored['original_name'],
            $stored['stored_name'],
            $stored['file_path'],
            $stored['mime_type'],
            $stored['file_size']
        );
    }

    public function deleteDocument(): void
    {
        Auth::requireRole(['admin', 'reception']);
        $documentId = (int) ($_POST['document_id'] ?? 0);
        $patientId = (int) ($_POST['patient_id'] ?? 0);

        if ($documentId > 0 && $patientId > 0) {
            $doc = (new Patient())->deleteDocument($documentId);
            if ($doc) {
                DocumentStorage::delete((string) $doc['file_path']);
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
            (new Patient())->anonymizeCascade($patientId);
            (new Lgpd())->logRequest($patientId, (int) (Auth::user()['id'] ?? 0), 'anonymize', 'Anonimização LGPD');
            auditLog('lgpd.anonymize', 'Anonimização de dados do paciente ID ' . $patientId);
            flash('success', 'Paciente anonimizado conforme LGPD.');
        }
        redirect('/?route=patients');
    }
}

