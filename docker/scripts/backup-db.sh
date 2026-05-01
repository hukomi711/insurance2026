#!/bin/bash
# Backup MariaDB to a date-stamped gzip dump.
# Env vars (set in /etc/environment or systemd timer):
#   INS_DEPLOY_DIR  default /opt/insurance2026
#   INS_DB_USER     default insurance
#   INS_DB_NAME     default insurance2026
set -euo pipefail
APP_DIR="${INS_DEPLOY_DIR:-/opt/insurance2026}"
BACKUP_DIR="${APP_DIR}/backups"
DB_USER="${INS_DB_USER:-insurance}"
DB_NAME="${INS_DB_NAME:-insurance2026}"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="${DB_NAME}_${DATE}.sql.gz"
DB_PASS=$(cat "${APP_DIR}/docker/secrets/db_password.txt")

mkdir -p "${BACKUP_DIR}"

if docker exec ins2026-db mariadb-dump -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
    --single-transaction --routines --triggers | gzip > "${BACKUP_DIR}/${FILENAME}"; then
    echo "[$(date)] Backup OK: ${FILENAME}" >> "${BACKUP_DIR}/backup.log"
else
    echo "[$(date)] Backup FAILED" >> "${BACKUP_DIR}/backup.log"
    exit 1
fi

# Keep only last 7 days of backups
find "${BACKUP_DIR}" -name "${DB_NAME}_*.sql.gz" -mtime +7 -delete
