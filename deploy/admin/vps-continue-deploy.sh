#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="/opt/insurance2026"
DOMAIN="lexusforbon.com"
WWW_DOMAIN="www.${DOMAIN}"
ENV_FILE="${DEPLOY_DIR}/.env.production"
DB_VOLUME="insurance2026_db-data"

cd "$DEPLOY_DIR"

echo "[1/7] Validate uploaded .env.production"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE"
  exit 1
fi

if grep -E '^[[:space:]]*[^#[:space:]].*CHANGE_ME' "$ENV_FILE" >/dev/null; then
  echo "ENV_HAS_CHANGE_ME"
  grep -nE '^[[:space:]]*[^#[:space:]].*CHANGE_ME' "$ENV_FILE"
  exit 1
fi

DB_PASSWORD_VALUE="$(grep '^DB_PASSWORD=' "$ENV_FILE" | sed -e 's/^DB_PASSWORD=//' -e "s/^['\"]//;s/['\"]$//")"
if [[ -z "$DB_PASSWORD_VALUE" ]]; then
  echo "DB_PASSWORD_EMPTY"
  exit 1
fi

chmod 600 "$ENV_FILE"
cp "$ENV_FILE" "${DEPLOY_DIR}/.env"
chmod 600 "${DEPLOY_DIR}/.env"

echo "[2/7] Write Docker secrets"
mkdir -p "${DEPLOY_DIR}/docker/secrets"
printf '%s\n' "$DB_PASSWORD_VALUE" > "${DEPLOY_DIR}/docker/secrets/db_password.txt"
if [[ ! -s "${DEPLOY_DIR}/docker/secrets/db_root_password.txt" ]]; then
  openssl rand -hex 32 > "${DEPLOY_DIR}/docker/secrets/db_root_password.txt"
fi
chmod 600 "${DEPLOY_DIR}/docker/secrets"/*.txt

echo "[3/7] Reset new MariaDB volume"
docker compose down --remove-orphans || true
docker volume rm "$DB_VOLUME" 2>/dev/null || true

echo "[4/7] Build and start backend services"
docker compose build --pull app nginx 2>&1 | tail -80
docker compose up -d db redis app horizon reverb scheduler

echo "[5/7] Wait for database and app"
for i in $(seq 1 60); do
  if docker exec ins2026-db sh -c 'mariadb -uroot -p$(cat /run/secrets/db_root_password) -e "SELECT 1"' >/dev/null 2>&1; then
    echo "DB_READY"
    break
  fi
  if [[ $i -eq 60 ]]; then
    echo "DB_NOT_READY"
    docker compose ps
    exit 1
  fi
  sleep 2
done

for i in $(seq 1 60); do
  status="$(docker inspect -f '{{.State.Health.Status}}' ins2026-app 2>/dev/null || true)"
  if [[ "$status" == "healthy" ]]; then
    echo "APP_HEALTHY"
    break
  fi
  if [[ $i -eq 60 ]]; then
    echo "APP_NOT_HEALTHY"
    docker compose ps
    docker compose logs --tail=100 app
    exit 1
  fi
  sleep 2
done

echo "[6/7] Run migrations and Laravel caches"
docker exec ins2026-app php artisan migrate --force
docker exec ins2026-app php artisan optimize:clear
docker exec ins2026-app php artisan config:cache
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan event:cache

echo "[7/7] Issue SSL and start Docker nginx"
mkdir -p docker/certbot/conf docker/certbot/www
if [[ ! -f "docker/certbot/conf/live/${DOMAIN}/fullchain.pem" ]]; then
  systemctl stop nginx 2>/dev/null || true
  docker run --rm -p 80:80 \
    -v "$PWD/docker/certbot/conf:/etc/letsencrypt" \
    -v "$PWD/docker/certbot/www:/var/www/certbot" \
    certbot/certbot certonly --standalone --non-interactive --agree-tos \
    -m "admin@${DOMAIN}" \
    -d "$DOMAIN" -d "$WWW_DOMAIN" 2>&1 | grep -v '^Saving debug log'
fi

systemctl disable nginx 2>/dev/null || true
docker compose up -d nginx

for SERVICE in app horizon reverb scheduler; do
  docker exec "ins2026-${SERVICE}" \
    sh -c 'chown appuser:appuser /var/www/html/.env && chmod 640 /var/www/html/.env' 2>/dev/null || true
done

docker compose ps
echo "DEPLOYMENT_COMPLETE"
