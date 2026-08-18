#!/bin/bash

# Insurance2026 Complete Server Setup
# Run this on fresh AlmaLinux 9 server
# Execute: bash server-init.sh

set -e

PROJECT_ROOT="/opt/insurance2026"
REPO_URL="https://github.com/mobanihani99/tameni2026.git"
BRANCH="hardening/clean-rebuild"
DB_PASSWORD="insurance2026"
REDIS_PASSWORD=""

echo "╔════════════════════════════════════════════════════════╗"
echo "║  🚀 INSURANCE2026 - COMPLETE SERVER SETUP               ║"
echo "║     Server: server1.ttamikomzz.com (209.74.64.215)      ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

# Step 1: Update system
echo "📦 Step 1: Updating system packages..."
dnf update -y
dnf install -y epel-release

# Step 2: Install Docker
echo ""
echo "🐳 Step 2: Installing Docker CE and Compose v2..."

dnf config-manager --add-repo=https://download.docker.com/linux/centos/docker-ce.repo || true

dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable docker
systemctl start docker

docker --version
docker compose version

# Step 3: Install dependencies
echo ""
echo "📚 Step 3: Installing dependencies..."
dnf install -y \
  git \
  curl \
  wget \
  python3 \
  python3-pip \
  npm \
  nodejs \
  openssl

# Step 4: Configure firewall
echo ""
echo "🔥 Step 4: Configuring firewall..."
if ! systemctl is-active --quiet firewalld; then
  systemctl enable --now firewalld
fi
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-port=8080/tcp  # Reverb WebSocket
firewall-cmd --reload
echo "✅ Firewall configured (HTTP, HTTPS, WebSocket 8080 allowed)"

# Step 5: Clone repository
echo ""
echo "📥 Step 5: Cloning repository..."
mkdir -p /opt
cd /opt
if [ -d "insurance2026" ]; then
  rm -rf insurance2026
fi
git clone "$REPO_URL" insurance2026
cd "$PROJECT_ROOT"
git checkout "$BRANCH"
echo "✅ Repository cloned: $BRANCH"

# Step 6: Create .env file
echo ""
echo "⚙️  Step 6: Creating production environment..."
cat > "$PROJECT_ROOT/.env" << 'ENVFILE'
APP_NAME=Insurance2026
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ttamikomzz.com
APP_TIMEZONE=Asia/Riyadh

ASSET_URL=https://ttamikomzz.com/build/assets
VITE_ASSET_URL=https://ttamikomzz.com/build/assets

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=insurance2026
DB_USERNAME=insurance
DB_PASSWORD=insurance2026

# Cache & Session & Queue
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS=noreply@ttamikomzz.com
MAIL_FROM_NAME="Insurance2026"

# Broadcasting
BROADCAST_DRIVER=reverb
REVERB_APP_ID=insurance2026
REVERB_APP_KEY=insurance2026_key
REVERB_APP_SECRET=insurance2026_secret
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=https

# Security
APP_KEY=base64:dSz+uKpMDKVxBjVAT9n6/E1Q3Z9wP7mN0K8qL3vR4hI=
CIPHER=AES-256-CBC
ENVFILE

chmod 640 "$PROJECT_ROOT/.env"
echo "✅ Environment configured"

# Step 7: Build Docker image
echo ""
echo "🔨 Step 7: Building Docker image (this may take 10-15 minutes)..."
cd "$PROJECT_ROOT"
docker compose build app --no-cache
echo "✅ Docker image built"

# Step 8: Start services
echo ""
echo "🚀 Step 8: Starting services..."
docker compose up -d
echo "✅ Services started"

# Step 9: Wait for services to be healthy
echo ""
echo "⏳ Step 9: Waiting for services to stabilize (30 seconds)..."
sleep 30

# Step 10: Run migrations
echo ""
echo "🗄️  Step 10: Running database migrations..."
docker compose exec -T app php artisan migrate --force
echo "✅ Migrations completed"

# Step 11: Verify deployment
echo ""
echo "🔍 Step 11: Verifying deployment..."
echo ""
echo "📊 Service Status:"
docker compose ps
echo ""

echo "🏥 Health Check:"
curl -sk https://localhost/api/health -H "Host: ttamikomzz.com" | python3 -m json.tool
echo ""

# Step 12: Summary
echo "╔════════════════════════════════════════════════════════╗"
echo "║  ✅ SERVER SETUP COMPLETE                              ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 Production URLs:"
echo "   Homepage:   https://ttamikomzz.com/"
echo "   API Health: https://ttamikomzz.com/api/health"
echo ""
echo "📊 Services Running:"
echo "   ✓ PHP-FPM (app)     - Port 9000"
echo "   ✓ Nginx (nginx)     - Ports 80, 443"
echo "   ✓ MariaDB (db)      - Port 3306 (internal)"
echo "   ✓ Redis (redis)     - Port 6379 (internal)"
echo "   ✓ Horizon (horizon) - Queue workers"
echo "   ✓ Reverb (reverb)   - WebSocket on port 8080"
echo "   ✓ Scheduler         - Cron tasks"
echo ""
echo "📋 Useful Commands:"
echo "   View logs:       docker logs ins2026-app -f"
echo "   Bash shell:      docker compose exec app bash"
echo "   Tinker console:  docker compose exec app php artisan tinker"
echo "   Status:          docker compose ps"
echo ""
echo "🔐 Important Notes:"
echo "   - SSL certificate must be installed separately (Let's Encrypt recommended)"
echo "   - Database is initialized and migrations applied"
echo "   - Redis cache and sessions configured"
echo "   - Queue workers running via Horizon"
echo "   - Email will be logged locally (configure SMTP if needed)"
echo ""
