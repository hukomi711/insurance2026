#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

APP_PASS="$(tr -d '\r\n' < docker/secrets/db_password.txt)"
ROOT_PASS="$(tr -d '\r\n' < docker/secrets/db_root_password.txt)"

docker compose stop app horizon reverb scheduler nginx db
docker rm -f ins2026-db-recovery >/dev/null 2>&1 || true

docker run -d \
  --name ins2026-db-recovery \
  --network insurance2026_backend \
  -v insurance2026_db-data:/var/lib/mysql \
  mariadb:11-jammy \
  --skip-grant-tables \
  --skip-networking=0

for _ in $(seq 1 30); do
  if docker exec ins2026-db-recovery mariadb -uroot -e 'select 1' >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

docker exec \
  -e ROOT_PASS="$ROOT_PASS" \
  -e APP_PASS="$APP_PASS" \
  ins2026-db-recovery \
  sh -lc 'mariadb -uroot <<SQL
FLUSH PRIVILEGES;
ALTER USER '\''root'\''@'\''localhost'\'' IDENTIFIED BY '\'''"$ROOT_PASS"''\'';
CREATE USER IF NOT EXISTS '\''insurance'\''@'\''%'\'' IDENTIFIED BY '\'''"$APP_PASS"''\'';
ALTER USER '\''insurance'\''@'\''%'\'' IDENTIFIED BY '\'''"$APP_PASS"''\'';
GRANT ALL PRIVILEGES ON insurance2026.* TO '\''insurance'\''@'\''%'\'';
FLUSH PRIVILEGES;
SQL'

docker rm -f ins2026-db-recovery
docker compose up -d db redis

for _ in $(seq 1 30); do
  if docker compose exec -T db mariadb -uinsurance -p"$APP_PASS" insurance2026 -e 'select 1' >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

docker compose exec -T db mariadb -uinsurance -p"$APP_PASS" insurance2026 -e 'select 1 as ok'
docker compose up -d app horizon reverb scheduler nginx
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear
