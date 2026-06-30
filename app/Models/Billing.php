<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Billing
{
    public function plans(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY monthly_price_cents ASC');
        return $stmt->fetchAll();
    }

    public function currentSubscription(int $tenantId, bool $activeOnly = true): ?array
    {
        $statusFilter = $activeOnly ? 'ts.status = "active"' : 'ts.status IN ("active", "past_due")';
        $sql = 'SELECT ts.*, p.code AS plan_code, p.name AS plan_name, p.max_users, p.max_patients, p.monthly_price_cents
                FROM tenant_subscriptions ts
                INNER JOIN plans p ON p.id = ts.plan_id
                WHERE ts.tenant_id = :tenant_id AND ' . $statusFilter . '
                ORDER BY ts.id DESC
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function invoices(int $tenantId): array
    {
        $sql = 'SELECT * FROM invoices WHERE tenant_id = :tenant_id ORDER BY due_date DESC, id DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    public function createInitialSubscription(int $tenantId): void
    {
        $plan = Database::connection()->query("SELECT id, monthly_price_cents FROM plans WHERE code = 'starter' LIMIT 1")->fetch();
        if (!$plan) {
            return;
        }

        $insert = Database::connection()->prepare('INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, started_at, next_billing_at)
                VALUES (:tenant_id, :plan_id, "active", NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))');
        $insert->execute(['tenant_id' => $tenantId, 'plan_id' => (int) $plan['id']]);

        $invoice = Database::connection()->prepare('INSERT INTO invoices (tenant_id, subscription_id, amount_cents, status, due_date)
                VALUES (:tenant_id, :subscription_id, :amount_cents, "open", DATE_ADD(CURDATE(), INTERVAL 30 DAY))');
        $invoice->execute([
            'tenant_id' => $tenantId,
            'subscription_id' => (int) Database::connection()->lastInsertId(),
            'amount_cents' => (int) $plan['monthly_price_cents'],
        ]);
    }

    public function changePlan(int $tenantId, int $planId): void
    {
        $stmt = Database::connection()->prepare('UPDATE tenant_subscriptions SET status = "cancelled" WHERE tenant_id = :tenant_id AND status = "active"');
        $stmt->execute(['tenant_id' => $tenantId]);

        $newSub = Database::connection()->prepare('INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, started_at, next_billing_at)
                VALUES (:tenant_id, :plan_id, "active", NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))');
        $newSub->execute(['tenant_id' => $tenantId, 'plan_id' => $planId]);
        $subscriptionId = (int) Database::connection()->lastInsertId();

        $plan = Database::connection()->prepare('SELECT monthly_price_cents FROM plans WHERE id = :id');
        $plan->execute(['id' => $planId]);
        $amount = (int) $plan->fetchColumn();

        $invoice = Database::connection()->prepare('INSERT INTO invoices (tenant_id, subscription_id, amount_cents, status, due_date)
                VALUES (:tenant_id, :subscription_id, :amount_cents, "open", DATE_ADD(CURDATE(), INTERVAL 30 DAY))');
        $invoice->execute(['tenant_id' => $tenantId, 'subscription_id' => $subscriptionId, 'amount_cents' => $amount]);
    }

    public function canCreateUser(int $tenantId): bool
    {
        $sub = $this->currentSubscription($tenantId);
        if (!$sub) {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM users WHERE tenant_id = :tenant_id AND is_active = 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
        $activeUsers = (int) $stmt->fetchColumn();

        return $activeUsers < (int) $sub['max_users'];
    }

    public function canCreatePatient(int $tenantId): bool
    {
        $sub = $this->currentSubscription($tenantId, true);
        if (!$sub) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM patients WHERE tenant_id = :tenant_id AND anonymized_at IS NULL'
        );
        $stmt->execute(['tenant_id' => $tenantId]);

        return (int) $stmt->fetchColumn() < (int) $sub['max_patients'];
    }

    public function isPastDue(int $tenantId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM tenant_subscriptions WHERE tenant_id = :tenant_id AND status = "past_due"'
        );
        $stmt->execute(['tenant_id' => $tenantId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function markInvoicePaid(int $invoiceId, ?string $stripeInvoiceId = null): void
    {
        $sql = 'UPDATE invoices SET status = "paid", paid_at = NOW(), stripe_invoice_id = COALESCE(:stripe_id, stripe_invoice_id)
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $invoiceId, 'stripe_id' => $stripeInvoiceId]);
    }

    public function markSubscriptionPastDue(int $tenantId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tenant_subscriptions SET status = "past_due" WHERE tenant_id = :tenant_id AND status = "active"'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
    }

    public function markSubscriptionActive(int $tenantId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tenant_subscriptions SET status = "active" WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
    }

    public function findOpenInvoiceByStripeId(string $stripeInvoiceId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM invoices WHERE stripe_invoice_id = :stripe_id LIMIT 1'
        );
        $stmt->execute(['stripe_id' => $stripeInvoiceId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function saveStripeIds(int $tenantId, string $customerId, string $subscriptionId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tenant_subscriptions
             SET stripe_customer_id = :customer_id, stripe_subscription_id = :subscription_id
             WHERE tenant_id = :tenant_id AND status IN ("active", "past_due")
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
        ]);
    }

    public function findTenantIdByStripeSubscription(string $subscriptionId): ?int
    {
        if ($subscriptionId === '') {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT tenant_id FROM tenant_subscriptions
             WHERE stripe_subscription_id = :subscription_id
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['subscription_id' => $subscriptionId]);
        $tenantId = $stmt->fetchColumn();

        return $tenantId !== false ? (int) $tenantId : null;
    }
}

