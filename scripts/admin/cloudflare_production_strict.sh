#!/usr/bin/env bash
set -euo pipefail

# Production Strict profile for Cloudflare (safe-by-default)
# ----------------------------------------------------------
# Required:
#   CF_TOKEN=... ZONE_ID=...
# Optional:
#   APEX=lexusforbon.com
#   WWW=www.lexusforbon.com
#   ORIGIN_IP=209.74.64.215
#   HEALTH_URL=https://lexusforbon.com/api/health
#   CF_PROXY_MODE=on|off            (default: on)
#   BROWSER_CACHE_TTL=43200         (default: 12h)
#   ATTACK_MODE=0|1                 (default: 0)  # 1 => under_attack
#   PURGE_AFTER=1|0                 (default: 1)
#
# Example:
#   CF_TOKEN='***' ZONE_ID='***' bash scripts/admin/cloudflare_production_strict.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ADV_SCRIPT="$SCRIPT_DIR/cloudflare_hardening_advanced.sh"
POSTCHECK_SCRIPT="$SCRIPT_DIR/cloudflare_postcheck.sh"

CF_TOKEN="${CF_TOKEN:-}"
ZONE_ID="${ZONE_ID:-}"
APEX="${APEX:-lexusforbon.com}"
WWW="${WWW:-www.lexusforbon.com}"
ORIGIN_IP="${ORIGIN_IP:-209.74.64.215}"
HEALTH_URL="${HEALTH_URL:-https://lexusforbon.com/api/health}"
CF_PROXY_MODE="${CF_PROXY_MODE:-on}"
BROWSER_CACHE_TTL="${BROWSER_CACHE_TTL:-43200}"
ATTACK_MODE="${ATTACK_MODE:-0}"
PURGE_AFTER="${PURGE_AFTER:-1}"

if [[ -z "$CF_TOKEN" || -z "$ZONE_ID" ]]; then
  echo "ERROR: CF_TOKEN and ZONE_ID are required" >&2
  exit 1
fi

if [[ "$CF_PROXY_MODE" != "on" && "$CF_PROXY_MODE" != "off" ]]; then
  echo "ERROR: CF_PROXY_MODE must be on|off" >&2
  exit 1
fi

if [[ "$ATTACK_MODE" != "0" && "$ATTACK_MODE" != "1" ]]; then
  echo "ERROR: ATTACK_MODE must be 0|1" >&2
  exit 1
fi

log() { echo "[STRICT] $*"; }
warn() { echo "[STRICT][WARN] $*"; }

check_health() {
  local body
  body=$(curl -sS -m 20 "$HEALTH_URL" || true)
  if [[ "$body" == *'"ok":true'* ]]; then
    log "Health OK"
    return 0
  fi

  warn "Health check failed after phase"
  echo "$body" | head -c 300; echo
  echo "Rollback hint:"
  echo "  CF_TOKEN='***' ZONE_ID='$ZONE_ID' PHASE=dns CF_PROXY_MODE=off APEX='$APEX' WWW='$WWW' ORIGIN_IP='$ORIGIN_IP' bash '$ADV_SCRIPT'"
  echo "  CF_TOKEN='***' ZONE_ID='$ZONE_ID' PHASE=security SECURITY_LEVEL=medium bash '$ADV_SCRIPT'"
  return 1
}

run_phase() {
  local phase="$1"
  shift
  log "Running phase: $phase"
  CF_TOKEN="$CF_TOKEN" \
  ZONE_ID="$ZONE_ID" \
  APEX="$APEX" \
  WWW="$WWW" \
  ORIGIN_IP="$ORIGIN_IP" \
  HEALTH_URL="$HEALTH_URL" \
  PHASE="$phase" \
  "$@" \
  bash "$ADV_SCRIPT"
}

log "Profile: production strict"
log "Settings: proxy=$CF_PROXY_MODE attack_mode=$ATTACK_MODE browser_cache_ttl=$BROWSER_CACHE_TTL purge_after=$PURGE_AFTER"

run_phase precheck env

run_phase dns env CF_PROXY_MODE="$CF_PROXY_MODE"
check_health

run_phase tls env
check_health

if [[ "$ATTACK_MODE" == "1" ]]; then
  run_phase security env SECURITY_LEVEL=under_attack
else
  run_phase security env SECURITY_LEVEL=high
fi
check_health

run_phase performance env
run_phase cache env BROWSER_CACHE_TTL="$BROWSER_CACHE_TTL"

if [[ "$PURGE_AFTER" == "1" ]]; then
  run_phase purge env
fi

run_phase postcheck env

if [[ -x "$POSTCHECK_SCRIPT" ]]; then
  log "Running extra postcheck script"
  DOMAIN="$APEX" WWW="$WWW" bash "$POSTCHECK_SCRIPT" || true
fi

log "DONE: production strict profile completed"
