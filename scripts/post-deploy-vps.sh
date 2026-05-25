#!/usr/bin/env bash
# Pós-deploy na VPS: pull, migrate, health, restart app.
set -euo pipefail

ROOT="${CLINIX_ROOT:-/opt/clinix-app}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"

cd "${ROOT}"

echo "==> git pull"
if ! git pull --ff-only; then
    echo "git pull falhou. Se config.php foi editado na mão:"
    echo "  git checkout -- app/Config/config.php && git pull --ff-only"
    exit 1
fi

echo "==> migrate"
docker compose -f "${COMPOSE_FILE}" exec -T app php database/migrate.php

echo "==> restart app"
docker compose -f "${COMPOSE_FILE}" restart app

sleep 3
PORT="$(grep -E '^APP_HTTP_PORT=' .env 2>/dev/null | cut -d= -f2 || echo 8080)"
PORT="${PORT:-8080}"

echo "==> health"
curl -fsS "http://127.0.0.1:${PORT}/health.php" | head -c 200
echo ""
echo "Deploy concluído. Teste: curl -sI https://clinix.tdesksolutions.com.br/health.php"
