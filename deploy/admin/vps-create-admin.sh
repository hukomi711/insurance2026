#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

ADMIN_EMAIL_VALUE="$(
  { grep '^ADMIN_EMAIL=' .env.production || true; } \
    | sed -e 's/^ADMIN_EMAIL=//' -e "s/^['\"]//;s/['\"]$//" \
    | tr -d '\r'
)"

ADMIN_PASS="$(
  { grep '^ADMIN_PASSWORD=' .env.production || true; } \
    | sed -e 's/^ADMIN_PASSWORD=//' -e "s/^['\"]//;s/['\"]$//" \
    | tr -d '\r'
)"

if [[ -z "$ADMIN_EMAIL_VALUE" ]]; then
  echo "ADMIN_EMAIL_EMPTY"
  exit 1
fi

if [[ -z "$ADMIN_PASS" ]]; then
  echo "ADMIN_PASSWORD_EMPTY"
  exit 1
fi

docker exec \
  -e ADMIN_EMAIL_VALUE="$ADMIN_EMAIL_VALUE" \
  -e ADMIN_PASS="$ADMIN_PASS" \
  ins2026-app php artisan tinker --execute='
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::updateOrCreate(
    ["email" => getenv("ADMIN_EMAIL_VALUE")],
    [
        "name" => "مدير النظام",
        "password" => Hash::make(getenv("ADMIN_PASS")),
        "role" => "admin",
    ]
);

echo $user->email . " role=" . $user->role . PHP_EOL;
'

unset ADMIN_EMAIL_VALUE
unset ADMIN_PASS
