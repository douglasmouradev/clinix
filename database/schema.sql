CREATE DATABASE IF NOT EXISTS clinix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinix;

CREATE TABLE tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(180) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'reception', 'nurse', 'doctor') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_users_tenant_username (tenant_id, username),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

CREATE TABLE patients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    cpf CHAR(11) NOT NULL,
    birth_date DATE NOT NULL,
    sex VARCHAR(20) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    cep CHAR(8) DEFAULT NULL,
    address VARCHAR(180) DEFAULT NULL,
    medical_history TEXT DEFAULT NULL,
    lgpd_consent_at DATETIME DEFAULT NULL,
    lgpd_consent_version VARCHAR(20) DEFAULT NULL,
    anonymized_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ux_patients_tenant_cpf (tenant_id, cpf),
    CONSTRAINT fk_patients_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

CREATE TABLE queue_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    ticket_number VARCHAR(8) NOT NULL,
    status ENUM('waiting', 'called', 'done') NOT NULL DEFAULT 'waiting',
    room VARCHAR(80) DEFAULT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    called_by BIGINT UNSIGNED DEFAULT NULL,
    called_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_queue_status (tenant_id, status),
    CONSTRAINT fk_queue_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_queue_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_queue_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_queue_called_by FOREIGN KEY (called_by) REFERENCES users(id)
);

CREATE TABLE patient_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED NOT NULL,
    entry_type ENUM('triage', 'consultation', 'diagnosis', 'prescription', 'medical_note') NOT NULL,
    content TEXT NOT NULL,
    structured_data JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_records_patient (tenant_id, patient_id, created_at),
    CONSTRAINT fk_records_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_record_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_record_user FOREIGN KEY (professional_id) REFERENCES users(id)
);

CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED DEFAULT NULL,
    scheduled_at DATETIME NOT NULL,
    status ENUM('scheduled', 'checked_in', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    reason VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_appointments_tenant_date_status (tenant_id, scheduled_at, status),
    CONSTRAINT fk_appointments_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_appointments_professional FOREIGN KEY (professional_id) REFERENCES users(id),
    CONSTRAINT fk_appointments_created_by FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE patient_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    professional_id BIGINT UNSIGNED DEFAULT NULL,
    source_appointment_id BIGINT UNSIGNED DEFAULT NULL,
    appointment_id BIGINT UNSIGNED DEFAULT NULL,
    return_due_date DATE NOT NULL,
    status ENUM('pending', 'scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    reason VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_returns_tenant_status_due (tenant_id, status, return_due_date),
    INDEX idx_returns_tenant_patient (tenant_id, patient_id),
    CONSTRAINT fk_returns_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_returns_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_returns_professional FOREIGN KEY (professional_id) REFERENCES users(id),
    CONSTRAINT fk_returns_source_appointment FOREIGN KEY (source_appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    CONSTRAINT fk_returns_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    CONSTRAINT fk_returns_created_by FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE record_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    record_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(180) NOT NULL,
    stored_name VARCHAR(180) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_record_documents_record (tenant_id, record_id),
    CONSTRAINT fk_record_documents_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_record_documents_record FOREIGN KEY (record_id) REFERENCES patient_records(id) ON DELETE CASCADE
);

CREATE TABLE patient_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(180) NOT NULL,
    stored_name VARCHAR(180) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patient_documents_patient (tenant_id, patient_id),
    CONSTRAINT fk_patient_documents_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_patient_documents_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(80) NOT NULL,
    details VARCHAR(255) NOT NULL DEFAULT '',
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (tenant_id, user_id, created_at),
    CONSTRAINT fk_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE auth_login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at DATETIME NOT NULL,
    locked_until DATETIME DEFAULT NULL,
    UNIQUE KEY ux_auth_attempt_tenant_username_ip (tenant_id, username, ip_address),
    INDEX idx_auth_attempt_locked_until (locked_until)
);

CREATE TABLE app_settings (
    tenant_id BIGINT UNSIGNED NOT NULL,
    `key` VARCHAR(80) NOT NULL,
    `value` VARCHAR(255) NOT NULL
    ,PRIMARY KEY (tenant_id, `key`)
);

CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    monthly_price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    max_users INT UNSIGNED NOT NULL DEFAULT 5,
    max_patients INT UNSIGNED NOT NULL DEFAULT 1000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tenant_subscriptions (
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

CREATE TABLE invoices (
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

CREATE TABLE lgpd_data_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    request_type ENUM('export', 'anonymize') NOT NULL,
    status ENUM('completed', 'rejected') NOT NULL DEFAULT 'completed',
    notes VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lgpd_requests_tenant_patient (tenant_id, patient_id, created_at),
    CONSTRAINT fk_lgpd_requests_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_lgpd_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_lgpd_requests_user FOREIGN KEY (requested_by) REFERENCES users(id)
);

CREATE TABLE lgpd_consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    patient_id BIGINT UNSIGNED NOT NULL,
    term_version VARCHAR(20) NOT NULL,
    consented_at DATETIME NOT NULL,
    revoked_at DATETIME DEFAULT NULL,
    collected_by BIGINT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lgpd_consents_tenant_patient (tenant_id, patient_id, created_at),
    CONSTRAINT fk_lgpd_consents_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_lgpd_consents_patient FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_lgpd_consents_user FOREIGN KEY (collected_by) REFERENCES users(id)
);

CREATE TABLE tenant_retention_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL UNIQUE,
    retention_days INT UNSIGNED NOT NULL DEFAULT 1825,
    auto_anonymize TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_retention_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_retention_user FOREIGN KEY (updated_by) REFERENCES users(id)
);

INSERT INTO tenants (id, name, slug, is_active) VALUES
(1, 'Clínica Demo', 'clinica-demo', 1);

INSERT INTO app_settings (tenant_id, `key`, `value`) VALUES
(1, 'panel_access_token', 'clinix-painel-2026')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

INSERT INTO plans (code, name, monthly_price_cents, max_users, max_patients) VALUES
('starter', 'Starter', 9900, 5, 3000),
('growth', 'Growth', 19900, 15, 12000),
('scale', 'Scale', 39900, 40, 50000);

INSERT INTO users (tenant_id, name, username, password_hash, role) VALUES
(1, 'Administrador Clinix', 'admin', '$2y$12$m8eZ9EWCSAmfpHHzHbv.EunCVj13BAfqi7VIRXHP0YByqi3gHYLle', 'admin'),
(1, 'Ana Recepção', 'recepção', '$2y$12$m8eZ9EWCSAmfpHHzHbv.EunCVj13BAfqi7VIRXHP0YByqi3gHYLle', 'reception'),
(1, 'Bruna Enfermagem', 'enfermeira', '$2y$12$m8eZ9EWCSAmfpHHzHbv.EunCVj13BAfqi7VIRXHP0YByqi3gHYLle', 'nurse'),
(1, 'Carlos Médico', 'médico', '$2y$12$m8eZ9EWCSAmfpHHzHbv.EunCVj13BAfqi7VIRXHP0YByqi3gHYLle', 'doctor');

INSERT INTO tenant_subscriptions (tenant_id, plan_id, status, started_at, next_billing_at)
SELECT 1, id, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM plans WHERE code = 'starter' LIMIT 1;

INSERT INTO tenant_retention_policies (tenant_id, retention_days, auto_anonymize, updated_by)
VALUES (1, 1825, 0, NULL);

-- senha padrao para usuários de exemplo: 123456

