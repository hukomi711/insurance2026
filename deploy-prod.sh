#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Insurance 2026 — Production Deploy Helper
#
# Usage: bash deploy-prod.sh <github-url>
# Example:
#   bash deploy-prod.sh git@github.com:tegarahowner-ui/insurance2026.git
#
# Prerequisites:
#   1. SSH key must exist: ~/.ssh/insurance2026_deploy
#   2. Public key must be installed on VPS root user
#   3. DNS must resolve tamiikom.online to 162.254.35.48
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

GITHUB_URL="${1:?Usage: bash deploy-prod.sh <github-url>}"

# Configuration
INS_SERVER_IP="162.254.35.48"
INS_DOMAIN="tamiikom.online"
INS_REPO_URL="$GITHUB_URL"
INS_BRANCH="${INS_BRANCH:-main}"
SSH_KEY="${HOME}/.ssh/insurance2026_deploy"
INS_DEPLOY_USER="root"
INS_DEPLOY_DIR="/opt/insurance2026"

# Validate prerequisites
echo "Validating prerequisites..."
[[ -f "$SSH_KEY" ]] || { echo "✗ SSH key not found: $SSH_KEY"; exit 1; }
echo "✓ SSH key exists"

# Test SSH connection
echo "Testing SSH connection to $INS_SERVER_IP..."
ssh -i "$SSH_KEY" -o ConnectTimeout=10 root@"$INS_SERVER_IP" 'echo "✓ SSH OK"; uname -r' || {
  echo "✗ SSH connection failed. Make sure public key is installed."
  exit 1
}

# Test DNS resolution
echo "Checking DNS resolution for $INS_DOMAIN..."
RESOLVED="$(nslookup "$INS_DOMAIN" 8.8.8.8 2>/dev/null | awk '/^Name:/ {found=1; next} found && /^Address: / {print $2; exit}' || echo '')"
if [[ "$RESOLVED" == "$INS_SERVER_IP" ]]; then
  echo "✓ DNS resolves correctly"
else
  echo "⚠ DNS resolves to: $RESOLVED (expected: $INS_SERVER_IP)"
  echo "  This will cause Let's Encrypt certificate issuance to fail."
  read -p "  Continue anyway? [y/N] " -n 1 -r
  echo
  [[ $REPLY =~ ^[Yy]$ ]] || exit 1
fi

# Run deployment
echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Starting deployment to $INS_DOMAIN ($INS_SERVER_IP)"
echo "════════════════════════════════════════════════════════════════"
echo ""

cd "$(dirname "$0")"
export INS_SERVER_IP INS_DOMAIN INS_REPO_URL INS_BRANCH SSH_KEY INS_DEPLOY_USER INS_DEPLOY_DIR

bash deploy/new-server/deploy.sh

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "✓ Deployment complete!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Next steps:"
echo "  1. Update missing values in .env.production (mail, API keys)"
echo "  2. SSH to server: ssh -i $SSH_KEY root@$INS_SERVER_IP"
echo "  3. View logs: docker logs -f ins2026-app"
echo ""
