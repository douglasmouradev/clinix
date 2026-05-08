ALTER TABLE patients
    ADD COLUMN lgpd_consent_at DATETIME NULL AFTER medical_history,
    ADD COLUMN lgpd_consent_version VARCHAR(20) NULL AFTER lgpd_consent_at,
    ADD COLUMN anonymized_at DATETIME NULL AFTER lgpd_consent_version;

CREATE TABLE IF NOT EXISTS lgpd_data_requests (
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

CREATE INDEX idx_queue_tenant_created_status ON queue_tickets (tenant_id, created_at, status);
CREATE INDEX idx_records_tenant_created_type ON patient_records (tenant_id, created_at, entry_type);
CREATE INDEX idx_patients_tenant_anonymized ON patients (tenant_id, anonymized_at);

