<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Tenant
{
    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = 'SELECT * FROM tenants WHERE slug = :slug AND is_active = 1 LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, string $slug): int
    {
        $sql = 'INSERT INTO tenants (name, slug, is_active) VALUES (:name, :slug, 1)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        return (int) Database::connection()->lastInsertId();
    }

    public function seedDefaultSettings(int $tenantId): void
    {
        $sql = 'INSERT INTO app_settings (tenant_id, `key`, `value`) VALUES (:tenant_id, "panel_access_token", :token)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId, 'token' => bin2hex(random_bytes(12))]);
    }

    public function slugTakenByOtherTenant(string $slug, int $tenantId): bool
    {
        $sql = 'SELECT COUNT(*) FROM tenants WHERE slug = :slug AND id <> :tenant_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['slug' => $slug, 'tenant_id' => $tenantId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateSlug(int $tenantId, string $slug): void
    {
        $stmt = Database::connection()->prepare('UPDATE tenants SET slug = :slug WHERE id = :id');
        $stmt->execute(['slug' => $slug, 'id' => $tenantId]);
    }
}

