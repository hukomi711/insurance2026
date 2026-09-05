#!/usr/bin/env bash
set -Eeuo pipefail

# Cloudflare production profile with one-host canary and automatic rollback.
# No production mutation is allowed unless ALLOW_PRODUCTION_CHANGE=1 is set.
# Required env: CF_TOKEN, ZONE_ID
# Optional env: APEX, TARGET_HOST, ORIGIN_IP, HEALTH_URL, CF_PROXY_MODE,
#   BROWSER_CACHE_TTL, ATTACK_MODE, PURGE_AFTER, REVERB_APP_KEY, TMP_DIR

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ADV_SCRIPT="$SCRIPT_DIR/cloudflare_hardening_advanced.sh"

CF_TOKEN="${CF_TOKEN:-}"
ZONE_ID="${ZONE_ID:-}"
APEX="${APEX:-lexusforbon.com}"
WWW="${WWW:-www.lexusforbon.com}"
ORIGIN_IP="${ORIGIN_IP:-209.74.64.215}"
TARGET_HOST="${TARGET_HOST:-$APEX}"
HEALTH_URL="${HEALTH_URL:-https://$TARGET_HOST/api/health}"
HTTP_URL="${HTTP_URL:-https://$TARGET_HOST/}"
CF_PROXY_MODE="${CF_PROXY_MODE:-off}"
BROWSER_CACHE_TTL="${BROWSER_CACHE_TTL:-43200}"
ATTACK_MODE="${ATTACK_MODE:-0}"
PURGE_AFTER="${PURGE_AFTER:-1}"
ALLOW_PRODUCTION_CHANGE="${ALLOW_PRODUCTION_CHANGE:-0}"
REVERB_APP_KEY="${REVERB_APP_KEY:-}"
TMP_DIR="${TMP_DIR:-/tmp/cf_production_strict_$$}"
ROLLBACK_DIR="${ROLLBACK_DIR:-$TMP_DIR/rollback}"

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
if [[ "$CF_PROXY_MODE" == "on" && "$ALLOW_PRODUCTION_CHANGE" != "1" ]]; then
  echo "ERROR: CF_PROXY_MODE=on requires ALLOW_PRODUCTION_CHANGE=1" >&2
  exit 1
fi
if [[ "$CF_PROXY_MODE" == "on" && -z "$REVERB_APP_KEY" ]]; then
  echo "ERROR: REVERB_APP_KEY is required for the WebSocket 101 postcheck" >&2
  exit 1
fi
for required_cmd in bash curl grep jq openssl; do
  command -v "$required_cmd" >/dev/null 2>&1 || {
    echo "ERROR: required command is missing: $required_cmd" >&2
    exit 1
  }
done

log() { echo "[STRICT] $*"; }

origin_precheck() {
  mkdir -p "$TMP_DIR" "$ROLLBACK_DIR"
  local headers="$TMP_DIR/origin.headers"
  local body="$TMP_DIR/origin.body"
  local status
  status=$(curl -sS --resolve "$TARGET_HOST:443:$ORIGIN_IP" -m 20 \
    -D "$headers" -o "$body" -w '%{http_code}' "$HEALTH_URL")
  if [[ ! "$status" =~ ^2[0-9][0-9]$ ]] || ! grep -q '"ok":true' "$body"; then
    echo "ERROR: direct origin health failed ($status)" >&2
    head -c 300 "$body" >&2; echo >&2
    return 1
  fi
  log "Direct origin health OK before Cloudflare change"
}

run_phase() {
  local phase="$1"
  shift
  log "Running phase: $phase (host=$TARGET_HOST)"
  CF_TOKEN="$CF_TOKEN" \
  ZONE_ID="$ZONE_ID" \
  APEX="$APEX" \
  WWW="$WWW" \
  ORIGIN_IP="$ORIGIN_IP" \
  TARGET_HOST="$TARGET_HOST" \
  HEALTH_URL="$HEALTH_URL" \
  HTTP_URL="$HTTP_URL" \
  TMP_DIR="$TMP_DIR" \
  ROLLBACK_DIR="$ROLLBACK_DIR" \
  REVERB_APP_KEY="$REVERB_APP_KEY" \
  REQUIRE_CF_RAY="$([[ "$CF_PROXY_MODE" == "on" ]] && echo 1 || echo 0)" \
  PHASE="$phase" \
  "$@" \
  bash "$ADV_SCRIPT"
}

rollback_done=0
rollback_on_error() {
  local rc=$?
  if [[ "$rollback_done" == "1" ]]; then
    exit "$rc"
  fi
  rollback_done=1
  if [[ -f "$ROLLBACK_DIR/dns_${TARGET_HOST}.json" || -f "$ROLLBACK_DIR/cache_entrypoint.json" ]]; then
    log "Failure detected; attempting automatic rollback"
    CF_TOKEN="$CF_TOKEN" ZONE_ID="$ZONE_ID" TARGET_HOST="$TARGET_HOST" \
      TMP_DIR="$TMP_DIR" ROLLBACK_DIR="$ROLLBACK_DIR" PHASE=rollback \
      bash "$ADV_SCRIPT" || log "Automatic rollback failed; inspect $ROLLBACK_DIR"
  fi
  exit "$rc"
}
trap rollback_on_error ERR

log "Profile: production strict"
log "Settings: host=$TARGET_HOST proxy=$CF_PROXY_MODE attack_mode=$ATTACK_MODE browser_cache_ttl=$BROWSER_CACHE_TTL purge_after=$PURGE_AFTER"

run_phase precheck env
origin_precheck

# DNS is the only phase that changes routing. It snapshots the original record
# first; every subsequent failure invokes rollback automatically.
run_phase dns env CF_PROXY_MODE="$CF_PROXY_MODE"
run_phase postcheck env

run_phase tls env
if [[ "$ATTACK_MODE" == "1" ]]; then
  run_phase security env SECURITY_LEVEL=under_attack
else
  run_phase security env SECURITY_LEVEL=high
fi
run_phase performance env
run_phase cache env BROWSER_CACHE_TTL="$BROWSER_CACHE_TTL"
if [[ "$PURGE_AFTER" == "1" ]]; then
  run_phase purge env
fi
run_phase postcheck env

log "DONE: production strict profile completed for $TARGET_HOST"
