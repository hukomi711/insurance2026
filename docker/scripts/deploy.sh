#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — Docker Deploy Script
# ═══════════════════════════════════════════════════════════════════
# Usage (from a clean detached release worktree on the VPS):
#   cd /opt/insurance-releases/<full-commit-sha>
#   bash docker/scripts/deploy.sh
#
# What it does:
#   1. Proves the release source is clean, detached and SHA-addressed
#   2. Validates runtime environment and Docker secrets
#   3. Builds one traceable application image
#   4. Runs migrations from that new image
#   5. Recreates all application roles from the same image
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

# ── Config ───────────────────────────────────────────────────────
COMPOSE="docker compose"
APP_SERVICE="app"
APP_IMAGE_REF="${APP_IMAGE_REF:-insurance2026-app:latest}"
RELEASE_ROOT="${RELEASE_ROOT:-/opt/insurance-releases}"

echo "══════════════════════════════════════════════════════════════"
echo "  Insurance 2026 — Deploy"
echo "══════════════════════════════════════════════════════════════"

# ── 1. Prove this is an immutable release source ─────────────────
echo "[1/7] Verifying clean detached release source..."
GIT_SHA="$(git rev-parse HEAD)"
RELEASE_DIR="$(pwd -P)"

case "$RELEASE_DIR" in
    "$RELEASE_ROOT"/"$GIT_SHA") ;;
    *)
        echo "  !! ERROR: Build directory must be $RELEASE_ROOT/$GIT_SHA"
        exit 1
        ;;
esac

if git symbolic-ref -q HEAD >/dev/null; then
    echo "  !! ERROR: Release worktree must use detached HEAD."
    exit 1
fi

if [ -n "$(git status --porcelain --untracked-files=all)" ]; then
    echo "  !! ERROR: Release source is not clean."
    git status --short
    exit 1
fi

echo "  -> Release source verified: $GIT_SHA"

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
$COMPOSE build --no-cache --build-arg APP_BUILD_SHA="$GIT_SHA" app
# `docker compose images -q app` reports the image used by the currently
# running container, which is intentionally still the previous release here.
# Inspect the freshly built service tag instead.
IMAGE_SHA="$(docker image inspect --format='{{.Id}}' "$APP_IMAGE_REF")"
IMAGE_REVISION="$(docker image inspect --format='{{ index .Config.Labels "org.opencontainers.image.revision" }}' "$IMAGE_SHA")"
if [ "$IMAGE_REVISION" != "$GIT_SHA" ]; then
    echo "  !! ERROR: Image revision $IMAGE_REVISION does not match release $GIT_SHA"
    exit 1
fi
echo "  -> Built $IMAGE_SHA from commit $GIT_SHA"

# ── 5. Run migrations from the new image before switching roles ─
echo "[5/7] Checking and applying migrations from the new image..."
INS_BACKUP_DIR="${INS_BACKUP_DIR:-/opt/server-state-backups/database}" \
    bash docker/scripts/backup-db.sh
$COMPOSE run --rm --no-deps "$APP_SERVICE" sh -lc '
    test "$APP_BUILD_SHA" = "'"$GIT_SHA"'" || exit 91
    php artisan migrate:status --no-interaction
    php artisan migrate --pretend --no-interaction
'
$COMPOSE run --rm --no-deps "$APP_SERVICE" sh -lc '
    test "$APP_BUILD_SHA" = "'"$GIT_SHA"'" || exit 91
    php artisan migrate --force --no-interaction
'

# ── 6. Start/restart ALL services from the same image ────────────
echo "[6/7] Starting services (force-recreate to pick up new image)..."
$COMPOSE up -d --no-build --force-recreate --remove-orphans

# ── 6b. Assert image-ID consistency (drift guard) ────────────────
echo "[6b] Verifying all PHP services use the same image..."
sleep 5
APP_IMAGE_ID=$(docker inspect --format='{{.Image}}' ins2026-app 2>/dev/null || echo "MISSING")
DRIFT_FOUND=0
for svc in ins2026-horizon ins2026-reverb ins2026-scheduler; do
    SVC_IMAGE_ID=$(docker inspect --format='{{.Image}}' "$svc" 2>/dev/null || echo "MISSING")
    if [ "$SVC_IMAGE_ID" != "$APP_IMAGE_ID" ]; then
        echo "  !! DRIFT: $svc image ($SVC_IMAGE_ID) ≠ app ($APP_IMAGE_ID)"
        echo "  -> Forcing recreate of ${svc#ins2026-}..."
        $COMPOSE up -d --no-build --force-recreate "${svc#ins2026-}"
        DRIFT_FOUND=1
    fi
done
if [ "$DRIFT_FOUND" -eq 0 ]; then
    echo "  -> All services on same image: ${APP_IMAGE_ID:0:16}"
else
    # Re-verify after healing
    for svc in ins2026-horizon ins2026-reverb ins2026-scheduler; do
        SVC_IMAGE_ID=$(docker inspect --format='{{.Image}}' "$svc" 2>/dev/null || echo "MISSING")
        if [ "$SVC_IMAGE_ID" != "$APP_IMAGE_ID" ]; then
            echo "  !! FATAL: $svc still on wrong image after retry. Deploy FAILED."
            exit 1
        fi
    done
    echo "  -> Drift healed. All services now consistent."
fi

# ── 7. Finalize application runtime ──────────────────────────────
echo "[7/7] Finalizing application runtime..."
$COMPOSE exec "$APP_SERVICE" php artisan storage:link 2>/dev/null || true

# Horizon was recreated from the new image; terminate workers once so the
# supervisor proves it can restart them cleanly.
echo "  -> Terminating Horizon workers once..."
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
