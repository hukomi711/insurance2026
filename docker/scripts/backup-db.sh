#!/bin/bash
BACKUP_DIR="/opt/tamicomz/backups"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="insurance2026_${DATE}.sql.gz"
DB_PASS=$(cat /opt/tamicomz/docker/secrets/db_password.txt)

docker exec ins2026-db mariadb-dump -u insurance -p"${DB_PASS}" insurance2026 --single-transaction --routines --triggers | gzip > "${BACKUP_DIR}/${FILENAME}"

if [ $? -eq 0 ]; then
    echo "[$(date)] Backup OK: ${FILENAME}" >> "${BACKUP_DIR}/backup.log"
else
    echo "[$(date)] Backup FAILED" >> "${BACKUP_DIR}/backup.log"
fi

# Keep only last 7 days of backups
find "${BACKUP_DIR}" -name "insurance2026_*.sql.gz" -mtime +7 -delete
