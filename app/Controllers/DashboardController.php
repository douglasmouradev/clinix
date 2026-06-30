<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\DashboardStats;
use App\Models\Tenant;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        $stats = (new DashboardStats())->forRole((string) ($user['role'] ?? ''));
        $tenant = (new Tenant())->find((int) ($user['tenant_id'] ?? tenantId()));
        View::render('dashboard/index', [
            'user' => $user,
            'stats' => $stats,
            'tenant_slug' => (string) ($tenant['slug'] ?? ''),
        ]);
    }
}
