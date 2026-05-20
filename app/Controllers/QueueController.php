<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Tenant;

final class QueueController
{
    private ?string $cachedPanelToken = null;

    public function index(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $queue = (new Queue())->ticketsForManage();
        $patients = (new Patient())->all();
        $tenant = (new Tenant())->find(tenantId());
        View::render('queue/index', [
            'queue' => $queue,
            'patients' => $patients,
            'role' => Auth::user()['role'],
            'csrfToken' => csrfToken(),
            'clinicName' => (string) ($tenant['name'] ?? APP_NAME),
        ]);
    }

    public function data(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        jsonResponse([
            'ok' => true,
            'queue' => $this->serializeQueue((new Queue())->ticketsForManage()),
            'waiting_count' => (new Queue())->waitingCount(),
        ]);
    }

    public function generate(): void
    {
        Auth::requireRole(['admin', 'reception']);
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $room = trim((string) ($_POST['room'] ?? ''));
        $ticket = null;
        if ($patientId > 0) {
            $ticket = (new Queue())->generateToken($patientId, (int) Auth::user()['id'], $room !== '' ? $room : null);
            auditLog('queue.generate', 'Senha gerada para paciente ID ' . $patientId . ($room !== '' ? ' sala ' . $room : ''));
        }
        $ok = $ticket !== null;

        if (wantsJson()) {
            jsonResponse([
                'ok' => $ok,
                'message' => $ok ? 'Senha gerada com sucesso.' : 'Selecione um paciente.',
                'ticket' => $ticket ? $this->serializeTicket($ticket) : null,
                'queue' => $this->serializeQueue((new Queue())->ticketsForManage()),
                'waiting_count' => (new Queue())->waitingCount(),
            ], $ok ? 200 : 422);
        }

        flash($ok ? 'success' : 'error', $ok ? 'Senha gerada com sucesso.' : 'Selecione um paciente.');
        redirect('/?route=queue');
    }

    public function call(): void
    {
        Auth::requireRole(['admin', 'reception', 'nurse', 'doctor']);
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $room = trim((string) ($_POST['room'] ?? 'Triagem'));
        $ok = false;
        $called = null;

        if ($ticketId > 0) {
            $queueModel = new Queue();
            $queueModel->call($ticketId, $room, (int) Auth::user()['id']);
            auditLog('queue.call', 'Senha ID ' . $ticketId . ' chamada para ' . $room);
            $ok = true;
            $called = $queueModel->currentCalled();
        }

        if (wantsJson()) {
            jsonResponse([
                'ok' => $ok,
                'message' => $ok ? 'Paciente chamado.' : 'Senha inválida.',
                'called' => $called ? $this->serializeCalled($called) : null,
                'queue' => $this->serializeQueue((new Queue())->ticketsForManage()),
                'waiting_count' => (new Queue())->waitingCount(),
            ], $ok ? 200 : 422);
        }

        flash($ok ? 'success' : 'error', $ok ? 'Paciente chamado.' : 'Senha inválida.');
        redirect('/?route=queue');
    }

    public function done(): void
    {
        Auth::requireRole(['admin', 'nurse', 'doctor']);
        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $ok = false;
        if ($ticketId > 0) {
            (new Queue())->finish($ticketId);
            auditLog('queue.done', 'Senha ID ' . $ticketId . ' finalizada');
            $ok = true;
        }

        if (wantsJson()) {
            jsonResponse([
                'ok' => $ok,
                'message' => $ok ? 'Atendimento finalizado.' : 'Senha inválida.',
                'called' => $this->serializeCalled((new Queue())->currentCalled()),
                'queue' => $this->serializeQueue((new Queue())->ticketsForManage()),
                'waiting_count' => (new Queue())->waitingCount(),
            ], $ok ? 200 : 422);
        }

        flash($ok ? 'success' : 'error', $ok ? 'Atendimento finalizado.' : 'Senha inválida.');
        redirect('/?route=queue');
    }

    public function ticketPrint(): void
    {
        Auth::requireRole(['admin', 'reception']);
        $id = (int) ($_GET['id'] ?? 0);
        $ticket = $id > 0 ? (new Queue())->findTicket($id) : null;
        if ($ticket === null) {
            http_response_code(404);
            echo 'Senha não encontrada.';
            exit;
        }

        $tenant = (new Tenant())->find(tenantId());
        $clinicName = (string) ($tenant['name'] ?? APP_NAME);
        $ticketData = $this->serializeTicket($ticket);

        require __DIR__ . '/../Views/queue/ticket_print.php';
        exit;
    }

    public function panel(): void
    {
        if (!$this->authorizePanel()) {
            http_response_code(403);
            echo 'Acesso negado ao painel.';
            exit;
        }

        $tenant = (new Tenant())->find(tenantId());
        $slug = (string) ($tenant['slug'] ?? '');
        $payload = $this->buildPanelPayload();
        $useSse = APP_ENV === 'production' && (getenv('PANEL_USE_SSE') ?: '1') !== '0';

        View::render('queue/panel', [
            'waiting_count' => $payload['waiting_count'],
            'panelDataUrl' => $this->panelDataUrl($slug),
            'tenantSlug' => $slug,
            'panelInitialPayload' => $payload,
            'recentCalls' => $payload['recent'],
            'displayCalled' => $payload['called'],
            'panelUseSse' => $useSse,
            'panelPollMs' => $useSse ? 3000 : 4000,
        ]);
    }

    public function panelData(): void
    {
        if (!$this->authorizePanel()) {
            jsonResponse(['ok' => false, 'error' => 'Acesso negado.'], 403);
        }

        jsonResponse($this->buildPanelPayload());
    }

    public function panelStream(): void
    {
        if (!$this->authorizePanel()) {
            http_response_code(403);
            echo 'event: error' . "\ndata: denied\n\n";
            exit;
        }

        // SSE segura o php -S (1 worker): em local use só polling no painel.
        if (APP_ENV !== 'production') {
            http_response_code(204);
            exit;
        }

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $lastRevision = '';
        $iterations = 0;
        while ($iterations < 30) {
            $payload = $this->buildPanelPayload();
            $revision = (string) ($payload['revision'] ?? 'idle');

            if ($revision !== $lastRevision) {
                $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
                echo 'event: update' . "\n";
                echo 'data: ' . $payloadJson . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $lastRevision = $revision;
            }

            $iterations++;
            usleep(500000);
        }
        exit;
    }

    private function authorizePanel(): bool
    {
        $this->resolvePanelTenantFromRequest();

        if (Auth::check()) {
            return true;
        }

        $token = trim((string) ($_GET['token'] ?? ''));
        $expectedToken = $this->panelToken();

        return $token !== '' && hash_equals($expectedToken, $token);
    }

    private function resolvePanelTenantFromRequest(): void
    {
        $slug = trim((string) ($_GET['tenant'] ?? ''));
        if ($slug === '') {
            if (Auth::check() && isset($_SESSION['user']['tenant_id'])) {
                $_SESSION['tenant_context_id'] = (int) $_SESSION['user']['tenant_id'];
            }
            return;
        }

        $tenant = (new Tenant())->findBySlug($slug);
        if ($tenant) {
            $_SESSION['tenant_context_id'] = (int) $tenant['id'];
        }
    }

    private function panelDataUrl(string $tenantSlug): string
    {
        return $this->panelPublicUrl('queue.panel.data', $tenantSlug);
    }

    private function panelPublicUrl(string $route, string $tenantSlug): string
    {
        $query = [
            'route' => $route,
            'token' => $this->panelToken(),
        ];
        if ($tenantSlug !== '') {
            $query['tenant'] = $tenantSlug;
        }

        return APP_URL . '/?' . http_build_query($query);
    }

    /** @return array{ok:bool,called:?array,recent:list<array>,waiting_count:int,revision:string} */
    private function buildPanelPayload(): array
    {
        $snapshot = (new Queue())->panelSnapshot(10);
        $display = $snapshot['display'];
        $recent = $snapshot['recent'];

        return [
            'ok' => true,
            'called' => $display ? $this->serializeCalled($display) : null,
            'recent' => array_map(fn (array $row): array => $this->serializeRecent($row), $recent),
            'waiting_count' => (int) $snapshot['waiting_count'],
            'revision' => $this->panelRevision($display, $recent),
        ];
    }

    private function panelRevision(?array $display, array $recent): string
    {
        if ($display !== null) {
            return (int) $display['id'] . '-' . strtotime((string) ($display['called_at'] ?? 'now'));
        }
        if ($recent !== []) {
            $row = $recent[0];
            return (int) $row['id'] . '-' . strtotime((string) ($row['called_at'] ?? 'now'));
        }

        return 'idle';
    }

    /** @param array<string, mixed> $row */
    private function serializeRecent(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'ticket_number' => (string) $row['ticket_number'],
            'full_name' => (string) $row['full_name'],
            'room' => (string) ($row['room'] ?? ''),
            'called_at' => (string) ($row['called_at'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'time_label' => formatDateTimeBr((string) ($row['called_at'] ?? '')) ?: '',
        ];
    }

    private function panelToken(): string
    {
        if ($this->cachedPanelToken !== null) {
            return $this->cachedPanelToken;
        }

        $stmt = Database::connection()->prepare('SELECT `value` FROM app_settings WHERE `key` = "panel_access_token" AND tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['tenant_id' => tenantId()]);
        $token = (string) ($stmt->fetchColumn() ?: '');

        $this->cachedPanelToken = $token !== '' ? $token : PANEL_ACCESS_TOKEN;

        return $this->cachedPanelToken;
    }

    /** @param array<string, mixed> $ticket */
    private function serializeTicket(array $ticket): array
    {
        return [
            'id' => (int) $ticket['id'],
            'ticket_number' => (string) $ticket['ticket_number'],
            'full_name' => (string) $ticket['full_name'],
            'room' => (string) ($ticket['room'] ?? ''),
            'status' => (string) ($ticket['status'] ?? ''),
            'created_at' => (string) ($ticket['created_at'] ?? ''),
            'created_label' => formatDateTimeBr((string) ($ticket['created_at'] ?? '')),
        ];
    }

    /** @param list<array<string, mixed>> $queue */
    private function serializeQueue(array $queue): array
    {
        return array_map(fn (array $ticket): array => [
            'id' => (int) $ticket['id'],
            'ticket_number' => (string) $ticket['ticket_number'],
            'full_name' => (string) $ticket['full_name'],
            'status' => (string) $ticket['status'],
            'room' => (string) ($ticket['room'] ?? ''),
        ], $queue);
    }

    /** @param array<string, mixed> $called */
    private function serializeCalled(array $called): array
    {
        $live = (bool) ($called['panel_live'] ?? ((string) ($called['status'] ?? '') === 'called'));

        return [
            'id' => (int) $called['id'],
            'ticket_number' => (string) $called['ticket_number'],
            'full_name' => (string) $called['full_name'],
            'room' => (string) ($called['room'] ?? ''),
            'called_at' => (string) ($called['called_at'] ?? ''),
            'live' => $live,
            'revision' => (int) $called['id'] . '-' . strtotime((string) ($called['called_at'] ?? 'now')),
        ];
    }
}
