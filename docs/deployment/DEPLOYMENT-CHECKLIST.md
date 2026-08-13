# VPS Deployment Checklist & Commands Reference

**Server**: 66.29.149.94 | **Domain**: tamnyfordr.online | **OS**: AlmaLinux 9

---

## 📋 Pre-Deployment Checklist

### Local Preparation
- [ ] Domain updated to `tamnyfordr.online` in codebase
- [ ] `.env.production.example` reviewed and updated (no secrets)
- [ ] `.env` file prepared locally with all production values
- [ ] Database backup created (if migrating)
- [ ] Git repository is clean (no uncommitted changes)
- [ ] Latest code pushed to repository

### Database Credentials Ready
- [ ] Database name: `insurance2026`
- [ ] Database user: `ins_user`
- [ ] Database password: (stored securely, not in Git)
- [ ] Redis configured and tested locally
- [ ] Email service credentials ready

### API & Integration Keys
- [ ] Pusher/Reverb credentials (REVERB_APP_ID, REVERB_APP_KEY, etc.)
- [ ] Email service (SMTP credentials)
- [ ] Payment gateway credentials (if applicable)
- [ ] Any third-party API keys needed

---

## 🚀 Step-by-Step Deployment Guide

### Step 1: Initial SSH Connection
```bash
# From your local machine
ssh root@66.29.149.94 -p 22

# First login: change password immediately
passwd

# Verify system
uname -a
```

### Step 2: Run Initial Setup (15-20 minutes)
```bash
# On VPS, as root
sudo bash /path/to/vps-initial-setup.sh

# Watch for completion and any errors
```

**What this does:**
- Updates AlmaLinux system
- Installs PHP 8.3 with all extensions
- Installs Composer, MariaDB, Redis, Nginx, Node.js, Supervisor, Certbot

### Step 3: Secure MariaDB
```bash
# On VPS
sudo mysql_secure_installation

# Answers:
# Switch to unix_socket authentication? N
# Change root password? N (already done)
# Remove anonymous users? Y
# Disable root login remotely? Y
# Remove test database? Y
# Reload privilege tables? Y
```

### Step 4: Create Database & User
```bash
# On VPS
sudo mysql -u root -p

# Paste these commands in MySQL prompt:
CREATE DATABASE insurance2026;
CREATE USER 'ins_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON insurance2026.* TO 'ins_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 5: Clone Repository
```bash
# On VPS
cd /opt/insurance2026

# Clone repo (replace with your repo URL)
sudo git clone https://github.com/YOUR_ORG/insurance2026.git .

# Fix permissions
sudo chown -R www-data:www-data /opt/insurance2026
```

### Step 6: Create & Configure .env File
```bash
# On VPS
sudo cp /opt/insurance2026/.env.production.example /opt/insurance2026/.env
sudo nano /opt/insurance2026/.env

# Update these critical values:
# APP_KEY=                    (leave empty, will be generated)
# DB_PASSWORD=YOUR_PASSWORD   (from Step 4)
# MAIL_FROM_ADDRESS=          (set email)
# REVERB_APP_ID=              (generate a random string)
# REVERB_APP_KEY=             (generate a random string)
# REVERB_APP_SECRET=          (generate a random string)

# Save: Ctrl+X, Y, Enter
```

### Step 7: Set File Permissions
```bash
# On VPS
sudo chmod 640 /opt/insurance2026/.env
sudo chown www-data:www-data /opt/insurance2026/.env

# Verify it's not readable by others
ls -l /opt/insurance2026/.env
```

### Step 8: Run Application Deployment
```bash
# On VPS, as root
cd /opt/insurance2026
sudo bash deploy/vps-deploy-app.sh

# Watch for completion (takes 5-10 minutes)
```

**What this does:**
- Installs PHP dependencies (Composer)
- Generates application key
- Installs Node dependencies
- Builds frontend assets
- Clears Laravel caches
- Runs database migrations
- Configures Nginx
- Sets up Supervisor for queues
- Installs SSL certificate
- Optimizes PHP-FPM

### Step 9: Configure DNS
```
Log in to Namecheap → Domain Management → tamnyfordr.online → Advanced DNS

Add/Update Records:
┌─────────────────────────────────────────────┐
│ Type │ Host │ Value        │ TTL             │
├──────┼──────┼──────────────┼─────────────────┤
│ A    │ @    │ 66.29.149.94 │ 3600 (auto)     │
│ A    │ www  │ 66.29.149.94 │ 3600 (auto)     │
└─────────────────────────────────────────────┘

DNS propagation: 24-48 hours
```

### Step 10: Test Application
```bash
# Wait for DNS to propagate (can take up to 48 hours)
# Then test:

# Test HTTP → HTTPS redirect
curl -I http://tamnyfordr.online/

# Test HTTPS
curl -I https://tamnyfordr.online/

# Test API health
curl -I https://tamnyfordr.online/api/health

# View certificate
curl --insecure -vvI https://tamnyfordr.online/ 2>&1 | grep -A5 "SSL"

# Test in browser
# Open: https://tamnyfordr.online
```

---

## 🔍 Verification & Diagnostics

### Check Service Status
```bash
# On VPS

# Check all services at once
systemctl status nginx php-fpm mariadb redis supervisord

# Check Nginx
sudo systemctl status nginx
sudo nginx -t

# Check PHP-FPM
sudo systemctl status php-fpm

# Check MariaDB
sudo systemctl status mariadb
sudo mysql -u ins_user -p -e "SELECT 1;"

# Check Redis
sudo systemctl status redis
redis-cli ping

# Check Supervisor/Horizon
sudo supervisorctl status insurance2026-horizon
```

### View Application Logs
```bash
# On VPS

# Laravel application log
tail -f /opt/insurance2026/storage/logs/laravel.log

# Nginx errors
tail -f /var/log/nginx/insurance2026-error.log
tail -f /var/log/nginx/insurance2026-access.log

# PHP-FPM errors
tail -f /var/log/php-fpm/www-error.log

# Horizon/Queue worker
sudo tail -f /var/log/supervisor/insurance2026-horizon.log

# System messages
sudo journalctl -u nginx -n 50
sudo journalctl -u php-fpm -n 50
```

### Route List & Configuration
```bash
# On VPS
cd /opt/insurance2026

# Show registered routes
sudo -u www-data php artisan route:list

# Test database connection
sudo -u www-data php artisan tinker
# Type: DB::connection()->getPdo()
# Type: exit

# Check configuration
sudo -u www-data php artisan config:show | grep -E "APP_URL|DB_"
```

### Check Disk & Memory
```bash
# On VPS

# Disk usage
df -h

# Memory usage
free -h

# Top processes by memory
ps aux --sort=-%mem | head -10

# Monitor in real-time
top
# Press Q to exit
```

### SSL Certificate Status
```bash
# On VPS

# List certificates
sudo certbot certificates

# Check expiration
sudo openssl x509 -in /etc/letsencrypt/live/tamnyfordr.online/fullchain.pem -noout -dates

# Dry-run renewal
sudo certbot renew --dry-run
```

### Port Availability Check
```bash
# On VPS

# Check listening ports
sudo ss -tlnp | grep -E ":(80|443|3306|6379|9000)"

# Expected output:
# 80:   HTTP (Nginx)
# 443:  HTTPS/SSL (Nginx)
# 3306: MariaDB
# 6379: Redis
# 9000: PHP-FPM (unix socket)
```

---

## 🛠️ Common Operations

### Restart Services
```bash
# On VPS

# Restart Nginx
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php-fpm

# Restart MariaDB
sudo systemctl restart mariadb

# Restart all services
sudo systemctl restart nginx php-fpm mariadb redis supervisord
```

### Clear Application Caches
```bash
# On VPS
cd /opt/insurance2026

sudo -u www-data php artisan config:cache
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan route:clear
```

### Update Application
```bash
# On VPS
cd /opt/insurance2026

# Pull latest code
sudo git pull origin main

# Install composer updates
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install npm updates
sudo -u www-data npm install --production

# Build frontend
sudo -u www-data npm run build

# Clear caches
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan cache:clear

# Run migrations (if applicable)
sudo -u www-data php artisan migrate --force

# Restart services
sudo systemctl restart php-fpm nginx
sudo supervisorctl restart insurance2026-horizon
```

### Put Application in Maintenance Mode
```bash
# On VPS
cd /opt/insurance2026

# Enable maintenance
sudo -u www-data php artisan down

# Show custom message
sudo -u www-data php artisan down --message="Upgrading database" --retry=60

# Disable maintenance
sudo -u www-data php artisan up
```

### Restart Horizon Queue Worker
```bash
# On VPS
sudo supervisorctl restart insurance2026-horizon

# Or to stop/start individually
sudo supervisorctl stop insurance2026-horizon
sudo supervisorctl start insurance2026-horizon
```

### Backup Database
```bash
# On VPS

# Manual backup
sudo mysqldump -u ins_user -p insurance2026 | gzip > /backups/insurance2026_$(date +%Y%m%d_%H%M%S).sql.gz

# Restore from backup
sudo gunzip < /backups/backup.sql.gz | mysql -u ins_user -p insurance2026
```

---

## ❌ Troubleshooting

### Issue: 502 Bad Gateway
```bash
# On VPS

# Check PHP-FPM
sudo systemctl status php-fpm
sudo tail -f /var/log/php-fpm/www-error.log

# Restart PHP-FPM
sudo systemctl restart php-fpm

# Check socket permissions
sudo ls -l /run/php-fpm/www.sock
sudo chown www-data:www-data /run/php-fpm/www.sock
```

### Issue: Database Connection Error
```bash
# On VPS

# Check MariaDB is running
sudo systemctl status mariadb

# Test connection
mysql -u ins_user -p -e "SELECT 1;"

# Verify credentials in .env
grep DB_ /opt/insurance2026/.env

# Check database exists
mysql -u ins_user -p -e "SHOW DATABASES;"
```

### Issue: High Memory Usage
```bash
# On VPS

# Check memory usage
free -h

# Find memory hogs
ps aux --sort=-%mem | head -10

# Restart services to free memory
sudo systemctl restart nginx php-fpm redis
```

### Issue: SSL Certificate Not Working
```bash
# On VPS

# Check certificate exists
sudo ls -l /etc/letsencrypt/live/tamnyfordr.online/

# Check certificate validity
sudo openssl x509 -in /etc/letsencrypt/live/tamnyfordr.online/fullchain.pem -noout -text | grep -E "Subject:|Issuer:|Not Before|Not After"

# Renew certificate
sudo certbot renew --force-renewal

# Test SSL
curl -I https://tamnyfordr.online/
```

### Issue: Logs Are Filling Up Disk
```bash
# On VPS

# Check log sizes
sudo du -sh /var/log/nginx/
sudo du -sh /opt/insurance2026/storage/logs/
sudo du -sh /var/log/php-fpm/

# Rotate logs manually
sudo logrotate -f /etc/logrotate.d/nginx

# Archive old logs
cd /opt/insurance2026/storage/logs
find . -name "laravel-*.log" -mtime +7 -exec gzip {} \;
```

---

## 📊 Performance Monitoring

### Real-time Monitoring
```bash
# On VPS

# CPU and memory
top
# or
htop

# Disk I/O
iostat -x 1 5

# Network connections
netstat -an | grep ESTABLISHED | wc -l
```

### Application Performance
```bash
# On VPS
cd /opt/insurance2026

# Check slow queries
mysql -u ins_user -p insurance2026 -e "SHOW PROCESSLIST;"

# Monitor Horizon
sudo supervisorctl tail -f insurance2026-horizon
```

---

## 🔐 Security Reminders

✅ **Do This:**
- [ ] Store `.env` file only on server (chmod 640)
- [ ] Use strong passwords for all services
- [ ] Enable SSL certificate (automatic with script)
- [ ] Keep system updated (`sudo yum update -y`)
- [ ] Monitor logs regularly
- [ ] Backup database daily
- [ ] Use SSH keys instead of passwords
- [ ] Disable root login via SSH
- [ ] Configure firewall rules

❌ **Don't Do This:**
- Don't commit `.env` to Git
- Don't use weak passwords
- Don't disable SSL
- Don't ignore security updates
- Don't expose database directly to internet
- Don't store backups only on server
- Don't share SSH credentials
- Don't run everything as root

---

## 📞 Support & Resources

- **Laravel Docs**: https://laravel.com/docs
- **Nginx Docs**: https://nginx.org/en/docs/
- **PHP-FPM Docs**: https://www.php.net/manual/en/install.fpm.php
- **MariaDB Docs**: https://mariadb.com/kb/en/
- **Let's Encrypt**: https://letsencrypt.org/

---

**Last Updated**: May 29, 2026
**Created for**: Insurance2026 Project | tamnyfordr.online
