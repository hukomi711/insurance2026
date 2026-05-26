#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

ADMIN_PASS="$(
  grep '^ADMIN_PASSWORD=' .env.production \
    | sed -e 's/^ADMIN_PASSWORD=//' -e "s/^['\"]//;s/['\"]$//"
)"

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
  curl -skS -X POST 'https://tamminzonlinez.online/api/admin/login' \
    -H 'Accept: application/json' \
    -H 'Content-Type: application/json' \
    --data "{\"email\":\"dr@tamminzonlinez.online\",\"password\":\"${ADMIN_PASS}\"}"
)"

after_count="$(
  docker exec ins2026-app php artisan tinker --execute='
use App\Models\AdminLoginCode;
echo AdminLoginCode::count();
'
)"

printf '%s\n' "$response"
printf 'codes_before=%s codes_after=%s\n' "$before_count" "$after_count"
