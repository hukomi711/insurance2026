#!/bin/bash
# Backup MariaDB to a date-stamped gzip dump.
# Env vars (set in /etc/environment or systemd timer):
#   INS_BACKUP_DIR  default /opt/server-state-backups/database
#   INS_DB_USER     default insurance
#   INS_DB_NAME     default insurance2026
set -euo pipefail
BACKUP_DIR="${INS_BACKUP_DIR:-/opt/server-state-backups/database}"
DB_USER="${INS_DB_USER:-insurance}"
DB_NAME="${INS_DB_NAME:-insurance2026}"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="${DB_NAME}_${DATE}.sql.gz"
mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

if docker exec \
    -e BACKUP_DB_USER="${DB_USER}" \
    -e BACKUP_DB_NAME="${DB_NAME}" \
    ins2026-db sh -eu -c '
        defaults_file="$(mktemp)"
        trap '\''rm -f "$defaults_file"'\'' EXIT
        chmod 600 "$defaults_file"
        printf "[client]\npassword=%s\n" "$(cat /run/secrets/db_password)" > "$defaults_file"
        mariadb-dump --defaults-extra-file="$defaults_file" \
            --user="$BACKUP_DB_USER" \
            --single-transaction --routines --triggers \
            "$BACKUP_DB_NAME"
    ' | gzip > "${BACKUP_DIR}/${FILENAME}"; then
    chmod 600 "${BACKUP_DIR}/${FILENAME}"
    test -s "${BACKUP_DIR}/${FILENAME}"
    echo "[$(date)] Backup OK: ${FILENAME}" >> "${BACKUP_DIR}/backup.log"
else
    echo "[$(date)] Backup FAILED" >> "${BACKUP_DIR}/backup.log"
    exit 1
fi

# Keep only last 7 days of backups
find "${BACKUP_DIR}" -name "${DB_NAME}_*.sql.gz" -mtime +7 -delete
