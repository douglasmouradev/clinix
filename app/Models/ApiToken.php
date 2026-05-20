<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ApiToken
{
    public function all(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, is_active, created_at FROM api_tokens WHERE tenant_id = :tenant_id ORDER BY id DESC'
        );
        $stmt->execute(['tenant_id' => tenantId()]);
        return $stmt->fetchAll();
    }

    public function create(string $name): string
    {
        $plain = bin2hex(random_bytes(24));
        $sql = 'INSERT INTO api_tokens (tenant_id, name, token_hash, is_active) VALUES (:tenant_id, :name, :hash, 1)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => tenantId(),
            'name' => $name,
            'hash' => hash('sha256', $plain),
        ]);

        return $plain;
    }

    public function revoke(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE api_tokens SET is_active = 0 WHERE id = :id AND tenant_id = :tenant_id'
        );
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
    }
}
