#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — Upload Project to Server via SCP
# ═══════════════════════════════════════════════════════════════════
# Run from your LOCAL machine (Git Bash on Windows or any shell):
#   bash docker/scripts/upload-to-server.sh
#
# Prerequisites:
#   - SSH access to the server (root or deploy user)
#   - Server setup already done (setup-server.sh)
#   - INS_SERVER_IP env var exported (no hardcoded IPs in repo)
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

# ── Configuration ────────────────────────────────────────────────
SERVER_IP="${INS_SERVER_IP:?set INS_SERVER_IP env var (target deployment server)}"
SERVER_USER="${INS_DEPLOY_USER:-root}"
SERVER_DIR="${INS_DEPLOY_DIR:-/opt/insurance2026}"
ARCHIVE="insurance2026.tar.gz"

echo "══════════════════════════════════════════════════════════════"
echo "  Insurance 2026 — Upload to Server"
echo "══════════════════════════════════════════════════════════════"

# ── Find project root ────────────────────────────────────────────
# If running from scripts dir, go up to project root
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_ROOT"

if [ ! -f "docker-compose.yml" ]; then
    echo "!! ERROR: docker-compose.yml not found."
    echo "   Run this script from the project root or from docker/scripts/"
    exit 1
fi

echo "  Project root: $PROJECT_ROOT"
echo "  Target: ${SERVER_USER}@${SERVER_IP}:${SERVER_DIR}"
echo ""

# ── 0. Capture git SHA for build tracking ────────────────────
GIT_SHA=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
echo "$GIT_SHA" > .build-sha
echo "  Build SHA: $GIT_SHA"

# ── 1. Create archive ───────────────────────────────────────────
echo "[1/3] Creating archive (excluding dev files)..."
tar -czf "/tmp/$ARCHIVE" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.env.backup' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='public/build' \
    --exclude='public/hot' \
    --exclude='public/storage' \
    --exclude='_ide_helper*.php' \
    --exclude='.phpstorm.meta.php' \
    --exclude='insurance2026' \
    --exclude='db_result.txt' \
    --exclude='*.log' \
    --exclude='docker/secrets/db_password.txt' \
    --exclude='docker/secrets/db_root_password.txt' \
    --exclude='docker/certbot/conf/*' \
    --exclude='docker/certbot/www/*' \
    .

ARCHIVE_SIZE=$(du -h "/tmp/$ARCHIVE" | awk '{print $1}')
echo "  -> Archive created: $ARCHIVE_SIZE"

# ── 2. Upload to server ─────────────────────────────────────────
echo "[2/3] Uploading to $SERVER_USER@$SERVER_IP..."
ssh "$SERVER_USER@$SERVER_IP" "mkdir -p $SERVER_DIR"
scp "/tmp/$ARCHIVE" "$SERVER_USER@$SERVER_IP:$SERVER_DIR/$ARCHIVE"
echo "  -> Upload complete."

# ── 3. Extract on server ────────────────────────────────────────
echo "[3/3] Extracting on server..."
ssh "$SERVER_USER@$SERVER_IP" "cd $SERVER_DIR && tar -xzf $ARCHIVE && rm -f $ARCHIVE"
echo "  -> Extracted and archive cleaned up."

# ── Cleanup local temp ──────────────────────────────────────────
rm -f "/tmp/$ARCHIVE"

echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  Upload complete!"
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "  Next steps (on the server):"
echo "    ssh $SERVER_USER@$SERVER_IP"
echo "    cd $SERVER_DIR"
echo ""
echo "  If first time:"
echo "    bash docker/scripts/setup-server-ubuntu.sh"
echo "    bash docker/scripts/first-deploy.sh"
echo "    # provision SSL (see first-deploy.sh output)"
echo "    bash docker/scripts/deploy.sh"
echo ""
echo "  If updating:"
echo "    bash docker/scripts/deploy.sh"
echo "══════════════════════════════════════════════════════════════"
