#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Insurance 2026 — Path B: Upload Archive to VPS
#
# This script uploads the packaged archive to the VPS.
#
# Usage:
#   bash deploy/legacy/upload-to-vps.sh
#
# Prerequisites:
#   1. SSH key installed on VPS: ssh-copy-id -i ~/.ssh/insurance2026_deploy.pub root@66.29.142.104
#   2. insurance2026-vps.tar.gz created: bash deploy/legacy/package-for-vps.sh
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ARCHIVE_PATH="${SCRIPT_DIR}/insurance2026-vps.tar.gz"
SSH_KEY="${HOME}/.ssh/insurance2026_deploy"
SERVER_IP="66.29.142.104"
REMOTE_USER="root"
REMOTE_PATH="/opt/insurance2026-upload.tar.gz"

echo "═══════════════════════════════════════════════════════════════"
echo "Insurance 2026 — Uploading to VPS"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Verify archive exists
if [[ ! -f "$ARCHIVE_PATH" ]]; then
  echo "✗ Archive not found: $ARCHIVE_PATH"
  echo "  Run: bash deploy/legacy/package-for-vps.sh"
  exit 1
fi

# Verify SSH key exists
if [[ ! -f "$SSH_KEY" ]]; then
  echo "✗ SSH key not found: $SSH_KEY"
  exit 1
fi

ARCHIVE_SIZE=$(du -h "$ARCHIVE_PATH" | cut -f1)

echo "Archive: $ARCHIVE_PATH"
echo "Size:    $ARCHIVE_SIZE"
echo "Target:  $REMOTE_USER@$SERVER_IP:$REMOTE_PATH"
echo ""

# Test SSH connection
echo "Testing SSH connection..."
if ! ssh -i "$SSH_KEY" -o ConnectTimeout=10 "$REMOTE_USER@$SERVER_IP" 'echo "✓ SSH OK"' >/dev/null 2>&1; then
  echo "✗ SSH connection failed"
  echo "  Verify SSH key is installed:"
  echo "  ssh-copy-id -i ~/.ssh/insurance2026_deploy.pub root@$SERVER_IP"
  exit 1
fi
echo "✓ SSH connection verified"
echo ""

# Upload archive
echo "Uploading archive (~$ARCHIVE_SIZE)..."
scp -i "$SSH_KEY" "$ARCHIVE_PATH" "$REMOTE_USER@$SERVER_IP:$REMOTE_PATH"

if [[ $? -eq 0 ]]; then
  echo ""
  echo "═══════════════════════════════════════════════════════════════"
  echo "✓ Upload complete!"
  echo ""
  echo "Next: Extract and deploy on VPS"
  echo "  bash /opt/insurance2026/deploy/legacy/deploy-extract-and-build.sh"
  echo ""
  echo "Or manually SSH and run:"
  echo "  ssh -i ~/.ssh/insurance2026_deploy root@$SERVER_IP"
  echo "  bash /opt/insurance2026/deploy/legacy/deploy-extract-and-build.sh"
  echo "═══════════════════════════════════════════════════════════════"
else
  echo "✗ Upload failed"
  exit 1
fi
