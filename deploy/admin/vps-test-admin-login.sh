#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

read_env_value() {
  local key="$1"
  { grep "^${key}=" .env.production || true; } \
    | sed -e "s/^${key}=//" -e "s/^['\"]//;s/['\"]$//" \
    | tr -d '\r'
}

APP_URL_VALUE="$(read_env_value APP_URL)"
ADMIN_EMAIL_VALUE="$(read_env_value ADMIN_EMAIL)"
ADMIN_PASS="$(
  read_env_value ADMIN_PASSWORD
)"

if [[ -z "$APP_URL_VALUE" ]]; then
  echo "APP_URL_EMPTY"
  exit 1
fi

if [[ -z "$ADMIN_EMAIL_VALUE" ]]; then
  echo "ADMIN_EMAIL_EMPTY"
  exit 1
fi

if [[ -z "$ADMIN_PASS" ]]; then
  echo "ADMIN_PASSWORD_EMPTY"
  exit 1
fi

before_count="$(
  docker exec ins2026-app php artisan tinker --execute='
use App\Models\AdminLoginCode;
echo AdminLoginCode::count();
'
)"

response="$(
  curl -skS -X POST "${APP_URL_VALUE%/}/api/admin/login" \
    -H 'Accept: application/json' \
    --data-urlencode "email=${ADMIN_EMAIL_VALUE}" \
    --data-urlencode "password=${ADMIN_PASS}"
)"

after_count="$(
  docker exec ins2026-app php artisan tinker --execute='
use App\Models\AdminLoginCode;
echo AdminLoginCode::count();
'
)"

printf '%s\n' "$response"
printf 'codes_before=%s codes_after=%s\n' "$before_count" "$after_count"

unset APP_URL_VALUE
unset ADMIN_EMAIL_VALUE
unset ADMIN_PASS
