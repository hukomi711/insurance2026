#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-/home/forge/insurance2026}"
THRESHOLD_DAYS="${THRESHOLD_DAYS:-20}"
ENV_FILE="${ENV_FILE:-$DEPLOY_DIR/.env}"
DOMAIN="${DOMAIN:-}"

read_env_value() {
  local key="$1"
  [[ -f "$ENV_FILE" ]] || return 0

  local raw
  raw="$(grep -E "^${key}=" "$ENV_FILE" | tail -n1 | cut -d= -f2- || true)"
  raw="${raw%$'\r'}"
  raw="${raw#\"}"; raw="${raw%\"}"
  raw="${raw#\'}"; raw="${raw%\'}"
  printf '%s' "$raw"
}

trim() {
  local s="$1"
  s="${s#"${s%%[![:space:]]*}"}"
  s="${s%"${s##*[![:space:]]}"}"
  printf '%s' "$s"
}

send_telegram_alert() {
  local message="$1"

  local bot_token chat_id thread_id
  bot_token="${TELEGRAM_BOT_TOKEN:-$(read_env_value TELEGRAM_BOT_TOKEN)}"
  chat_id="${TELEGRAM_CHAT_ID:-$(read_env_value TELEGRAM_CHAT_ID)}"
  thread_id="${TELEGRAM_THREAD_ID:-$(read_env_value TELEGRAM_THREAD_ID)}"

  if [[ -z "$bot_token" || -z "$chat_id" ]]; then
    echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] INFO cert_expiry notify_telegram skipped reason=missing_token_or_chat_id"
    return 0
  fi

  local api="https://api.telegram.org/bot${bot_token}/sendMessage"
  local -a args
  args=(
    --silent --show-error --fail --max-time 20
    -X POST "$api"
    --data-urlencode "chat_id=$chat_id"
    --data-urlencode "text=$message"
    --data-urlencode "disable_web_page_preview=true"
  )
  if [[ -n "$thread_id" ]]; then
    args+=(--data-urlencode "message_thread_id=$thread_id")
  fi

  if curl "${args[@]}" >/dev/null; then
    echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] INFO cert_expiry notify_telegram sent"
  else
    echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] ERROR cert_expiry notify_telegram failed"
    return 1
  fi
}

send_email_alert() {
  local severity="$1"
  local message="$2"

  local to from host port user pass scheme require_tls
  to="${ALERT_EMAIL_TO:-$(read_env_value SSL_ALERT_EMAIL_TO)}"
  [[ -n "$to" ]] || to="$(read_env_value ADMIN_VERIFICATION_EMAIL)"
  [[ -n "$to" ]] || to="$(read_env_value ADMIN_EMAIL)"
  [[ -n "$to" ]] || to="$(read_env_value MAIL_FROM_ADDRESS)"

  host="${ALERT_SMTP_HOST:-$(read_env_value MAIL_HOST)}"
  port="${ALERT_SMTP_PORT:-$(read_env_value MAIL_PORT)}"
  user="${ALERT_SMTP_USER:-$(read_env_value MAIL_USERNAME)}"
  pass="${ALERT_SMTP_PASS:-$(read_env_value MAIL_PASSWORD)}"
  from="${ALERT_EMAIL_FROM:-$(read_env_value MAIL_FROM_ADDRESS)}"
  scheme="${ALERT_SMTP_SCHEME:-$(read_env_value MAIL_SCHEME)}"
  require_tls="${ALERT_MAIL_REQUIRE_TLS:-$(read_env_value MAIL_REQUIRE_TLS)}"

  if [[ -z "$to" ]]; then
    echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] INFO cert_expiry notify_email skipped reason=missing_recipient"
    return 0
  fi
  if [[ -z "$host" || -z "$port" ]]; then
    echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] INFO cert_expiry notify_email skipped reason=missing_smtp_host_or_port"
    return 0
  fi
  if [[ -z "$from" ]]; then
    from="noreply@${DOMAIN}"
  fi

  local protocol
  protocol="smtp"
  case "${scheme,,}" in
    smtps|ssl)
      protocol="smtps"
      ;;
    *)
      if [[ "$port" == "465" ]]; then
        protocol="smtps"
      fi
      ;;
  esac

  local tmp
  tmp="$(mktemp)"
  {
    printf 'From: %s\n' "$from"
    printf 'To: %s\n' "$to"
    printf 'Subject: [%s] SSL cert expiry: %s (%sd left)\n' "$severity" "$DOMAIN" "$DAYS_LEFT"
    printf 'Date: %s\n' "$(date -R)"
    printf 'Content-Type: text/plain; charset=UTF-8\n'
    printf '\n%s\n' "$message"
  } > "$tmp"

  local -a curl_args
  curl_args=(
    --silent --show-error --fail --max-time 30
    --url "${protocol}://${host}:${port}"
    --mail-from "$from"
    --upload-file "$tmp"
  )

  local rcpt
  IFS=',' read -r -a rcpts <<< "$to"
  for rcpt in "${rcpts[@]}"; do
    rcpt="$(trim "$rcpt")"
    [[ -n "$rcpt" ]] && curl_args+=(--mail-rcpt "$rcpt")
  done
  if [[ -n "$user" ]]; then
    curl_args+=(--user "${user}:${pass}")
  fi

  if [[ "${protocol}" == "smtp" && "${require_tls,,}" == "true" ]]; then
    curl_args+=(--ssl-reqd)
  fi

  if curl "${curl_args[@]}" >/dev/null; then
    echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] INFO cert_expiry notify_email sent to=$to"
  else
    echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] ERROR cert_expiry notify_email failed to=$to"
    rm -f "$tmp"
    return 1
  fi

  rm -f "$tmp"
}

if [[ -z "$DOMAIN" && -f "$ENV_FILE" ]]; then
  DOMAIN="$(grep -E '^DOMAIN=' "$ENV_FILE" | tail -n1 | cut -d= -f2- | tr -d '"' | tr -d "'")"
fi

if [[ -z "$DOMAIN" ]]; then
  echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] ERROR cert_expiry DOMAIN is empty"
  exit 2
fi

CERT_FILE="${CERT_FILE:-$DEPLOY_DIR/docker/certbot/conf/live/$DOMAIN/fullchain.pem}"
if [[ ! -s "$CERT_FILE" ]]; then
  echo "[$(date -u '+%Y-%m-%dT%H:%M:%SZ')] ERROR cert_expiry cert_not_found file=$CERT_FILE domain=$DOMAIN"
  exit 2
fi

END_RAW="$(openssl x509 -in "$CERT_FILE" -noout -enddate | cut -d= -f2)"
END_EPOCH="$(date -u -d "$END_RAW" +%s)"
NOW_EPOCH="$(date -u +%s)"
DAYS_LEFT=$(( (END_EPOCH - NOW_EPOCH) / 86400 ))
EXPIRY_UTC="$(date -u -d "@$END_EPOCH" '+%Y-%m-%dT%H:%M:%SZ')"
TS="$(date -u '+%Y-%m-%dT%H:%M:%SZ')"
HOST_NAME="$(hostname -f 2>/dev/null || hostname || echo unknown-host)"

STATUS="OK"
EXIT_CODE=0

if (( DAYS_LEFT < 0 )); then
  STATUS="CRIT"
  EXIT_CODE=2
elif (( DAYS_LEFT < THRESHOLD_DAYS )); then
  STATUS="WARN"
  EXIT_CODE=2
fi

LOG_LINE="[$TS] $STATUS cert_expiry host=$HOST_NAME domain=$DOMAIN days_left=$DAYS_LEFT threshold=$THRESHOLD_DAYS expires_utc=$EXPIRY_UTC file=$CERT_FILE"
echo "$LOG_LINE"

if [[ "$STATUS" == "WARN" || "$STATUS" == "CRIT" ]]; then
  ALERT_MESSAGE="[$STATUS] SSL certificate expiry alert\nhost=$HOST_NAME\ndomain=$DOMAIN\ndays_left=$DAYS_LEFT\nthreshold=$THRESHOLD_DAYS\nexpires_utc=$EXPIRY_UTC\nfile=$CERT_FILE\nat=$TS"
  send_telegram_alert "$ALERT_MESSAGE" || true
  send_email_alert "$STATUS" "$ALERT_MESSAGE" || true
fi

exit "$EXIT_CODE"
