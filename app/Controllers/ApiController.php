<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Models\Patient;
use App\Models\Queue;

final class ApiController
{
    public function patients(): void
    {
        if (!$this->authorize()) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        jsonResponse([
            'data' => (new Patient())->all($search !== '' ? $search : null),
        ]);
    }

    public function queue(): void
    {
        if (!$this->authorize()) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        jsonResponse([
            'waiting' => (new Queue())->ticketsForManage(),
            'called' => (new Queue())->currentCalled(),
        ]);
    }

    private function authorize(): bool
    {
        $token = $this->resolveApiToken();
        if ($token === '') {
            return false;
        }

        $hash = hash('sha256', $token);
        $stmt = Database::connection()->prepare(
            'SELECT tenant_id FROM api_tokens WHERE token_hash = :hash AND is_active = 1 LIMIT 1'
        );
        $stmt->execute(['hash' => $hash]);
        $tenantId = $stmt->fetchColumn();
        if (!$tenantId) {
            return false;
        }

        $_SESSION['tenant_context_id'] = (int) $tenantId;
        return true;
    }

    private function resolveApiToken(): string
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
