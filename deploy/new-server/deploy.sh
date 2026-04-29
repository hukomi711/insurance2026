#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Insurance2026 — One-shot deploy to NEW server (AlmaLinux 9)
#
# Run from your local machine (Git Bash) AFTER:
#   1. DNS A record:  taminsurnce.site -> NEW_IP  (verify with: nslookup taminsurnce.site 8.8.8.8)
#   2. SSH key auth working: ssh -i ~/.ssh/id_ed25519 root@NEW_IP echo OK
#   3. Server has >= 2GB RAM (recommended 4GB)
#
# Usage:  bash deploy/new-server/deploy.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# ───── Config (edit if needed) ─────
NEW_IP="${NEW_IP:-162.0.216.105}"
DOMAIN="${DOMAIN:-taminsurnce.site}"
WWW_DOMAIN="www.${DOMAIN}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519}"
SSH_USER="${SSH_USER:-root}"
REPO_URL="${REPO_URL:-https://github.com/}"   # <-- set your git repo URL
DEPLOY_DIR="/opt/insurance2026"
DUMP_LOCAL="$(dirname "$0")/insurance2026.sql.gz"
ENV_LOCAL="$(dirname "$0")/.env.production.template"
LE_EMAIL="${LE_EMAIL:-admin@${DOMAIN}}"

SSH="ssh -i $SSH_KEY -o StrictHostKeyChecking=no $SSH_USER@$NEW_IP"
SCP="scp -i $SSH_KEY -o StrictHostKeyChecking=no"

echo "═══════════════════════════════════════════════════════════════"
echo "  Deploying to: $NEW_IP  (domain: $DOMAIN)"
echo "═══════════════════════════════════════════════════════════════"

# ───── 0. Pre-flight ─────
echo; echo "[0/9] Pre-flight checks..."
$SSH 'echo "  ✓ SSH OK; uname: $(uname -r); RAM: $(free -m | awk "/^Mem:/ {print \$2\"MB\"}"); disk: $(df -h / | awk "NR==2 {print \$4\" free\"}")"'
[[ -f "$DUMP_LOCAL" ]] || { echo "  ✗ Missing $DUMP_LOCAL"; exit 1; }
[[ -f "$ENV_LOCAL"  ]] || { echo "  ✗ Missing $ENV_LOCAL"; exit 1; }

# Verify DNS resolves to new server
RESOLVED="$(nslookup "$DOMAIN" 8.8.8.8 2>/dev/null | awk '/^Address: / {print $2}' | tail -1 || true)"
if [[ "$RESOLVED" != "$NEW_IP" ]]; then
  echo "  ⚠ DNS for $DOMAIN resolves to '$RESOLVED' (expected $NEW_IP)"
  echo "    SSL issuance WILL FAIL. Continue? [y/N]"
  read -r ans; [[ "$ans" =~ ^[Yy]$ ]] || exit 1
fi

# ───── 1. Install OS deps (AlmaLinux 9) ─────
echo; echo "[1/9] Installing Docker, git, curl on AlmaLinux..."
$SSH 'set -e
  if ! command -v docker >/dev/null; then
    dnf -y install dnf-plugins-core
    dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
    dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin git curl tar gzip
    systemctl enable --now docker
  fi
  command -v docker; docker --version; docker compose version; git --version
'

# ───── 2. Clone repo ─────
echo; echo "[2/9] Cloning repository..."
$SSH "set -e
  mkdir -p $DEPLOY_DIR
  if [[ ! -d $DEPLOY_DIR/.git ]]; then
    git clone $REPO_URL $DEPLOY_DIR
  else
    cd $DEPLOY_DIR && git fetch --all && git reset --hard origin/main
  fi
  cd $DEPLOY_DIR && git log -1 --oneline
"

# ───── 3. Upload .env + DB password secret ─────
echo; echo "[3/9] Uploading .env.production and secrets..."
$SCP "$ENV_LOCAL" "$SSH_USER@$NEW_IP:$DEPLOY_DIR/.env.production"
$SSH "set -e
  cd $DEPLOY_DIR
  mkdir -p docker/secrets
  # Extract DB_PASSWORD from .env.production into Docker secret file
  grep '^DB_PASSWORD=' .env.production | sed 's/^DB_PASSWORD=//' | sed 's/^\"\(.*\)\"\$/\1/' > docker/secrets/db_password.txt
  # Generate root password if not present
  [[ -s docker/secrets/db_root_password.txt ]] || openssl rand -hex 32 > docker/secrets/db_root_password.txt
  chmod 600 docker/secrets/*.txt
  ls -la docker/secrets/
"

# ───── 4. Upload DB dump ─────
echo; echo "[4/9] Uploading DB dump..."
$SCP "$DUMP_LOCAL" "$SSH_USER@$NEW_IP:$DEPLOY_DIR/insurance2026.sql.gz"

# ───── 5. Issue SSL cert (standalone, before nginx is up) ─────
echo; echo "[5/9] Issuing SSL certificate via certbot standalone..."
$SSH "set -e
  cd $DEPLOY_DIR
  mkdir -p docker/nginx/ssl/live/$DOMAIN
  if [[ ! -f docker/nginx/ssl/live/$DOMAIN/fullchain.pem ]]; then
    docker run --rm -p 80:80 \
      -v \$PWD/docker/nginx/ssl:/etc/letsencrypt \
      certbot/certbot certonly --standalone --non-interactive --agree-tos \
      -m $LE_EMAIL -d $DOMAIN -d $WWW_DOMAIN
  else
    echo '  cert already exists, skipping'
  fi
  ls -la docker/nginx/ssl/live/$DOMAIN/
"

# ───── 6. Build + start stack ─────
echo; echo "[6/9] Building and starting containers (this takes ~5-10 min)..."
$SSH "set -e
  cd $DEPLOY_DIR
  docker compose pull --ignore-pull-failures || true
  docker compose up -d --build
  sleep 10
  docker compose ps
"

# ───── 7. Run migrations + import DB ─────
echo; echo "[7/9] Running migrations + importing DB dump..."
$SSH "set -e
  cd $DEPLOY_DIR
  # Wait for DB to be ready
  for i in {1..30}; do
    docker exec ins2026-db sh -c 'mariadb -uroot -p\$(cat /run/secrets/db_root_password) -e \"SELECT 1\"' >/dev/null 2>&1 && break || sleep 2
  done
  # Import the dump (truncates existing data)
  zcat insurance2026.sql.gz | docker exec -i ins2026-db sh -c 'mariadb -uroot -p\$(cat /run/secrets/db_root_password) insurance2026'
  echo '  ✓ DB imported'
  # Run any pending migrations
  docker exec ins2026-app php artisan migrate --force
  docker exec ins2026-app php artisan config:clear
  docker exec ins2026-app php artisan route:cache
  docker exec ins2026-app php artisan view:cache
  docker exec ins2026-app php artisan event:cache
"

# ───── 8. Health checks ─────
echo; echo "[8/9] Health checks..."
$SSH "set -e
  echo '─── containers ───'
  docker ps --format 'table {{.Names}}\t{{.Status}}'
  echo
  echo '─── /api/health ───'
  curl -sk https://$DOMAIN/api/health
  echo
  echo '─── /api/health/queues ───'
  curl -sk https://$DOMAIN/api/health/queues
  echo
  echo '─── /api/health/realtime ───'
  curl -sk https://$DOMAIN/api/health/realtime
  echo
"

# ───── 9. End-to-end test ─────
echo; echo "[9/9] End-to-end public test from local..."
for path in / /login /sitemap.xml; do
  code=$(curl -sk -o /dev/null -w '%{http_code}' "https://$DOMAIN$path" || echo 000)
  echo "  $code  https://$DOMAIN$path"
done

echo
echo "═══════════════════════════════════════════════════════════════"
echo "  ✓ DEPLOYMENT COMPLETE"
echo "  Login URL:  https://$DOMAIN/login"
echo "═══════════════════════════════════════════════════════════════"
