#!/usr/bin/env bash
# Deploy Clinix na VPS (Docker). Execute na pasta do projeto.
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
APP_URL="${APP_URL:-http://62.72.63.161}"

echo "==> Clinix deploy — ${APP_URL}"

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker não encontrado. Instale: https://docs.docker.com/engine/install/"
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose plugin não encontrado."
    exit 1
fi

if [[ ! -f .env ]]; then
    echo "==> Criando .env a partir de .env.production.example"
    cp .env.production.example .env
    if command -v openssl >/dev/null 2>&1; then
        DB_PASS="$(openssl rand -hex 16)"
        MYSQL_ROOT="$(openssl rand -hex 16)"
        PANEL_TOKEN="$(openssl rand -hex 16)"
        CRON_SECRET="$(openssl rand -hex 16)"
        sed -i.bak \
            -e "s|ALTERE_SENHA_DB_AQUI|${DB_PASS}|" \
            -e "s|ALTERE_SENHA_ROOT_AQUI|${MYSQL_ROOT}|" \
            -e "s|ALTERE_TOKEN_PAINEL_AQUI|${PANEL_TOKEN}|" \
            -e "s|ALTERE_CRON_SECRET_AQUI|${CRON_SECRET}|" \
            .env
        rm -f .env.bak
        echo "Senhas geradas automaticamente no .env"
    else
        echo "Edite .env e defina DB_PASS, MYSQL_ROOT_PASSWORD, PANEL_ACCESS_TOKEN e CRON_SECRET antes de continuar."
        exit 1
    fi
fi

# shellcheck disable=SC1091
set -a && source .env && set +a

if [[ "${DB_PASS:-}" == *"ALTERE"* ]] || [[ "${MYSQL_ROOT_PASSWORD:-}" == *"ALTERE"* ]]; then
    echo "Configure senhas reais no arquivo .env"
    exit 1
fi

mkdir -p storage/logs storage/cache storage/uploads storage/backups
chmod -R 775 storage 2>/dev/null || true

echo "==> Subindo containers"
docker compose -f "${COMPOSE_FILE}" up -d --build

echo "==> Aguardando MySQL"
sleep 8

DB_HAS_TABLES="$(docker compose -f "${COMPOSE_FILE}" exec -T db \
    mysql -u"${DB_USER}" -p"${DB_PASS}" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'" 2>/dev/null || echo 0)"

if [[ "${DB_HAS_TABLES}" == "0" ]]; then
    echo "==> Importando schema inicial"
    docker compose -f "${COMPOSE_FILE}" exec -T db \
        mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < database/schema.sql
fi

echo "==> Marcando migrations legadas (schema.sql já inclui tenant_id)"
for f in database/migrations/20260505*.sql; do
    [[ -f "$f" ]] || continue
    fn=$(basename "$f")
    docker compose -f "${COMPOSE_FILE}" exec -T db \
        mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
        -e "INSERT IGNORE INTO migrations (filename) VALUES ('${fn}');" 2>/dev/null || true
done

echo "==> Migrations"
if ! docker compose -f "${COMPOSE_FILE}" exec -T app php database/migrate.php; then
    echo "Aviso: migrate falhou. Rode: ./scripts/fix-migrations-vps.sh"
fi

echo ""
echo "Deploy concluído."
echo "  App:    ${APP_URL}/?route=login"
echo "  Health: ${APP_URL}/health.php"
echo "  Demo:   slug clinica-demo | senha ChangeMe2026! (troque após login)"
echo ""
echo "Cron LGPD (adicione no crontab da VPS):"
echo "  0 3 * * * curl -fsS \"${APP_URL}/?route=cron.retention&secret=${CRON_SECRET}\" >/dev/null"
