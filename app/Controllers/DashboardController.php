<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\DashboardStats;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        $stats = (new DashboardStats())->forRole((string) ($user['role'] ?? ''));
        View::render('dashboard/index', ['user' => $user, 'stats' => $stats]);
    }
}
