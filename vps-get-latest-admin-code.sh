#!/usr/bin/env bash
set -euo pipefail

cd /opt/insurance2026

docker exec ins2026-app php artisan tinker --execute='
use App\Models\AdminLoginCode;

$code = AdminLoginCode::query()
    ->where("used", false)
    ->latest("id")
    ->first();

if (! $code) {
    echo "NO_ACTIVE_CODE" . PHP_EOL;
    return;
}

echo "code=" . $code->code . PHP_EOL;
echo "expires_at=" . $code->expires_at . PHP_EOL;
'
