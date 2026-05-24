#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Insurance 2026 — Path B: VPS Side - Extract Archive and Deploy
#
# Run this ON THE VPS after archive is uploaded.
#
# This script:
# 1. Extracts archive to /opt/insurance2026
# 2. Sets up .env.production (template + secrets)
# 3. Builds and starts Docker containers
# 4. Runs migrations
# 5. Fixes .env ownership
#
# Usage (on VPS):
#   cd /opt && bash insurance2026/deploy-extract-and-build.sh
#
# Prerequisites (must exist on VPS):
#   - /opt/insurance2026-upload.tar.gz (uploaded via scp)
#   - domain: lwxustotamin.online (DNS already configured)
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

ARCHIVE_PATH="/opt/insurance2026-upload.tar.gz"
DEPLOY_DIR="/opt/insurance2026"
DOMAIN="lwxustotamin.online"
WWW_DOMAIN="www.${DOMAIN}"
CERTBOT_SAVED_DIR="/tmp/insurance2026.certbot.backup"

echo "═══════════════════════════════════════════════════════════════"
echo "Insurance 2026 — VPS Deployment (Path B - Extract & Build)"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# ───── 0. Pre-flight ─────
echo "[0/7] Pre-flight checks..."

# Verify archive exists
if [[ ! -f "$ARCHIVE_PATH" ]]; then
  echo "✗ Archive not found: $ARCHIVE_PATH"
  exit 1
fi

# Verify Docker is installed
if ! command -v docker >/dev/null; then
  echo "ℹ Docker not installed. Installing..."
  dnf -y install dnf-plugins-core >/dev/null
  dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo >/dev/null
  dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin >/dev/null
  systemctl enable --now docker
  echo "✓ Docker installed"
fi

echo "✓ Archive exists: $ARCHIVE_PATH"
echo "✓ Docker available: $(docker --version)"
echo ""

# ───── 1. Extract archive ─────
echo "[1/7] Extracting archive to $DEPLOY_DIR..."

rm -rf "$CERTBOT_SAVED_DIR"
if [[ -d "${DEPLOY_DIR}/docker/certbot" ]]; then
  cp -a "${DEPLOY_DIR}/docker/certbot" "$CERTBOT_SAVED_DIR"
elif [[ -d "${DEPLOY_DIR}.backup/docker/certbot" ]]; then
  cp -a "${DEPLOY_DIR}.backup/docker/certbot" "$CERTBOT_SAVED_DIR"
fi

if [[ -d "$DEPLOY_DIR" ]]; then
  # Backup existing if it exists
  if [[ -d "${DEPLOY_DIR}.backup" ]]; then
    rm -rf "${DEPLOY_DIR}.backup"
  fi
  mv "$DEPLOY_DIR" "${DEPLOY_DIR}.backup"
  echo "  ℹ Backed up existing to ${DEPLOY_DIR}.backup"
fi

mkdir -p "$DEPLOY_DIR"
tar -xzf "$ARCHIVE_PATH" -C "$DEPLOY_DIR"
echo "✓ Archive extracted"
echo ""

# ───── 2. Setup .env.production ─────
echo "[2/7] Setting up .env.production..."

ENV_TEMPLATE="${DEPLOY_DIR}/.env.production.example"
ENV_FILE="${DEPLOY_DIR}/.env.production"
ENV_BACKUP_FILE="${DEPLOY_DIR}.backup/.env.production"
ENV_SAVED_FILE="/tmp/insurance2026.env.production.backup"
DB_SECRET_BACKUP_FILE="${DEPLOY_DIR}.backup/docker/secrets/db_password.txt"
DB_ROOT_SECRET_BACKUP_FILE="${DEPLOY_DIR}.backup/docker/secrets/db_root_password.txt"
CERTBOT_BACKUP_DIR="${DEPLOY_DIR}.backup/docker/certbot"

set_env_value() {
  local file="$1"
  local key="$2"
  local value="$3"
  local tmp
  tmp="$(mktemp)"
  awk -v key="$key" -v value="$value" '
    BEGIN { updated = 0 }
    $0 ~ "^" key "=" {
      print key "=" value
      updated = 1
      next
    }
    { print }
    END {
      if (updated == 0) {
        print key "=" value
      }
    }
  ' "$file" > "$tmp"
  cat "$tmp" > "$file"
  rm -f "$tmp"
}

if [[ ! -f "$ENV_TEMPLATE" ]]; then
  echo "✗ .env.production.example not found in archive"
  exit 1
fi

if [[ -f "$ENV_SAVED_FILE" ]]; then
  cp "$ENV_SAVED_FILE" "$ENV_FILE"
elif [[ -f "$ENV_BACKUP_FILE" ]]; then
  cp "$ENV_BACKUP_FILE" "$ENV_FILE"
elif [[ ! -f "$ENV_FILE" ]]; then
  cp "$ENV_TEMPLATE" "$ENV_FILE"
fi

# Substitute __DOMAIN__ placeholder
sed -i "s|__DOMAIN__|${DOMAIN}|g" "$ENV_FILE"

# Keep the existing database secret stable across redeploys. The MariaDB data
# volume survives extraction, so replacing DB_PASSWORD from an older env backup
# breaks app authentication against the existing database user.
if [[ -s "$DB_SECRET_BACKUP_FILE" ]]; then
  DB_PASSWORD_VALUE="$(tr -d '\r\n' < "$DB_SECRET_BACKUP_FILE")"
  set_env_value "$ENV_FILE" "DB_PASSWORD" "$DB_PASSWORD_VALUE"
fi

# Verify no placeholders remain
if grep -q '__DOMAIN__' "$ENV_FILE"; then
  echo "✗ .env.production still contains __DOMAIN__ placeholder"
  exit 1
fi

# Verify no CHANGE_ME remain (except in comments)
if grep -E '^[[:space:]]*[^#[:space:]].*CHANGE_ME' "$ENV_FILE" >/dev/null; then
  echo "⚠ .env.production contains CHANGE_ME placeholders:"
  grep -nE '^[[:space:]]*[^#[:space:]].*CHANGE_ME' "$ENV_FILE"
  echo ""
  echo "You must fill these values before proceeding:"
  echo "  • MAIL_* (email/SMTP config)"
  echo "  • IP_API_KEY, NEXAFLOW_API_KEY, NEXAFLOW_WEBSITE_ID (API keys)"
  echo ""
  read -p "Continue anyway? [y/N] " -n 1 -r
  echo
  [[ $REPLY =~ ^[Yy]$ ]] || exit 1
fi

chmod 600 "$ENV_FILE"

# Copy to .env for docker compose
cp "$ENV_FILE" "${DEPLOY_DIR}/.env"
chmod 600 "${DEPLOY_DIR}/.env"

echo "✓ .env.production configured"
echo ""

# ───── 3. Extract DB password to Docker secrets ─────
echo "[3/7] Setting up Docker secrets..."

mkdir -p "${DEPLOY_DIR}/docker/secrets"

# Preserve existing DB secrets when the database volume already exists.
if [[ -s "$DB_SECRET_BACKUP_FILE" ]]; then
  cp "$DB_SECRET_BACKUP_FILE" "${DEPLOY_DIR}/docker/secrets/db_password.txt"
else
  grep '^DB_PASSWORD=' "$ENV_FILE" \
    | sed -e 's/^DB_PASSWORD=//' -e "s/^['\"]//;s/['\"]$//" \
    > "${DEPLOY_DIR}/docker/secrets/db_password.txt"
fi

if [[ ! -s "${DEPLOY_DIR}/docker/secrets/db_password.txt" ]]; then
  echo "✗ DB_PASSWORD is empty in .env.production"
  exit 1
fi

# Generate root password if not present
if [[ -s "$DB_ROOT_SECRET_BACKUP_FILE" ]]; then
  cp "$DB_ROOT_SECRET_BACKUP_FILE" "${DEPLOY_DIR}/docker/secrets/db_root_password.txt"
elif [[ ! -s "${DEPLOY_DIR}/docker/secrets/db_root_password.txt" ]]; then
  openssl rand -hex 32 > "${DEPLOY_DIR}/docker/secrets/db_root_password.txt"
fi

chmod 600 "${DEPLOY_DIR}/docker/secrets"/*.txt

echo "✓ Docker secrets configured"
echo ""

# ───── 4. Issue SSL certificate ─────
echo "[4/7] Issuing SSL certificate via Let's Encrypt..."

cd "$DEPLOY_DIR"

mkdir -p docker/certbot/conf docker/certbot/www

if [[ -d "$CERTBOT_SAVED_DIR" && ! -f "docker/certbot/conf/live/${DOMAIN}/fullchain.pem" ]]; then
  cp -a "${CERTBOT_SAVED_DIR}/." "docker/certbot/"
elif [[ -d "$CERTBOT_BACKUP_DIR" && ! -f "docker/certbot/conf/live/${DOMAIN}/fullchain.pem" ]]; then
  cp -a "${CERTBOT_BACKUP_DIR}/." "docker/certbot/"
fi

issue_standalone_cert() {
  local cert_name="$1"
  local domain_name="$2"

  echo "  Issuing cert for ${domain_name}..."

  # Stop nginx if running
  docker compose stop nginx 2>/dev/null || true

  set +e
  docker run --rm -p 80:80 \
    -v "$PWD/docker/certbot/conf:/etc/letsencrypt" \
    -v "$PWD/docker/certbot/www:/var/www/certbot" \
    certbot/certbot certonly --standalone --non-interactive --agree-tos \
    --cert-name "$cert_name" \
    -m "admin@${DOMAIN}" \
    -d "$domain_name" 2>&1 | grep -v "^Saving debug log"
  CERTBOT_STATUS=${PIPESTATUS[0]}
  set -e

  if [[ $CERTBOT_STATUS -ne 0 ]]; then
    echo "✗ Certificate issuance failed for ${domain_name}; refusing to deploy a self-signed HTTPS fallback."
    exit $CERTBOT_STATUS
  fi

  echo "✓ Certificate issued for ${domain_name}"
}

if [[ ! -f "docker/certbot/conf/live/${DOMAIN}/fullchain.pem" ]]; then
  issue_standalone_cert "$DOMAIN" "$DOMAIN"
else
  echo "✓ Certificate already exists for $DOMAIN"
fi

if [[ ! -f "docker/certbot/conf/live/${WWW_DOMAIN}/fullchain.pem" ]]; then
  issue_standalone_cert "$WWW_DOMAIN" "$WWW_DOMAIN"
else
  echo "✓ Certificate already exists for $WWW_DOMAIN"
fi
echo ""

# ───── 5. Build and start containers ─────
echo "[5/7] Building and starting Docker containers..."
echo "  This may take 5-10 minutes..."
echo ""

cd "$DEPLOY_DIR"

docker compose pull 2>/dev/null || true
docker compose build --pull 2>&1 | tail -20

docker compose up -d
sleep 10

echo ""
echo "✓ Containers started"
docker compose ps
echo ""

# ───── 6. Run migrations ─────
echo "[6/7] Running database migrations..."

# Wait for DB to be ready
for i in $(seq 1 30); do
  if docker exec ins2026-db sh -c 'DB_PASS="$(cat /run/secrets/db_password)"; mariadb -uinsurance -p"$DB_PASS" insurance2026 -e "SELECT 1"' >/dev/null 2>&1; then
    echo "✓ Database ready"
    break
  fi
  if [[ $i -eq 30 ]]; then
    echo "✗ Database did not become ready"
    exit 1
  fi
  sleep 2
done

# Run migrations
docker exec ins2026-app php artisan migrate --force

# Clear and cache (but NOT config:cache, only config:clear)
docker exec ins2026-app php artisan optimize:clear
docker exec ins2026-app php artisan config:clear
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan event:cache

echo "✓ Migrations complete"
echo ""

# ───── 7. Fix .env ownership ─────
echo "[7/7] Fixing .env ownership in containers..."

for SERVICE in app horizon reverb scheduler; do
  docker exec "ins2026-${SERVICE}" \
    sh -c 'chown appuser:appuser /var/www/html/.env && chmod 640 /var/www/html/.env' 2>/dev/null || true
  echo "  ✓ ins2026-$SERVICE"
done

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "✓ DEPLOYMENT COMPLETE"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Application URL: https://${DOMAIN}"
echo "Login: https://${DOMAIN}/login"
echo ""
echo "Next steps:"
echo "  1. Update .env.production with missing values (mail, API keys)"
echo "  2. Recreate containers: docker compose up -d --force-recreate app horizon reverb scheduler"
echo "  3. View logs: docker logs -f ins2026-app"
echo ""
echo "Health checks:"
echo "  curl -s https://${DOMAIN}/api/health | jq ."
echo "  curl -s https://${DOMAIN}/api/health/queues | jq ."
echo ""
