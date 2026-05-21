#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

MAIL_USER="$(
  grep '^ADMIN_VERIFICATION_EMAIL=' .env.production \
    | sed -e 's/^ADMIN_VERIFICATION_EMAIL=//' -e "s/^['\"]//;s/['\"]$//"
)"

if [[ -z "$MAIL_USER" ]]; then
  echo "ADMIN_VERIFICATION_EMAIL_EMPTY"
  exit 1
fi

for env_file in .env.production .env; do
  if [[ -f "$env_file" ]]; then
    sed -i "s|^MAIL_USERNAME=.*|MAIL_USERNAME=${MAIL_USER}|" "$env_file"
    sed -i "s|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=${MAIL_USER}|" "$env_file"
  fi
done

for service in app horizon reverb scheduler; do
  if docker ps --format '{{.Names}}' | grep -qx "ins2026-${service}"; then
    docker exec "ins2026-${service}" sh -lc "sed -i 's|^MAIL_USERNAME=.*|MAIL_USERNAME=${MAIL_USER}|' /var/www/html/.env && sed -i 's|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=${MAIL_USER}|' /var/www/html/.env"
  fi
done

docker exec ins2026-app php artisan optimize:clear
docker exec ins2026-app php artisan config:clear

echo "MAIL_FIXED"
