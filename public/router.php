<?php

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if (str_starts_with($uri, '/uploads/')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Acesso negado. Use o sistema autenticado para baixar anexos.';
    return true;
}

if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';

return true;
