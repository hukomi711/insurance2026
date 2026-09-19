#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="${DEPLOY_DIR:-/home/forge/insurance2026}"
cd "$DEPLOY_DIR"

# Renew certificates using webroot (no need to stop nginx)
docker run --rm \
  -v "$PWD/docker/certbot/conf:/etc/letsencrypt" \
  -v "$PWD/docker/certbot/www:/var/www/certbot" \
  certbot/certbot renew \
    --webroot -w /var/www/certbot \
    --non-interactive --quiet --no-random-sleep-on-renew

# Reload nginx so renewed cert is picked up
if docker ps --format "{{.Names}}" | grep -qx "ins2026-nginx"; then
  docker exec ins2026-nginx nginx -s reload >/dev/null 2>&1 || docker compose restart nginx >/dev/null 2>&1 || true
fi
