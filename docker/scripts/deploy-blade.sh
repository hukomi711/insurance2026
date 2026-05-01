#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Deploy Blade Templates
# ═══════════════════════════════════════════════════════════════════
# Usage: bash docker/scripts/deploy-blade.sh [file...]
#   bash docker/scripts/deploy-blade.sh resources/views/app.blade.php
#   bash docker/scripts/deploy-blade.sh resources/views/app.blade.php resources/views/errors/404.blade.php
#
# Flushes: compiled views + OPcache (PHP-FPM restart)
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

SERVER_IP="${INS_SERVER_IP:?set INS_SERVER_IP env var (target deployment server)}"
SERVER_USER="${INS_DEPLOY_USER:-root}"
CONTAINER="${INS_APP_CONTAINER:-ins2026-app}"

if [ $# -eq 0 ]; then
    echo "Usage: $0 <blade-file> [blade-file...]"
    echo "Example: $0 resources/views/app.blade.php"
    exit 1
fi

echo "==> Deploying Blade templates..."

for FILE in "$@"; do
    if [ ! -f "$FILE" ]; then
        echo "!! ERROR: $FILE not found locally"
        exit 1
    fi
    BASENAME=$(basename "$FILE")
    echo "    Uploading: $FILE"
    scp "$FILE" "${SERVER_USER}@${SERVER_IP}:/tmp/${BASENAME}"
    ssh "${SERVER_USER}@${SERVER_IP}" \
        "docker cp /tmp/${BASENAME} ${CONTAINER}:/var/www/html/${FILE} && rm /tmp/${BASENAME}"
done

echo "==> Flushing view cache + OPcache..."
ssh "${SERVER_USER}@${SERVER_IP}" "\
    docker exec ${CONTAINER} php artisan view:clear && \
    docker exec ${CONTAINER} php artisan view:cache && \
    docker exec ${CONTAINER} kill -USR2 1"

echo "==> Blade deploy complete!"
