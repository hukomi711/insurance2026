#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Insurance 2026 — Path B: Local Archive Packaging
#
# This script packages the current local worktree into an archive suitable for
# uploading to the VPS. It excludes large/transient files and git data.
#
# Usage:
#   bash deploy/legacy/package-for-vps.sh
#
# Output:
#   insurance2026-vps.tar.gz (in current directory)
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
ARCHIVE_NAME="insurance2026-vps.tar.gz"
ARCHIVE_PATH="${SCRIPT_DIR}/${ARCHIVE_NAME}"
cd "$PROJECT_ROOT"

echo "═══════════════════════════════════════════════════════════════"
echo "Insurance 2026 — Packaging for VPS (Path B)"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Verify we're in the project root
if [[ ! -f composer.json ]] || [[ ! -f docker-compose.yml ]]; then
  echo "✗ Not in project root (missing composer.json or docker-compose.yml)"
  exit 1
fi

echo "📦 Packaging worktree..."
echo ""
echo "Includes:"
echo "  ✓ Source code (app/, config/, routes/, etc.)"
echo "  ✓ Frontend assets (resources/)"
echo "  ✓ public/build/ (Vite build output)"
echo "  ✓ Docker configuration (docker/, docker-compose.yml)"
echo "  ✓ Composer & npm lockfiles"
echo ""
echo "Excludes:"
echo "  ✗ .git/ (not needed for production)"
echo "  ✗ node_modules/ (rebuilt from lockfile)"
echo "  ✗ vendor/ (rebuilt from lockfile)"
echo "  ✗ storage/logs/ (transient)"
echo "  ✗ storage/framework/cache/ (transient)"
echo "  ✗ storage/framework/sessions/ (transient)"
echo "  ✗ storage/framework/views/ (transient)"
echo "  ✗ .env* (never shipped)"
echo "  ✗ bootstrap/cache/ (generated)"
echo ""

# Build tar with exclusions
tar -czf "$ARCHIVE_PATH" \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='todo-app' \
  --exclude='storage/logs' \
  --exclude='storage/framework/cache' \
  --exclude='storage/framework/sessions' \
  --exclude='storage/framework/views' \
  --exclude='.env' \
  --exclude='.env.production' \
  --exclude='bootstrap/cache' \
  --exclude='.phpunit.result.cache' \
  --exclude='insurance2026-vps.tar.gz' \
  --exclude='ali@Bon71' \
  --exclude='ali@Bon71.pub' \
  --exclude='.DS_Store' \
  --exclude='Thumbs.db' \
  --exclude='.vscode' \
  --exclude='.idea' \
  --exclude='*.swp' \
  --exclude='*.swo' \
  .

if [[ $? -eq 0 ]]; then
  SIZE=$(du -h "$ARCHIVE_PATH" | cut -f1)
  echo "✓ Archive created: $ARCHIVE_PATH"
  echo "  Size: $SIZE"
  echo ""
  echo "═══════════════════════════════════════════════════════════════"
  echo "✓ Ready to upload!"
  echo ""
  echo "Next: Run upload script"
  echo "  bash deploy/legacy/upload-to-vps.sh"
  echo ""
  echo "Or manually:"
  echo "  scp -i ~/.ssh/insurance2026_deploy $ARCHIVE_PATH root@66.29.142.104:/opt/"
  echo "═══════════════════════════════════════════════════════════════"
else
  echo "✗ Archive creation failed"
  exit 1
fi
