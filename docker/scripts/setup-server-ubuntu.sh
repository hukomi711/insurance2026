#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — Ubuntu 20.04+ VPS Initial Setup
# ═══════════════════════════════════════════════════════════════════
# Run once as root on a fresh Ubuntu VPS:
#   bash docker/scripts/setup-server-ubuntu.sh
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

APP_DIR="/opt/tamincom"
DEPLOY_USER="deploy"
DOMAIN="watheeq.plus"

echo "══════════════════════════════════════════════════════════════"
echo "  Insurance 2026 — Server Setup (Ubuntu)"
echo "══════════════════════════════════════════════════════════════"

# ── 1. System update ─────────────────────────────────────────────
echo "[1/8] Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt update
apt upgrade -y
apt install -y curl wget git nano unzip htop ncdu tmux \
    ca-certificates gnupg lsb-release fail2ban certbot ufw

# ── 2. Firewall (ufw) ───────────────────────────────────────────
echo "[2/8] Configuring firewall..."
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
echo "  -> UFW enabled: SSH(22), HTTP(80), HTTPS(443)"

# ── 3. Fail2Ban ──────────────────────────────────────────────────
echo "[3/8] Configuring Fail2Ban..."
cat > /etc/fail2ban/jail.local <<'JAIL'
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port    = ssh
filter  = sshd
logpath = /var/log/auth.log
JAIL

systemctl enable --now fail2ban

# ── 4. Deploy user ──────────────────────────────────────────────
echo "[4/8] Creating deploy user..."
if ! id "$DEPLOY_USER" &>/dev/null; then
    useradd -m -s /bin/bash "$DEPLOY_USER"
    usermod -aG sudo "$DEPLOY_USER"
    echo "  -> User '$DEPLOY_USER' created."
else
    echo "  -> User '$DEPLOY_USER' already exists."
fi

# ── 5. Docker Engine ────────────────────────────────────────────
echo "[5/8] Installing Docker Engine..."
if ! command -v docker &>/dev/null; then
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
        | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
        https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" \
        > /etc/apt/sources.list.d/docker.list
    apt update
    apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    systemctl enable --now docker
    usermod -aG docker "$DEPLOY_USER"
    echo "  -> Docker installed and $DEPLOY_USER added to docker group."
else
    echo "  -> Docker already installed: $(docker --version)"
fi

# ── 6. Application directory ────────────────────────────────────
echo "[6/8] Creating application directory..."
mkdir -p "$APP_DIR"
chown "$DEPLOY_USER":"$DEPLOY_USER" "$APP_DIR"

# ── 7. Certbot directories ──────────────────────────────────────
echo "[7/8] Preparing certbot mount paths..."
mkdir -p "$APP_DIR/docker/certbot/conf/live/$DOMAIN"
mkdir -p "$APP_DIR/docker/certbot/www"

# ── 8. SSH hardening ────────────────────────────────────────────
echo "[8/8] Hardening SSH..."
SSHD_CONFIG="/etc/ssh/sshd_config"
sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/' "$SSHD_CONFIG"
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' "$SSHD_CONFIG"
sed -i 's/^#\?MaxAuthTries.*/MaxAuthTries 3/' "$SSHD_CONFIG"

SERVER_IP=$(hostname -I | awk '{print $1}')

echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  Server setup complete!"
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "  ⚠  BEFORE restarting SSH, set up key auth for '$DEPLOY_USER':"
echo ""
echo "      On your LOCAL machine:"
echo "        ssh-copy-id $DEPLOY_USER@$SERVER_IP"
echo ""
echo "      Then restart SSH:"
echo "        systemctl restart sshd"
echo ""
echo "  Next steps:"
echo "    1. Set deploy user password:  passwd $DEPLOY_USER"
echo "    2. Copy SSH key (above)"
echo "    3. Upload project files to $APP_DIR"
echo "    4. Run: bash docker/scripts/first-deploy.sh"
echo "    5. Provision SSL (certbot)"
echo "    6. Run: bash docker/scripts/deploy.sh"
echo "══════════════════════════════════════════════════════════════"
