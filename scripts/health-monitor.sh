#!/usr/bin/env bash
# Monitor simples: health HTTP + container app. Uso: cron a cada 5 min.
set -euo pipefail

ROOT="${CLINIX_ROOT:-/opt/clinix-app}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
PORT="${APP_HTTP_PORT:-8080}"

if [[ -f "${ROOT}/.env" ]]; then
  # shellcheck disable=SC1090
  source <(grep -E '^APP_HTTP_PORT=' "${ROOT}/.env" | sed 's/^/export /')
  PORT="${APP_HTTP_PORT:-8080}"
fi

LOG="${ROOT}/storage/logs/health-monitor.log"
mkdir -p "$(dirname "${LOG}")"

ts() { date '+%Y-%m-%d %H:%M:%S'; }

if ! curl -fsS --max-time 8 "http://127.0.0.1:${PORT}/health.php" >/dev/null; then
    echo "$(ts) FAIL health HTTP :${PORT}" >> "${LOG}"
    cd "${ROOT}" && docker compose -f "${COMPOSE_FILE}" up -d app 2>>"${LOG}" || true
    exit 1
fi

if ! docker compose -f "${ROOT}/${COMPOSE_FILE}" ps --status running 2>/dev/null | grep -q app; then
    echo "$(ts) WARN container app não running" >> "${LOG}"
    exit 1
fi

echo "$(ts) OK" >> "${LOG}"
