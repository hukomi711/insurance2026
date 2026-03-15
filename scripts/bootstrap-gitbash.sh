#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[bootstrap] Project root: $ROOT_DIR"

require_cmd() {
  local cmd="$1"
  if ! command -v "$cmd" >/dev/null 2>&1; then
    echo "[bootstrap] ERROR: Missing required command: $cmd"
    exit 1
  fi
}

echo "[bootstrap] Checking required tools..."
require_cmd git
require_cmd php
require_cmd composer
require_cmd node
require_cmd npm

IS_WINDOWS_GIT_BASH="false"
case "$(uname -s)" in
  MINGW*|MSYS*|CYGWIN*) IS_WINDOWS_GIT_BASH="true" ;;
esac

if [[ ! -f .env && -f .env.example ]]; then
  echo "[bootstrap] Creating .env from .env.example"
  cp .env.example .env
fi

echo "[bootstrap] Installing PHP dependencies..."
if [[ "$IS_WINDOWS_GIT_BASH" == "true" ]]; then
  composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
else
  composer install
fi

echo "[bootstrap] Installing Node dependencies (lockfile)..."
npm ci

if [[ -f .env ]]; then
  if grep -Eq '^APP_KEY=$|^APP_KEY=""$' .env || ! grep -q '^APP_KEY=' .env; then
    echo "[bootstrap] Generating APP_KEY"
    php artisan key:generate --ansi
  fi
fi

echo "[bootstrap] Running migrations"
php artisan migrate --ansi

echo "[bootstrap] Ensuring storage symlink"
php artisan storage:link >/dev/null 2>&1 || true

echo "[bootstrap] Building frontend assets"
npm run build

echo ""
echo "[bootstrap] Done."
echo "Run app server: php artisan serve"
echo "Run dev assets: npm run dev"
