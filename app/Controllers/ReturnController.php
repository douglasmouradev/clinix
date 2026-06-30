<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\ReturnVisit;
use App\Models\User;

final class ReturnController
{
    private function requireTable(): bool
    {
        if (ReturnVisit::tableExists()) {
            return true;
        }

        View::render('returns/setup_required');
        return false;
    }

    public function index(): void
    {
        if (!$this->requireTable()) {
            return;
        }

        $filter = trim((string) ($_GET['filter'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        $model = new ReturnVisit();
        View::render('returns/index', [
            'returns' => $model->list($filter !== '' ? $filter : null, $search, $from, $to),
            'filters' => [
                'filter' => $filter,
                'q' => $search,
                'from' => $from,
                'to' => $to,
            ],
            'counts' => [
                'overdue' => $model->countOverdue(),
                'pending' => $model->countPending(),
            ],
        ]);
    }

    public function form(): void
    {
        if (!$this->requireTable()) {
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $model = new ReturnVisit();
        $returnVisit = $id > 0 ? $model->find($id) : null;

        if ($returnVisit === null && $id > 0) {
            flash('error', 'Retorno não encontrado.');
            redirect('/?route=returns');
            return;
        }

        if ($returnVisit === null) {
            $patientId = (int) ($_GET['patient_id'] ?? 0);
            $sourceAppointmentId = (int) ($_GET['source_appointment_id'] ?? 0);
            $dueDays = max(1, (int) ($_GET['due_days'] ?? 30));

            $returnVisit = [
                'id' => 0,
                'patient_id' => $patientId,
                'professional_id' => null,
                'source_appointment_id' => $sourceAppointmentId > 0 ? $sourceAppointmentId : null,
                'return_due_date' => date('Y-m-d', strtotime('+' . $dueDays . ' days')),
                'reason' => 'Retorno',
                'notes' => '',
            ];

            if ($sourceAppointmentId > 0) {
                $appointment = (new Appointment())->find($sourceAppointmentId);
                if ($appointment !== null) {
                    $returnVisit['patient_id'] = (int) $appointment['patient_id'];
                    $returnVisit['professional_id'] = $appointment['professional_id'] ?? null;
                    if (!empty($appointment['reason'])) {
                        $returnVisit['reason'] = 'Retorno — ' . $appointment['reason'];
                    }
                }
            }
        }

        if ($returnVisit !== null && in_array($returnVisit['status'] ?? 'pending', ['completed', 'cancelled'], true)) {
            flash('error', 'Este retorno não pode mais ser editado.');
            redirect('/?route=returns');
            return;
        }

        View::render('returns/form', [
            'returnVisit' => $returnVisit,
            'selectedPatientName' => $this->patientNameForReturn($returnVisit),
            'doctors' => (new User())->doctors(),
        ]);
    }

    /** @param array<string, mixed> $returnVisit */
    private function patientNameForReturn(array $returnVisit): string
    {
        $patientId = (int) ($returnVisit['patient_id'] ?? 0);
        if ($patientId <= 0) {
            return '';
        }

        $patient = (new Patient())->find($patientId);

        return (string) ($patient['full_name'] ?? '');
    }

    public function save(): void
    {
        verifyCsrf();

        if (!$this->requireTable()) {
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'patient_id' => (int) ($_POST['patient_id'] ?? 0),
            'professional_id' => ($_POST['professional_id'] ?? '') !== '' ? (int) $_POST['professional_id'] : null,
            'source_appointment_id' => ($_POST['source_appointment_id'] ?? '') !== '' ? (int) $_POST['source_appointment_id'] : null,
            'return_due_date' => trim((string) ($_POST['return_due_date'] ?? '')),
            'reason' => trim((string) ($_POST['reason'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'created_by' => (int) Auth::user()['id'],
        ];

        if ($data['patient_id'] <= 0 || $data['return_due_date'] === '') {
            flash('error', 'Informe paciente e data prevista do retorno.');
            redirect('/?route=' . ($id > 0 ? 'return.form&id=' . $id : 'return.form'));
            return;
        }

        $model = new ReturnVisit();

        if ($id > 0) {
            $existing = $model->find($id);
            if ($existing === null) {
                flash('error', 'Retorno não encontrado.');
                redirect('/?route=returns');
                return;
            }

            if (in_array($existing['status'], ['completed', 'cancelled'], true)) {
                flash('error', 'Este retorno não pode mais ser editado.');
                redirect('/?route=returns');
                return;
            }

            $model->update($id, $data);
            auditLog('return.update', 'Retorno ID ' . $id . ' atualizado');
            flash('success', 'Retorno atualizado.');
        } else {
            $newId = $model->create($data);
            auditLog('return.create', 'Novo retorno ID ' . $newId);
            flash('success', 'Retorno registrado com sucesso.');
        }

        redirect('/?route=returns');
    }

    public function scheduleForm(): void
    {
        if (!$this->requireTable()) {
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $returnVisit = (new ReturnVisit())->find($id);

        if ($returnVisit === null) {
            flash('error', 'Retorno não encontrado.');
            redirect('/?route=returns');
            return;
        }

        if (($returnVisit['status'] ?? '') !== 'pending') {
            flash('error', 'Somente retornos pendentes podem ser agendados.');
            redirect('/?route=returns');
            return;
        }

        View::render('returns/schedule', ['returnVisit' => $returnVisit]);
    }

    public function schedule(): void
    {
        verifyCsrf();

        if (!$this->requireTable()) {
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $scheduledAt = trim((string) ($_POST['scheduled_at'] ?? ''));
        $model = new ReturnVisit();
        $returnVisit = $model->find($id);

        if ($returnVisit === null) {
            flash('error', 'Retorno não encontrado.');
            redirect('/?route=returns');
            return;
        }

        if (($returnVisit['status'] ?? '') !== 'pending') {
            flash('error', 'Somente retornos pendentes podem ser agendados.');
            redirect('/?route=returns');
            return;
        }

        if ($scheduledAt === '') {
            flash('error', 'Informe data e hora do agendamento.');
            redirect('/?route=return.schedule.form&id=' . $id);
            return;
        }

        $appointmentModel = new Appointment();
        $professionalId = $returnVisit['professional_id'] !== null ? (int) $returnVisit['professional_id'] : null;

        if ($appointmentModel->hasConflict($professionalId, $scheduledAt)) {
            flash('error', 'Conflito de horário: este profissional já tem consulta nesse período.');
            redirect('/?route=return.schedule.form&id=' . $id);
            return;
        }

        $reason = trim((string) ($returnVisit['reason'] ?? ''));
        if ($reason === '') {
            $reason = 'Retorno';
        }

        $appointmentModel->create([
            'patient_id' => (int) $returnVisit['patient_id'],
            'professional_id' => $professionalId,
            'scheduled_at' => $scheduledAt,
            'status' => 'scheduled',
            'reason' => $reason,
            'notes' => trim((string) ($returnVisit['notes'] ?? '')),
            'created_by' => (int) Auth::user()['id'],
        ]);

        $appointmentId = (int) \App\Core\Database::connection()->lastInsertId();
        $model->linkAppointment($id, $appointmentId);
        auditLog('return.schedule', 'Retorno ID ' . $id . ' agendado como consulta ID ' . $appointmentId);
        flash('success', 'Retorno agendado na agenda.');
        redirect('/?route=appointments&date=' . urlencode(date('Y-m-d', strtotime($scheduledAt))));
    }

    public function updateStatus(): void
    {
        verifyCsrf();

        if (!$this->requireTable()) {
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $allowed = ['completed', 'cancelled', 'pending'];

        if ($id <= 0 || !in_array($status, $allowed, true)) {
            flash('error', 'Ação inválida.');
            redirect('/?route=returns');
            return;
        }

        $model = new ReturnVisit();
        $returnVisit = $model->find($id);
        if ($returnVisit === null) {
            flash('error', 'Retorno não encontrado.');
            redirect('/?route=returns');
            return;
        }

        if ($status === 'pending' && ($returnVisit['status'] ?? '') !== 'cancelled') {
            flash('error', 'Somente retornos cancelados podem ser reabertos.');
            redirect('/?route=returns');
            return;
        }

        $model->updateStatus($id, $status);
        auditLog('return.status', 'Retorno ID ' . $id . ' -> ' . $status);
        flash('success', 'Status do retorno atualizado.');
        redirect('/?route=returns');
    }
}
