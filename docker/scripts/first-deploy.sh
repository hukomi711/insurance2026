#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — First Deploy: Generate Secrets & Configure .env
# ═══════════════════════════════════════════════════════════════════
# Run once on the server BEFORE the first deploy.sh:
#   cd /opt/tamicomz
#   bash docker/scripts/first-deploy.sh
#
# What it does:
#   1. Generates Docker secrets (DB passwords)
#   2. Copies .env.production → .env
#   3. Auto-generates and fills all __CHANGE_ME__ values
#   4. Prints credentials for you to save
#
# After this script, you must:
#   1. Provision SSL certificates (certbot)
#   2. Copy certs to docker/certbot/conf/live/tamicomz.store/
#   3. Run: bash docker/scripts/deploy.sh
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

DOMAIN="tamicomz.store"

echo "══════════════════════════════════════════════════════════════"
echo "  Insurance 2026 — First Deploy Setup"
echo "══════════════════════════════════════════════════════════════"

# ── Sanity checks ────────────────────────────────────────────────
if [ ! -f .env.production ]; then
    echo "!! ERROR: .env.production not found."
    echo "   Are you in the project root? (/opt/tamicomz)"
    exit 1
fi

if [ -f .env ]; then
    echo "!! WARNING: .env already exists."
    read -rp "   Overwrite? (y/N): " answer
    if [[ ! "$answer" =~ ^[Yy]$ ]]; then
        echo "   Aborted."
        exit 0
    fi
fi

# ── 1. Generate Docker secrets ──────────────────────────────────
echo "[1/4] Generating Docker secrets..."
mkdir -p docker/secrets
DB_PASS=$(openssl rand -base64 24)
DB_ROOT_PASS=$(openssl rand -base64 24)
printf '%s' "$DB_PASS" > docker/secrets/db_password.txt
printf '%s' "$DB_ROOT_PASS" > docker/secrets/db_root_password.txt
chmod 600 docker/secrets/db_password.txt docker/secrets/db_root_password.txt
echo "  -> Docker secrets generated."

# ── 2. Copy .env.production → .env ──────────────────────────────
echo "[2/4] Creating .env from .env.production..."
cp .env.production .env

# ── 3. Generate and fill all __CHANGE_ME__ values ────────────────
echo "[3/4] Generating secure values..."

APP_KEY="base64:$(openssl rand -base64 32)"
REVERB_KEY=$(openssl rand -base64 24)
REVERB_SECRET=$(openssl rand -base64 32)
POLL_SECRET=$(openssl rand -base64 48)
ADMIN_PASS=$(openssl rand -base64 16)
MY_IP=$(curl -s --max-time 10 ifconfig.me || echo "DETECT_FAILED")

sed -i "s|base64:__CHANGE_ME__|${APP_KEY}|" .env
sed -i "s|__CHANGE_ME_REVERB_KEY__|${REVERB_KEY}|" .env
sed -i "s|__CHANGE_ME_REVERB_SECRET__|${REVERB_SECRET}|" .env
sed -i "s|__CHANGE_ME_POLL_SECRET__|${POLL_SECRET}|" .env
sed -i "s|__CHANGE_ME_ADMIN_PASSWORD__|${ADMIN_PASS}|" .env
sed -i "s|__CHANGE_ME_YOUR_IP__|${MY_IP}|" .env

# Also fill .env.production for Docker build (Vite reads VITE_* from it)
cp .env .env.production

echo "  -> All values generated and filled."

# ── 4. Validate no placeholders remain ──────────────────────────
echo "[4/4] Validating..."
REMAINING=$(grep -c "__CHANGE_ME" .env 2>/dev/null || true)
if [ "$REMAINING" -gt 0 ]; then
    echo "!! WARNING: $REMAINING unfilled placeholders remain in .env:"
    grep "__CHANGE_ME" .env
    echo ""
    echo "   Please fill them manually: nano .env"
else
    echo "  -> All placeholders filled successfully."
fi

# ── Print credentials ────────────────────────────────────────────
echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  ✅ First deploy setup complete!"
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "  ┌─────────────────────────────────────────────────────────┐"
echo "  │  SAVE THESE CREDENTIALS — SHOWN ONLY ONCE              │"
echo "  ├─────────────────────────────────────────────────────────┤"
echo "  │  APP_KEY:         ${APP_KEY}"
echo "  │  REVERB_KEY:      ${REVERB_KEY}"
echo "  │  REVERB_SECRET:   ${REVERB_SECRET}"
echo "  │  ADMIN_PASSWORD:  ${ADMIN_PASS}"
echo "  │  POLL_SECRET:     ${POLL_SECRET}"
echo "  │  DB_PASSWORD:     ${DB_PASS}"
echo "  │  DB_ROOT_PASS:    ${DB_ROOT_PASS}"
echo "  │  YOUR_IP:         ${MY_IP}"
echo "  └─────────────────────────────────────────────────────────┘"
echo ""
echo "  Next steps:"
echo "  ─────────────────────────────────────────────────────────"
echo "  1. Provision SSL certificate:"
echo ""
echo "     certbot certonly --standalone -d $DOMAIN -d www.$DOMAIN"
echo ""
echo "  2. Copy certificates to Docker mount path:"
echo ""
echo "     mkdir -p docker/certbot/conf/live/$DOMAIN/"
echo "     cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem docker/certbot/conf/live/$DOMAIN/"
echo "     cp /etc/letsencrypt/live/$DOMAIN/privkey.pem docker/certbot/conf/live/$DOMAIN/"
echo ""
echo "  3. Run the deployment:"
echo ""
echo "     bash docker/scripts/deploy.sh"
echo ""
echo "══════════════════════════════════════════════════════════════"
