<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class AuditController
{
    public function index(): void
    {
        $action = trim((string) ($_GET['action'] ?? ''));
        $sql = 'SELECT al.*, u.name AS user_name FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE al.tenant_id = :tenant_id';
        $params = ['tenant_id' => tenantId()];
        if ($action !== '') {
            $sql .= ' AND al.action LIKE :action';
            $params['action'] = '%' . $action . '%';
        }
        $sql .= ' ORDER BY al.created_at DESC LIMIT 200';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        View::render('dashboard/audit', ['logs' => $stmt->fetchAll(), 'action' => $action]);
    }
}
