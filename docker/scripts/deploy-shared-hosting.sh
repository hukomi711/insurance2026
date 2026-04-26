#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — Shared Hosting Deploy Script
# Target: daliltameni.online (Namecheap StellarBus18)
# Server: business72.web-hosting.com
# SFTP Port: 21098 | SSH Port: 21098
# ═══════════════════════════════════════════════════════════════════
# Usage: bash docker/scripts/deploy-shared-hosting.sh
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

SFTP_HOST="business72.web-hosting.com"
SFTP_USER="daliozwz"
SFTP_PORT="21098"
REMOTE_APP_DIR="/home/daliozwz/laravel"
REMOTE_PUBLIC_DIR="/home/daliozwz/public_html"

echo "══════════════════════════════════════════════════════════════"
echo "  Insurance 2026 — Shared Hosting Deploy"
echo "  Target: ${SFTP_USER}@${SFTP_HOST}"
echo "══════════════════════════════════════════════════════════════"

# ── Find project root ────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_ROOT"

# ── 1. Copy .env.shared-hosting → .env for Vite build ────────────
echo "[1/6] Preparing environment for build..."
if [ ! -f .env.shared-hosting ]; then
    echo "!! ERROR: .env.shared-hosting not found!"
    exit 1
fi
cp .env.shared-hosting .env.build-temp

# ── 2. Build frontend assets ─────────────────────────────────────
echo "[2/6] Building frontend (npm run build)..."
# Source the env file so Vite picks up VITE_* vars
set -a && . ./.env.shared-hosting && set +a
npm run build
echo "  -> Frontend built: public/build/"

# ── 3. Install PHP dependencies (production) ─────────────────────
echo "[3/6] Installing PHP dependencies (--no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts 2>/dev/null \
    || composer install --no-dev --optimize-autoloader --no-interaction
echo "  -> vendor/ ready"

# ── 4. Create app archive (Laravel files, excluding public/) ─────
echo "[4/6] Creating archives..."
APP_ARCHIVE="/tmp/ins2026-laravel.tar.gz"
PUBLIC_ARCHIVE="/tmp/ins2026-public.tar.gz"

tar -czf "$APP_ARCHIVE" \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='public' \
    --exclude='node_modules' \
    --exclude='_ide_helper*.php' \
    --exclude='*.log' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/framework/cache/data/*' \
    .

tar -czf "$PUBLIC_ARCHIVE" \
    --exclude='public/hot' \
    --exclude='public/storage' \
    public/

APP_SIZE=$(du -h "$APP_ARCHIVE" | awk '{print $1}')
PUB_SIZE=$(du -h "$PUBLIC_ARCHIVE" | awk '{print $1}')
echo "  -> App archive:    $APP_SIZE"
echo "  -> Public archive: $PUB_SIZE"

# ── 5. Upload via SCP ────────────────────────────────────────────
echo "[5/6] Uploading to server (password will be prompted)..."
echo "  Password: (enter your cPanel password)"
echo ""

# Create remote directories and upload
ssh -p "$SFTP_PORT" -o StrictHostKeyChecking=no "${SFTP_USER}@${SFTP_HOST}" \
    "mkdir -p ${REMOTE_APP_DIR} ${REMOTE_PUBLIC_DIR}"

scp -P "$SFTP_PORT" -o StrictHostKeyChecking=no \
    "$APP_ARCHIVE" \
    "${SFTP_USER}@${SFTP_HOST}:${REMOTE_APP_DIR}/ins2026-laravel.tar.gz"

scp -P "$SFTP_PORT" -o StrictHostKeyChecking=no \
    "$PUBLIC_ARCHIVE" \
    "${SFTP_USER}@${SFTP_HOST}:${REMOTE_PUBLIC_DIR}/ins2026-public.tar.gz"

echo "  -> Upload complete."

# ── 6. Extract and configure on server ──────────────────────────
echo "[6/6] Extracting and configuring on server..."
ssh -p "$SFTP_PORT" -o StrictHostKeyChecking=no "${SFTP_USER}@${SFTP_HOST}" << 'REMOTE'
set -e

# Extract app
cd /home/daliozwz/laravel
tar -xzf ins2026-laravel.tar.gz
rm -f ins2026-laravel.tar.gz

# Extract public
cd /home/daliozwz/public_html
tar -xzf ins2026-public.tar.gz --strip-components=1
rm -f ins2026-public.tar.gz

# Copy env
cp /home/daliozwz/laravel/.env.shared-hosting /home/daliozwz/laravel/.env 2>/dev/null || true

# Permissions
chmod -R 775 /home/daliozwz/laravel/storage
chmod -R 775 /home/daliozwz/laravel/bootstrap/cache
chmod 644 /home/daliozwz/laravel/.env

# Storage symlink
cd /home/daliozwz/laravel
php artisan storage:link --relative 2>/dev/null || true

# Cache (no config:cache on shared hosting — use config:clear only)
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear

echo "Done!"
REMOTE

# ── Cleanup local temp ──────────────────────────────────────────
rm -f "$APP_ARCHIVE" "$PUBLIC_ARCHIVE" .env.build-temp

echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  Deploy complete!"
echo ""
echo "  Next steps:"
echo "  1. SSH to server and run: php artisan migrate --force"
echo "  2. Set DB_PASSWORD in /home/daliozwz/laravel/.env"
echo "  3. Add cron job in cPanel for queue worker:"
echo "     * * * * * cd /home/daliozwz/laravel && php artisan queue:work --stop-when-empty 2>&1"
echo "  4. Check: https://daliltameni.online"
echo "══════════════════════════════════════════════════════════════"
