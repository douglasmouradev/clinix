ALTER TABLE patient_records
    ADD COLUMN structured_data JSON DEFAULT NULL AFTER content;

CREATE TABLE IF NOT EXISTS appointments (
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

