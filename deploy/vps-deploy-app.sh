#!/bin/bash
# ============================================================================
# VPS Application Deployment Script
# Domain: tamnyfordr.online
# ============================================================================
# Run as root: sudo bash vps-deploy-app.sh
# Prerequisites: vps-initial-setup.sh must be run first
# ============================================================================

set -e  # Exit on error

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║    Insurance2026 Application Deployment                       ║"
echo "║    Domain: tamnyfordr.online                                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo "❌ This script must be run as root (sudo)"
   exit 1
fi

APP_DIR="/opt/insurance2026"
APP_USER="www-data"
APP_GROUP="www-data"

# ============================================================================
# Phase 1: Application Directory Setup
# ============================================================================
echo "📦 Phase 1: Setting up application directory..."
echo ""

if [ ! -d "$APP_DIR" ]; then
    echo "❌ Directory $APP_DIR not found. Please clone the repository first:"
    echo "   cd /opt/insurance2026 && git clone <repo-url> ."
    exit 1
fi

# Fix permissions
chown -R $APP_USER:$APP_GROUP $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

echo "✅ Directory permissions configured"
echo ""

# ============================================================================
# Phase 2: Environment Configuration
# ============================================================================
echo "📦 Phase 2: Checking environment configuration..."
echo ""

if [ ! -f "$APP_DIR/.env" ]; then
    echo "❌ .env file not found!"
    echo "   Please create it with: cp $APP_DIR/.env.production.example $APP_DIR/.env"
    echo "   Then edit it with your database and API credentials"
    exit 1
fi

# Check critical environment variables
required_vars=(
    "APP_KEY"
    "DB_HOST"
    "DB_DATABASE"
    "DB_USERNAME"
    "DB_PASSWORD"
    "REDIS_HOST"
)

for var in "${required_vars[@]}"; do
    if ! grep -q "^${var}=" "$APP_DIR/.env"; then
        echo "❌ Missing required environment variable: $var"
        echo "   Please update $APP_DIR/.env"
        exit 1
    fi
done

echo "✅ Environment configuration validated"
echo ""

# ============================================================================
# Phase 3: PHP Dependencies
# ============================================================================
echo "📦 Phase 3: Installing PHP dependencies..."
echo ""

cd $APP_DIR

# Install composer dependencies
sudo -u $APP_USER composer install --no-dev --optimize-autoloader

# Generate application key if not set
if grep -q "^APP_KEY=$" "$APP_DIR/.env"; then
    echo "   🔑 Generating application key..."
    sudo -u $APP_USER php artisan key:generate
fi

echo "✅ PHP dependencies installed"
echo ""

# ============================================================================
# Phase 4: Node.js Dependencies & Build
# ============================================================================
echo "📦 Phase 4: Building frontend assets..."
echo ""

cd $APP_DIR

# Install npm dependencies
sudo -u $APP_USER npm install --production

# Build assets
echo "   🔨 Running npm build..."
sudo -u $APP_USER npm run build

echo "✅ Frontend built successfully"
echo ""

# ============================================================================
# Phase 5: Laravel Configuration
# ============================================================================
echo "📦 Phase 5: Configuring Laravel application..."
echo ""

cd $APP_DIR

# Clear all caches
echo "   🧹 Clearing caches..."
sudo -u $APP_USER php artisan config:clear
sudo -u $APP_USER php artisan cache:clear
sudo -u $APP_USER php artisan view:clear
sudo -u $APP_USER php artisan route:clear

# Create storage link
echo "   🔗 Creating storage link..."
sudo -u $APP_USER php artisan storage:link 2>/dev/null || true

# Publish configuration
echo "   📝 Publishing configuration..."
sudo -u $APP_USER php artisan optimize:clear 2>/dev/null || true

echo "✅ Laravel configured"
echo ""

# ============================================================================
# Phase 6: Database Setup
# ============================================================================
echo "📦 Phase 6: Running database migrations..."
echo ""

cd $APP_DIR

# Run migrations
echo "   💾 Running migrations..."
sudo -u $APP_USER php artisan migrate --force

echo "✅ Database migrations completed"
echo ""

# ============================================================================
# Phase 7: Nginx Configuration
# ============================================================================
echo "📦 Phase 7: Configuring Nginx..."
echo ""

# Create Nginx configuration
cat > /etc/nginx/sites-available/insurance2026.conf << 'EOF'
# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name tamnyfordr.online www.tamnyfordr.online;

    location / {
        return 301 https://$host$request_uri;
    }
}

# HTTPS server block
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name tamnyfordr.online www.tamnyfordr.online;

    root /opt/insurance2026/public;
    index index.php;

    # SSL Certificate paths (will be set by Certbot)
    ssl_certificate /etc/letsencrypt/live/tamnyfordr.online/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tamnyfordr.online/privkey.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # CSP header
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self' wss://tamnyfordr.online; media-src 'self' blob:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'" always;

    # Client max upload size
    client_max_body_size 100M;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;

    # Log files
    access_log /var/log/nginx/insurance2026-access.log;
    error_log /var/log/nginx/insurance2026-error.log;

    # Static files with far-future expiry
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ ~$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Public assets
    location /build {
        try_files $uri $uri/ =404;
    }

    location /storage {
        try_files $uri $uri/ =404;
    }

    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/insurance2026.conf /etc/nginx/sites-enabled/

# Test configuration
nginx -t

# Reload Nginx
systemctl reload nginx

echo "✅ Nginx configured"
echo ""

# ============================================================================
# Phase 8: Supervisor Configuration
# ============================================================================
echo "📦 Phase 8: Configuring Supervisor..."
echo ""

cat > /etc/supervisor/conf.d/insurance2026-horizon.conf << 'EOF'
[program:insurance2026-horizon]
process_name=%(program_name)s
command=php /opt/insurance2026/artisan horizon
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/insurance2026-horizon.log
user=www-data
EOF

supervisorctl reread
supervisorctl update
supervisorctl start insurance2026-horizon

echo "✅ Supervisor configured"
echo ""

# ============================================================================
# Phase 9: PHP-FPM Optimization
# ============================================================================
echo "📦 Phase 9: Optimizing PHP-FPM..."
echo ""

# Update PHP-FPM configuration
sed -i 's/^pm.max_children = .*/pm.max_children = 20/' /etc/php-fpm.d/www.conf
sed -i 's/^pm.start_servers = .*/pm.start_servers = 10/' /etc/php-fpm.d/www.conf
sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 5/' /etc/php-fpm.d/www.conf
sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 15/' /etc/php-fpm.d/www.conf

systemctl restart php-fpm

echo "✅ PHP-FPM optimized"
echo ""

# ============================================================================
# Phase 10: SSL Certificate
# ============================================================================
echo "📦 Phase 10: Installing SSL Certificate..."
echo ""

# Check if certificate already exists
if [ ! -f "/etc/letsencrypt/live/tamnyfordr.online/fullchain.pem" ]; then
    echo "   🔐 Obtaining SSL certificate..."
    certbot --nginx \
      -d tamnyfordr.online \
      -d www.tamnyfordr.online \
      --non-interactive \
      --agree-tos \
      -m admin@tamnyfordr.online \
      --rsa-key-size 2048
else
    echo "   ℹ️  SSL certificate already exists"
fi

# Enable auto-renewal
systemctl enable certbot-renew.timer
systemctl start certbot-renew.timer

echo "✅ SSL certificate installed"
echo ""

# ============================================================================
# Phase 11: Service Verification
# ============================================================================
echo "📦 Phase 11: Verifying services..."
echo ""

echo "Nginx status:"
systemctl status --no-pager nginx || true
echo ""

echo "PHP-FPM status:"
systemctl status --no-pager php-fpm || true
echo ""

echo "MariaDB status:"
systemctl status --no-pager mariadb || true
echo ""

echo "Redis status:"
systemctl status --no-pager redis || true
echo ""

echo "Supervisor status:"
systemctl status --no-pager supervisord || true
echo ""

# ============================================================================
# Phase 12: Health Checks
# ============================================================================
echo "📦 Phase 12: Running health checks..."
echo ""

cd $APP_DIR

echo "✅ Routes configured:"
sudo -u $APP_USER php artisan route:list | head -15

echo ""
echo "✅ Database connection:"
if sudo -u $APP_USER php artisan tinker --execute="DB::connection()->getPdo()" >/dev/null 2>&1; then
    echo "   Database: ✅ Connected"
else
    echo "   Database: ❌ Connection failed"
fi

echo ""
echo "✅ Redis connection:"
if redis-cli ping | grep -q "PONG"; then
    echo "   Redis: ✅ Connected"
else
    echo "   Redis: ❌ Connection failed"
fi

echo ""

# ============================================================================
# Summary
# ============================================================================
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         ✅ Deployment Complete!                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Application Status:"
echo "  • URL: https://tamnyfordr.online"
echo "  • Root: $APP_DIR"
echo "  • User: $APP_USER"
echo ""
echo "Service Logs:"
echo "  • Laravel: tail -f $APP_DIR/storage/logs/laravel.log"
echo "  • Nginx: tail -f /var/log/nginx/insurance2026-error.log"
echo "  • PHP-FPM: tail -f /var/log/php-fpm/www-error.log"
echo "  • Horizon: tail -f /var/log/supervisor/insurance2026-horizon.log"
echo ""
echo "Useful Commands:"
echo "  • systemctl restart nginx php-fpm"
echo "  • supervisorctl status insurance2026-horizon"
echo "  • cd $APP_DIR && php artisan tinker"
echo "  • certbot certificates"
echo ""
echo "Next Steps:"
echo "  1. Configure DNS records in Namecheap:"
echo "     A record: @ -> 66.29.149.94"
echo "     A record: www -> 66.29.149.94"
echo ""
echo "  2. Test SSL certificate:"
echo "     curl -I https://tamnyfordr.online/"
echo ""
echo "  3. Monitor logs during first 24 hours:"
echo "     tail -f $APP_DIR/storage/logs/laravel.log"
echo ""
