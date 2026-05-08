CREATE TABLE IF NOT EXISTS plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    monthly_price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    max_users INT UNSIGNED NOT NULL DEFAULT 5,
    max_patients INT UNSIGNED NOT NULL DEFAULT 1000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tenant_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'cancelled', 'past_due') NOT NULL DEFAULT 'active',
    started_at DATETIME NOT NULL,
    next_billing_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subscriptions_tenant_status (tenant_id, status),
    CONSTRAINT fk_subscriptions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
);

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NOT NULL,
    amount_cents INT UNSIGNED NOT NULL,
    status ENUM('open', 'paid', 'cancelled') NOT NULL DEFAULT 'open',
    due_date DATE NOT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_invoices_tenant_due (tenant_id, due_date),
    CONSTRAINT fk_invoices_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_invoices_subscription FOREIGN KEY (subscription_id) REFERENCES tenant_subscriptions(id)
);

INSERT INTO plans (code, name, monthly_price_cents, max_users, max_patients) VALUES
('starter', 'Starter', 9900, 5, 3000),
('growth', 'Growth', 19900, 15, 12000),
('scale', 'Scale', 39900, 40, 50000)
ON DUPLICATE KEY UPDATE
name = VALUES(name),
monthly_price_cents = VALUES(monthly_price_cents),
max_users = VALUES(max_users),
max_patients = VALUES(max_patients),
is_active = 1;

INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, started_at, next_billing_at)
SELECT 1, p.id, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM plans p
WHERE p.code = 'starter'
  AND NOT EXISTS (
    SELECT 1 FROM tenant_subscriptions ts WHERE ts.tenant_id = 1 AND ts.status = 'active'
  )
LIMIT 1;

