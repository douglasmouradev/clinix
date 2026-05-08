<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;

final class AppointmentController
{
    public function index(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $status = trim((string) ($_GET['status'] ?? ''));
        $appointments = (new Appointment())->all($date, $status);
        View::render('appointments/index', ['appointments' => $appointments, 'filters' => ['date' => $date, 'status' => $status]]);
    }

    public function form(): void
    {
        Auth::requireRole(['admin', 'reception']);
        $id = (int) ($_GET['id'] ?? 0);
        $appointment = $id > 0 ? (new Appointment())->find($id) : null;

        View::render('appointments/form', [
            'appointment' => $appointment,
            'patients' => (new Patient())->all(),
            'doctors' => (new User())->doctors(),
        ]);
    }

    public function save(): void
    {
        Auth::requireRole(['admin', 'reception']);
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
        }

        $model = new Appointment();
        if ($id > 0) {
            $model->update($id, $data);
            auditLog('appointment.update', 'Agendamento ID ' . $id . ' atualizado');
            flash('success', 'Agendamento atualizado.');
        } else {
            $model->create($data);
            auditLog('appointment.create', 'Novo agendamento criado');
            flash('success', 'Agendamento criado com sucesso.');
        }

        redirect('/?route=appointments');
    }

    public function updateStatus(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
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
}

