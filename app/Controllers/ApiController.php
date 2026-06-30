<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiAuth;
use App\Core\CpfValidator;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\ReturnVisit;

final class ApiController
{
    public function patients(): void
    {
        if (ApiAuth::authorize('patients') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        jsonResponse([
            'data' => (new Patient())->search($search !== '' ? $search : null, 100),
        ]);
    }

    public function patientsCreate(): void
    {
        if (ApiAuth::authorize('patients') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            jsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        $cpf = CpfValidator::normalize((string) ($payload['cpf'] ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $birthDate = trim((string) ($payload['birth_date'] ?? ''));
        $sex = trim((string) ($payload['sex'] ?? 'Outro'));

        if ($fullName === '' || !CpfValidator::isValid($cpf) || $birthDate === '') {
            jsonResponse(['error' => 'full_name, cpf and birth_date are required'], 422);
        }

        if (!(new \App\Models\Billing())->canCreatePatient(tenantId())) {
            jsonResponse(['error' => 'Patient limit reached'], 403);
        }

        $patientModel = new Patient();
        if ($patientModel->findByCpf($cpf) !== null) {
            jsonResponse(['error' => 'CPF already registered'], 409);
        }

        $id = $patientModel->create([
            'full_name' => $fullName,
            'cpf' => $cpf,
            'birth_date' => $birthDate,
            'sex' => $sex !== '' ? $sex : 'Outro',
            'phone' => trim((string) ($payload['phone'] ?? '')) ?: null,
            'email' => trim((string) ($payload['email'] ?? '')) ?: null,
            'cep' => preg_replace('/\D+/', '', (string) ($payload['cep'] ?? '')) ?: null,
            'address' => trim((string) ($payload['address'] ?? '')) ?: null,
            'medical_history' => trim((string) ($payload['medical_history'] ?? '')) ?: null,
            'lgpd_consent_at' => null,
            'lgpd_consent_version' => null,
        ]);

        jsonResponse(['ok' => true, 'id' => $id], 201);
    }

    public function queue(): void
    {
        if (ApiAuth::authorize('queue') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        jsonResponse([
            'waiting' => (new Queue())->ticketsForManage(),
            'called' => (new Queue())->currentCalled(),
        ]);
    }

    public function appointments(): void
    {
        if (ApiAuth::authorize('appointments') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $status = trim((string) ($_GET['status'] ?? ''));
        jsonResponse([
            'data' => (new Appointment())->all($date, $status !== '' ? $status : null),
        ]);
    }

    public function appointmentsStatus(): void
    {
        if (ApiAuth::authorize('appointments') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            jsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        $id = (int) ($payload['id'] ?? 0);
        $status = (string) ($payload['status'] ?? '');
        $allowed = ['scheduled', 'checked_in', 'in_progress', 'completed', 'cancelled'];
        if ($id <= 0 || !in_array($status, $allowed, true)) {
            jsonResponse(['error' => 'id and valid status are required'], 422);
        }

        $appointment = (new Appointment())->find($id);
        if ($appointment === null) {
            jsonResponse(['error' => 'Appointment not found'], 404);
        }

        (new Appointment())->updateStatus($id, $status);
        jsonResponse(['ok' => true, 'id' => $id, 'status' => $status]);
    }

    public function returns(): void
    {
        if (ApiAuth::authorize('returns') === null) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $filter = trim((string) ($_GET['filter'] ?? ''));
        jsonResponse([
            'data' => (new ReturnVisit())->list($filter !== '' ? $filter : null),
        ]);
    }
}
