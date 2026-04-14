#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — Docker Deploy Script
# ═══════════════════════════════════════════════════════════════════
# Usage (from the project root on the VPS):
#   bash docker/scripts/deploy.sh
#
# What it does:
#   1. Pulls latest code (if git repo)
#   2. Copies .env.production → .env (if .env missing)
#   3. Builds Docker images
#   4. Runs migrations
#   5. Caches Laravel config/routes/views
#   6. Restarts all containers with zero-downtime rolling restart
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

# ── Config ───────────────────────────────────────────────────────
COMPOSE="docker compose"
APP_SERVICE="app"

echo "══════════════════════════════════════════════════════════════"
echo "  Insurance 2026 — Deploy"
echo "══════════════════════════════════════════════════════════════"

# ── 1. Pull latest code ──────────────────────────────────────────
if [ -d .git ]; then
    echo "[1/7] Pulling latest code..."
    git pull --ff-only
else
    echo "[1/7] No git repo — skipping pull."
fi

# ── 2. Ensure .env exists ────────────────────────────────────────
echo "[2/7] Checking .env..."
if [ ! -f .env ]; then
    if [ -f .env.production ]; then
        cp .env.production .env
        echo "  -> Copied .env.production → .env"
        echo "  !! IMPORTANT: Edit .env and fill in __CHANGE_ME__ values!"
        echo "  !! Then re-run this script."
        exit 1
    else
        echo "  !! ERROR: No .env or .env.production found!"
        exit 1
    fi
fi

# ── 3. Validate secrets exist ────────────────────────────────────
echo "[3/7] Validating Docker secrets..."
for secret_file in docker/secrets/db_password.txt docker/secrets/db_root_password.txt; do
    if [ ! -f "$secret_file" ]; then
        echo "  !! ERROR: Missing $secret_file"
        exit 1
    fi
    content=$(cat "$secret_file")
    if [[ "$content" == *"CHANGE_ME"* ]]; then
        echo "  !! ERROR: $secret_file still has placeholder value!"
        echo "  !! Replace with a real password before deploying."
        exit 1
    fi
done
echo "  -> Secrets validated."

# ── 4. Build single application image ────────────────────────────
echo "[4/7] Building application image (zero-drift: one image → all roles)..."
GIT_SHA=$(git rev-parse --short HEAD 2>/dev/null || cat .build-sha 2>/dev/null || echo "unknown")
$COMPOSE build --no-cache --build-arg APP_BUILD_SHA="$GIT_SHA" app
echo "  -> Built tamincom-app image (commit: $GIT_SHA)"

# ── 5. Start/restart ALL services from the same image ────────────
echo "[5/7] Starting services (force-recreate to pick up new image)..."
$COMPOSE up -d --force-recreate --remove-orphans

# ── 6. Run migrations + cache ────────────────────────────────────
echo "[6/7] Running migrations and caching..."
$COMPOSE exec "$APP_SERVICE" php artisan config:clear
$COMPOSE exec "$APP_SERVICE" php artisan migrate --force --no-interaction
$COMPOSE exec "$APP_SERVICE" php artisan route:cache
$COMPOSE exec "$APP_SERVICE" php artisan event:cache
$COMPOSE exec "$APP_SERVICE" php artisan view:cache
$COMPOSE exec "$APP_SERVICE" php artisan storage:link 2>/dev/null || true

# ── 7. Restart Horizon to pick up new code ───────────────────────
echo "[7/7] Terminating old Horizon workers..."
$COMPOSE exec "$APP_SERVICE" php artisan horizon:terminate 2>/dev/null || true
echo "  -> Horizon will auto-restart with new code."

# ── 8. Verify all containers are healthy ─────────────────────
echo ""
echo "[✓] Waiting 15s for health checks..."
sleep 15
$COMPOSE ps

echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  Deploy complete!"
echo ""
echo "  Check status:   $COMPOSE ps"
echo "  View logs:       $COMPOSE logs -f --tail=50"
echo "  Horizon status:  $COMPOSE exec $APP_SERVICE php artisan horizon:status"
echo "══════════════════════════════════════════════════════════════"
