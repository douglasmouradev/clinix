<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** HTTPS visto pelo cliente (inclui reverse proxy aaPanel/Nginx). */
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

