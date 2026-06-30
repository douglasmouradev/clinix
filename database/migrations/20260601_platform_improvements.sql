ALTER TABLE users
    ADD COLUMN email VARCHAR(180) DEFAULT NULL AFTER username;

ALTER TABLE api_tokens
    ADD COLUMN scopes VARCHAR(255) NOT NULL DEFAULT 'patients,queue' AFTER token_hash;
