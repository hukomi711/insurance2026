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
#   PHASE=all|precheck|dns|tls|security|performance|cache|purge|postcheck|rollback
#   CF_PROXY_MODE=off|on     (default: off)
#   TARGET_HOST=lexusforbon.com (default: APEX; one hostname per run)
#   TMP_DIR=/tmp/...          (optional; keep it to retain rollback state)
#   ROLLBACK_DIR=/tmp/...     (optional; defaults to $TMP_DIR/rollback)
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
TARGET_HOST="${TARGET_HOST:-$APEX}"
HTTP_URL="${HTTP_URL:-https://$TARGET_HOST/}"
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
ROLLBACK_DIR="${ROLLBACK_DIR:-$TMP_DIR/rollback}"
mkdir -p "$TMP_DIR"
mkdir -p "$ROLLBACK_DIR"

for required_cmd in curl jq grep sed head openssl; do
  command -v "$required_cmd" >/dev/null 2>&1 || {
    echo "ERROR: required command is missing: $required_cmd" >&2
    exit 1
  }
done

if [[ ! "$TARGET_HOST" =~ ^[A-Za-z0-9.-]+$ ]]; then
  echo "ERROR: invalid TARGET_HOST" >&2
  exit 1
fi

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
  jq -e '.success == true' "$f" >/dev/null
}

snapshot_setting() {
  local key="$1"
  local f="$ROLLBACK_DIR/setting_${key}.json"
  if [[ -f "$f" ]]; then
    return 0
  fi
  api_get "/zones/$ZONE_ID/settings/$key" > "$f" || true
  if json_success "$f"; then
    return 0
  fi
  rm -f "$f"
  return 1
}

precheck() {
  api_get "/zones/$ZONE_ID" > "$TMP_DIR/zone.json"
  if ! json_success "$TMP_DIR/zone.json"; then
    echo "ERROR: cannot access zone with provided token" >&2
    cat "$TMP_DIR/zone.json"
    exit 2
  fi

  local zone_name
  zone_name=$(jq -r '.result.name // empty' "$TMP_DIR/zone.json")
  if [[ "$zone_name" != "$APEX" ]]; then
    echo "ERROR: ZONE_ID resolves to $zone_name, expected $APEX" >&2
    exit 2
  fi

  report_ok "Zone token and zone name verified: $zone_name"
}

apply_setting_required() {
  local key="$1" payload="$2" label="$3"
  local f="$TMP_DIR/set_${key}.json"
  if ! snapshot_setting "$key"; then
    echo "ERROR: cannot snapshot current setting before $label" >&2
    return 1
  fi
  api_patch "/zones/$ZONE_ID/settings/$key" "$payload" > "$f" || true
  if json_success "$f"; then
    report_ok "$label"
  else
    echo "ERROR: $label failed" >&2
    cat "$f" >&2
    return 1
  fi
}

apply_setting_optional() {
  local key="$1" payload="$2" label="$3"
  local f="$TMP_DIR/set_${key}.json"
  if ! snapshot_setting "$key"; then
    report_warn "$label skipped because its current value could not be snapshotted"
    return 0
  fi
  api_patch "/zones/$ZONE_ID/settings/$key" "$payload" > "$f" || true
  if json_success "$f"; then
    report_ok "$label"
  else
    report_warn "$label unavailable or not permitted"
  fi
}

dns_phase() {
  local proxied_bool=false
  [[ "$CF_PROXY_MODE" == "on" ]] && proxied_bool=true
  local rec_file="$TMP_DIR/rec_${TARGET_HOST}.json"
  local rollback_file="$ROLLBACK_DIR/dns_${TARGET_HOST}.json"
  api_get "/zones/$ZONE_ID/dns_records?name=$TARGET_HOST" > "$rec_file" || true
  if ! json_success "$rec_file"; then
    echo "ERROR: DNS read failed for $TARGET_HOST" >&2
    cat "$rec_file" >&2
    return 1
  fi

  local record_count
  record_count=$(jq '.result | length' "$rec_file")
  if [[ "$record_count" != "1" ]]; then
    echo "ERROR: expected exactly one DNS record for $TARGET_HOST; found $record_count" >&2
    jq -c '.result[] | {id,type,name,content,proxied}' "$rec_file" >&2
    return 1
  fi

  local rec_id rec_type rec_content rec_proxied
  rec_id=$(jq -r '.result[0].id' "$rec_file")
  rec_type=$(jq -r '.result[0].type' "$rec_file")
  rec_content=$(jq -r '.result[0].content' "$rec_file")
  rec_proxied=$(jq -r '.result[0].proxied // false' "$rec_file")
  case "$rec_type" in
    A|AAAA|CNAME) ;;
    *)
      echo "ERROR: $TARGET_HOST has unsupported record type $rec_type" >&2
      return 1
      ;;
  esac

  jq -c '.result[0] | {id,type,name,content,ttl,proxied}' "$rec_file" > "$rollback_file"
  report_ok "Saved DNS rollback state: $rollback_file"

  local payload="$TMP_DIR/dns_patch_${TARGET_HOST}.json"
  jq -nc --argjson proxied "$proxied_bool" '{proxied: $proxied}' > "$payload"
  api_patch "/zones/$ZONE_ID/dns_records/$rec_id" "$(<"$payload")" > "$TMP_DIR/upd_${TARGET_HOST}.json" || true
  if ! json_success "$TMP_DIR/upd_${TARGET_HOST}.json"; then
    echo "ERROR: DNS proxy update failed for $TARGET_HOST" >&2
    cat "$TMP_DIR/upd_${TARGET_HOST}.json" >&2
    return 1
  fi
  report_ok "DNS: $TARGET_HOST ($rec_type $rec_content) proxied=$proxied_bool; record type/content preserved"
}

tls_phase() {
  apply_setting_required "ssl" '{"value":"strict"}' "SSL mode => Full (strict)"
  apply_setting_required "websockets" '{"value":"on"}' "WebSockets => ON"
  apply_setting_required "always_use_https" '{"value":"on"}' "Always HTTPS => ON"
  apply_setting_required "min_tls_version" '{"value":"1.2"}' "Min TLS => 1.2"
  apply_setting_required "tls_1_3" '{"value":"on"}' "TLS 1.3 => ON"
  apply_setting_required "automatic_https_rewrites" '{"value":"on"}' "HTTPS Rewrites => ON"
  apply_setting_optional "http3" '{"value":"on"}' "HTTP/3 => ON"
  apply_setting_optional "ipv6" '{"value":"on"}' "IPv6 => ON"
}

security_phase() {
  apply_setting_required "security_level" "{\"value\":\"$SECURITY_LEVEL\"}" "Security Level => $SECURITY_LEVEL"
  apply_setting_required "browser_check" '{"value":"on"}' "Browser Integrity Check => ON"
  apply_setting_required "challenge_ttl" '{"value":1800}' "Challenge Passage => 30m"
  apply_setting_required "0rtt" '{"value":"off"}' "0-RTT => OFF"
  apply_setting_optional "waf" '{"value":"on"}' "WAF => ON"
  apply_setting_optional "bot_fight_mode" '{"value":"on"}' "Bot Fight Mode => ON"
}

performance_phase() {
  apply_setting_required "brotli" '{"value":"on"}' "Brotli => ON"
  apply_setting_optional "early_hints" '{"value":"on"}' "Early Hints => ON"
  apply_setting_optional "opportunistic_encryption" '{"value":"on"}' "Opportunistic Encryption => ON"

  # Safe default for Laravel/Vite apps to avoid JS breakage
  apply_setting_optional "rocket_loader" '{"value":"off"}' "Rocket Loader => OFF (safe)"

  # Minify (may be unavailable on some plans)
  local f="$TMP_DIR/set_minify.json"
  if ! snapshot_setting "minify"; then
    report_warn "Minify skipped because its current value could not be snapshotted"
    return 0
  fi
  api_patch "/zones/$ZONE_ID/settings/minify" '{"value":{"css":"on","html":"on","js":"on"}}' > "$f" || true
  if json_success "$f"; then
    report_ok "Minify css/html/js => ON"
  else
    report_warn "Minify unavailable or not permitted"
  fi
}

cache_phase() {
  # Safer for dynamic Laravel apps than aggressive caching
  apply_setting_required "cache_level" '{"value":"standard"}' "Cache Level => Standard"
  apply_setting_required "browser_cache_ttl" "{\"value\":$BROWSER_CACHE_TTL}" "Browser Cache TTL => ${BROWSER_CACHE_TTL}s"
  apply_setting_optional "always_online" '{"value":"on"}' "Always Online => ON"

  cache_rules_phase
}

cache_rules_phase() {
  local entrypoint_file="$TMP_DIR/cache_entrypoint.json"
  local existing_rules_file="$TMP_DIR/cache_existing_rules.json"
  local new_rules_file="$TMP_DIR/cache_new_rules.json"
  local merged_rules_file="$TMP_DIR/cache_merged_rules.json"
  local rollback_file="$ROLLBACK_DIR/cache_entrypoint.json"
  local host_expr="(http.host eq \"$TARGET_HOST\")"
  local static_expr="($host_expr and (starts_with(http.request.uri.path, \"/build/\") or ends_with(http.request.uri.path, \".css\") or ends_with(http.request.uri.path, \".js\") or ends_with(http.request.uri.path, \".mjs\") or ends_with(http.request.uri.path, \".woff2\") or ends_with(http.request.uri.path, \".svg\") or ends_with(http.request.uri.path, \".png\") or ends_with(http.request.uri.path, \".jpg\") or ends_with(http.request.uri.path, \".jpeg\") or ends_with(http.request.uri.path, \".webp\") or ends_with(http.request.uri.path, \".ico\")))"
  local sensitive_expr="($host_expr and (starts_with(http.request.uri.path, \"/api/\") or starts_with(http.request.uri.path, \"/admin\") or http.request.uri.path eq \"/login\" or starts_with(http.request.uri.path, \"/checkout\") or starts_with(http.request.uri.path, \"/payment\") or starts_with(http.request.uri.path, \"/pay\") or http.request.uri.path eq \"/broadcasting/auth\" or http.request.uri.path eq \"/api/broadcasting/auth\" or http.cookie contains \"ins2026_session\" or http.cookie contains \"XSRF-TOKEN\"))"
  local nonstatic_expr="($host_expr and not ($static_expr))"

  api_get "/zones/$ZONE_ID/rulesets/phases/http_request_cache_settings/entrypoint" > "$entrypoint_file" || true
  if ! json_success "$entrypoint_file"; then
    report_warn "Cache Rules entrypoint does not exist; creating a zone ruleset"
    api_post "/zones/$ZONE_ID/rulesets" \
      '{"name":"Insurance 2026 cache policy","description":"Scoped cache policy for dynamic Laravel routes and static assets","kind":"zone","phase":"http_request_cache_settings","rules":[]}' \
      > "$TMP_DIR/cache_rules_create.json" || true
    if ! json_success "$TMP_DIR/cache_rules_create.json"; then
      echo "ERROR: cannot create cache rules entrypoint" >&2
      cat "$TMP_DIR/cache_rules_create.json" >&2
      return 1
    fi
    api_get "/zones/$ZONE_ID/rulesets/phases/http_request_cache_settings/entrypoint" > "$entrypoint_file" || true
  fi
  if ! json_success "$entrypoint_file"; then
    echo "ERROR: cannot read cache rules entrypoint after creation" >&2
    cat "$entrypoint_file" >&2
    return 1
  fi

  cp "$entrypoint_file" "$rollback_file"
  jq -c '.result.rules // []' "$entrypoint_file" > "$existing_rules_file"

  jq -n \
    --arg sensitive "$sensitive_expr" \
    --arg static "$static_expr" \
    --arg nonstatic "$nonstatic_expr" \
    '[
      {ref:"ins2026_bypass_sensitive_v1", description:"Insurance 2026 bypass dynamic and sensitive routes", expression:$sensitive, action:"set_cache_settings", action_parameters:{cache:false}, enabled:true},
      {ref:"ins2026_cache_static_v1", description:"Insurance 2026 cache immutable/static assets only", expression:$static, action:"set_cache_settings", action_parameters:{cache:true}, enabled:true},
      {ref:"ins2026_bypass_nonstatic_v1", description:"Insurance 2026 bypass all non-static requests", expression:$nonstatic, action:"set_cache_settings", action_parameters:{cache:false}, enabled:true}
    ]' > "$new_rules_file"

  jq -s '.[0] as $existing | .[1] as $new | $new + ($existing | map(select(.ref as $r | ($new | map(.ref) | index($r)) == null)))' \
    "$existing_rules_file" "$new_rules_file" > "$merged_rules_file"

  local payload
  payload=$(jq -c --argjson rules "$(<"$merged_rules_file")" '{rules:$rules}')
  api_put "/zones/$ZONE_ID/rulesets/phases/http_request_cache_settings/entrypoint" "$payload" > "$TMP_DIR/cache_rules_update.json" || true
  if ! json_success "$TMP_DIR/cache_rules_update.json"; then
    echo "ERROR: cache rules update failed" >&2
    cat "$TMP_DIR/cache_rules_update.json" >&2
    return 1
  fi
  report_ok "Cache Rules installed: sensitive bypass, static eligibility, non-static bypass"
}

purge_phase() {
  if [[ "$PURGE_AFTER" != "1" ]]; then
    report_skip "cache purge disabled (PURGE_AFTER=0)"
    return 0
  fi

  api_post "/zones/$ZONE_ID/purge_cache" '{"purge_everything":true}' > "$TMP_DIR/purge.json" || true
  if json_success "$TMP_DIR/purge.json"; then
    report_ok "Cache purged"
  else
    echo "ERROR: Cache purge failed" >&2
    cat "$TMP_DIR/purge.json" >&2
    return 1
  fi
}

rollback_phase() {
  local dns_file="$ROLLBACK_DIR/dns_${TARGET_HOST}.json"
  local cache_file="$ROLLBACK_DIR/cache_entrypoint.json"
  if [[ -f "$dns_file" ]]; then
    local rec_id original_proxied
    rec_id=$(jq -r '.id' "$dns_file")
    original_proxied=$(jq -r '.proxied // false' "$dns_file")
    api_patch "/zones/$ZONE_ID/dns_records/$rec_id" \
      "$(jq -nc --argjson proxied "$original_proxied" '{proxied:$proxied}')" \
      > "$TMP_DIR/rollback_dns.json" || true
    if ! json_success "$TMP_DIR/rollback_dns.json"; then
      echo "ERROR: DNS rollback failed" >&2
      cat "$TMP_DIR/rollback_dns.json" >&2
      return 1
    fi
    report_ok "DNS rollback restored proxied=$original_proxied for $TARGET_HOST"
  else
    report_skip "No DNS rollback snapshot found"
  fi

  if [[ -f "$cache_file" ]]; then
    api_put "/zones/$ZONE_ID/rulesets/phases/http_request_cache_settings/entrypoint" \
      "$(jq -c '{rules:(.result.rules // [])}' "$cache_file")" \
      > "$TMP_DIR/rollback_cache.json" || true
    if ! json_success "$TMP_DIR/rollback_cache.json"; then
      echo "ERROR: cache rules rollback failed" >&2
      cat "$TMP_DIR/rollback_cache.json" >&2
      return 1
    fi
    report_ok "Cache Rules rollback restored the previous entrypoint"
  else
    report_skip "No Cache Rules rollback snapshot found"
  fi

  local setting_file setting_key setting_payload
  shopt -s nullglob
  for setting_file in "$ROLLBACK_DIR"/setting_*.json; do
    setting_key="${setting_file##*/setting_}"
    setting_key="${setting_key%.json}"
    setting_payload=$(jq -c '{value:.result.value}' "$setting_file")
    api_patch "/zones/$ZONE_ID/settings/$setting_key" "$setting_payload" \
      > "$TMP_DIR/rollback_setting_${setting_key}.json" || true
    if ! json_success "$TMP_DIR/rollback_setting_${setting_key}.json"; then
      echo "ERROR: setting rollback failed for $setting_key" >&2
      cat "$TMP_DIR/rollback_setting_${setting_key}.json" >&2
      return 1
    fi
    report_ok "Zone setting rollback restored: $setting_key"
  done
}

postcheck_phase() {
  local require_cf_ray="${REQUIRE_CF_RAY:-0}"
  local http_headers="$TMP_DIR/postcheck_http.headers"
  local http_status
  http_status=$(curl -sS -m 20 -D "$http_headers" -o /dev/null -w '%{http_code}' "$HTTP_URL")
  if [[ ! "$http_status" =~ ^[23][0-9][0-9]$ ]]; then
    echo "ERROR: HTTP check failed ($http_status): $HTTP_URL" >&2
    return 1
  fi
  report_ok "HTTP check OK: $HTTP_URL ($http_status)"
  if [[ "$require_cf_ray" == "1" ]] && ! grep -qi '^cf-ray:' "$http_headers"; then
    echo "ERROR: expected CF-Ray on proxied HTTP response" >&2
    return 1
  fi

  local health_headers="$TMP_DIR/postcheck_health.headers"
  local health_body_file="$TMP_DIR/postcheck_health.body"
  local health_status
  health_status=$(curl -sS -m 20 -D "$health_headers" -o "$health_body_file" -w '%{http_code}' "$HEALTH_URL")
  if [[ ! "$health_status" =~ ^2[0-9][0-9]$ ]] || ! grep -q '"ok":true' "$health_body_file"; then
    echo "ERROR: health check failed ($health_status): $HEALTH_URL" >&2
    head -c 300 "$health_body_file" >&2; echo >&2
    return 1
  fi
  report_ok "Health check OK: $HEALTH_URL ($health_status)"

  if [[ "$require_cf_ray" == "1" ]] && ! grep -qi '^cf-ray:' "$health_headers"; then
    echo "ERROR: expected CF-Ray on proxied health response" >&2
    return 1
  fi

  local rt_url="${HEALTH_URL%/health}/health/realtime"
  local rt_headers="$TMP_DIR/postcheck_realtime.headers"
  local rt_body_file="$TMP_DIR/postcheck_realtime.body"
  local rt_status
  rt_status=$(curl -sS -m 20 -D "$rt_headers" -o "$rt_body_file" -w '%{http_code}' "$rt_url")
  if [[ ! "$rt_status" =~ ^2[0-9][0-9]$ ]] || ! grep -q '"ok":true' "$rt_body_file"; then
    echo "ERROR: realtime health check failed ($rt_status): $rt_url" >&2
    head -c 300 "$rt_body_file" >&2; echo >&2
    return 1
  fi
  report_ok "Realtime health OK: $rt_url ($rt_status)"

  if [[ "$require_cf_ray" == "1" ]] && ! grep -qi '^cf-ray:' "$rt_headers"; then
    echo "ERROR: expected CF-Ray on proxied realtime health response" >&2
    return 1
  fi

  local app_key="${REVERB_APP_KEY:-}"
  if [[ -z "$app_key" ]]; then
    echo "ERROR: REVERB_APP_KEY is required for WebSocket postcheck" >&2
    return 1
  fi
  local ws_url="${WS_URL:-wss://$TARGET_HOST/app/$app_key?protocol=7&client=js&version=8.4&flash=false}"
  local ws_key
  ws_key=$(openssl rand -base64 16 | tr -d '\n')
  local ws_headers="$TMP_DIR/postcheck_websocket.headers"
  curl -sS -m 15 --http1.1 -D "$ws_headers" -o /dev/null \
    -H 'Connection: Upgrade' \
    -H 'Upgrade: websocket' \
    -H 'Sec-WebSocket-Version: 13' \
    -H "Sec-WebSocket-Key: $ws_key" \
    "$ws_url" || true
  if ! grep -qE '^HTTP/[0-9.]+ 101 ' "$ws_headers"; then
    echo "ERROR: WebSocket handshake did not return 101: $ws_url" >&2
    sed -n '1,20p' "$ws_headers" >&2
    return 1
  fi
  report_ok "WebSocket handshake returned 101: $TARGET_HOST"

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
  rollback|9) precheck; rollback_phase ;;
  *)
    echo "ERROR: invalid PHASE=$PHASE" >&2
    exit 1
    ;;
esac

report_ok "Completed phase: $PHASE"
