#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Deploy Frontend Build (JS/CSS)
# ═══════════════════════════════════════════════════════════════════
# Usage: bash docker/scripts/deploy-build.sh
#
# Runs npm build locally, then uploads public/build/ to the server.
# No PHP cache flush needed — Vite manifest handles versioning.
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

SERVER_IP="159.198.43.139"
SERVER_USER="root"
SERVER_DIR="/opt/tamincom"
CONTAINER="ins2026-app"

echo "==> Running npm build..."
npm run build

echo "==> Packaging public/build/..."
tar -czf /tmp/build.tar.gz -C public build

echo "==> Uploading to server..."
scp /tmp/build.tar.gz "${SERVER_USER}@${SERVER_IP}:/tmp/build.tar.gz"

echo "==> Extracting into container..."
ssh "${SERVER_USER}@${SERVER_IP}" "\
    rm -rf /tmp/build && \
    mkdir -p /tmp/build && \
    tar -xzf /tmp/build.tar.gz -C /tmp/build && \
    docker exec ${CONTAINER} rm -rf /var/www/html/public/build && \
    docker cp /tmp/build/build ${CONTAINER}:/var/www/html/public/build && \
    rm -rf /tmp/build /tmp/build.tar.gz"

echo "==> Flushing view cache (Blade reads Vite manifest)..."
ssh "${SERVER_USER}@${SERVER_IP}" "\
    docker exec ${CONTAINER} php artisan view:clear && \
    docker exec ${CONTAINER} php artisan view:cache && \
    docker exec ${CONTAINER} kill -USR2 1"

echo "==> Frontend deploy complete!"
rm -f /tmp/build.tar.gz
