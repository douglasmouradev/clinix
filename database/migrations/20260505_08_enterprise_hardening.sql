CREATE TABLE IF NOT EXISTS lgpd_consents (
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

CREATE TABLE IF NOT EXISTS tenant_retention_policies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL UNIQUE,
    retention_days INT UNSIGNED NOT NULL DEFAULT 1825,
    auto_anonymize TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_retention_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    CONSTRAINT fk_retention_user FOREIGN KEY (updated_by) REFERENCES users(id)
);

INSERT INTO tenant_retention_policies (tenant_id, retention_days, auto_anonymize, updated_by)
SELECT 1, 1825, 0, NULL
WHERE NOT EXISTS (SELECT 1 FROM tenant_retention_policies WHERE tenant_id = 1);

