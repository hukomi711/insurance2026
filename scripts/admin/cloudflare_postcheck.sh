#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-lexusforbon.com}"
WWW="${WWW:-www.lexusforbon.com}"

echo "== DNS =="
nslookup "$DOMAIN" 1.1.1.1 | sed -n '1,20p' || true
nslookup "$WWW" 1.1.1.1 | sed -n '1,20p' || true

echo "\n== HTTPS headers =="
for u in "https://$DOMAIN" "https://$WWW"; do
  echo "-- $u"
  curl -sSI "$u" | sed -n '1,16p'
  echo
done

echo "== API health =="
for u in "https://$DOMAIN/api/health" "https://$DOMAIN/api/health/realtime"; do
  echo "-- $u"
  curl -sS "$u" && echo
  echo
done

echo "== TLS summary =="
openssl s_client -connect "$DOMAIN:443" -servername "$DOMAIN" < /dev/null 2>/dev/null \
  | openssl x509 -noout -subject -issuer -dates -ext subjectAltName
