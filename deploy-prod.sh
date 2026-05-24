#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Insurance 2026 — Production Deploy Helper
#
# Usage:
#   INS_SERVER_IP=<server-ip> INS_DOMAIN=<domain> INS_BRANCH=<branch> bash deploy-prod.sh <github-url>
# Example:
#   INS_SERVER_IP=69.57.161.222 INS_DOMAIN=lwxustotamin.online INS_BRANCH=hardening/clean-rebuild bash deploy-prod.sh git@github.com:tegarahowner-ui/insurance2026.git
#
# Prerequisites:
#   1. SSH key must exist: ~/.ssh/insurance2026_deploy
#   2. Public key must be installed on VPS root user
#   3. DNS must resolve $INS_DOMAIN to $INS_SERVER_IP
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

GITHUB_URL="${1:?Usage: INS_SERVER_IP=<server-ip> INS_DOMAIN=<domain> INS_BRANCH=<branch> bash deploy-prod.sh <github-url>}"

# Configuration
INS_SERVER_IP="${INS_SERVER_IP:-69.57.161.222}"
INS_DOMAIN="${INS_DOMAIN:-lwxustotamin.online}"
INS_REPO_URL="$GITHUB_URL"
INS_BRANCH="${INS_BRANCH:-hardening/clean-rebuild}"
SSH_KEY="${SSH_KEY:-${HOME}/.ssh/insurance2026_deploy}"
INS_DEPLOY_USER="${INS_DEPLOY_USER:-root}"
INS_DEPLOY_DIR="${INS_DEPLOY_DIR:-/opt/insurance2026}"

# Validate prerequisites
echo "Validating prerequisites..."
[[ -f "$SSH_KEY" ]] || { echo "✗ SSH key not found: $SSH_KEY"; exit 1; }
chmod 600 "$SSH_KEY" || true
echo "✓ SSH key exists"

# Test SSH connection
echo "Testing SSH connection to $INS_SERVER_IP..."
ssh -i "$SSH_KEY" \
  -o ConnectTimeout=10 \
  -o IdentitiesOnly=yes \
  "$INS_DEPLOY_USER@$INS_SERVER_IP" \
  'echo "✓ SSH OK"; uname -r' || {
  echo "✗ SSH connection failed. Make sure the public key is installed on the VPS user."
  exit 1
}

# Test DNS resolution
echo "Checking DNS resolution for $INS_DOMAIN..."
if command -v dig >/dev/null 2>&1; then
  RESOLVED_IPS="$(dig +short A "$INS_DOMAIN" @8.8.8.8 || true)"
else
  RESOLVED_IPS="$(nslookup "$INS_DOMAIN" 8.8.8.8 2>/dev/null | awk '/^Name:/ {found=1; next} found && /^Address: / {print $2}' || true)"
fi
if echo "$RESOLVED_IPS" | grep -qx "$INS_SERVER_IP"; then
  echo "✓ DNS resolves correctly"
else
  echo "⚠ DNS A records returned:"
  echo "${RESOLVED_IPS:-<empty>}"
  echo "Expected:"
  echo "$INS_SERVER_IP"
  echo ""
  echo "This may break Let's Encrypt HTTP-01 certificate issuance."
  read -r -p "Continue anyway? [y/N] " REPLY
  echo
  [[ "$REPLY" =~ ^[Yy]$ ]] || exit 1
fi

# Run deployment
echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Starting deployment"
echo "Domain: $INS_DOMAIN"
echo "Server: $INS_SERVER_IP"
echo "Branch: $INS_BRANCH"
echo "Deploy dir: $INS_DEPLOY_DIR"
echo "════════════════════════════════════════════════════════════════"
echo ""

cd "$(dirname "$0")"

[[ -f "deploy/new-server/deploy.sh" ]] || {
  echo "✗ Missing deploy/new-server/deploy.sh"
  exit 1
}

export INS_SERVER_IP INS_DOMAIN INS_REPO_URL INS_BRANCH SSH_KEY INS_DEPLOY_USER INS_DEPLOY_DIR

bash deploy/new-server/deploy.sh

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "✓ Deployment complete"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Next checks:"
echo "  ssh -i $SSH_KEY $INS_DEPLOY_USER@$INS_SERVER_IP"
echo "  cd $INS_DEPLOY_DIR"
echo "  docker ps"
echo "  docker logs -f ins2026-app"
echo "  docker logs -f ins2026-nginx"
echo "  curl -I https://$INS_DOMAIN/api/health"
echo "  curl -I https://$INS_DOMAIN/api/health/realtime"
echo ""
