#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — AlmaLinux 9 VPS Initial Setup
# ═══════════════════════════════════════════════════════════════════
# Run once as root on a fresh AlmaLinux 9 VPS:
#   chmod +x docker/scripts/setup-server.sh
#   sudo bash docker/scripts/setup-server.sh
# ═══════════════════════════════════════════════════════════════════
set -euo pipefail

APP_DIR="/opt/tamicomz"
DEPLOY_USER="deploy"

echo "══════════════════════════════════════════════════════════════"
echo "  Insurance 2026 — Server Setup (AlmaLinux 9)"
echo "══════════════════════════════════════════════════════════════"

# ── 1. System update ─────────────────────────────────────────────
echo "[1/7] Updating system packages..."
dnf update -y
dnf install -y epel-release
dnf install -y curl wget git nano unzip htop ncdu tmux firewalld fail2ban

# ── 2. Firewall ──────────────────────────────────────────────────
echo "[2/7] Configuring firewall..."
systemctl enable --now firewalld
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-service=ssh
firewall-cmd --reload

# ── 3. Fail2Ban ──────────────────────────────────────────────────
echo "[3/7] Configuring Fail2Ban..."
cat > /etc/fail2ban/jail.local <<'JAIL'
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port    = ssh
filter  = sshd
logpath = /var/log/secure
JAIL

systemctl enable --now fail2ban

# ── 4. Deploy user ──────────────────────────────────────────────
echo "[4/7] Creating deploy user..."
if ! id "$DEPLOY_USER" &>/dev/null; then
    useradd -m -s /bin/bash "$DEPLOY_USER"
    usermod -aG wheel "$DEPLOY_USER"
    echo "  -> User '$DEPLOY_USER' created. Set password with: passwd $DEPLOY_USER"
else
    echo "  -> User '$DEPLOY_USER' already exists."
fi

# ── 5. Docker Engine ────────────────────────────────────────────
echo "[5/7] Installing Docker Engine..."
if ! command -v docker &>/dev/null; then
    dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
    dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    systemctl enable --now docker
    usermod -aG docker "$DEPLOY_USER"
    echo "  -> Docker installed and $DEPLOY_USER added to docker group."
else
    echo "  -> Docker already installed."
fi

# ── 6. Application directory ────────────────────────────────────
echo "[6/7] Creating application directory..."
mkdir -p "$APP_DIR"
chown "$DEPLOY_USER":"$DEPLOY_USER" "$APP_DIR"

# ── 7. SSH hardening ────────────────────────────────────────────
echo "[7/7] Hardening SSH..."
SSHD_CONFIG="/etc/ssh/sshd_config"
sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/' "$SSHD_CONFIG"
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' "$SSHD_CONFIG"
sed -i 's/^#\?MaxAuthTries.*/MaxAuthTries 3/' "$SSHD_CONFIG"

echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  IMPORTANT: Before restarting SSH, ensure you have set up"
echo "  SSH key authentication for the '$DEPLOY_USER' user!"
echo ""
echo "  On your LOCAL machine:"
echo "    ssh-copy-id $DEPLOY_USER@$(hostname -I | awk '{print $1}')"
echo ""
echo "  Then restart SSH:"
echo "    systemctl restart sshd"
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "  Next steps:"
echo "  1. Set deploy user password:  passwd $DEPLOY_USER"
echo "  2. Copy SSH key (see above)"
echo "  3. Restart sshd:  systemctl restart sshd"
echo "  4. Clone repo to $APP_DIR"
echo "  5. Run deploy script:  bash docker/scripts/deploy.sh"
echo "══════════════════════════════════════════════════════════════"
