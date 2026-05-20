#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://localhost:8000}"
TENANT_SLUG="${SMOKE_TENANT_SLUG:-clinica-demo}"
PANEL_TOKEN="${SMOKE_PANEL_TOKEN:-}"
TMP="$(mktemp)"

echo "==> Health check"
curl -fsS "${BASE_URL}/health.php" >/dev/null

echo "==> Login page"
curl -fsS "${BASE_URL}/?route=login" | grep -q "Login da Clínica"

echo "==> Forgot password page"
curl -fsS "${BASE_URL}/?route=password.forgot" | grep -q "Esqueci minha senha"

echo "==> Panel JSON"
ENCODED_TENANT="$(python3 -c "import urllib.parse; print(urllib.parse.quote('${TENANT_SLUG}'))")"
if [[ -z "${PANEL_TOKEN}" ]]; then
  PANEL_TOKEN="$(php -r "
    require 'app/Config/config.php';
    spl_autoload_register(function (\$c) {
      if (strncmp(\$c, 'App\\\\', 4) !== 0) return;
      \$f = __DIR__ . '/app/' . str_replace('\\\\', '/', substr(\$c, 4)) . '.php';
      if (is_file(\$f)) require \$f;
    });
    \$_SESSION['tenant_context_id'] = 1;
    \$s = App\Core\Database::connection()->prepare('SELECT value FROM app_settings WHERE tenant_id = 1 AND \`key\` = \"panel_access_token\" LIMIT 1');
    \$s->execute();
    echo (string) (\$s->fetchColumn() ?: getenv('PANEL_ACCESS_TOKEN') ?: 'clinix-painel-2026');
  ")"
fi
PANEL_JSON="$(curl -fsS "${BASE_URL}/?route=queue.panel.data&tenant=${ENCODED_TENANT}&token=${PANEL_TOKEN}")"
echo "${PANEL_JSON}" | grep -q '"ok":true'

echo "==> CSRF + login session"
LOGIN_HTML="$(curl -fsS -c "${TMP}" "${BASE_URL}/?route=login")"
TOKEN="$(echo "${LOGIN_HTML}" | sed -n 's/.*name="_csrf_token" value="\([^"]*\)".*/\1/p' | head -1)"
if [[ -n "${TOKEN}" ]]; then
  LOGIN_CODE="$(curl -sS -o /dev/null -w "%{http_code}" -b "${TMP}" -c "${TMP}" \
    -X POST "${BASE_URL}/?route=login.submit" \
    --data-urlencode "tenant_slug=${TENANT_SLUG}" \
    --data-urlencode "username=admin" \
    --data-urlencode "password=ChangeMe2026!" \
    --data-urlencode "_csrf_token=${TOKEN}")"
  if [[ "${LOGIN_CODE}" != "302" && "${LOGIN_CODE}" != "200" ]]; then
    echo "Aviso: login retornou HTTP ${LOGIN_CODE}"
  fi
else
  echo "Aviso: CSRF não encontrado na página de login (pulando login)"
fi

echo "==> Cron retention (secret)"
CRON_SECRET="${CRON_SECRET:-}"
if [[ -n "${CRON_SECRET}" ]]; then
  curl -fsS "${BASE_URL}/?route=cron.retention&secret=${CRON_SECRET}" | grep -q '"ok":true'
fi

echo "Smoke test concluido."
