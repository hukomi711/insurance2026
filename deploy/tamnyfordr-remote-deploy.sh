#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${INS_DOMAIN:-tttaaammmin.xyz}"
DEPLOY_DIR="${INS_DEPLOY_DIR:-/opt/insurance2026}"
ARCHIVE_PATH="${INS_ARCHIVE_PATH:-/opt/insurance2026-upload.tar.gz}"
ENV_SOURCE="${INS_ENV_SOURCE:-/opt/insurance2026.env.production}"
TS="$(date +%Y%m%d%H%M%S)"

echo "== insurance2026 remote deploy =="
echo "domain: ${DOMAIN}"
echo "deploy_dir: ${DEPLOY_DIR}"

if [[ ! -f "$ARCHIVE_PATH" ]]; then
  echo "missing archive: $ARCHIVE_PATH"
  exit 1
fi

if [[ ! -f "$ENV_SOURCE" ]]; then
  echo "missing env source: $ENV_SOURCE"
  exit 1
fi

echo "== install base packages and docker =="
dnf -y install git curl tar gzip openssl dnf-plugins-core >/dev/null

if ! command -v docker >/dev/null 2>&1; then
  dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo >/dev/null
  dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin >/dev/null
fi

systemctl enable --now docker

if command -v firewall-cmd >/dev/null 2>&1 && systemctl is-active --quiet firewalld; then
  firewall-cmd --permanent --add-service=http >/dev/null || true
  firewall-cmd --permanent --add-service=https >/dev/null || true
  firewall-cmd --reload >/dev/null || true
fi

if [[ ! -f /swapfile ]]; then
  echo "== create 2G swapfile for docker build headroom =="
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  swapon /swapfile
  grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
elif ! swapon --show=NAME | grep -q '^/swapfile$'; then
  swapon /swapfile || true
fi

echo "== extract release =="
mkdir -p "$(dirname "$DEPLOY_DIR")"
if [[ -d "$DEPLOY_DIR" ]]; then
  mv "$DEPLOY_DIR" "${DEPLOY_DIR}.backup.${TS}"
fi
mkdir -p "$DEPLOY_DIR"
tar -xzf "$ARCHIVE_PATH" -C "$DEPLOY_DIR"

echo "== install env and docker secrets =="
cp "$ENV_SOURCE" "$DEPLOY_DIR/.env.production"
cp "$ENV_SOURCE" "$DEPLOY_DIR/.env"
chmod 600 "$DEPLOY_DIR/.env.production" "$DEPLOY_DIR/.env"

mkdir -p "$DEPLOY_DIR/docker/secrets"
grep '^DB_PASSWORD=' "$DEPLOY_DIR/.env.production" \
  | sed -e 's/^DB_PASSWORD=//' -e 's/^"//' -e 's/"$//' \
  > "$DEPLOY_DIR/docker/secrets/db_password.txt"
if [[ ! -s "$DEPLOY_DIR/docker/secrets/db_password.txt" ]]; then
  echo "DB_PASSWORD is empty"
  exit 1
fi
openssl rand -hex 32 > "$DEPLOY_DIR/docker/secrets/db_root_password.txt"
chmod 600 "$DEPLOY_DIR/docker/secrets/"*.txt

echo "== ensure temporary tls material exists =="
CERT_DIR="$DEPLOY_DIR/docker/certbot/conf/live/$DOMAIN"
mkdir -p "$CERT_DIR"
if [[ ! -f "$CERT_DIR/fullchain.pem" || ! -f "$CERT_DIR/privkey.pem" ]]; then
  openssl req -x509 -nodes -newkey rsa:2048 -days 14 \
    -keyout "$CERT_DIR/privkey.pem" \
    -out "$CERT_DIR/fullchain.pem" \
    -subj "/CN=$DOMAIN" \
    -addext "subjectAltName=DNS:$DOMAIN,DNS:www.$DOMAIN" >/dev/null 2>&1
  cp "$CERT_DIR/fullchain.pem" "$CERT_DIR/chain.pem"
fi

echo "== build and start stack =="
cd "$DEPLOY_DIR"
docker compose up -d --build --force-recreate

echo "== wait for database =="
for i in $(seq 1 60); do
  if docker exec ins2026-db sh -c 'mariadb -uroot -p$(cat /run/secrets/db_root_password) -e "SELECT 1"' >/dev/null 2>&1; then
    break
  fi
  if [[ "$i" -eq 60 ]]; then
    echo "database did not become ready"
    docker compose ps
    exit 1
  fi
  sleep 2
done

echo "== laravel migrations and caches =="
docker exec -u root ins2026-app sh -lc 'chown appuser:appuser /var/www/html/.env && chmod 640 /var/www/html/.env'
docker exec ins2026-app php artisan migrate --force
docker exec ins2026-app php artisan db:seed --class=DatabaseSeeder --force
docker exec ins2026-app php artisan config:clear
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan event:cache

echo "== status =="
docker compose ps
curl -sk "https://127.0.0.1/api/health" || true
echo
curl -sk "https://127.0.0.1/api/health/realtime" || true
echo
