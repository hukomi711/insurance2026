#!/bin/bash
# ============================================================================
# VPS Initial Setup Script - AlmaLinux 9
# Domain: tamnyfordr.online
# IP: 66.29.149.94
# ============================================================================
# Run as root: sudo bash vps-initial-setup.sh
# ============================================================================

set -e  # Exit on error

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         Insurance2026 VPS Initial Setup                       ║"
echo "║         AlmaLinux 9 - 66.29.149.94                            ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root (sudo)"
   exit 1
fi

# ============================================================================
# Phase 1: System Updates & Basic Setup
# ============================================================================
echo "📦 Phase 1: System Updates & Dependencies..."
echo ""

yum update -y
yum upgrade -y

yum install -y \
  curl \
  wget \
  git \
  nano \
  htop \
  zip \
  unzip \
  ca-certificates \
  openssl \
  net-tools

echo "✅ System updated and basic tools installed"
echo ""

# ============================================================================
# Phase 2: PHP 8.3 Installation
# ============================================================================
echo "📦 Phase 2: Installing PHP 8.3..."
echo ""

yum install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm

yum module enable php:remi-8.3 -y

yum install -y \
  php-cli \
  php-fpm \
  php-pdo \
  php-mysql \
  php-redis \
  php-mbstring \
  php-json \
  php-xml \
  php-bcmath \
  php-curl \
  php-gd \
  php-zip \
  php-dom \
  php-intl \
  php-opcache \
  php-process

php --version
echo "✅ PHP 8.3 installed"
echo ""

# ============================================================================
# Phase 3: Composer Installation
# ============================================================================
echo "📦 Phase 3: Installing Composer..."
echo ""

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

composer --version
echo "✅ Composer installed"
echo ""

# ============================================================================
# Phase 4: MariaDB Installation
# ============================================================================
echo "📦 Phase 4: Installing MariaDB..."
echo ""

yum install -y mariadb-server mariadb

systemctl start mariadb
systemctl enable mariadb

echo "✅ MariaDB installed and running"
echo ""
echo "⚠️  IMPORTANT: Run 'sudo mysql_secure_installation' to secure MariaDB"
echo ""

# ============================================================================
# Phase 5: Redis Installation
# ============================================================================
echo "📦 Phase 5: Installing Redis..."
echo ""

yum install -y redis

systemctl start redis
systemctl enable redis

redis-cli ping
echo "✅ Redis installed and running"
echo ""

# ============================================================================
# Phase 6: Nginx Installation
# ============================================================================
echo "📦 Phase 6: Installing Nginx..."
echo ""

yum install -y nginx

systemctl start nginx
systemctl enable nginx

nginx -v
echo "✅ Nginx installed and running"
echo ""

# ============================================================================
# Phase 7: Node.js Installation
# ============================================================================
echo "📦 Phase 7: Installing Node.js 20 LTS..."
echo ""

curl -fsSL https://rpm.nodesource.com/setup_20.x | bash
yum install -y nodejs

node --version
npm --version
echo "✅ Node.js 20 installed"
echo ""

# ============================================================================
# Phase 8: Supervisor Installation
# ============================================================================
echo "📦 Phase 8: Installing Supervisor..."
echo ""

yum install -y supervisor

systemctl enable supervisord
systemctl start supervisord

supervisorctl --version
echo "✅ Supervisor installed and running"
echo ""

# ============================================================================
# Phase 9: Certbot Installation
# ============================================================================
echo "📦 Phase 9: Installing Certbot..."
echo ""

yum install -y certbot python3-certbot-nginx

certbot --version
echo "✅ Certbot installed"
echo ""

# ============================================================================
# Phase 10: Create Application Directory
# ============================================================================
echo "📦 Phase 10: Creating application directory..."
echo ""

mkdir -p /opt/insurance2026
chown -R www-data:www-data /opt/insurance2026

echo "✅ Application directory created at /opt/insurance2026"
echo ""

# ============================================================================
# Summary
# ============================================================================
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         ✅ Initial Setup Complete!                           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Next Steps:"
echo "1. Secure MariaDB:"
echo "   sudo mysql_secure_installation"
echo ""
echo "2. Create database and user:"
echo "   mysql -u root -p"
echo "   CREATE DATABASE insurance2026;"
echo "   CREATE USER 'ins_user'@'localhost' IDENTIFIED BY 'PASSWORD';"
echo "   GRANT ALL ON insurance2026.* TO 'ins_user'@'localhost';"
echo "   FLUSH PRIVILEGES;"
echo ""
echo "3. Clone repository:"
echo "   cd /opt/insurance2026"
echo "   git clone <repo-url> ."
echo ""
echo "4. Copy environment file:"
echo "   cp /opt/insurance2026/.env.production.example /opt/insurance2026/.env"
echo "   nano /opt/insurance2026/.env"
echo ""
echo "5. Run application deployment:"
echo "   bash /opt/insurance2026/vps-deploy-app.sh"
echo ""
