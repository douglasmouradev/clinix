<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function all(): array
    {
        $sql = 'SELECT id, name, username, role, is_active, created_at FROM users WHERE tenant_id = :tenant_id ORDER BY name ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT id, name, username, email, role, is_active, tenant_id FROM users WHERE id = :id AND tenant_id = :tenant_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT id, name, username, email, password_hash, role, is_active, tenant_id,
                       must_change_password, two_factor_enabled, two_factor_secret
                FROM users WHERE username = :username AND tenant_id = :tenant_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['username' => $username, 'tenant_id' => tenantId()]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findWithSecrets(int $id): ?array
    {
        $sql = 'SELECT id, name, username, password_hash, role, is_active, tenant_id,
                       must_change_password, two_factor_enabled, two_factor_secret
                FROM users WHERE id = :id AND tenant_id = :tenant_id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id, 'tenant_id' => tenantId()]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(array $data): void
    {
        $sql = 'INSERT INTO users (tenant_id, name, username, email, password_hash, role, is_active) VALUES (:tenant_id, :name, :username, :email, :password_hash, :role, :is_active)';
        $data['tenant_id'] = tenantId();
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $sql = 'UPDATE users SET name = :name, username = :username, email = :email, role = :role, is_active = :is_active WHERE id = :id AND tenant_id = :tenant_id';
        $data['tenant_id'] = tenantId();
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);
    }

    public function updatePassword(int $id, string $passwordHash, bool $mustChange = false): void
    {
        $sql = 'UPDATE users SET password_hash = :password_hash, must_change_password = :must_change WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
            'must_change' => $mustChange ? 1 : 0,
            'tenant_id' => tenantId(),
        ]);
    }

    public function setTwoFactor(int $id, ?string $secret, bool $enabled): void
    {
        $sql = 'UPDATE users SET two_factor_secret = :secret, two_factor_enabled = :enabled WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'secret' => $secret,
            'enabled' => $enabled ? 1 : 0,
            'tenant_id' => tenantId(),
        ]);
    }

    public function existsByUsername(string $username, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE username = :username AND tenant_id = :tenant_id';
        $params = ['username' => $username, 'tenant_id' => tenantId()];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function activeAdminCount(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND tenant_id = :tenant_id");
        $stmt->execute(['tenant_id' => tenantId()]);
        return (int) $stmt->fetchColumn();
    }

    public function doctors(): array
    {
        $sql = "SELECT id, name FROM users WHERE tenant_id = :tenant_id AND role = 'doctor' AND is_active = 1 ORDER BY name";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => tenantId()]);
        return $stmt->fetchAll();
    }
}

