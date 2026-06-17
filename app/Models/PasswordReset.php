<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PasswordReset
{
    public function createToken(int $userId, int $tenantId): string
    {
        $plain = bin2hex(random_bytes(32));
        $sql = 'INSERT INTO password_reset_tokens (tenant_id, user_id, token_hash, expires_at)
                VALUES (:tenant_id, :user_id, :hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'hash' => hash('sha256', $plain),
        ]);

        return $plain;
    }

    public function findValidUserId(string $plainToken, ?int $tenantId = null): ?int
    {
        $hash = hash('sha256', $plainToken);
        $sql = 'SELECT user_id FROM password_reset_tokens
             WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW()';
        $params = ['hash' => $hash];
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = :tenant_id';
            $params['tenant_id'] = $tenantId;
        }
        $sql .= ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $userId = $stmt->fetchColumn();

        return $userId !== false ? (int) $userId : null;
    }

    public function markUsed(string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);
        $stmt = Database::connection()->prepare(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = :hash'
        );
        $stmt->execute(['hash' => $hash]);
    }
}
