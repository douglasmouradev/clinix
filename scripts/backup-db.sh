#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="${ROOT}/.env"
if [[ -f "${ENV_FILE}" ]]; then
  # shellcheck disable=SC1090
  source <(grep -E '^(DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS)=' "${ENV_FILE}" | sed 's/^/export /')
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-clinix}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

OUT_DIR="${ROOT}/storage/backups"
mkdir -p "${OUT_DIR}"
FILE="${OUT_DIR}/clinix-$(date +%Y%m%d-%H%M%S).sql"

echo "Gerando backup em ${FILE}"
mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" ${DB_PASS:+-p"${DB_PASS}"} \
  --single-transaction --routines --triggers "${DB_NAME}" > "${FILE}"

echo "Backup concluido."
