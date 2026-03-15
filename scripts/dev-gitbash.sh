#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "[dev] Starting Laravel server..."
php artisan serve &
LARAVEL_PID=$!

cleanup() {
  echo ""
  echo "[dev] Stopping Laravel server..."
  kill "$LARAVEL_PID" >/dev/null 2>&1 || true
}

trap cleanup EXIT INT TERM

echo "[dev] Starting Vite dev server..."
npm run dev
