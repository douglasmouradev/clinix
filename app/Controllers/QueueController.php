<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Patient;
use App\Models\Queue;

final class QueueController
{
    public function index(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $queue = (new Queue())->waiting();
        $patients = (new Patient())->all();
        View::render('queue/index', ['queue' => $queue, 'patients' => $patients, 'role' => Auth::user()['role']]);
    }

    public function generate(): void
    {
        Auth::requireRole(['admin', 'reception']);
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $room = trim((string) ($_POST['room'] ?? ''));
        if ($patientId > 0) {
            (new Queue())->generateToken($patientId, (int) Auth::user()['id'], $room !== '' ? $room : null);
            auditLog('queue.generate', 'Senha gerada para paciente ID ' . $patientId . ($room !== '' ? ' sala ' . $room : ''));
            flash('success', 'Senha gerada com sucesso.');
        }
        redirect('/?route=queue');
    }

    public function call(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $room = trim($_POST['room'] ?? 'Triagem');
        if ($ticketId > 0) {
            (new Queue())->call($ticketId, $room, (int) Auth::user()['id']);
            auditLog('queue.call', 'Senha ID ' . $ticketId . ' chamada para ' . $room);
            flash('success', 'Paciente chamado.');
        }
        redirect('/?route=queue');
    }

    public function done(): void
    {
        Auth::requireRole(['admin', 'nurse', 'doctor']);
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        if ($ticketId > 0) {
            (new Queue())->finish($ticketId);
            auditLog('queue.done', 'Senha ID ' . $ticketId . ' finalizada');
            flash('success', 'Atendimento finalizado.');
        }
        redirect('/?route=queue');
    }

    public function panel(): void
    {
        if (!Auth::check()) {
            $token = trim((string) ($_GET['token'] ?? ''));
            $expectedToken = $this->panelToken();
            if ($token === '' || !hash_equals($expectedToken, $token)) {
                http_response_code(403);
                echo 'Acesso negado ao painel.';
                exit;
            }
        }

        $queue = (new Queue())->waiting();
        View::render('queue/panel', ['queue' => $queue]);
    }

    private function panelToken(): string
    {
        $stmt = Database::connection()->prepare('SELECT `value` FROM app_settings WHERE `key` = "panel_access_token" AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['tenant_id' => tenantId()]);
        $token = (string) ($stmt->fetchColumn() ?: '');
        return $token !== '' ? $token : PANEL_ACCESS_TOKEN;
    }
}

