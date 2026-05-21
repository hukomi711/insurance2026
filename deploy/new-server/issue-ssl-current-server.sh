#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-/opt/insurance2026}"
DOMAIN="${DOMAIN:-lexusforbon.it.com}"
EMAIL="${EMAIL:-admin@lexusforbon.it.com}"

cd "$DEPLOY_DIR"

CERT_DIR="docker/certbot/conf/live/$DOMAIN"
WEBROOT="docker/certbot/www"

echo "== temporary certificate =="
mkdir -p "$CERT_DIR" "$WEBROOT"

if [[ ! -s "$CERT_DIR/fullchain.pem" || ! -s "$CERT_DIR/privkey.pem" || ! -s "$CERT_DIR/chain.pem" ]]; then
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout "$CERT_DIR/privkey.pem" \
    -out "$CERT_DIR/fullchain.pem" \
    -subj "/CN=$DOMAIN" \
    -addext "subjectAltName=DNS:$DOMAIN,DNS:www.$DOMAIN"
  cp "$CERT_DIR/fullchain.pem" "$CERT_DIR/chain.pem"
fi

echo "== start nginx with temporary certificate =="
docker compose up -d nginx
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'ins2026-nginx|NAMES'

echo "== request letsencrypt certificate =="
docker run --rm \
  -v "$PWD/docker/certbot/conf:/etc/letsencrypt" \
  -v "$PWD/docker/certbot/www:/var/www/certbot" \
  certbot/certbot certonly \
    --webroot \
    --webroot-path /var/www/certbot \
    --email "$EMAIL" \
    --agree-tos \
    --no-eff-email \
    --non-interactive \
    --force-renewal \
    -d "$DOMAIN" \
    -d "www.$DOMAIN"

echo "== reload nginx with letsencrypt certificate =="
docker compose up -d --force-recreate nginx
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | grep -E 'ins2026-nginx|NAMES'
docker logs --tail=80 ins2026-nginx || true

echo "OK: ssl issued"
