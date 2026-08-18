# 🚀 Insurance2026 Production Deployment Guide

**Date**: August 16, 2026  
**Server**: server1.ttamikomzz.com (209.74.64.215)  
**Status**: OS Reinstalled - Ready for Deployment  

---

## 📋 Prerequisites

✅ **GitHub Repository**

- Owner: mobanihani99
- Repo: tameni2026
- Branch: hardening/clean-rebuild
- Latest Commit: 7ede2f2

✅ **Server Credentials (NEW)**

- IP: 209.74.64.215
- Hostname: server1.ttamikomzz.com
- Root Username: root
- Root Password: `TY4gW9m4hp97AEcb4P`

✅ **Required Software (assumes fresh AlmaLinux 9)**

- Docker & Docker Compose
- Git
- curl
- bash

---

## 🔧 Installation Steps

### Step 1: Initial Server Setup (First Time Only)

```bash
ssh root@209.74.64.215
# Password: TY4gW9m4hp97AEcb4P
```

**Install Docker & Dependencies:**

```bash
#!/bin/bash
set -e

echo "====== Installing Docker & Dependencies ======"

# Update system
dnf update -y

# Install required packages
dnf install -y \
  docker \
  docker-compose \
  git \
  curl \
  python3 \
  npm

# Start and enable Docker
systemctl start docker
systemctl enable docker

# Verify installation
docker --version
docker-compose --version
git --version

echo "✅ Installation complete"
```

---

### Step 2: Clone Repository

```bash
cd /opt
git clone https://github.com/mobanihani99/tameni2026.git insurance2026
cd insurance2026
git checkout hardening/clean-rebuild
```

---

### Step 3: Configure Environment

**Create production .env file:**

```bash
cat > /opt/insurance2026/.env << 'EOF'
APP_NAME=Insurance2026
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ttamikomzz.com

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

# Mail
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_FROM_ADDRESS=noreply@ttamikomzz.com
MAIL_FROM_NAME=Insurance2026

# Broadcasting
BROADCAST_DRIVER=reverb
REVERB_APP_ID=insurance2026
REVERB_APP_KEY=insurance2026_key
REVERB_APP_SECRET=insurance2026_secret
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=https

# Keys
APP_KEY=base64:dSz+uKpMDKVxBjVAT9n6/E1Q3Z9wP7mN0K8qL3vR4hI=
EOF

chmod 640 /opt/insurance2026/.env
```

---

### Step 4: Build & Deploy

**Full Deployment Command:**

```bash
cd /opt/insurance2026

echo "📥 Pulling latest code..."
git pull origin hardening/clean-rebuild

echo "🔨 Building Docker image..."
docker compose build app --no-cache

echo "🚀 Starting all services..."
docker compose up -d

echo "⏳ Waiting for health checks..."
sleep 20

echo "📊 Service Status:"
docker compose ps

echo "🏥 Application Health:"
curl -sk https://localhost/api/health -H "Host: ttamikomzz.com" | python3 -m json.tool

echo "✅ DEPLOYMENT COMPLETE"
```

---

## 🧪 Verification Commands

### Check Service Status

```bash
docker compose ps

# Expected output:
# NAME                 STATUS          PORTS
# ins2026-app          Up (healthy)    9000/tcp
# ins2026-nginx        Up (healthy)    0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
# ins2026-db           Up (healthy)    3306/tcp
# ins2026-redis        Up (healthy)    6379/tcp
# ins2026-horizon      Up              
# ins2026-reverb       Up              0.0.0.0:8080->8080/tcp
# ins2026-scheduler    Up
```

### Test API Health

```bash
curl -sk https://localhost/api/health -H "Host: ttamikomzz.com"

# Expected response:
# {"ok":true,"database":"ok","redis":"ok"}
```

### View Application Logs

```bash
docker logs ins2026-app -f --tail=50
```

### Check Database Migrations

```bash
docker compose exec -T app php artisan migrate:status
```

### Check Redis Connection

```bash
docker compose exec -T redis redis-cli ping
# Expected: PONG
```

### Test SSL Certificate

```bash
openssl s_client -connect localhost:443 -servername ttamikomzz.com </dev/null 2>/dev/null | grep -A 2 "subject="
```

---

## 🔧 Troubleshooting

### Application returns 500 error

```bash
# Check logs
docker logs ins2026-app | tail -50 | grep -i error

# Verify .env permissions
docker compose exec -T app ls -la /var/www/html/.env

# Should be: appuser appuser with 640 permissions
```

### Services not starting

```bash
# Rebuild without cache
docker compose build app --no-cache

# Restart all services
docker compose down
docker compose up -d
```

### Database not initialized

```bash
# Run migrations
docker compose exec -T app php artisan migrate

# Seed if needed
docker compose exec -T app php artisan db:seed
```

### WebSocket not connecting (Reverb)

```bash
# Check Reverb logs
docker logs ins2026-reverb

# Test connectivity
curl -I http://localhost:8080/
```

---

## 📊 Production Monitoring

### Continuous Log Monitor

```bash
watch -n 5 'docker compose ps && echo "---" && docker logs ins2026-app | tail -10'
```

### Resource Usage

```bash
docker stats --no-stream
```

### Database Backups

```bash
# Create backup
docker compose exec -T db mysqldump -uinsurance -pinsurance2026 insurance2026 > backup.sql

# Restore from backup
docker compose exec -T db mysql -uinsurance -pinsurance2026 insurance2026 < backup.sql
```

---

## 🔐 Security Checklist

- [ ] Firewall rules allow only: SSH (22), HTTP (80), HTTPS (443), WebSocket (8080)
- [ ] SSL certificate installed and valid
- [ ] Database password changed from default
- [ ] Redis has authentication if exposed
- [ ] Logs do not contain sensitive data
- [ ] .env file not readable by web server
- [ ] Backups scheduled and tested
- [ ] Monitoring and alerting configured

---

## 📞 Support Commands

**Restart specific service:**

```bash
docker compose restart app  # Just the PHP application
docker compose restart nginx  # Just web server
docker compose restart horizon  # Just queue workers
```

**Full reset (DANGEROUS - clears data):**

```bash
docker compose down -v  # -v removes volumes
docker compose up -d
```

**Access application shell:**

```bash
docker compose exec -T app php artisan tinker
```

---

## ✅ Success Criteria

- [ ] All 7 containers showing "Up" status
- [ ] Health endpoint returns `{"ok":true}`
- [ ] SSL certificate valid
- [ ] Application loads on <https://ttamikomzz.com>
- [ ] No errors in application logs
- [ ] Database migrations completed
- [ ] Redis cache working
- [ ] Email queue working
- [ ] WebSocket server operational

---

**Created**: August 16, 2026  
**Last Updated**: August 16, 2026  
**Status**: Ready for Production Deployment
