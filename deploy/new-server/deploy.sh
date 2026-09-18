#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# Insurance2026 — One-shot deploy to NEW server (Ubuntu/AlmaLinux)
#
# Run from your local machine (Git Bash) AFTER:
#   1. DNS A record:  $INS_DOMAIN -> $INS_SERVER_IP  (verify with: nslookup $INS_DOMAIN 8.8.8.8)
#   2. SSH key auth working: ssh -i $SSH_KEY root@$INS_SERVER_IP echo OK
#   3. Server has >= 2GB RAM (recommended 4GB)
#
# Usage (fresh deploy, no dump):
#   INS_SERVER_IP=<server-ip> \
#   INS_DOMAIN=<your-domain> \
#   INS_REPO_URL=git@github.com:<user>/insurance2026.git \
#   INS_BRANCH=main \
#   bash deploy/new-server/deploy.sh
#
# Authentication for private repos:
#   - Preferred: SSH URL (git@github.com:...). The script generates a deploy
#     key on the server, prints its public half, and pauses while you add it
#     to the repo's Deploy Keys (read-only).
#   - Alternative: HTTPS URL + INS_GIT_TOKEN env var (PAT). The token is
#     used once and stripped from origin URL post-clone. Avoid if possible:
#     tokens may end up in shell history or process listings.
#
# Optional env vars:
#   SSH_KEY            path to private key (default: ~/.ssh/id_ed25519)
#   INS_DEPLOY_USER    SSH user (default: root)
#   INS_DEPLOY_DIR     remote dir (default: /opt/insurance2026)
#   INS_BRANCH         git branch to deploy (default: main)
#   INS_DB_DUMP        path to local .sql.gz to import (default: skip if missing)
#   INS_GIT_TOKEN      GitHub PAT (only when using HTTPS URL for private repo)
#   LE_EMAIL           Let's Encrypt contact (default: admin@$INS_DOMAIN)
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# Prompt for missing required values when script runs in an interactive shell.
prompt_var_if_missing() {
  local var_name="$1"
  local prompt_text="$2"
  local value="${!var_name:-}"

  if [[ -z "$value" && -t 0 ]]; then
    while [[ -z "$value" ]]; do
      read -r -p "$prompt_text" value
      if [[ -z "$value" ]]; then
        echo "  ! Value is required for $var_name. Enter a value or press Ctrl+C to cancel."
      fi
    done
    export "$var_name=$value"
  fi
}

prompt_var_if_missing INS_SERVER_IP "INS_SERVER_IP (e.g. 168.144.13.251): "
prompt_var_if_missing INS_DOMAIN "INS_DOMAIN (e.g. temp.tameni.local): "
prompt_var_if_missing INS_REPO_URL "INS_REPO_URL (e.g. git@github.com:mobanihani99/tameni2026.git): "

# ───── Required env vars ─────
missing_vars=()
for v in INS_SERVER_IP INS_DOMAIN INS_REPO_URL; do
  if [[ -z "${!v:-}" ]]; then
    missing_vars+=("$v")
  fi
done

if (( ${#missing_vars[@]} > 0 )); then
  echo "✗ Missing required environment variable(s): ${missing_vars[*]}"
  echo ""
  echo "Export variables in the current shell, then rerun the script."
  echo "Example:"
  echo "  export INS_SERVER_IP=168.144.13.251"
  echo "  export INS_DOMAIN=temp.tameni.local"
  echo "  export INS_REPO_URL='git@github.com:mobanihani99/tameni2026.git'"
  echo "  export INS_BRANCH=main"
  echo "  export SSH_KEY=\"$HOME/.ssh/insurance2026_deploy\""
  echo "  bash deploy/new-server/deploy.sh"
  exit 1
fi

NEW_IP="${INS_SERVER_IP}"
DOMAIN="${INS_DOMAIN}"
WWW_DOMAIN="www.${DOMAIN}"

# Prefer the repository deploy key when available.
if [[ -n "${SSH_KEY:-}" ]]; then
  SSH_KEY="${SSH_KEY}"
elif [[ -f "$HOME/.ssh/insurance2026_deploy" ]]; then
  SSH_KEY="$HOME/.ssh/insurance2026_deploy"
else
  SSH_KEY="$HOME/.ssh/id_ed25519"
fi

SSH_USER="${INS_DEPLOY_USER:-root}"
REPO_URL="${INS_REPO_URL}"
BRANCH="${INS_BRANCH:-main}"
DEPLOY_DIR="${INS_DEPLOY_DIR:-/opt/insurance2026}"
DUMP_LOCAL="${INS_DB_DUMP:-$(dirname "$0")/insurance2026.sql.gz}"
ENV_LOCAL="$(dirname "$0")/.env.production.template"
LE_EMAIL="${LE_EMAIL:-admin@${DOMAIN}}"
ENABLE_SSL="${INS_ENABLE_SSL:-auto}"      # auto|true|false
RUN_SEEDER="${INS_RUN_SEEDER:-false}"      # true|false
IMPORT_DUMP="${INS_IMPORT_DUMP:-auto}"     # auto|true|false
FORGE_SERVER_ID="${FORGE_SERVER_ID:-${INS_FORGE_SERVER_ID:-}}"
BOOTSTRAP_SSH_SCRIPT="$(dirname "$0")/forge-bootstrap-ssh-access.sh"

SSH="ssh -i $SSH_KEY -o StrictHostKeyChecking=accept-new $SSH_USER@$NEW_IP"
SCP="scp -i $SSH_KEY -o StrictHostKeyChecking=accept-new"

echo "═══════════════════════════════════════════════════════════════"
echo "  Deploying to: $NEW_IP  (domain: $DOMAIN, branch: $BRANCH)"
echo "═══════════════════════════════════════════════════════════════"

bootstrap_ssh_access_if_possible() {
  if [[ ! -f "$BOOTSTRAP_SSH_SCRIPT" ]]; then
    return 1
  fi

  if [[ -z "$FORGE_SERVER_ID" ]]; then
    if [[ -t 0 ]]; then
      read -r -p "FORGE_SERVER_ID (required for auto SSH bootstrap): " FORGE_SERVER_ID
      export FORGE_SERVER_ID
    fi
  fi

  if [[ -z "$FORGE_SERVER_ID" ]]; then
    echo "  ℹ FORGE_SERVER_ID (or INS_FORGE_SERVER_ID) is not set; skipping automatic SSH bootstrap."
    return 1
  fi

  if [[ -f "${SSH_KEY}.pub" ]]; then
    export SSH_PUBLIC_KEY_FILE="${SSH_PUBLIC_KEY_FILE:-${SSH_KEY}.pub}"
  fi
  export SSH_PRIVATE_KEY_FILE="${SSH_PRIVATE_KEY_FILE:-$SSH_KEY}"
  export FORGE_SERVER_ID
  export FORGE_SERVER_IP="$NEW_IP"
  export FORGE_KEY_USERS="${FORGE_KEY_USERS:-root,forge}"

  if [[ -z "${FORGE_API_TOKEN:-}" && -z "${FORGE_API_TOKEN_FILE:-}" ]]; then
    echo "  ℹ Forge token not pre-set; bootstrap will prompt for token interactively."
  fi

  echo "  → Attempting automatic Forge SSH bootstrap..."
  bash "$BOOTSTRAP_SSH_SCRIPT"
}

# ───── 0. Pre-flight ─────
echo; echo "[0/9] Pre-flight checks..."
if ! SSH_PREFLIGHT_OUT="$($SSH 'echo "  ✓ SSH OK; uname: $(uname -r); RAM: $(free -m | awk "/^Mem:/ {print \$2\"MB\"}"); disk: $(df -h / | awk "NR==2 {print \$4\" free\"}")"' 2>&1)"; then
  echo "$SSH_PREFLIGHT_OUT"
  if echo "$SSH_PREFLIGHT_OUT" | grep -qi 'Permission denied (publickey)'; then
    echo ""
    echo "✗ SSH public-key authentication failed for ${SSH_USER}@${NEW_IP}."
    echo "  Ensure this public key is installed on the server: ${SSH_KEY}.pub"
    if bootstrap_ssh_access_if_possible; then
      echo "  ✓ Bootstrap finished. Retrying SSH pre-flight..."
      if ! SSH_PREFLIGHT_OUT="$($SSH 'echo "  ✓ SSH OK; uname: $(uname -r); RAM: $(free -m | awk "/^Mem:/ {print \$2\"MB\"}"); disk: $(df -h / | awk "NR==2 {print \$4\" free\"}")"' 2>&1)"; then
        echo "$SSH_PREFLIGHT_OUT"
        echo ""
        echo "✗ SSH still failing after automatic bootstrap."
        exit 1
      fi
      echo "$SSH_PREFLIGHT_OUT"
    else
      if [[ -f "${SSH_KEY}.pub" ]]; then
        echo ""
        echo "To bootstrap access through Forge API (no SSH required):"
        echo "  export FORGE_API_TOKEN='<your-token>'"
        echo "  export FORGE_SERVER_ID='1254814'"
        echo "  export FORGE_SERVER_IP='${NEW_IP}'"
        echo "  export SSH_PUBLIC_KEY_FILE='${SSH_KEY}.pub'"
        echo "  bash deploy/new-server/forge-bootstrap-ssh-access.sh"
      else
        echo ""
        echo "Public key file not found at ${SSH_KEY}.pub"
        echo "Generate one or set SSH_KEY to an existing private key path."
      fi
      exit 1
    fi
  fi
fi

echo "$SSH_PREFLIGHT_OUT"

[[ -f "$ENV_LOCAL" ]] || { echo "  ✗ Missing $ENV_LOCAL"; exit 1; }

# Verify env template still contains required placeholders to substitute
if ! grep -q '__DOMAIN__' "$ENV_LOCAL"; then
  echo "  ⚠ $ENV_LOCAL contains no __DOMAIN__ placeholder — already rendered?"
fi
# Only treat __CHANGE_ME__ on non-comment lines as a real value to fill.
if grep -E '^[[:space:]]*[^#[:space:]].*__CHANGE_ME__' "$ENV_LOCAL" >/dev/null; then
  echo "  ✗ $ENV_LOCAL still contains __CHANGE_ME__ values (non-comment). Fill them first."
  grep -nE '^[[:space:]]*[^#[:space:]].*__CHANGE_ME__' "$ENV_LOCAL"
  exit 1
fi

# DB dump is optional
HAS_DUMP=0
if [[ -f "$DUMP_LOCAL" ]]; then
  HAS_DUMP=1
  echo "  ✓ DB dump found: $DUMP_LOCAL ($(du -h "$DUMP_LOCAL" | cut -f1))"
else
  echo "  ℹ No DB dump at $DUMP_LOCAL — fresh install, will run migrations only"
fi

# Verify DNS resolves to new server. nslookup output structure:
#   Server:   8.8.8.8
#   Address:  8.8.8.8#53        ← server line (skip)
#
#   Name:     example.com
#   Address:  1.2.3.4            ← answer (keep)
# We skip lines until we see "Name:", then read subsequent Address lines.
RESOLVED="$(nslookup "$DOMAIN" 8.8.8.8 2>/dev/null \
  | awk '/^Name:/ {found=1; next} found && /^Address: / {print $2; exit}' \
  || true)"
DNS_MATCH=0
if [[ -n "$RESOLVED" && "$RESOLVED" == "$NEW_IP" ]]; then
  DNS_MATCH=1
fi

if [[ "$ENABLE_SSL" == "auto" ]]; then
  if [[ "$DNS_MATCH" -eq 1 ]]; then
    ENABLE_SSL=true
  else
    ENABLE_SSL=false
  fi
fi

if [[ "$ENABLE_SSL" == "true" && "$DNS_MATCH" -eq 0 ]]; then
  echo "  ⚠ DNS for $DOMAIN resolves to '${RESOLVED:-<empty>}' (expected $NEW_IP)"
  echo "    SSL enabled but DNS not ready; cert issuance likely to fail."
fi

if [[ "$ENABLE_SSL" == "false" ]]; then
  echo "  ℹ SSL issuance disabled for this run (INS_ENABLE_SSL=false or DNS auto-skip)."
fi

if [[ "$IMPORT_DUMP" == "auto" ]]; then
  if [[ "$HAS_DUMP" -eq 1 ]]; then
    IMPORT_DUMP=true
  else
    IMPORT_DUMP=false
  fi
fi

# ───── 1. Install OS deps (Ubuntu/AlmaLinux) ─────
echo; echo "[1/9] Installing Docker, git, curl on target OS..."
$SSH 'set -e
  # Always ensure base tools (idempotent; cheap when already present).
  if command -v dnf >/dev/null 2>&1; then
    dnf -y install git curl tar gzip bind-utils openssl >/dev/null
    if ! command -v docker >/dev/null; then
      dnf -y install dnf-plugins-core
      dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
      dnf -y install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
      systemctl enable --now docker
    fi
  elif command -v apt-get >/dev/null 2>&1; then
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y >/dev/null
    apt-get install -y ca-certificates curl gnupg lsb-release git tar gzip dnsutils openssl >/dev/null
    if ! command -v docker >/dev/null; then
      install -m 0755 -d /etc/apt/keyrings
      curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
      chmod a+r /etc/apt/keyrings/docker.gpg
      . /etc/os-release
      echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" > /etc/apt/sources.list.d/docker.list
      apt-get update -y >/dev/null
      apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin >/dev/null
      systemctl enable --now docker
    fi
  else
    echo "Unsupported package manager on target host"
    exit 1
  fi

  command -v docker; docker --version; docker compose version; git --version

  # Open firewall (idempotent)
  if command -v firewall-cmd >/dev/null && systemctl is-active --quiet firewalld; then
    firewall-cmd --permanent --add-service=http  || true
    firewall-cmd --permanent --add-service=https || true
    firewall-cmd --reload || true
  fi
'

# Ensure current user can run docker compose immediately.
$SSH 'set -e
  if command -v docker >/dev/null 2>&1; then
    usermod -aG docker "$(id -un)" >/dev/null 2>&1 || true
  fi
'

# ───── 2. Clone / update repo on the configured branch ─────
# Auth strategy:
#   - SSH URL (git@github.com:...) → use a server-side deploy key.
#     If missing, the script generates /root/.ssh/id_ed25519, prints the
#     public key, and waits for you to register it as a Deploy Key in GitHub.
#   - HTTPS URL with INS_GIT_TOKEN set → inject token in URL once, then
#     scrub it from origin/.git/config after the clone.
#   - HTTPS URL without token → public repo, fetched anonymously.
echo; echo "[2/9] Cloning repository (branch: $BRANCH)..."

if [[ "$REPO_URL" =~ ^git@ || "$REPO_URL" =~ ^ssh:// ]]; then
  # ─ SSH deploy key path ─
  echo "  → SSH URL detected; ensuring server has a deploy key..."
  PUBKEY="$($SSH 'set -e
    if [[ ! -f /root/.ssh/id_ed25519 ]]; then
      mkdir -p /root/.ssh && chmod 700 /root/.ssh
      ssh-keygen -t ed25519 -C "deploy@$(hostname)" -f /root/.ssh/id_ed25519 -N "" -q
    fi
    # Add github.com to known_hosts (idempotent)
    ssh-keyscan -t ed25519,rsa github.com 2>/dev/null | sort -u | \
      while read -r line; do grep -qxF "$line" /root/.ssh/known_hosts 2>/dev/null || echo "$line" >> /root/.ssh/known_hosts; done
    chmod 644 /root/.ssh/known_hosts
    cat /root/.ssh/id_ed25519.pub
  ')"
  echo
  echo "─────────────────────────────────────────────────────────────"
  echo "  Server deploy public key:"
  echo "  $PUBKEY"
  echo "─────────────────────────────────────────────────────────────"
  echo "  Add this key to GitHub:"
  echo "    Repo → Settings → Deploy keys → Add deploy key"
  echo "    Title: tamicom-prod    Allow write access: NO"
  echo "─────────────────────────────────────────────────────────────"
  # Check if GitHub auth already works (deploy key already registered)
  AUTHMSG="$($SSH 'ssh -T -o BatchMode=yes -o StrictHostKeyChecking=accept-new git@github.com 2>&1 || true' | tr -d '\r')"
  echo "  GitHub auth probe: $AUTHMSG"
  if echo "$AUTHMSG" | grep -q "successfully authenticated"; then
    echo "  ✓ Deploy key already authenticated — skipping manual prompt."
  else
    read -r -p "  Press ENTER once the deploy key is registered (or Ctrl-C to abort)... " _
    # Re-verify after manual registration
    AUTHMSG="$($SSH 'ssh -T -o BatchMode=yes -o StrictHostKeyChecking=accept-new git@github.com 2>&1 || true' | tr -d '\r')"
    echo "  GitHub auth probe: $AUTHMSG"
    if ! echo "$AUTHMSG" | grep -q "successfully authenticated"; then
      echo "  ✗ GitHub did not recognise the deploy key. Re-check and retry."
      exit 1
    fi
  fi
  $SSH "set -e
    mkdir -p $DEPLOY_DIR
    if [[ ! -d $DEPLOY_DIR/.git ]]; then
      git clone --branch '$BRANCH' '$REPO_URL' $DEPLOY_DIR
    else
      cd $DEPLOY_DIR
      git remote set-url origin '$REPO_URL'
      git fetch --all --prune
      git checkout '$BRANCH'
      git reset --hard 'origin/$BRANCH'
    fi
    cd $DEPLOY_DIR && git log -1 --oneline
  "
elif [[ -n "${INS_GIT_TOKEN:-}" ]]; then
  # ─ HTTPS + PAT path (kept for compatibility; SSH preferred) ─
  echo "  → HTTPS URL with INS_GIT_TOKEN; using token for clone, then scrubbing."
  CLONE_URL="$(printf '%s' "$REPO_URL" | sed -E "s|^https://([^@]+@)?|https://x-access-token:${INS_GIT_TOKEN}@|")"
  $SSH "set -e
    if [[ -d $DEPLOY_DIR && ! -d $DEPLOY_DIR/.git && -n \"\
      \\$(ls -A $DEPLOY_DIR 2>/dev/null)\" ]]; then
      echo '  ✗ $DEPLOY_DIR exists and is not a Git repository. Remove or rename it before retrying.'
      exit 1
    fi
    mkdir -p $DEPLOY_DIR
    if [[ ! -d $DEPLOY_DIR/.git ]]; then
      git clone --branch '$BRANCH' '$CLONE_URL' $DEPLOY_DIR
      git -C $DEPLOY_DIR remote set-url origin '$REPO_URL'
    else
      cd $DEPLOY_DIR
      git remote set-url origin '$CLONE_URL'
      git fetch --all --prune
      git checkout '$BRANCH'
      git reset --hard 'origin/$BRANCH'
      git remote set-url origin '$REPO_URL'
    fi
    cd $DEPLOY_DIR && git log -1 --oneline
  " 2>&1 | grep -v 'x-access-token'
else
  # ─ Plain HTTPS (anonymous; works only for public repos) ─
  $SSH "set -e
    mkdir -p $DEPLOY_DIR
    if [[ ! -d $DEPLOY_DIR/.git ]]; then
      git clone --branch '$BRANCH' '$REPO_URL' $DEPLOY_DIR
    else
      cd $DEPLOY_DIR
      git fetch --all --prune
      git checkout '$BRANCH'
      git reset --hard 'origin/$BRANCH'
    fi
    cd $DEPLOY_DIR && git log -1 --oneline
  "
fi

# ───── 3. Render .env.production locally, upload it + secrets ─────
echo; echo "[3/9] Rendering and uploading .env.production + secrets..."
RENDERED_ENV="$(mktemp)"
trap 'rm -f "$RENDERED_ENV"' EXIT

# Substitute __DOMAIN__ placeholder. Use literal sed delimiter to avoid clashes
# with any '/' inside the domain (none today, but future-proof).
sed "s|__DOMAIN__|$DOMAIN|g" "$ENV_LOCAL" > "$RENDERED_ENV"

# Sanity: nothing should remain unrendered.
if grep -q '__DOMAIN__' "$RENDERED_ENV" \
   || grep -E '^[[:space:]]*[^#[:space:]].*__CHANGE_ME__' "$RENDERED_ENV" >/dev/null; then
  echo "  ✗ Rendered env still contains placeholders. Aborting."
  grep -nE '__DOMAIN__|^[[:space:]]*[^#[:space:]].*__CHANGE_ME__' "$RENDERED_ENV" | head -20
  exit 1
fi

$SCP "$RENDERED_ENV" "$SSH_USER@$NEW_IP:$DEPLOY_DIR/.env.production"
$SSH "set -e
  cd $DEPLOY_DIR
  chmod 600 .env.production
  # docker compose reads .env from the project dir; mirror .env.production there
  cp .env.production .env
  chmod 600 .env

  mkdir -p docker/secrets
  # Extract DB_PASSWORD from .env.production into Docker secret file (strip quotes)
  grep '^DB_PASSWORD=' .env.production \
    | sed -e 's/^DB_PASSWORD=//' -e 's/^\"//' -e 's/\"\$//' \
    > docker/secrets/db_password.txt
  if ! [[ -s docker/secrets/db_password.txt ]]; then
    echo '  ✗ DB_PASSWORD is empty in .env.production. Fill it before deploying.'
    exit 1
  fi
  # Generate root password if not present
  [[ -s docker/secrets/db_root_password.txt ]] || openssl rand -hex 32 > docker/secrets/db_root_password.txt
  chmod 600 docker/secrets/*.txt
  ls -la docker/secrets/
"

# ───── 4. Upload DB dump (optional) ─────
if [[ "$HAS_DUMP" -eq 1 ]]; then
  echo; echo "[4/9] Uploading DB dump..."
  $SCP "$DUMP_LOCAL" "$SSH_USER@$NEW_IP:$DEPLOY_DIR/insurance2026.sql.gz"
else
  echo; echo "[4/9] Skipping DB dump upload (none provided)."
fi

# ───── 5. Issue SSL cert (standalone, before nginx is up) ─────
# nginx reads certs from /etc/nginx/ssl/live/$DOMAIN/, which is mounted from
# ./docker/certbot/conf in docker-compose.yml — write certs there.
if [[ "$ENABLE_SSL" == "true" ]]; then
  echo; echo "[5/9] Issuing SSL certificate via certbot standalone..."
  $SSH "set -e
    cd $DEPLOY_DIR
    mkdir -p docker/certbot/conf docker/certbot/www
    # If nginx is already running on :80, stop it so certbot can bind.
    docker compose stop nginx 2>/dev/null || true
    if [[ ! -f docker/certbot/conf/live/$DOMAIN/fullchain.pem ]]; then
      docker run --rm -p 80:80 \
        -v \"\$PWD/docker/certbot/conf:/etc/letsencrypt\" \
        -v \"\$PWD/docker/certbot/www:/var/www/certbot\" \
        certbot/certbot certonly --standalone --non-interactive --agree-tos \
        -m '$LE_EMAIL' -d '$DOMAIN' -d '$WWW_DOMAIN'
    else
      echo '  cert already exists, skipping'
    fi
    ls -la docker/certbot/conf/live/$DOMAIN/
  "
else
  echo; echo "[5/9] Skipping Let's Encrypt; creating temporary self-signed cert..."
  $SSH "set -e
    cd $DEPLOY_DIR
    CERT_DIR=\"docker/certbot/conf/live/$DOMAIN\"
    mkdir -p \"\$CERT_DIR\" docker/certbot/www
    if [[ ! -f \"\$CERT_DIR/fullchain.pem\" || ! -f \"\$CERT_DIR/privkey.pem\" ]]; then
      openssl req -x509 -nodes -newkey rsa:2048 -days 30 \
        -keyout \"\$CERT_DIR/privkey.pem\" \
        -out \"\$CERT_DIR/fullchain.pem\" \
        -subj '/CN=$DOMAIN' \
        -addext 'subjectAltName=DNS:$DOMAIN,DNS:$WWW_DOMAIN' 2>/dev/null \
      || openssl req -x509 -nodes -newkey rsa:2048 -days 30 \
        -keyout \"\$CERT_DIR/privkey.pem\" \
        -out \"\$CERT_DIR/fullchain.pem\" \
        -subj '/CN=$DOMAIN'
      cp \"\$CERT_DIR/fullchain.pem\" \"\$CERT_DIR/chain.pem\"
      echo '  ✓ Temporary self-signed certificate created.'
    else
      echo '  temporary cert already exists, skipping'
    fi
    ls -la \"\$CERT_DIR\"/
  "
fi

# ───── 6. Build + start stack ─────
echo; echo "[6/9] Building and starting containers (this takes ~5-10 min)..."
$SSH "set -e
  cd $DEPLOY_DIR
  docker compose pull 2>/dev/null || true
  docker compose up -d --build --force-recreate
  sleep 10
  docker compose ps
"

# ───── 7. Run migrations + (optionally) import DB ─────
echo; echo "[7/9] Running migrations..."
$SSH "set -e
  cd $DEPLOY_DIR
  # Wait for DB to be ready
  for i in \$(seq 1 30); do
    docker exec ins2026-db sh -c 'mariadb -uroot -p\$(cat /run/secrets/db_root_password) -e \"SELECT 1\"' >/dev/null 2>&1 && break || sleep 2
  done
  if [[ '$IMPORT_DUMP' == 'true' && -f insurance2026.sql.gz ]]; then
    zcat insurance2026.sql.gz | docker exec -i ins2026-db sh -c 'mariadb -uroot -p\$(cat /run/secrets/db_root_password) insurance2026'
    echo '  ✓ DB imported'
  fi
  docker exec -u root ins2026-app chown appuser:appuser /var/www/html/.env
  docker exec -u root ins2026-app chmod 640 /var/www/html/.env
  docker exec -u appuser ins2026-app php artisan migrate --force
  if [[ '$RUN_SEEDER' == 'true' ]]; then
    docker exec -u appuser ins2026-app php artisan db:seed --class=DatabaseSeeder --force
  else
    echo '  ℹ Seeder skipped (INS_RUN_SEEDER=false)'
  fi
  docker exec -u appuser ins2026-app php artisan config:cache
  docker exec -u appuser ins2026-app php artisan route:cache
  docker exec -u appuser ins2026-app php artisan view:cache
  docker exec -u appuser ins2026-app php artisan event:cache
"

# ───── 8. Health checks ─────
echo; echo "[8/9] Health checks..."
$SSH "set -e
  echo '─── containers ───'
  docker ps --format 'table {{.Names}}\t{{.Status}}'
  echo
  echo '─── /api/health ───'
  curl -sk --resolve $DOMAIN:443:127.0.0.1 https://$DOMAIN/api/health || true
  echo
  echo '─── /api/health/queues ───'
  curl -sk --resolve $DOMAIN:443:127.0.0.1 https://$DOMAIN/api/health/queues || true
  echo
  echo '─── /api/health/realtime ───'
  curl -sk --resolve $DOMAIN:443:127.0.0.1 https://$DOMAIN/api/health/realtime || true
  echo
"

# ───── 9. End-to-end test ─────
echo; echo "[9/9] End-to-end public test from local..."
for path in / /login /sitemap.xml; do
  code=$(curl -sk --resolve "$DOMAIN:443:$NEW_IP" -o /dev/null -w '%{http_code}' "https://$DOMAIN$path" || echo 000)
  echo "  $code  https://$DOMAIN$path"
done

echo
echo "═══════════════════════════════════════════════════════════════"
echo "  ✓ DEPLOYMENT COMPLETE"
echo "  Login URL:  https://$DOMAIN/login"
echo "═══════════════════════════════════════════════════════════════"
