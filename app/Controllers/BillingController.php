<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\StripeClient;
use App\Core\View;
use App\Models\Billing;

final class BillingController
{
    public function index(): void
    {
        $billing = new Billing();
        $checkout = $_GET['checkout'] ?? '';
        if ($checkout === 'success') {
            flash('success', 'Pagamento recebido. Obrigado!');
        } elseif ($checkout === 'cancel') {
            flash('info', 'Checkout cancelado.');
        }

        View::render('billing/index', [
            'plans' => $billing->plans(),
            'current' => $billing->currentSubscription(tenantId(), false),
            'invoices' => $billing->invoices(tenantId()),
            'stripeEnabled' => StripeClient::isConfigured(),
            'pastDue' => $billing->isPastDue(tenantId()),
        ]);
    }

    public function changePlan(): void
    {
        if (StripeClient::isConfigured()) {
            flash('error', 'Com Stripe ativo, altere o plano apenas pelo checkout de pagamento.');
            redirect('/?route=billing');
            return;
        }

        $planId = (int) ($_POST['plan_id'] ?? 0);
        if ($planId > 0) {
            (new Billing())->changePlan(tenantId(), $planId);
            auditLog('billing.plan.change', 'Plano alterado para ID ' . $planId);
            flash('success', 'Plano alterado com sucesso.');
        }
        redirect('/?route=billing');
    }

    public function checkout(): void
    {
        $planId = (int) ($_POST['plan_id'] ?? 0);
        $billing = new Billing();
        $plans = $billing->plans();
        $selected = null;
        foreach ($plans as $plan) {
            if ((int) $plan['id'] === $planId) {
                $selected = $plan;
                break;
            }
        }

        if ($selected === null) {
            flash('error', 'Plano inválido.');
            redirect('/?route=billing');
            return;
        }

        if (StripeClient::isConfigured()) {
            $session = StripeClient::createCheckoutSession(
                (int) $selected['monthly_price_cents'],
                (string) $selected['name'],
                tenantId(),
                $planId
            );
            if ($session !== null) {
                header('Location: ' . $session['url']);
                exit;
            }
            flash('error', 'Não foi possível iniciar o checkout Stripe.');
            redirect('/?route=billing');
            return;
        }

        $billing->changePlan(tenantId(), $planId);
        flash('info', 'Plano atualizado localmente (modo demo — configure STRIPE_SECRET_KEY).');
        redirect('/?route=billing');
    }

    public function webhook(): void
    {
        $secret = STRIPE_WEBHOOK_SECRET;
        if ($secret === '') {
            http_response_code(503);
            echo 'Webhook não configurado.';
            exit;
        }

        $payload = (string) file_get_contents('php://input');
        $signature = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
        if (!$this->verifyStripeSignature($payload, $signature, $secret)) {
            http_response_code(400);
            echo 'Assinatura inválida.';
            exit;
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            http_response_code(400);
            exit;
        }

        $billing = new Billing();
        $type = (string) ($event['type'] ?? '');

        if ($type === 'checkout.session.completed') {
            $object = $event['data']['object'] ?? [];
            $tenantId = (int) ($object['metadata']['tenant_id'] ?? $object['client_reference_id'] ?? 0);
            $planId = (int) ($object['metadata']['plan_id'] ?? 0);
            $customerId = (string) ($object['customer'] ?? '');
            $subscriptionId = (string) ($object['subscription'] ?? '');
            if ($tenantId > 0 && $planId > 0) {
                $billing->changePlan($tenantId, $planId);
                $billing->markSubscriptionActive($tenantId);
                if ($customerId !== '' && $subscriptionId !== '') {
                    $billing->saveStripeIds($tenantId, $customerId, $subscriptionId);
                }
            }
        }

        if ($type === 'invoice.paid') {
            $object = $event['data']['object'] ?? [];
            $stripeId = (string) ($object['id'] ?? '');
            $subscriptionId = (string) ($object['subscription'] ?? '');
            $tenantId = $billing->findTenantIdByStripeSubscription($subscriptionId);
            $invoice = $billing->findOpenInvoiceByStripeId($stripeId);
            if ($invoice) {
                $billing->markInvoicePaid((int) $invoice['id'], $stripeId);
                $billing->markSubscriptionActive((int) $invoice['tenant_id']);
            } elseif ($tenantId !== null) {
                $billing->markSubscriptionActive($tenantId);
            }
        }

        if ($type === 'invoice.payment_failed') {
            $object = $event['data']['object'] ?? [];
            $subscriptionId = (string) ($object['subscription'] ?? '');
            $tenantId = $billing->findTenantIdByStripeSubscription($subscriptionId);
            if ($tenantId !== null) {
                $billing->markSubscriptionPastDue($tenantId);
            }
        }

        if ($type === 'customer.subscription.deleted') {
            $object = $event['data']['object'] ?? [];
            $subscriptionId = (string) ($object['id'] ?? '');
            $tenantId = $billing->findTenantIdByStripeSubscription($subscriptionId);
            if ($tenantId !== null) {
                $billing->markSubscriptionPastDue($tenantId);
            }
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
        exit;
    }

    private function verifyStripeSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($signatureHeader === '' || !str_contains($signatureHeader, 'v1=')) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_map('trim', explode('=', $segment, 2) + ['', '']);
            $parts[$key] = $value;
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);

        return hash_equals($expected, $parts['v1']);
    }
}
