<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\User;

final class AppointmentController
{
    public function index(): void
    {
        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $status = trim((string) ($_GET['status'] ?? ''));
        $appointments = (new Appointment())->all($date, $status);
        View::render('appointments/index', [
            'appointments' => $appointments,
            'filters' => ['date' => $date, 'status' => $status],
        ]);
    }

    public function week(): void
    {
        $start = trim((string) ($_GET['start'] ?? date('Y-m-d')));
        $appointments = (new Appointment())->week($start);
        View::render('appointments/week', ['appointments' => $appointments, 'start' => $start]);
    }

    public function form(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $appointment = $id > 0 ? (new Appointment())->find($id) : null;
        $selectedPatientName = '';
        if ($appointment !== null && (int) ($appointment['patient_id'] ?? 0) > 0) {
            $patient = (new Patient())->find((int) $appointment['patient_id']);
            $selectedPatientName = (string) ($patient['full_name'] ?? '');
        }

        View::render('appointments/form', [
            'appointment' => $appointment,
            'selectedPatientName' => $selectedPatientName,
            'doctors' => (new User())->doctors(),
        ]);
    }

    public function save(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'patient_id' => (int) ($_POST['patient_id'] ?? 0),
            'professional_id' => ($_POST['professional_id'] ?? '') !== '' ? (int) $_POST['professional_id'] : null,
            'scheduled_at' => trim((string) ($_POST['scheduled_at'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'scheduled'),
            'reason' => trim((string) ($_POST['reason'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'created_by' => (int) Auth::user()['id'],
        ];

        $allowedStatus = ['scheduled', 'checked_in', 'in_progress', 'completed', 'cancelled'];
        if ($data['patient_id'] <= 0 || $data['scheduled_at'] === '' || !in_array($data['status'], $allowedStatus, true)) {
            flash('error', 'Preencha paciente, horario e status validos.');
            redirect('/?route=appointments');
            return;
        }

        $model = new Appointment();
        if ($model->hasConflict($data['professional_id'], $data['scheduled_at'], $id > 0 ? $id : null)) {
            flash('error', 'Conflito de horário: este profissional já tem consulta nesse período.');
            redirect('/?route=' . ($id > 0 ? 'appointment.form&id=' . $id : 'appointment.form'));
            return;
        }

        if ($id > 0) {
            $model->update($id, $data);
            auditLog('appointment.update', 'Agendamento ID ' . $id . ' atualizado');
            flash('success', 'Agendamento atualizado.');
        } else {
            $newId = $model->create($data);
            auditLog('appointment.create', 'Novo agendamento criado');
            $this->notifyAppointmentCreated($newId, $data['patient_id'], $data['scheduled_at'], $data['reason']);
            flash('success', 'Agendamento criado com sucesso.');
        }

        $this->scheduleReminder($data['patient_id'], $data['scheduled_at'], $data['reason']);
        redirect('/?route=appointments');
    }

    public function updateStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $allowedStatus = ['scheduled', 'checked_in', 'in_progress', 'completed', 'cancelled'];
        if ($id > 0 && in_array($status, $allowedStatus, true)) {
            (new Appointment())->updateStatus($id, $status);
            auditLog('appointment.status', 'Agendamento ID ' . $id . ' -> ' . $status);
            flash('success', 'Status do agendamento atualizado.');
        }
        redirect('/?route=appointments');
    }

    public function confirm(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $appointment = (new Appointment())->findByConfirmToken($token);
        if (!$appointment) {
            http_response_code(404);
            echo '<!doctype html><html lang="pt-BR"><body style="font-family:sans-serif;padding:40px;text-align:center;">';
            echo '<h1>Link inválido</h1><p>Confirmação expirada ou já utilizada.</p></body></html>';
            return;
        }

        $_SESSION['tenant_context_id'] = (int) $appointment['tenant_id'];
        (new Appointment())->updateStatus((int) $appointment['id'], 'checked_in');

        echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Confirmado</title></head>';
        echo '<body style="font-family:sans-serif;max-width:480px;margin:40px auto;text-align:center;">';
        echo '<h1 style="color:#166534;">Consulta confirmada</h1>';
        echo '<p>Obrigado. Sua presença foi registrada para <strong>' . e(formatDateTimeBr((string) $appointment['scheduled_at'])) . '</strong>.</p>';
        echo '<p style="color:#64748b;font-size:14px;">Clinix</p></body></html>';
    }

    private function notifyAppointmentCreated(int $appointmentId, int $patientId, string $scheduledAt, string $reason): void
    {
        $appointment = (new Appointment())->find($appointmentId);
        $patient = (new Patient())->find($patientId);
        if ($appointment === null || $patient === null) {
            return;
        }

        $confirmUrl = APP_URL . '/?route=appointment.confirm&token=' . urlencode((string) $appointment['confirm_token']);
        $when = formatDateTimeBr($scheduledAt);
        $body = "Olá " . ($patient['full_name'] ?? 'paciente') . ",\n\n"
            . "Sua consulta foi agendada para {$when}."
            . ($reason !== '' ? "\nMotivo: {$reason}" : '')
            . "\n\nConfirme sua presença pelo link:\n{$confirmUrl}\n\nClinix";

        $notifier = new Notification();
        $recipient = $this->patientNotificationRecipient($patient);
        $channel = $recipient['channel'];
        $address = $recipient['address'];

        if ($address !== '') {
            $notifier->schedule($channel, $address, 'Confirme sua consulta - Clinix', $body, date('Y-m-d H:i:s'));
        }
        $notifier->schedule('log', $address !== '' ? $address : 'sem-contato', 'Confirme sua consulta', $body, date('Y-m-d H:i:s'));
    }

    private function scheduleReminder(int $patientId, string $scheduledAt, string $reason): void
    {
        $patient = (new Patient())->find($patientId);
        if ($patient === null) {
            return;
        }

        $reminderAt = date('Y-m-d H:i:s', strtotime($scheduledAt . ' -1 day'));
        if (strtotime($reminderAt) <= time()) {
            return;
        }

        $body = 'Lembrete Clinix: consulta em ' . formatDateTimeBr($scheduledAt) . ($reason !== '' ? ' — ' . $reason : '');
        $notifier = new Notification();
        $recipient = $this->patientNotificationRecipient($patient);

        if ($recipient['address'] !== '') {
            $notifier->schedule($recipient['channel'], $recipient['address'], 'Lembrete de consulta', $body, $reminderAt);
        }
        $notifier->schedule('log', $recipient['address'] !== '' ? $recipient['address'] : 'sem-contato', 'Lembrete de consulta', $body, $reminderAt);
    }

    /** @return array{channel: string, address: string} */
    private function patientNotificationRecipient(array $patient): array
    {
        $email = trim((string) ($patient['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['channel' => 'email', 'address' => $email];
        }

        $phone = trim((string) ($patient['phone'] ?? ''));
        if ($phone !== '') {
            return ['channel' => 'log', 'address' => $phone];
        }

        return ['channel' => 'log', 'address' => ''];
    }
}
