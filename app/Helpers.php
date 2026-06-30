<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function queueStatusBadgeClass(string $status): string
{
    return match ($status) {
        'waiting' => 'status-badge status-waiting',
        'called' => 'status-badge status-called',
        'done' => 'status-badge status-done',
        default => 'status-badge',
    };
}

/** HTTPS visto pelo cliente (inclui reverse proxy aaPanel/Nginx). */
function queueStatusLabel(string $status): string
{
    return match ($status) {
        'waiting' => 'Aguardando',
        'called' => 'Chamado',
        'done' => 'Finalizado',
        default => $status,
    };
}

function queueSuggestedCallRoom(?string $ticketRoom, string $defaultRoom): string
{
    $ticketRoom = trim((string) $ticketRoom);
    $kioskKinds = ['Prioritário', 'Agendado', 'Sem agendamento'];

    if ($ticketRoom === '' || in_array($ticketRoom, $kioskKinds, true)) {
        return $defaultRoom;
    }

    return $ticketRoom;
}

function queueDefaultCallRoom(string $role): string
{
    return match ($role) {
        'reception' => 'Recepção',
        'nurse' => 'Triagem 1',
        'doctor' => 'Consultorio 1',
        default => 'Triagem',
    };
}

function returnVisitStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'Pendente',
        'overdue' => 'Vencido',
        'scheduled' => 'Agendado',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
        default => $status,
    };
}

function returnVisitStatusBadgeClass(string $status): string
{
    return match ($status) {
        'pending' => 'status-badge status-waiting',
        'overdue' => 'status-badge status-overdue',
        'scheduled' => 'status-badge status-called',
        'completed' => 'status-badge status-done',
        'cancelled' => 'status-badge status-muted',
        default => 'status-badge',
    };
}

function panelDisplayName(string $fullName, bool $hideNames): string
{
    $name = trim($fullName);
    if ($hideNames && $name !== '') {
        return 'Paciente';
    }

    return $name;
}

function requestIsHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($proto === 'https') {
        return true;
    }

    $forwarded = (string) ($_SERVER['HTTP_FORWARDED'] ?? '');
    if ($forwarded !== '' && preg_match('/proto=https/i', $forwarded) === 1) {
        return true;
    }

    return false;
}

function redirect(string $path): void
{
    header('Location: ' . APP_URL . $path);
    exit;
}

function csrfToken(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrfInput(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $submitted = (string) ($_POST['_csrf_token'] ?? '');
    $token = (string) ($_SESSION['_csrf_token'] ?? '');

    if ($submitted === '' || $token === '' || !hash_equals($token, $submitted)) {
        http_response_code(419);
        echo 'Sessão expirada. Atualize a pagina e tente novamente.';
        exit;
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function auditLog(string $action, string $details = ''): void
{
    if (!isset($_SESSION['user']['id'])) {
        return;
    }

    try {
        $sql = 'INSERT INTO audit_logs (tenant_id, user_id, action, details, ip_address) VALUES (:tenant_id, :user_id, :action, :details, :ip_address)';
        $stmt = \App\Core\Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'user_id' => (int) $_SESSION['user']['id'],
            'action' => $action,
            'details' => $details,
            'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        ]);
    } catch (\Throwable $exception) {
        // Auditoria não deve interromper fluxo principal.
    }
}

function tenantId(): int
{
    if (isset($_SESSION['user']['tenant_id'])) {
        return (int) $_SESSION['user']['tenant_id'];
    }

    if (isset($_SESSION['tenant_context_id'])) {
        return (int) $_SESSION['tenant_context_id'];
    }

    return DEFAULT_TENANT_ID;
}

function roleLabel(string $role): string
{
    return match ($role) {
        'admin' => 'Administrador',
        'reception' => 'Recepção',
        'nurse' => 'Enfermeira',
        'doctor' => 'Médico',
        default => 'Usuário',
    };
}

function formatDateBr(?string $date): string
{
    if ($date === null || trim($date) === '') {
        return '-';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('d/m/Y', $timestamp);
}

function formatDateTimeBr(?string $dateTime): string
{
    if ($dateTime === null || trim($dateTime) === '') {
        return '-';
    }

    $timestamp = strtotime($dateTime);
    if ($timestamp === false) {
        return $dateTime;
    }

    return date('d/m/Y H:i', $timestamp);
}

function formatCep(?string $cep): string
{
    $digits = preg_replace('/\D+/', '', (string) $cep) ?? '';
    if (strlen($digits) !== 8) {
        return (string) $cep;
    }

    return substr($digits, 0, 5) . '-' . substr($digits, 5);
}

/** @return array{logradouro: string, bairro: string, localidade: string, uf: string, complemento: string}|null */
function lookupCepFromViaCep(string $cep): ?array
{
    $digits = preg_replace('/\D+/', '', $cep) ?? '';
    if (strlen($digits) !== 8) {
        return null;
    }

    $url = 'https://viacep.com.br/ws/' . $digits . '/json/';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'header' => "Accept: application/json\r\nUser-Agent: Clinix/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || !empty($data['erro'])) {
        return null;
    }

    return [
        'logradouro' => (string) ($data['logradouro'] ?? ''),
        'bairro' => (string) ($data['bairro'] ?? ''),
        'localidade' => (string) ($data['localidade'] ?? ''),
        'uf' => (string) ($data['uf'] ?? ''),
        'complemento' => (string) ($data['complemento'] ?? ''),
    ];
}

/** @return array{cep: ?string, address: ?string} */
function buildPatientAddressFromRequest(array $input): array
{
    $cep = preg_replace('/\D+/', '', (string) ($input['cep'] ?? ''));
    if (strlen($cep) !== 8) {
        $cep = '';
    }

    $street = trim((string) ($input['address_street'] ?? ''));
    $number = trim((string) ($input['address_number'] ?? ''));
    $complement = trim((string) ($input['address_complement'] ?? ''));
    $neighborhood = trim((string) ($input['address_neighborhood'] ?? ''));
    $city = trim((string) ($input['address_city'] ?? ''));
    $state = strtoupper(trim((string) ($input['address_state'] ?? '')));

    $line1 = implode(', ', array_filter([
        $street,
        $number !== '' ? 'nº ' . $number : '',
        $complement,
    ]));
    $cityState = $city;
    if ($city !== '' && $state !== '') {
        $cityState = $city . '/' . $state;
    } elseif ($state !== '') {
        $cityState = $state;
    }
    $line2 = implode(' - ', array_filter([$neighborhood, $cityState]));

    $address = trim(implode(' - ', array_filter([$line1, $line2])));

    return [
        'cep' => $cep !== '' ? $cep : null,
        'address' => $address !== '' ? $address : null,
    ];
}

/** @return array<string, string> */
function patientAddressFieldsFromRequest(array $input): array
{
    return [
        'address_street' => trim((string) ($input['address_street'] ?? '')),
        'address_number' => trim((string) ($input['address_number'] ?? '')),
        'address_complement' => trim((string) ($input['address_complement'] ?? '')),
        'address_neighborhood' => trim((string) ($input['address_neighborhood'] ?? '')),
        'address_city' => trim((string) ($input['address_city'] ?? '')),
        'address_state' => strtoupper(trim((string) ($input['address_state'] ?? ''))),
    ];
}

function wantsJson(): bool
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    if (str_contains($accept, 'application/json')) {
        return true;
    }

    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function cacheRemember(string $key, int $ttlSeconds, callable $resolver): mixed
{
    $dir = __DIR__ . '/../storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $safeKey = preg_replace('/[^a-z0-9_\-]/i', '_', $key) ?? 'cache';
    $file = $dir . '/' . $safeKey . '.phpcache';
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['expires_at']) && (int) $decoded['expires_at'] >= time()) {
                return $decoded['payload'] ?? null;
            }
        }
    }

    $payload = $resolver();
    $content = json_encode([
        'expires_at' => time() + $ttlSeconds,
        'payload' => $payload,
    ], JSON_UNESCAPED_UNICODE);
    if ($content !== false) {
        @file_put_contents($file, $content, LOCK_EX);
    }
    return $payload;
}

/**
 * Inicia download CSV limpo (sem warnings no output) com BOM para Excel.
 *
 * @return resource
 */
function csvBeginDownload(string $filename)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        exit;
    }

    fwrite($output, "\xEF\xBB\xBF");

    return $output;
}

/** @param resource $handle */
function csvWriteRow($handle, array $fields): void
{
    fputcsv($handle, $fields, ',', '"', '\\');
}

