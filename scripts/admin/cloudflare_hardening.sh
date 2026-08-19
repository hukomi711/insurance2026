#!/usr/bin/env bash
set -euo pipefail

# Cloudflare hardening + performance baseline
# Usage:
#   CF_TOKEN=... ZONE_ID=... bash scripts/admin/cloudflare_hardening.sh
# Optional:
#   APEX=lexusforbon.com WWW=www.lexusforbon.com ORIGIN_IP=209.74.64.215

ZONE_ID="${ZONE_ID:-}"
CF_TOKEN="${CF_TOKEN:-}"
APEX="${APEX:-lexusforbon.com}"
WWW="${WWW:-www.lexusforbon.com}"
ORIGIN_IP="${ORIGIN_IP:-209.74.64.215}"

if [[ -z "$ZONE_ID" || -z "$CF_TOKEN" ]]; then
  echo "ERROR: ZONE_ID and CF_TOKEN are required" >&2
  echo "Example: CF_TOKEN=*** ZONE_ID=*** bash scripts/admin/cloudflare_hardening.sh" >&2
  exit 1
fi

api_get() {
  local path="$1"
  curl -sS "https://api.cloudflare.com/client/v4${path}" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json'
}

api_patch() {
  local path="$1"
  local payload="$2"
  curl -sS -X PATCH "https://api.cloudflare.com/client/v4${path}" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json' \
    --data "$payload"
}

api_put() {
  local path="$1"
  local payload="$2"
  curl -sS -X PUT "https://api.cloudflare.com/client/v4${path}" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json' \
    --data "$payload"
}

json_success() {
  grep -q '"success":true' "$1"
}

report_ok() { echo "[OK] $*"; }
report_skip() { echo "[SKIP] $*"; }
report_warn() { echo "[WARN] $*"; }

TMP_DIR="${TMP_DIR:-/tmp/cf_hardening_$$}"
mkdir -p "$TMP_DIR"

# 0) Verify token + permissions
api_get "/zones/$ZONE_ID" > "$TMP_DIR/zone.json"
if ! json_success "$TMP_DIR/zone.json"; then
  echo "ERROR: cannot access zone with provided token" >&2
  cat "$TMP_DIR/zone.json"
  exit 2
fi
report_ok "Zone access verified"

PERMS=$(tr -d '\n' < "$TMP_DIR/zone.json")
HAS_DNS_EDIT=false
HAS_ZONE_SETTINGS_EDIT=false
HAS_WAF_EDIT=false
HAS_CACHE_PURGE_EDIT=false

grep -q '#dns_records:edit' <<< "$PERMS" && HAS_DNS_EDIT=true || true
grep -q '#zone_settings:edit' <<< "$PERMS" && HAS_ZONE_SETTINGS_EDIT=true || true
grep -q '#waf:edit' <<< "$PERMS" && HAS_WAF_EDIT=true || true
grep -q '#cache_purge:edit' <<< "$PERMS" && HAS_CACHE_PURGE_EDIT=true || true

echo "Permissions: dns_edit=$HAS_DNS_EDIT zone_settings_edit=$HAS_ZONE_SETTINGS_EDIT waf_edit=$HAS_WAF_EDIT cache_purge_edit=$HAS_CACHE_PURGE_EDIT"

# 1) DNS sanity (apex/www → origin)
if [[ "$HAS_DNS_EDIT" == true ]]; then
  for name in "$APEX" "$WWW"; do
    api_get "/zones/$ZONE_ID/dns_records?name=$name" > "$TMP_DIR/rec_${name}.json"
    if ! json_success "$TMP_DIR/rec_${name}.json"; then
      report_warn "Cannot read DNS record for $name"
      continue
    fi

    REC_ID=$(sed -n 's/.*"id":"\([a-f0-9]\{32\}\)".*/\1/p' "$TMP_DIR/rec_${name}.json" | head -n1)
    if [[ -z "$REC_ID" ]]; then
      report_warn "No record id found for $name"
      continue
    fi

    # Keep DNS-only unless explicitly changing to proxied later.
    api_put "/zones/$ZONE_ID/dns_records/$REC_ID" \
      "{\"type\":\"A\",\"name\":\"$name\",\"content\":\"$ORIGIN_IP\",\"ttl\":300,\"proxied\":false}" \
      > "$TMP_DIR/upd_${name}.json"

    if json_success "$TMP_DIR/upd_${name}.json"; then
      report_ok "DNS updated: $name -> $ORIGIN_IP (proxied=false)"
    else
      report_warn "DNS update failed for $name"
      cat "$TMP_DIR/upd_${name}.json"
    fi
  done
else
  report_skip "Token lacks #dns_records:edit"
fi

# 2) SSL/TLS + HTTPS hardening
if [[ "$HAS_ZONE_SETTINGS_EDIT" == true ]]; then
  declare -a PATCHES=(
    "/zones/$ZONE_ID/settings/ssl|{\"value\":\"full\"}|SSL mode => Full"
    "/zones/$ZONE_ID/settings/always_use_https|{\"value\":\"on\"}|Always Use HTTPS => ON"
    "/zones/$ZONE_ID/settings/min_tls_version|{\"value\":\"1.2\"}|Min TLS => 1.2"
    "/zones/$ZONE_ID/settings/tls_1_3|{\"value\":\"on\"}|TLS 1.3 => ON"
    "/zones/$ZONE_ID/settings/automatic_https_rewrites|{\"value\":\"on\"}|Automatic HTTPS Rewrites => ON"
    "/zones/$ZONE_ID/settings/http3|{\"value\":\"on\"}|HTTP/3 => ON"
    "/zones/$ZONE_ID/settings/brotli|{\"value\":\"on\"}|Brotli => ON"
    "/zones/$ZONE_ID/settings/0rtt|{\"value\":\"off\"}|0-RTT => OFF"
    "/zones/$ZONE_ID/settings/challenge_ttl|{\"value\":1800}|Challenge Passage => 30m"
    "/zones/$ZONE_ID/settings/security_level|{\"value\":\"medium\"}|Security Level => Medium"
    "/zones/$ZONE_ID/settings/browser_check|{\"value\":\"on\"}|Browser Integrity Check => ON"
    "/zones/$ZONE_ID/settings/ipv6|{\"value\":\"on\"}|IPv6 => ON"
  )

  for item in "${PATCHES[@]}"; do
    path="${item%%|*}"
    rest="${item#*|}"
    payload="${rest%%|*}"
    label="${rest#*|}"

    api_patch "$path" "$payload" > "$TMP_DIR/patch_$(basename "$path").json" || true
    if json_success "$TMP_DIR/patch_$(basename "$path").json"; then
      report_ok "$label"
    else
      report_warn "$label (not applied - maybe plan limitation)"
    fi
  done
else
  report_skip "Token lacks #zone_settings:edit"
fi

# 3) WAF / bot protections (managed + custom phases where allowed)
if [[ "$HAS_WAF_EDIT" == true ]]; then
  # Security products quick toggles (where API supports)
  declare -a WAF_PATCHES=(
    "/zones/$ZONE_ID/settings/waf|{\"value\":\"on\"}|WAF => ON"
    "/zones/$ZONE_ID/settings/super_bot_fight_mode|{\"value\":\"on\"}|Super Bot Fight Mode => ON"
  )

  for item in "${WAF_PATCHES[@]}"; do
    path="${item%%|*}"
    rest="${item#*|}"
    payload="${rest%%|*}"
    label="${rest#*|}"

    api_patch "$path" "$payload" > "$TMP_DIR/waf_$(basename "$path").json" || true
    if json_success "$TMP_DIR/waf_$(basename "$path").json"; then
      report_ok "$label"
    else
      report_warn "$label (not available on current plan/API)"
    fi
  done
else
  report_skip "Token lacks #waf:edit"
fi

# 4) Cache strategy basics (safe defaults)
if [[ "$HAS_ZONE_SETTINGS_EDIT" == true ]]; then
  api_patch "/zones/$ZONE_ID/settings/cache_level" '{"value":"aggressive"}' > "$TMP_DIR/cache_level.json" || true
  if json_success "$TMP_DIR/cache_level.json"; then
    report_ok "Cache Level => Aggressive"
  else
    report_warn "Cache Level not changed"
  fi

  api_patch "/zones/$ZONE_ID/settings/browser_cache_ttl" '{"value":14400}' > "$TMP_DIR/browser_cache_ttl.json" || true
  if json_success "$TMP_DIR/browser_cache_ttl.json"; then
    report_ok "Browser Cache TTL => 4h"
  else
    report_warn "Browser Cache TTL not changed"
  fi
else
  report_skip "Zone settings edit not available for cache tuning"
fi

# 5) Purge cache
if [[ "$HAS_CACHE_PURGE_EDIT" == true ]]; then
  curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/purge_cache" \
    -H "Authorization: Bearer $CF_TOKEN" \
    -H 'Content-Type: application/json' \
    --data '{"purge_everything":true}' > "$TMP_DIR/purge.json" || true

  if json_success "$TMP_DIR/purge.json"; then
    report_ok "Cache purged"
  else
    report_warn "Cache purge failed"
  fi
else
  report_skip "Token lacks #cache_purge:edit"
fi

# 6) Post-checks
report_ok "Hardening run completed"
echo "Artifacts: $TMP_DIR"
