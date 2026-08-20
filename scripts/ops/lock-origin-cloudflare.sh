#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-lexusforbon.com}"
MODE="dry-run"
FORCE_APPLY=0

for arg in "$@"; do
  case "$arg" in
    --apply)
      MODE="apply"
      ;;
    --force)
      FORCE_APPLY=1
      ;;
  esac
done

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ERROR: run as root (sudo)."
  exit 1
fi

echo "[1/5] Preflight: domain=${DOMAIN}, mode=${MODE}"
HEADERS="$(curl -sI --max-time 15 "https://${DOMAIN}" || true)"
if grep -qi '^cf-ray:' <<<"$HEADERS"; then
  echo "OK: Cloudflare edge header detected (cf-ray)."
  EDGE_OK=1
else
  echo "WARN: Cloudflare edge header NOT detected."
  EDGE_OK=0
fi

echo "[2/5] Preflight: current origin direct reachability"
curl -sI --max-time 10 "http://$(hostname -I | awk '{print $1}')" | sed -n '1,3p' || true

if [[ "$MODE" == "dry-run" ]]; then
  echo "[3/5] Dry-run complete."
  echo "Next: enable Cloudflare Proxy (orange cloud) for root/www records, then rerun with --apply."
  exit 0
fi

if [[ "$EDGE_OK" -ne 1 && "$FORCE_APPLY" -ne 1 ]]; then
  echo "ERROR: refusing to apply firewall rules because Cloudflare edge is not confirmed."
  exit 2
fi

if [[ "$EDGE_OK" -ne 1 && "$FORCE_APPLY" -eq 1 ]]; then
  echo "WARN: forcing apply without Cloudflare edge confirmation."
  echo "WARN: this may block public web traffic until rollback or DNS/proxy is fixed."
fi

echo "[3/5] Backing up current firewall rules"
TS="$(date +%Y%m%d_%H%M%S)"
V4_BACKUP="/root/iptables-backup-${TS}.rules"
V6_BACKUP="/root/ip6tables-backup-${TS}.rules"
iptables-save > "$V4_BACKUP"
ip6tables-save > "$V6_BACKUP"

if command -v at >/dev/null 2>&1; then
  ROLLBACK_SCRIPT="/root/iptables-rollback-${TS}.sh"
  cat > "$ROLLBACK_SCRIPT" <<EOF
#!/usr/bin/env bash
set -e
iptables-restore < "$V4_BACKUP"
ip6tables-restore < "$V6_BACKUP"
EOF
  chmod 700 "$ROLLBACK_SCRIPT"
  ROLLBACK_JOB="$(echo "bash $ROLLBACK_SCRIPT" | at now + 5 minutes 2>&1 | awk '/job/ {print $2}' | tail -1)"
  if [[ -n "${ROLLBACK_JOB:-}" ]]; then
    echo "Safety rollback scheduled in 5 minutes (job: $ROLLBACK_JOB)."
    echo "Cancel after successful verification: atrm $ROLLBACK_JOB"
  else
    echo "WARN: could not confirm rollback job creation."
  fi
else
  echo "WARN: 'at' is not installed; no automatic rollback scheduled."
fi

echo "[4/5] Applying Cloudflare-only allowlist for 80/443"
iptables -N CF_EDGE_V4 2>/dev/null || true
iptables -F CF_EDGE_V4
iptables -A CF_EDGE_V4 -p tcp -m multiport ! --dports 80,443 -j RETURN
iptables -A CF_EDGE_V4 -i lo -j RETURN
iptables -A CF_EDGE_V4 -s 127.0.0.0/8 -j RETURN
iptables -A CF_EDGE_V4 -s 10.0.0.0/8 -j RETURN
iptables -A CF_EDGE_V4 -s 172.16.0.0/12 -j RETURN
iptables -A CF_EDGE_V4 -s 192.168.0.0/16 -j RETURN
while IFS= read -r ip; do
  [[ -z "$ip" ]] && continue
  iptables -A CF_EDGE_V4 -p tcp -s "$ip" --dport 80 -j ACCEPT
  iptables -A CF_EDGE_V4 -p tcp -s "$ip" --dport 443 -j ACCEPT
done < <(curl -fsSL https://www.cloudflare.com/ips-v4)
iptables -A CF_EDGE_V4 -p tcp --dport 80 -j DROP
iptables -A CF_EDGE_V4 -p tcp --dport 443 -j DROP
iptables -C INPUT -j CF_EDGE_V4 2>/dev/null || iptables -I INPUT 1 -j CF_EDGE_V4

ip6tables -N CF_EDGE_V6 2>/dev/null || true
ip6tables -F CF_EDGE_V6
ip6tables -A CF_EDGE_V6 -p tcp -m multiport ! --dports 80,443 -j RETURN
ip6tables -A CF_EDGE_V6 -i lo -j RETURN
while IFS= read -r ip; do
  [[ -z "$ip" ]] && continue
  ip6tables -A CF_EDGE_V6 -p tcp -s "$ip" --dport 80 -j ACCEPT
  ip6tables -A CF_EDGE_V6 -p tcp -s "$ip" --dport 443 -j ACCEPT
done < <(curl -fsSL https://www.cloudflare.com/ips-v6)
ip6tables -A CF_EDGE_V6 -p tcp --dport 80 -j DROP
ip6tables -A CF_EDGE_V6 -p tcp --dport 443 -j DROP
ip6tables -C INPUT -j CF_EDGE_V6 2>/dev/null || ip6tables -I INPUT 1 -j CF_EDGE_V6

echo "[5/5] Done. Active rules summary:"
iptables -S CF_EDGE_V4 | sed -n '1,25p'
ip6tables -S CF_EDGE_V6 | sed -n '1,25p'

echo "Rollback backups:"
echo "  ipv4: $V4_BACKUP"
echo "  ipv6: $V6_BACKUP"
