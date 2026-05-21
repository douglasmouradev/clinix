#!/usr/bin/env bash
# Corrige erro "Duplicate column tenant_id" após schema.sql + migrations legadas.
set -euo pipefail

cd "$(dirname "$0")/.."
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"

set -a && source .env && set +a

echo "==> Marcando migrations 20260505* como já aplicadas"
for f in database/migrations/20260505*.sql; do
    [[ -f "$f" ]] || continue
    fn=$(basename "$f")
    docker compose -f "${COMPOSE_FILE}" exec -T db \
        mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
        -e "INSERT IGNORE INTO migrations (filename) VALUES ('${fn}');"
    echo "  ${fn}"
done

echo "==> Rodando migrations pendentes"
docker compose -f "${COMPOSE_FILE}" exec -T app php database/migrate.php

echo "Concluído."
