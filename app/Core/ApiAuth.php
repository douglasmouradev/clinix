<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\ApiToken;

final class ApiAuth
{
    /** @return array{tenant_id:int, scopes:list<string>}|null */
    public static function authorize(string $requiredScope = ''): ?array
    {
        $token = self::resolveToken();
        if ($token === '') {
            return null;
        }

        if (!ApiRateLimiter::allow(hash('sha256', $token))) {
            http_response_code(429);
            jsonResponse(['error' => 'Rate limit exceeded'], 429);
        }

        $hash = hash('sha256', $token);
        $stmt = Database::connection()->prepare(
            'SELECT tenant_id, scopes FROM api_tokens WHERE token_hash = :hash AND is_active = 1 LIMIT 1'
        );
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $scopes = array_filter(array_map('trim', explode(',', (string) ($row['scopes'] ?? ''))));
        if ($requiredScope !== '' && !in_array($requiredScope, $scopes, true)) {
            http_response_code(403);
            jsonResponse(['error' => 'Scope not allowed'], 403);
        }

        $_SESSION['tenant_context_id'] = (int) $row['tenant_id'];

        return [
            'tenant_id' => (int) $row['tenant_id'],
            'scopes' => $scopes,
        ];
    }

    public static function resolveToken(): string
    {
        $header = trim((string) ($_SERVER['HTTP_X_API_TOKEN'] ?? ''));
        if ($header !== '') {
            return $header;
        }

        $auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
