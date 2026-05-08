CREATE INDEX idx_patients_tenant_name ON patients (tenant_id, full_name);
CREATE INDEX idx_users_tenant_role_active ON users (tenant_id, role, is_active);

