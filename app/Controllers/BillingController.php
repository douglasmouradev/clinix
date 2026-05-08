<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Billing;

final class BillingController
{
    public function index(): void
    {
        Auth::requireRole(['admin']);
        $billing = new Billing();
        View::render('billing/index', [
            'plans' => $billing->plans(),
            'current' => $billing->currentSubscription(tenantId()),
            'invoices' => $billing->invoices(tenantId()),
        ]);
    }

    public function changePlan(): void
    {
        Auth::requireRole(['admin']);
        $planId = (int) ($_POST['plan_id'] ?? 0);
        if ($planId > 0) {
            (new Billing())->changePlan(tenantId(), $planId);
            auditLog('billing.plan.change', 'Plano alterado para ID ' . $planId);
            flash('success', 'Plano alterado com sucesso.');
        }
        redirect('/?route=billing');
    }
}

