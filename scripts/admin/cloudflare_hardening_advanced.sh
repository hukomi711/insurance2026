#!/usr/bin/env bash
set -euo pipefail

# Cloudflare Advanced Hardening (Phased)
# ------------------------------------------------------------
# Required env:
#   CF_TOKEN=... ZONE_ID=...
# Optional env:
#   APEX=lexusforbon.com
#   WWW=www.lexusforbon.com
#   ORIGIN_IP=209.74.64.215
#   HEALTH_URL=https://lexusforbon.com/api/health
#   PHASE=all|precheck|dns|tls|security|performance|cache|purge|postcheck
#   CF_PROXY_MODE=off|on     (default: off)
#   SECURITY_LEVEL=medium|high|under_attack (default: high)
#   BROWSER_CACHE_TTL=14400  (default: 4h)
#   PURGE_AFTER=1|0          (default: 1)
#
# Example:
#   CF_TOKEN=*** ZONE_ID=*** PHASE=precheck bash scripts/admin/cloudflare_hardening_advanced.sh

ZONE_ID="${ZONE_ID:-}"
CF_TOKEN="${CF_TOKEN:-}"
APEX="${APEX:-lexusforbon.com}"
WWW="${WWW:-www.lexusforbon.com}"
ORIGIN_IP="${ORIGIN_IP:-209.74.64.215}"
HEALTH_URL="${HEALTH_URL:-https://lexusforbon.com/api/health}"
PHASE="${PHASE:-all}"
CF_PROXY_MODE="${CF_PROXY_MODE:-off}"
SECURITY_LEVEL="${SECURITY_LEVEL:-high}"
BROWSER_CACHE_TTL="${BROWSER_CACHE_TTL:-14400}"
PURGE_AFTER="${PURGE_AFTER:-1}"

if [[ -z "$ZONE_ID" || -z "$CF_TOKEN" ]]; then
  echo "ERROR: CF_TOKEN and ZONE_ID are required" >&2
  exit 1
fi

if [[ "$CF_PROXY_MODE" != "off" && "$CF_PROXY_MODE" != "on" ]]; then
  echo "ERROR: CF_PROXY_MODE must be off|on" >&2
  exit 1
fi

if [[ "$SECURITY_LEVEL" != "medium" && "$SECURITY_LEVEL" != "high" && "$SECURITY_LEVEL" != "under_attack" ]]; then
  echo "ERROR: SECURITY_LEVEL must be medium|high|under_attack" >&2
  exit 1
fi

TMP_DIR="${TMP_DIR:-/tmp/cf_hardening_advanced_$$}"
mkdir -p "$TMP_DIR"

report_ok()   { echo "[OK] $*"; }
report_warn() { echo "[WARN] $*"; }
report_skip() { echo "[SKIP] $*"; }

api_get() {
  local path="$1"
  curl -sS "https://api.cloudflare.com/client/v4${path}" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json'
}

api_patch() {
  local path="$1" payload="$2"
  curl -sS -X PATCH "https://api.cloudflare.com/client/v4${path}" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json' \
    --data "$payload"
}

api_put() {
  local path="$1" payload="$2"
  curl -sS -X PUT "https://api.cloudflare.com/client/v4${path}" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json' \
    --data "$payload"
}

api_post() {
  local path="$1" payload="$2"
  curl -sS -X POST "https://api.cloudflare.com/client/v4${path}" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json' \
    --data "$payload"
}

json_success() {
  local f="$1"
  grep -q '"success":true' "$f"
}

HAS_DNS_EDIT=false
HAS_ZONE_SETTINGS_EDIT=false
HAS_WAF_EDIT=false
HAS_CACHE_PURGE_EDIT=false

precheck() {
  api_get "/zones/$ZONE_ID" > "$TMP_DIR/zone.json"
  if ! json_success "$TMP_DIR/zone.json"; then
    echo "ERROR: cannot access zone with provided token" >&2
    cat "$TMP_DIR/zone.json"
    exit 2
  fi

  local perms
  perms=$(tr -d '\n' < "$TMP_DIR/zone.json")

  grep -q '#dns_records:edit' <<< "$perms" && HAS_DNS_EDIT=true || true
  grep -q '#zone_settings:edit' <<< "$perms" && HAS_ZONE_SETTINGS_EDIT=true || true
  grep -q '#waf:edit' <<< "$perms" && HAS_WAF_EDIT=true || true
  grep -q '#cache_purge:edit' <<< "$perms" && HAS_CACHE_PURGE_EDIT=true || true

  report_ok "Zone token verified"
  echo "Permissions: dns_edit=$HAS_DNS_EDIT zone_settings_edit=$HAS_ZONE_SETTINGS_EDIT waf_edit=$HAS_WAF_EDIT cache_purge_edit=$HAS_CACHE_PURGE_EDIT"
}

apply_setting() {
  local key="$1" payload="$2" label="$3"
  local f="$TMP_DIR/set_${key}.json"
  api_patch "/zones/$ZONE_ID/settings/$key" "$payload" > "$f" || true
  if json_success "$f"; then
    report_ok "$label"
  else
    report_warn "$label (not applied - plan/permission limitation)"
  fi
}

dns_phase() {
  if [[ "$HAS_DNS_EDIT" != true ]]; then
    report_skip "dns phase: missing #dns_records:edit"
    return 0
  fi

  local proxied_bool=false
  [[ "$CF_PROXY_MODE" == "on" ]] && proxied_bool=true

  for name in "$APEX" "$WWW"; do
    api_get "/zones/$ZONE_ID/dns_records?name=$name" > "$TMP_DIR/rec_${name}.json"
    if ! json_success "$TMP_DIR/rec_${name}.json"; then
      report_warn "DNS read failed for $name"
      continue
    fi

    local rec_id
    rec_id=$(sed -n 's/.*"id":"\([a-f0-9]\{32\}\)".*/\1/p' "$TMP_DIR/rec_${name}.json" | head -n1)
    if [[ -z "$rec_id" ]]; then
      report_warn "DNS record id not found for $name"
      continue
    fi

    api_put "/zones/$ZONE_ID/dns_records/$rec_id" \
      "{\"type\":\"A\",\"name\":\"$name\",\"content\":\"$ORIGIN_IP\",\"ttl\":300,\"proxied\":$proxied_bool}" \
      > "$TMP_DIR/upd_${name}.json" || true

    if json_success "$TMP_DIR/upd_${name}.json"; then
      report_ok "DNS: $name -> $ORIGIN_IP (proxied=$proxied_bool)"
    else
      report_warn "DNS update failed for $name"
      cat "$TMP_DIR/upd_${name}.json"
    fi
  done
}

tls_phase() {
  if [[ "$HAS_ZONE_SETTINGS_EDIT" != true ]]; then
    report_skip "tls phase: missing #zone_settings:edit"
    return 0
  fi

  # Strong baseline + compatibility
  apply_setting "ssl" '{"value":"full"}' "SSL mode => Full"
  apply_setting "always_use_https" '{"value":"on"}' "Always HTTPS => ON"
  apply_setting "min_tls_version" '{"value":"1.2"}' "Min TLS => 1.2"
  apply_setting "tls_1_3" '{"value":"on"}' "TLS 1.3 => ON"
  apply_setting "automatic_https_rewrites" '{"value":"on"}' "HTTPS Rewrites => ON"
  apply_setting "http3" '{"value":"on"}' "HTTP/3 => ON"
  apply_setting "ipv6" '{"value":"on"}' "IPv6 => ON"
}

security_phase() {
  if [[ "$HAS_ZONE_SETTINGS_EDIT" != true ]]; then
    report_skip "security phase: missing #zone_settings:edit"
    return 0
  fi

  apply_setting "security_level" "{\"value\":\"$SECURITY_LEVEL\"}" "Security Level => $SECURITY_LEVEL"
  apply_setting "browser_check" '{"value":"on"}' "Browser Integrity Check => ON"
  apply_setting "challenge_ttl" '{"value":1800}' "Challenge Passage => 30m"
  apply_setting "0rtt" '{"value":"off"}' "0-RTT => OFF"

  if [[ "$HAS_WAF_EDIT" == true ]]; then
    apply_setting "waf" '{"value":"on"}' "WAF => ON"
    # one of these may not be available depending on plan
    apply_setting "bot_fight_mode" '{"value":"on"}' "Bot Fight Mode => ON"
    apply_setting "super_bot_fight_mode" '{"value":"on"}' "Super Bot Fight Mode => ON"
  else
    report_skip "waf/bot toggles: missing #waf:edit"
  fi
}

performance_phase() {
  if [[ "$HAS_ZONE_SETTINGS_EDIT" != true ]]; then
    report_skip "performance phase: missing #zone_settings:edit"
    return 0
  fi

  apply_setting "brotli" '{"value":"on"}' "Brotli => ON"
  apply_setting "early_hints" '{"value":"on"}' "Early Hints => ON"
  apply_setting "opportunistic_encryption" '{"value":"on"}' "Opportunistic Encryption => ON"

  # Safe default for Laravel/Vite apps to avoid JS breakage
  apply_setting "rocket_loader" '{"value":"off"}' "Rocket Loader => OFF (safe)"

  # Minify (may be unavailable on some plans)
  local f="$TMP_DIR/set_minify.json"
  api_patch "/zones/$ZONE_ID/settings/minify" '{"value":{"css":"on","html":"on","js":"on"}}' > "$f" || true
  if json_success "$f"; then
    report_ok "Minify css/html/js => ON"
  else
    report_warn "Minify not applied"
  fi
}

cache_phase() {
  if [[ "$HAS_ZONE_SETTINGS_EDIT" != true ]]; then
    report_skip "cache phase: missing #zone_settings:edit"
    return 0
  fi

  # Safer for dynamic Laravel apps than aggressive caching
  apply_setting "cache_level" '{"value":"standard"}' "Cache Level => Standard"
  apply_setting "browser_cache_ttl" "{\"value\":$BROWSER_CACHE_TTL}" "Browser Cache TTL => ${BROWSER_CACHE_TTL}s"
  apply_setting "always_online" '{"value":"on"}' "Always Online => ON"
}

purge_phase() {
  if [[ "$PURGE_AFTER" != "1" ]]; then
    report_skip "cache purge disabled (PURGE_AFTER=0)"
    return 0
  fi
  if [[ "$HAS_CACHE_PURGE_EDIT" != true ]]; then
    report_skip "purge phase: missing #cache_purge:edit"
    return 0
  fi

  api_post "/zones/$ZONE_ID/purge_cache" '{"purge_everything":true}' > "$TMP_DIR/purge.json" || true
  if json_success "$TMP_DIR/purge.json"; then
    report_ok "Cache purged"
  else
    report_warn "Cache purge failed"
  fi
}

postcheck_phase() {
  report_ok "Running health checks"

  set +e
  health_body=$(curl -sS -m 20 "$HEALTH_URL")
  health_rc=$?
  set -e

  if [[ $health_rc -eq 0 && "$health_body" == *'"ok":true'* ]]; then
    report_ok "Health check OK: $HEALTH_URL"
  else
    report_warn "Health check failed: $HEALTH_URL"
    echo "$health_body" | head -c 300; echo
  fi

  local rt_url
  rt_url="${HEALTH_URL%/health}/health/realtime"
  set +e
  rt_body=$(curl -sS -m 20 "$rt_url")
  rt_rc=$?
  set -e
  if [[ $rt_rc -eq 0 && "$rt_body" == *'"ok":true'* ]]; then
    report_ok "Realtime health OK: $rt_url"
  else
    report_warn "Realtime health failed: $rt_url"
    echo "$rt_body" | head -c 300; echo
  fi

  echo "Artifacts: $TMP_DIR"
}

run_all() {
  precheck
  dns_phase
  tls_phase
  security_phase
  performance_phase
  cache_phase
  purge_phase
  postcheck_phase
}

case "$PHASE" in
  all) run_all ;;
  precheck|1) precheck ;;
  dns|2) precheck; dns_phase ;;
  tls|3) precheck; tls_phase ;;
  security|4) precheck; security_phase ;;
  performance|5) precheck; performance_phase ;;
  cache|6) precheck; cache_phase ;;
  purge|7) precheck; purge_phase ;;
  postcheck|8) precheck; postcheck_phase ;;
  *)
    echo "ERROR: invalid PHASE=$PHASE" >&2
    exit 1
    ;;
esac

report_ok "Completed phase: $PHASE"
