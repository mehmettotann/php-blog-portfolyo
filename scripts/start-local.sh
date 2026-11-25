#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOST="${1:-127.0.0.1}"
PORT="${2:-8000}"

echo "========================================================="
echo " example.com — Lokal Geliştirme Sunucusu"
echo " Adres: http://${HOST}:${PORT}"
echo " Admin: http://${HOST}:${PORT}/admin/giris"
echo " Durdurmak için Ctrl+C tuşlarına basın."
echo "========================================================="

exec php \
  -d upload_max_filesize=160M \
  -d post_max_size=180M \
  -d memory_limit=256M \
  -d max_execution_time=120 \
  -S "${HOST}:${PORT}" \
  -t "${ROOT_DIR}" \
  "${ROOT_DIR}/router.php"
