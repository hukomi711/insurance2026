#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

APP_PASS="$(tr -d '\r\n' < docker/secrets/db_password.txt)"

set_db_password() {
  file="$1"
  tmp="$(mktemp)"
  awk -v pass="$APP_PASS" '
    BEGIN { updated = 0 }
    /^DB_PASSWORD=/ {
      print "DB_PASSWORD=" pass
      updated = 1
      next
    }
    { print }
    END {
      if (updated == 0) {
        print "DB_PASSWORD=" pass
      }
    }
  ' "$file" > "$tmp"
  cat "$tmp" > "$file"
  rm -f "$tmp"
  chmod 600 "$file"
}

set_db_password .env
set_db_password .env.production

docker compose up -d --force-recreate app horizon reverb scheduler nginx
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan route:clear
docker compose exec -T app php artisan view:clear
