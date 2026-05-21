#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-/opt/insurance2026}"
BACKUP_DIR="${BACKUP_DIR:-/opt/insurance2026.backup.20260520171801}"
DOMAIN="${DOMAIN:-lexusforbon.it.com}"

cd "$DEPLOY_DIR"
TS="$(date +%Y%m%d%H%M%S)"

echo "== secrets =="
mkdir -p "$DEPLOY_DIR/docker/secrets"

if [[ -d "$BACKUP_DIR/docker/secrets" ]]; then
  cp -a "$BACKUP_DIR/docker/secrets/." "$DEPLOY_DIR/docker/secrets/"
  echo "copied secrets from $BACKUP_DIR"
else
  echo "backup secrets dir missing: $BACKUP_DIR/docker/secrets"
fi

if [[ ! -s docker/secrets/db_password.txt ]]; then
  grep '^DB_PASSWORD=' .env | cut -d= -f2- > docker/secrets/db_password.txt
  echo "created db_password.txt from .env"
fi

if [[ ! -s docker/secrets/db_root_password.txt ]]; then
  if grep -q '^DB_ROOT_PASSWORD=' .env; then
    grep '^DB_ROOT_PASSWORD=' .env | cut -d= -f2- > docker/secrets/db_root_password.txt
    echo "created db_root_password.txt from DB_ROOT_PASSWORD"
  elif grep -q '^MYSQL_ROOT_PASSWORD=' .env; then
    grep '^MYSQL_ROOT_PASSWORD=' .env | cut -d= -f2- > docker/secrets/db_root_password.txt
    echo "created db_root_password.txt from MYSQL_ROOT_PASSWORD"
  elif grep -q '^MARIADB_ROOT_PASSWORD=' .env; then
    grep '^MARIADB_ROOT_PASSWORD=' .env | cut -d= -f2- > docker/secrets/db_root_password.txt
    echo "created db_root_password.txt from MARIADB_ROOT_PASSWORD"
  else
    echo "ERROR: no root DB password variable found in .env"
    grep -nE 'DB_PASSWORD|DB_ROOT|MYSQL_ROOT|MARIADB_ROOT' .env || true
    exit 1
  fi
fi

chmod 600 docker/secrets/*.txt
ls -la docker/secrets
wc -c docker/secrets/db_password.txt docker/secrets/db_root_password.txt

echo "== env domain =="
cp .env ".env.bak.$TS"
for env_file in .env .env.production; do
  if [[ -f "$env_file" ]]; then
    cp "$env_file" "$env_file.bak.$TS"
    sed -i "s#https://tamiikom.online#https://$DOMAIN#g" "$env_file"
    sed -i "s#tamiikom.online#$DOMAIN#g" "$env_file"
    sed -i "s#tamiicom.site#$DOMAIN#g" "$env_file"
    sed -i "s#tamiikom.site#$DOMAIN#g" "$env_file"
    sed -i "s#https://tamlexus.sbs#https://$DOMAIN#g" "$env_file"
    sed -i "s#tamlexus.sbs#$DOMAIN#g" "$env_file"
    grep -nE 'APP_URL|REVERB_HOST|VITE_REVERB_HOST' "$env_file"
  fi
done

echo "== docker compose =="
docker compose up -d --force-recreate
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'ins2026|NAMES'

echo "== laravel =="
docker exec ins2026-app sh -lc 'chown appuser:appuser /var/www/html/.env && chmod 640 /var/www/html/.env'
docker exec ins2026-app php artisan config:clear
docker exec ins2026-app php artisan migrate --force
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan event:cache

echo "== health =="
curl -Ik "https://$DOMAIN/api/health" || true
curl -Ik "https://$DOMAIN/api/health/realtime" || true

echo "== logs =="
docker logs --tail=100 ins2026-app || true
docker logs --tail=100 ins2026-nginx || true
docker logs --tail=100 ins2026-reverb || true
docker logs --tail=100 ins2026-horizon || true

echo "OK: repair completed"
