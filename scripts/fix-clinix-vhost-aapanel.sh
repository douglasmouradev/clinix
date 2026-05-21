#!/usr/bin/env bash
# Corrige clinix.tdesksolutions.com.br: sobe Docker na 8080 e ajusta proxy no Nginx aaPanel.
set -euo pipefail

CLINIX_DIR="${CLINIX_DIR:-/opt/clinix-app}"
VHOST="/www/server/panel/vhost/nginx/clinix.tdesksolutions.com.br.conf"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
PROXY_TARGET="http://127.0.0.1:8080"

if [[ ! -d "${CLINIX_DIR}" ]]; then
    echo "Pasta ${CLINIX_DIR} não encontrada."
    exit 1
fi

cd "${CLINIX_DIR}"

if [[ ! -f .env ]]; then
    echo "Crie o .env em ${CLINIX_DIR} (copie de .env.production.example)"
    exit 1
fi

# shellcheck disable=SC1091
set -a && source .env && set +a

if grep -q '^APP_HTTP_PORT=80' .env 2>/dev/null; then
    sed -i.bak 's/^APP_HTTP_PORT=80/APP_HTTP_PORT=8080/' .env
    rm -f .env.bak
    echo "APP_HTTP_PORT ajustado para 8080"
fi

grep -q '^APP_HTTP_PORT=' .env || echo 'APP_HTTP_PORT=8080' >> .env

echo "==> Subindo Clinix (Docker)"
docker compose -f "${COMPOSE_FILE}" up -d --build

echo "==> Aguardando app"
for i in $(seq 1 30); do
    if curl -sf "${PROXY_TARGET}/health.php" >/dev/null 2>&1; then
        echo "health.php OK"
        break
    fi
    sleep 2
    if [[ "$i" -eq 30 ]]; then
        echo "Falha: ${PROXY_TARGET}/health.php não respondeu"
        docker compose -f "${COMPOSE_FILE}" ps
        docker compose -f "${COMPOSE_FILE}" logs --tail=40 app
        exit 1
    fi
done

if [[ ! -f "${VHOST}" ]]; then
    echo "Arquivo ${VHOST} não existe. Crie o site clinix no aaPanel primeiro."
    exit 1
fi

if grep -q 'proxy_pass.*127.0.0.1:8080' "${VHOST}"; then
    echo "==> proxy_pass 8080 já configurado em ${VHOST}"
else
    echo "==> Ajustando Nginx do clinix (proxy → 8080)"
    cp "${VHOST}" "${VHOST}.bak.$(date +%Y%m%d%H%M%S)"

    python3 <<'PY'
import re
from pathlib import Path

vhost = Path("/www/server/panel/vhost/nginx/clinix.tdesksolutions.com.br.conf")
text = vhost.read_text()

proxy_block = """
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300;
    }
"""

if "proxy_pass http://127.0.0.1:8080" in text:
    print("proxy já presente")
else:
    # Remove blocos location / antigos (PHP estático no host)
    text = re.sub(
        r"\n\s*location\s+/\s*\{[^}]*\}",
        "",
        text,
        flags=re.DOTALL,
    )
    # Insere proxy antes do fechamento do primeiro server { ... }
    text = re.sub(
        r"(\n\s*access_log[^\n]*\n)(\s*\})",
        r"\1" + proxy_block + r"\2",
        text,
        count=1,
    )
    if "proxy_pass http://127.0.0.1:8080" not in text:
        text = re.sub(r"(\n)(\})", proxy_block + r"\1\2", text, count=1)
    vhost.write_text(text)
    print("proxy_pass adicionado")
PY
fi

echo "==> Recarregando Nginx aaPanel"
/www/server/nginx/sbin/nginx -t
/www/server/nginx/sbin/nginx -s reload

echo ""
echo "Teste:"
echo "  curl -s http://127.0.0.1:8080/health.php"
echo "  curl -sI http://127.0.0.1/health.php -H 'Host: clinix.tdesksolutions.com.br'"
echo "  https://clinix.tdesksolutions.com.br/health.php"
echo "  https://clinix.tdesksolutions.com.br/?route=login"
