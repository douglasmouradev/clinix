CREATE TABLE IF NOT EXISTS tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tenants (id, name, slug, is_active)
VALUES (1, 'Clínica Demo', 'clínica-demo', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

ALTER TABLE users
    ADD COLUMN tenant_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id;

ALTER TABLE patients
    ADD COLUMN tenant_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id;

ALTER TABLE queue_tickets
    ADD COLUMN tenant_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id;

ALTER TABLE patient_records
    ADD COLUMN tenant_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id;

ALTER TABLE audit_logs
    ADD COLUMN tenant_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id;

ALTER TABLE auth_login_attempts
    ADD COLUMN tenant_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id;

ALTER TABLE users DROP INDEX username;
ALTER TABLE users ADD UNIQUE KEY ux_users_tenant_username (tenant_id, username);
ALTER TABLE patients DROP INDEX cpf;
ALTER TABLE patients ADD UNIQUE KEY ux_patients_tenant_cpf (tenant_id, cpf);
ALTER TABLE auth_login_attempts DROP INDEX ux_auth_attempt_username_ip;
ALTER TABLE auth_login_attempts ADD UNIQUE KEY ux_auth_attempt_tenant_username_ip (tenant_id, username, ip_address);

ALTER TABLE users
    ADD CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE patients
    ADD CONSTRAINT fk_patients_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE queue_tickets
    ADD CONSTRAINT fk_queue_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE patient_records
    ADD CONSTRAINT fk_records_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE audit_logs
    ADD CONSTRAINT fk_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);
ALTER TABLE auth_login_attempts
    ADD CONSTRAINT fk_auth_attempts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id);

CREATE TABLE IF NOT EXISTS app_settings (
    tenant_id BIGINT UNSIGNED NOT NULL,
    `key` VARCHAR(80) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    PRIMARY KEY (tenant_id, `key`),
    CONSTRAINT fk_app_settings_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

ALTER TABLE queue_tickets DROP INDEX idx_queue_status;
ALTER TABLE queue_tickets ADD INDEX idx_queue_status (tenant_id, status);
ALTER TABLE patient_records ADD INDEX idx_records_tenant_patient_created (tenant_id, patient_id, created_at);
ALTER TABLE audit_logs ADD INDEX idx_audit_tenant_user_created (tenant_id, user_id, created_at);

INSERT INTO app_settings (tenant_id, `key`, `value`)
VALUES (1, 'panel_access_token', 'clinix-painel-2026')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

