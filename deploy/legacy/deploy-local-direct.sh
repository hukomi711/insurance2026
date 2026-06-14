#!/usr/bin/env bash
set -euo pipefail

INS_SERVER_IP="${INS_SERVER_IP:-66.29.142.104}"
INS_DOMAIN="${INS_DOMAIN:-tamnyfordr.online}"
INS_BRANCH_EXPECTED="${INS_BRANCH_EXPECTED:-hardening/clean-rebuild}"
EXPECTED_HEAD="${EXPECTED_HEAD:-2cd5f80}"

SSH_KEY="${SSH_KEY:-$HOME/.ssh/insurance2026_deploy}"
INS_DEPLOY_USER="${INS_DEPLOY_USER:-root}"
INS_DEPLOY_DIR="${INS_DEPLOY_DIR:-/opt/insurance2026}"
SMTP_IDENTITY="${SMTP_IDENTITY:-support@lexusforbon.it.com}"

TS="$(date +%Y%m%d%H%M%S)"
STAGE="/tmp/insurance2026-local-$TS"
BACKUP="${INS_DEPLOY_DIR}.backup.$TS"

echo "Checking local repository..."

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
CURRENT_HEAD="$(git rev-parse --short HEAD)"

if [[ "$CURRENT_BRANCH" != "$INS_BRANCH_EXPECTED" ]]; then
  echo "ERROR: Wrong branch: $CURRENT_BRANCH"
  echo "       Expected: $INS_BRANCH_EXPECTED"
  exit 1
fi

if [[ "$CURRENT_HEAD" != "$EXPECTED_HEAD" ]]; then
  echo "ERROR: Wrong HEAD: $CURRENT_HEAD"
  echo "       Expected: $EXPECTED_HEAD"
  exit 1
fi

if [[ ! -f "$SSH_KEY" ]]; then
  echo "ERROR: SSH key not found: $SSH_KEY"
  exit 1
fi

chmod 600 "$SSH_KEY" || true

echo "OK: Branch: $CURRENT_BRANCH"
echo "OK: HEAD: $CURRENT_HEAD"
echo "OK: Domain: $INS_DOMAIN"
echo "OK: Server: $INS_SERVER_IP"
echo ""
echo "Important: uncommitted working-tree changes will NOT be deployed."
echo "Deploying exact git HEAD only."
echo ""

echo "Testing SSH..."
ssh -i "$SSH_KEY" \
  -o IdentitiesOnly=yes \
  -o ConnectTimeout=10 \
  "$INS_DEPLOY_USER@$INS_SERVER_IP" \
  'echo "OK: SSH"; hostname'

echo ""
echo "Preparing remote stage: $STAGE"

ssh -i "$SSH_KEY" -o IdentitiesOnly=yes "$INS_DEPLOY_USER@$INS_SERVER_IP" "
  rm -rf '$STAGE'
  mkdir -p '$STAGE'
"

echo "Uploading git archive..."
git archive --format=tar HEAD | gzip -c | ssh -i "$SSH_KEY" -o IdentitiesOnly=yes "$INS_DEPLOY_USER@$INS_SERVER_IP" "
  tar -xzf - -C '$STAGE'
"

echo "Activating release on server..."

ssh -i "$SSH_KEY" -o IdentitiesOnly=yes "$INS_DEPLOY_USER@$INS_SERVER_IP" bash -s <<REMOTE
set -euo pipefail

DEPLOY_DIR="$INS_DEPLOY_DIR"
STAGE="$STAGE"
BACKUP="$BACKUP"
DOMAIN="$INS_DOMAIN"
SMTP_IDENTITY="$SMTP_IDENTITY"

echo "Stage: \$STAGE"
echo "Deploy dir: \$DEPLOY_DIR"
echo "Backup: \$BACKUP"

if [[ -d "\$DEPLOY_DIR" ]]; then
  echo "Backing up current deploy dir..."
  mv "\$DEPLOY_DIR" "\$BACKUP"
fi

mkdir -p "\$(dirname "\$DEPLOY_DIR")"
mv "\$STAGE" "\$DEPLOY_DIR"

echo "Preserving production environment files..."

if [[ -d "\$BACKUP" ]]; then
  [[ -f "\$BACKUP/.env" ]] && cp -a "\$BACKUP/.env" "\$DEPLOY_DIR/.env"
  [[ -f "\$BACKUP/.env.production" ]] && cp -a "\$BACKUP/.env.production" "\$DEPLOY_DIR/.env.production"

  if [[ -d "\$BACKUP/storage" ]]; then
    mkdir -p "\$DEPLOY_DIR/storage"
    cp -a "\$BACKUP/storage/." "\$DEPLOY_DIR/storage/"
  fi

  if [[ -d "\$BACKUP/docker/secrets" ]]; then
    mkdir -p "\$DEPLOY_DIR/docker/secrets"
    cp -a "\$BACKUP/docker/secrets/." "\$DEPLOY_DIR/docker/secrets/"
  fi

  if [[ -d "\$BACKUP/docker/certbot" ]]; then
    mkdir -p "\$DEPLOY_DIR/docker/certbot"
    cp -a "\$BACKUP/docker/certbot/." "\$DEPLOY_DIR/docker/certbot/"
  fi
fi

if [[ ! -f "\$DEPLOY_DIR/.env" && -f "\$DEPLOY_DIR/.env.production" ]]; then
  cp -a "\$DEPLOY_DIR/.env.production" "\$DEPLOY_DIR/.env"
fi

if [[ ! -f "\$DEPLOY_DIR/.env" ]]; then
  echo "ERROR: Missing production .env on server."
  echo "       Create \$DEPLOY_DIR/.env first, then rerun."
  exit 1
fi

echo "Checking domain values..."
set_env_value() {
  local env_file="\$1"
  local key="\$2"
  local value="\$3"

  if grep -q "^\$key=" "\$env_file"; then
    sed -i "s#^\$key=.*#\$key=\$value#" "\$env_file"
  else
    printf '%s=%s\n' "\$key" "\$value" >> "\$env_file"
  fi
}

normalize_domain_env() {
  local env_file="\$1"

  set_env_value "\$env_file" "APP_URL" "https://\$DOMAIN"
  set_env_value "\$env_file" "ASSET_URL" "https://\$DOMAIN"
  set_env_value "\$env_file" "DOMAIN" "\$DOMAIN"
  set_env_value "\$env_file" "SUPPORT_EMAIL_DOMAIN" "\$DOMAIN"
  set_env_value "\$env_file" "SESSION_DOMAIN" ".\$DOMAIN"
  set_env_value "\$env_file" "SANCTUM_STATEFUL_DOMAINS" "\$DOMAIN,www.\$DOMAIN"
  set_env_value "\$env_file" "CORS_ALLOWED_ORIGINS" "https://\$DOMAIN,https://www.\$DOMAIN"
  set_env_value "\$env_file" "REVERB_HOST" "\$DOMAIN"
  set_env_value "\$env_file" "REVERB_ALLOWED_ORIGINS" "https://\$DOMAIN,https://www.\$DOMAIN,http://\$DOMAIN,http://www.\$DOMAIN"
  set_env_value "\$env_file" "VITE_REVERB_HOST" "\$DOMAIN"
  set_env_value "\$env_file" "MAIL_USERNAME" "\$SMTP_IDENTITY"
  set_env_value "\$env_file" "MAIL_FROM_ADDRESS" "\$SMTP_IDENTITY"
  set_env_value "\$env_file" "ADMIN_VERIFICATION_EMAIL" "\$SMTP_IDENTITY"
}

for env_file in "\$DEPLOY_DIR/.env" "\$DEPLOY_DIR/.env.production"; do
  if [[ -f "\$env_file" ]]; then
    normalize_domain_env "\$env_file"
    grep -nE 'APP_URL|REVERB_HOST|VITE_REVERB_HOST' "\$env_file" || true
  fi
done

cd "\$DEPLOY_DIR"

echo "Building and recreating containers..."
docker compose up -d --build --force-recreate

echo "Fixing .env permissions inside app container..."
docker exec ins2026-app sh -lc '
  chown appuser:appuser /var/www/html/.env &&
  chmod 640 /var/www/html/.env
'

echo "Clearing Laravel config cache only..."
docker exec ins2026-app php artisan config:clear

echo "Running migrations..."
docker exec ins2026-app php artisan migrate --force

echo "Ensuring production admin user exists..."
docker exec ins2026-app php artisan db:seed --class=DatabaseSeeder --force

echo "Rebuilding safe Laravel caches..."
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan event:cache

echo "Containers:"
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'ins2026|NAMES'

echo "OK: Local direct deploy completed."
REMOTE

echo ""
echo "Running smoke tests..."
curl -Ik "https://$INS_DOMAIN/api/health" || true
curl -Ik "https://$INS_DOMAIN/api/health/realtime" || true

echo ""
echo "OK: Done."
