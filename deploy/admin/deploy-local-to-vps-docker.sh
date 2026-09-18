#!/usr/bin/env bash
set -euo pipefail

INS_SERVER_IP="${INS_SERVER_IP:-203.161.38.43}"
INS_DOMAIN="${INS_DOMAIN:-taminatssak.com}"
INS_DEPLOY_DIR="${INS_DEPLOY_DIR:-/opt/insurance2026}"
INS_DEPLOY_USER="${INS_DEPLOY_USER:-root}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/insurance2026_deploy}"

WWW_DOMAIN="www.${INS_DOMAIN}"
BUILD_SHA="$(git rev-parse HEAD)"

SSH_OPTS=(
  -i "$SSH_KEY"
  -o IdentitiesOnly=yes
  -o StrictHostKeyChecking=no
  -o UserKnownHostsFile=/dev/null
)

ssh_cmd() {
  ssh "${SSH_OPTS[@]}" "${INS_DEPLOY_USER}@${INS_SERVER_IP}" "$@"
}

echo "[1/8] Local preflight"
test -f "$SSH_KEY" || {
  echo "ERROR: SSH key not found: $SSH_KEY"
  exit 1
}
chmod 600 "$SSH_KEY" || true

echo "[2/8] SSH connectivity"
ssh_cmd 'echo SSH_OK && hostname && whoami'

echo "[3/8] Upload local source archive"
tar \
  --exclude=.git \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=output \
  --exclude=.env \
  --exclude=.env.* \
  --exclude=docker/secrets \
  --exclude=storage/logs \
  --exclude=storage/framework/sessions \
  --exclude=storage/framework/views \
  --exclude=storage/framework/cache/data \
  -czf - . \
  | ssh "${SSH_OPTS[@]}" "${INS_DEPLOY_USER}@${INS_SERVER_IP}" 'cat > /tmp/insurance2026-local.tar.gz'

echo "[4/8] Activate release + preserve runtime state"
ssh "${SSH_OPTS[@]}" "${INS_DEPLOY_USER}@${INS_SERVER_IP}" \
  DEPLOY_DIR="$INS_DEPLOY_DIR" DOMAIN="$INS_DOMAIN" WWW_DOMAIN="$WWW_DOMAIN" BUILD_SHA="$BUILD_SHA" 'bash -s' <<'REMOTE'
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR}"
DOMAIN="${DOMAIN}"
WWW_DOMAIN="${WWW_DOMAIN}"
BUILD_SHA="${BUILD_SHA}"
TS="$(date +%Y%m%d%H%M%S)"
STAGE="${DEPLOY_DIR}.stage.${TS}"
BACKUP="${DEPLOY_DIR}.backup.${TS}"

mkdir -p "$STAGE"
tar -xzf /tmp/insurance2026-local.tar.gz -C "$STAGE"

if [[ -d "$DEPLOY_DIR" ]]; then
  mv "$DEPLOY_DIR" "$BACKUP"
fi
mv "$STAGE" "$DEPLOY_DIR"

if [[ -d "$BACKUP/storage" ]]; then
  mkdir -p "$DEPLOY_DIR/storage"
  cp -a "$BACKUP/storage/." "$DEPLOY_DIR/storage/"
fi
if [[ -d "$BACKUP/docker/secrets" ]]; then
  mkdir -p "$DEPLOY_DIR/docker/secrets"
  cp -a "$BACKUP/docker/secrets/." "$DEPLOY_DIR/docker/secrets/"
fi
if [[ -d "$BACKUP/docker/certbot" ]]; then
  mkdir -p "$DEPLOY_DIR/docker/certbot"
  cp -a "$BACKUP/docker/certbot/." "$DEPLOY_DIR/docker/certbot/"
fi

[[ -f "$BACKUP/.env" ]] && cp -a "$BACKUP/.env" "$DEPLOY_DIR/.env" || true
[[ -f "$BACKUP/.env.production" ]] && cp -a "$BACKUP/.env.production" "$DEPLOY_DIR/.env.production" || true

if [[ ! -f "$DEPLOY_DIR/.env.production" && -f "$DEPLOY_DIR/deploy/new-server/.env.production.template" ]]; then
  cp "$DEPLOY_DIR/deploy/new-server/.env.production.template" "$DEPLOY_DIR/.env.production"
fi
if [[ ! -f "$DEPLOY_DIR/.env.production" && -f "$DEPLOY_DIR/.env.production.example" ]]; then
  cp "$DEPLOY_DIR/.env.production.example" "$DEPLOY_DIR/.env.production"
fi

cd "$DEPLOY_DIR"
[[ -f .env.production ]] && cp .env.production .env
[[ -f .env ]] || { echo "ERROR: missing .env after activation"; exit 1; }

set_env_value() {
  local f="$1"
  local k="$2"
  local v="$3"
  if grep -q "^${k}=" "$f"; then
    sed -i "s#^${k}=.*#${k}=${v}#" "$f"
  else
    printf '%s=%s\n' "$k" "$v" >> "$f"
  fi
}

normalize_env_file() {
  local f="$1"
  set_env_value "$f" APP_URL "https://${DOMAIN}"
  set_env_value "$f" ASSET_URL "https://${DOMAIN}"
  set_env_value "$f" DOMAIN "${DOMAIN}"
  set_env_value "$f" SUPPORT_EMAIL_DOMAIN "${DOMAIN}"
  set_env_value "$f" SESSION_DOMAIN ".${DOMAIN}"
  set_env_value "$f" SANCTUM_STATEFUL_DOMAINS "${DOMAIN},${WWW_DOMAIN}"
  set_env_value "$f" CORS_ALLOWED_ORIGINS "https://${DOMAIN},https://${WWW_DOMAIN}"
  set_env_value "$f" REVERB_HOST "${DOMAIN}"
  set_env_value "$f" REVERB_ALLOWED_ORIGINS "https://${DOMAIN},https://${WWW_DOMAIN},http://${DOMAIN},http://${WWW_DOMAIN}"
  set_env_value "$f" VITE_REVERB_HOST "${DOMAIN}"
  # MAIL_USERNAME is intentionally NOT touched here: for Gmail SMTP it must
  # remain the actual Gmail account tied to MAIL_PASSWORD's app password,
  # not a noreply@domain alias. Only the visible From address changes.
  set_env_value "$f" MAIL_FROM_ADDRESS "noreply@${DOMAIN}"
  set_env_value "$f" ADMIN_EMAIL "admin@${DOMAIN}"
  set_env_value "$f" ADMIN_VERIFICATION_EMAIL "admin@${DOMAIN}"
  set_env_value "$f" APP_BUILD_SHA "${BUILD_SHA}"
  set_env_value "$f" MAIL_REQUIRE_TLS "true"
}

for f in .env .env.production; do
  [[ -f "$f" ]] || continue
  normalize_env_file "$f"
done

cp .env.production .env 2>/dev/null || true
chmod 600 .env .env.production 2>/dev/null || true

mkdir -p docker/secrets
if [[ ! -s docker/secrets/db_password.txt ]]; then
  pass="$(grep '^DB_PASSWORD=' .env.production | sed -e 's/^DB_PASSWORD=//' -e "s/^['\"]//;s/['\"]$//")"
  [[ -n "$pass" ]] || { echo "ERROR: DB_PASSWORD is empty"; exit 1; }
  printf '%s\n' "$pass" > docker/secrets/db_password.txt
fi
if [[ ! -s docker/secrets/db_root_password.txt ]]; then
  openssl rand -hex 32 > docker/secrets/db_root_password.txt
fi
chmod 600 docker/secrets/*.txt

echo "[5/8] SSL certificate"
mkdir -p docker/certbot/conf docker/certbot/www
if [[ ! -f "docker/certbot/conf/live/${DOMAIN}/fullchain.pem" ]]; then
  docker compose stop nginx >/dev/null 2>&1 || true
  docker run --rm -p 80:80 \
    -v "$PWD/docker/certbot/conf:/etc/letsencrypt" \
    -v "$PWD/docker/certbot/www:/var/www/certbot" \
    certbot/certbot certonly --standalone --non-interactive --agree-tos \
    -m "admin@${DOMAIN}" -d "${DOMAIN}" -d "${WWW_DOMAIN}"
fi

echo "[6/8] Build and start containers"
docker compose up -d --build --force-recreate --remove-orphans

echo "[7/8] Wait for app health + migrate"
for i in $(seq 1 90); do
  state="$(docker inspect -f '{{.State.Health.Status}}' ins2026-app 2>/dev/null || true)"
  if [[ "$state" == "healthy" ]]; then
    break
  fi
  if [[ "$i" -eq 90 ]]; then
    echo "ERROR: app did not become healthy"
    docker logs --tail=120 ins2026-app || true
    exit 1
  fi
  sleep 2
done

docker exec -u appuser ins2026-app php artisan migrate --force --no-interaction

echo "[8/8] Health checks"
for p in /api/health /api/health/queues /api/health/realtime; do
  code="$(curl -sS -o /tmp/health.json -w '%{http_code}' --resolve "${DOMAIN}:443:127.0.0.1" "https://${DOMAIN}${p}")"
  echo "local${p} => ${code}"
  cat /tmp/health.json
  echo
done

docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'ins2026-|NAMES'
echo "DEPLOY_SUCCESS domain=${DOMAIN} build=${BUILD_SHA}"
REMOTE

echo "Done."
