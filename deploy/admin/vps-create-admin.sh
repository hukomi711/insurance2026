#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

ADMIN_PASS="$(
  grep '^ADMIN_PASSWORD=' .env.production \
    | sed -e 's/^ADMIN_PASSWORD=//' -e "s/^['\"]//;s/['\"]$//" \
    | tr -d '\r'
)"

if [[ -z "$ADMIN_PASS" ]]; then
  echo "ADMIN_PASSWORD_EMPTY"
  exit 1
fi

docker exec -e ADMIN_PASS="$ADMIN_PASS" ins2026-app php artisan tinker --execute='
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::updateOrCreate(
    ["email" => "dr@tamnyfordr.online"],
    [
        "name" => "Dr",
        "password" => Hash::make(getenv("ADMIN_PASS")),
        "role" => "admin",
    ]
);

echo $user->email . " role=" . $user->role . PHP_EOL;
'

unset ADMIN_PASS
