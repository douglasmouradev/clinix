#!/usr/bin/env bash
# Libera porta 80/443 para o aaPanel: Clinix só na 8080.
set -euo pipefail

cd "$(dirname "$0")/.."
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"

if [[ ! -f .env ]]; then
    echo "Arquivo .env não encontrado em $(pwd)"
    exit 1
fi

# shellcheck disable=SC1091
set -a && source .env && set +a

if grep -q '^APP_HTTP_PORT=80' .env 2>/dev/null; then
    sed -i.bak 's/^APP_HTTP_PORT=80/APP_HTTP_PORT=8080/' .env
    rm -f .env.bak
    echo "APP_HTTP_PORT alterado de 80 para 8080 no .env"
elif ! grep -q '^APP_HTTP_PORT=' .env; then
    echo 'APP_HTTP_PORT=8080' >> .env
    echo "APP_HTTP_PORT=8080 adicionado ao .env"
fi

echo "==> Reiniciando Clinix na porta 8080"
docker compose -f "${COMPOSE_FILE}" up -d

echo "==> Portas em uso"
ss -tlnp | grep -E ':80|:443|:8080' || netstat -tlnp | grep -E ':80|:443|:8080' || true

echo "==> Reiniciando Nginx do aaPanel (se existir)"
if command -v bt >/dev/null 2>&1; then
    bt reload 2>/dev/null || /etc/init.d/nginx reload 2>/dev/null || true
elif systemctl is-active nginx >/dev/null 2>&1; then
    systemctl reload nginx
fi

echo ""
echo "Pronto. Teste:"
echo "  curl -I http://127.0.0.1/health.php  (outros sites na 80)"
echo "  curl -I http://127.0.0.1:8080/health.php  (Clinix)"
echo "  aaPanel: https://62.72.63.161:12103"
