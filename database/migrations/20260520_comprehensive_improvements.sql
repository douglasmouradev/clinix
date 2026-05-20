ALTER TABLE users
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active,
    ADD COLUMN two_factor_secret VARCHAR(64) DEFAULT NULL AFTER must_change_password,
    ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER two_factor_secret;

ALTER TABLE tenant_subscriptions
    ADD COLUMN stripe_customer_id VARCHAR(80) DEFAULT NULL AFTER status,
    ADD COLUMN stripe_subscription_id VARCHAR(80) DEFAULT NULL AFTER stripe_customer_id;

ALTER TABLE invoices
    ADD COLUMN stripe_invoice_id VARCHAR(80) DEFAULT NULL AFTER status;

CREATE TABLE IF NOT EXISTS patient_record_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    record_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NOT NULL,
    entry_type VARCHAR(40) NOT NULL,
    content TEXT NOT NULL,
    structured_data JSON DEFAULT NULL,
    version_no INT UNSIGNED NOT NULL,
    change_reason VARCHAR(255) NOT NULL DEFAULT '',
    changed_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_record_versions_record (tenant_id, record_id, version_no),
    CONSTRAINT fk_record_versions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_record_versions_record FOREIGN KEY (record_id) REFERENCES patient_records(id),
    CONSTRAINT fk_record_versions_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_record_versions_user FOREIGN KEY (changed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notification_outbox (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('email', 'whatsapp', 'log') NOT NULL DEFAULT 'log',
    recipient VARCHAR(180) NOT NULL,
    subject VARCHAR(255) NOT NULL DEFAULT '',
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_pending (status, scheduled_at),
    CONSTRAINT fk_notification_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_api_tokens_hash (token_hash),
    CONSTRAINT fk_api_tokens_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

INSERT INTO app_settings (tenant_id, `key`, `value`)
SELECT 1, 'retention_last_run_at', ''
WHERE NOT EXISTS (
    SELECT 1 FROM app_settings WHERE tenant_id = 1 AND `key` = 'retention_last_run_at'
);

UPDATE users
SET password_hash = '$2y$12$DOxraFPtVVhRDHaisHi7tOVVksh8VDS42dfJy44gbCTVv4Iq7mfB2',
    must_change_password = 1
WHERE tenant_id = 1;

ALTER TABLE appointments
    ADD COLUMN confirm_token VARCHAR(64) DEFAULT NULL AFTER notes,
    ADD UNIQUE KEY ux_appointments_confirm_token (confirm_token);
