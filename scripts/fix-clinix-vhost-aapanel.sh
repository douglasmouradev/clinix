#!/usr/bin/env bash
# Corrige clinix.tdesksolutions.com.br: sobe Docker na 8080 e ajusta proxy no Nginx aaPanel.
set -euo pipefail

CLINIX_DIR="${CLINIX_DIR:-/opt/clinix-app}"
VHOST="/www/server/panel/vhost/nginx/clinix.tdesksolutions.com.br.conf"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
PROXY_TARGET="http://127.0.0.1:8080"
NGINX_BIN="/www/server/nginx/sbin/nginx"

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

# Só troca porta 80 exata (sed com "=80" quebraria APP_HTTP_PORT=8080 → 808080)
if grep -qE '^APP_HTTP_PORT=80$' .env 2>/dev/null; then
    sed -i.bak 's/^APP_HTTP_PORT=80$/APP_HTTP_PORT=8080/' .env
    rm -f .env.bak
    echo "APP_HTTP_PORT ajustado de 80 para 8080"
elif grep -qE '^APP_HTTP_PORT=8080+' .env 2>/dev/null; then
    sed -i.bak -E 's/^APP_HTTP_PORT=8080+$/APP_HTTP_PORT=8080/' .env
    rm -f .env.bak
    echo "APP_HTTP_PORT corrigido (valor duplicado)"
fi

grep -qE '^APP_HTTP_PORT=' .env || echo 'APP_HTTP_PORT=8080' >> .env

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

echo "==> Ajustando Nginx do clinix (proxy → 8080)"
cp "${VHOST}" "${VHOST}.bak.$(date +%Y%m%d%H%M%S)"

python3 <<'PY'
import re
from pathlib import Path

vhost = Path("/www/server/panel/vhost/nginx/clinix.tdesksolutions.com.br.conf")
text = vhost.read_text()

PROXY = """
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


def find_matching_brace(s: str, open_pos: int) -> int:
    depth = 0
    i = open_pos
    while i < len(s):
        if s[i] == "{":
            depth += 1
        elif s[i] == "}":
            depth -= 1
            if depth == 0:
                return i
        i += 1
    return -1


def strip_location_blocks(body: str, name: str) -> str:
    pattern = re.compile(rf"\n\s*location\s+{re.escape(name)}\s*\{{", re.MULTILINE)
    while True:
        m = pattern.search(body)
        if not m:
            break
        start = m.start()
        brace = body.find("{", m.end() - 1)
        end = find_matching_brace(body, brace)
        if end < 0:
            break
        body = body[:start] + body[end + 1 :]
    return body


def patch_server_block(block: str) -> str:
    if "proxy_pass http://127.0.0.1:8080" in block:
        return block
    inner_open = block.find("{")
    inner_close = find_matching_brace(block, inner_open)
    if inner_close < 0:
        return block
    body = block[inner_open + 1 : inner_close]
    body = strip_location_blocks(body, "/")
    body = strip_location_blocks(body, "~ .*\\.(gif|jpg|jpeg|png|bmp|swf)$")
    body = re.sub(r"\n\s*include\s+enable-php[^\n]*\n", "\n", body)
    body = re.sub(r"\n\s*include\s+/www/server/panel/vhost/nginx/well-known[^\n]*\n", "\n", body)
    return block[: inner_open + 1] + body + PROXY + block[inner_close:]


parts = []
pos = 0
while True:
    idx = text.find("server", pos)
    if idx < 0:
        parts.append(text[pos:])
        break
    brace = text.find("{", idx)
    if brace < 0:
        parts.append(text[pos:])
        break
    end = find_matching_brace(text, brace)
    if end < 0:
        parts.append(text[pos:])
        break
    parts.append(text[pos:idx])
    block = text[idx : end + 1]
    parts.append(patch_server_block(block))
    pos = end + 1

text = "".join(parts)
vhost.write_text(text)
print("proxy_pass aplicado em cada bloco server {}")
PY

echo "==> Testando Nginx"
if ! "${NGINX_BIN}" -t; then
    echo "ERRO: config inválida. Restaure o backup mais recente:"
    ls -t "${VHOST}.bak."* 2>/dev/null | head -3 || true
    echo "  cp ${VHOST}.bak.XXXX ${VHOST}"
    echo "Ou use aaPanel → Site → clinix → Reverse proxy → http://127.0.0.1:8080"
    exit 1
fi

"${NGINX_BIN}" -s reload

echo ""
echo "Teste:"
echo "  curl -s http://127.0.0.1:8080/health.php"
echo "  curl -s http://127.0.0.1/health.php -H 'Host: clinix.tdesksolutions.com.br'"
echo "  https://clinix.tdesksolutions.com.br/health.php"
