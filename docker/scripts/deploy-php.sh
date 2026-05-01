#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Deploy PHP Files (Controllers, Models, Middleware, etc.)
# ═══════════════════════════════════════════════════════════════════
# Usage: bash docker/scripts/deploy-php.sh [file...]
#   bash docker/scripts/deploy-php.sh app/Http/Middleware/CountryRestriction.php
#   bash docker/scripts/deploy-php.sh app/Http/Controllers/SpaController.php app/Models/Customer.php
#
# Flushes: route cache + OPcache (PHP-FPM restart)
# Does NOT run config:cache (secrets come from Docker env).
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

SERVER_IP="${INS_SERVER_IP:?set INS_SERVER_IP env var (target deployment server)}"
SERVER_USER="${INS_DEPLOY_USER:-root}"
CONTAINER="${INS_APP_CONTAINER:-ins2026-app}"

if [ $# -eq 0 ]; then
    echo "Usage: $0 <php-file> [php-file...]"
    echo "Example: $0 app/Http/Middleware/CountryRestriction.php"
    exit 1
fi

echo "==> Deploying PHP files..."

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

echo "==> Flushing route cache + OPcache..."
ssh "${SERVER_USER}@${SERVER_IP}" "\
    docker exec ${CONTAINER} php artisan route:clear && \
    docker exec ${CONTAINER} php artisan route:cache && \
    docker exec ${CONTAINER} kill -USR2 1"

echo "==> PHP deploy complete!"
