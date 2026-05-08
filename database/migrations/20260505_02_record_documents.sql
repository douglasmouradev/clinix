CREATE TABLE IF NOT EXISTS record_documents (
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

