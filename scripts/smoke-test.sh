#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://localhost:8000}"

echo "==> Health check"
curl -fsS "${BASE_URL}/health.php" >/dev/null

echo "==> Login page check"
curl -fsS "${BASE_URL}/?route=login" | grep -q "Login da Clínica"

echo "Smoke test concluido."

