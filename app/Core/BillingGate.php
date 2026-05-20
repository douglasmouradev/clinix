<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Billing;

final class BillingGate
{
    /** @var list<string> */
    private const EXEMPT_ROUTES = [
        'login', 'login.submit', 'logout', 'onboarding', 'onboarding.submit',
        'password.change', 'password.change.submit', 'password.forgot', 'password.forgot.submit',
        'password.reset', 'password.reset.submit',
        'billing', 'billing.plan.change', 'billing.checkout', 'billing.webhook',
    ];

    public static function assertActiveSubscription(string $route): void
    {
        if (in_array($route, self::EXEMPT_ROUTES, true) || !Auth::check()) {
            return;
        }

        if ((new Billing())->isPastDue(tenantId())) {
            flash('error', 'Assinatura em atraso. Regularize o pagamento em Billing para continuar.');
            redirect('/?route=billing');
        }
    }
}
