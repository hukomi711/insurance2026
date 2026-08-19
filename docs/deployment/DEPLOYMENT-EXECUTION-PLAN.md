# VPS Deployment Execution Plan

**Insurance2026 → lexusforbon.com | 66.29.149.94**

**Status**: 🟡 Ready for Execution | **Created**: May 29, 2026

---

## 📋 Executive Summary

This document provides the **complete step-by-step execution workflow** for deploying Insurance2026 to a VPS at 66.29.149.94 (AlmaLinux 9).

**All preparation is complete:**

- ✅ Code updated with new domain (lexusforbon.com)
- ✅ Deployment automation scripts created
- ✅ Comprehensive documentation ready
- ✅ Security review completed

**Your Next Action**: Follow this execution plan sequentially on the VPS.

---

## 🎯 Deployment Phases at a Glance

| Phase | Duration | What Happens | Automation |
| ------- | ---------- | -------------- | ----------- |
| **0** | 5 min | SSH connection, password change | Manual |
| **1** | 15-20 min | System setup, service installation | `vps-initial-setup.sh` |
| **2-12** | 5-10 min | App deployment, config, SSL | `vps-deploy-app.sh` |
| **13** | 5 min | DNS configuration | Manual (Namecheap UI) |
| **14** | 24-48 hrs | DNS propagation | Automatic |
| **15** | 10 min | SSL & connectivity verification | Manual tests |

**Total Active Time**: ~40 minutes (plus DNS waiting time)

---

## 📁 Files Available in Repository

**Location**: `d:\insurance2026\`

| File | Purpose | Size | User |
| ------ | --------- | ------ | ------ |
| `docs/deployment/DEPLOYMENT-VPS-GUIDE.md` | Detailed 12-phase manual guide | 809 lines | Reference |
| `docs/deployment/DEPLOYMENT-CHECKLIST.md` | Pre-deployment checklist & commands | 400 lines | Reference |
| `deploy/vps-initial-setup.sh` | Phase 1 automation (system setup) | 300 lines | Run on VPS |
| `deploy/vps-deploy-app.sh` | Phases 2-12 automation (app setup) | 400 lines | Run on VPS |
| `.env.production.example` | Environment template (updated domain) | 28 lines | Reference |
| `docs/deployment/DEPLOYMENT-EXECUTION-PLAN.md` | This file (your roadmap) | - | Follow now |

---

## 🚀 Execution Workflow

### PRE-EXECUTION VERIFICATION (Do Now - Local Machine)

```bash
# Verify local environment is clean
cd d:\insurance2026

# Check that code changes are committed
git status
# Should show: "On branch main ... nothing to commit"

# Verify domain was updated correctly
grep -r "lexusforbon.com" config/ app/
# Should show many matches

# Verify build succeeds
npm run build
# Should show: "✓ built in X.XXs"

# Verify no uncommitted changes
git diff
# Should show: (empty)
```

✅ **Continue only if all checks pass**

---

## 🔑 PHASE 0: SSH Connection & Initial Access

**Time**: 5 minutes | **Location**: Your local machine

### Step 0.1: Test SSH Connection

```bash
# From your Windows terminal/Git Bash
ssh root@66.29.149.94 -p 22

# Expected prompt:
# root@66.29.149.94's password: [type default password from VPS provider]

# If successful, you'll see:
# [root@almalinux-server ~]#
```

**Troubleshooting SSH**:

- If connection refused: Verify IP is correct (66.29.149.94)
- If timeout: Check firewall/network - contact VPS provider
- If permission denied: Verify password with VPS provider

### Step 0.2: Change Root Password Immediately

```bash
# On VPS, at root prompt
passwd

# Follow prompts:
# Current password: [type current password]
# New password: [type your strong password]
# Retype new password: [type again]

# Expected: "passwd: password updated successfully"
```

**Security**: Use a strong password (16+ chars, mix of upper/lowercase, numbers, symbols)

### Step 0.3: Verify System Information

```bash
# Still on VPS
uname -a
# Should show: Linux almalinux-server 5.x.x ... #1 SMP ... x86_64 GNU/Linux

cat /etc/almalinux-release
# Should show: AlmaLinux 9.x x86_64

# If both match, proceed to Phase 1
```

✅ **Phase 0 Complete** - You're now connected to the VPS

---

## 🛠️ PHASE 1: Automated System Setup (15-20 minutes)

**Time**: 15-20 minutes | **Location**: VPS | **Automation**: `vps-initial-setup.sh`

### Step 1.1: Get Setup Script to VPS

**Option A: Via Git (Recommended)**

```bash
# On VPS, as root
cd /tmp
git clone https://github.com/YOUR_ORG/insurance2026.git
cd insurance2026
sudo bash deploy/vps-initial-setup.sh
```

**Option B: Via SSH Copy**

```bash
# From your local machine
scp deploy/vps-initial-setup.sh root@66.29.149.94:/tmp/

# Then on VPS
sudo bash /tmp/vps-initial-setup.sh
```

### Step 1.2: Run Automated Setup

```bash
# On VPS
sudo bash vps-initial-setup.sh

# Expected output:
# ╔════════════════════════════════════════════════════════════════╗
# ║    Insurance2026 VPS Initial Setup                           ║
# ║    AlmaLinux 9 System Configuration                          ║
# ╚════════════════════════════════════════════════════════════════╝
#
# 📦 Phase X: [Installing X]...
# ✅ [Service] installed and enabled
#
# ...continues for 15-20 minutes...
#
# ╔════════════════════════════════════════════════════════════════╗
# ║         ✅ System Setup Complete!                            ║
# ╚════════════════════════════════════════════════════════════════╝
```

### Step 1.3: Verify Installation

```bash
# After script completes, verify each service
php --version
# Should show: PHP 8.3.x

composer --version
# Should show: Composer version 2.x.x

node --version
npm --version
# Should show: v20.x.x and 10.x.x

mysql --version
# Should show: mysql Ver 15.x Distrib 5.7.x

redis-cli ping
# Should show: PONG

nginx -v
# Should show: nginx/1.x.x

supervisorctl status
# Should show: unix:///var/run/supervisor.sock refused connection
# (this is OK - means supervisor is installed but not configured yet)
```

✅ **Phase 1 Complete** - All services installed

---

## 🚀 PHASES 2-12: Automated Application Deployment (5-10 minutes)

**Time**: 5-10 minutes | **Location**: VPS | **Automation**: `vps-deploy-app.sh`

### Step 2.1: Prepare Database

```bash
# On VPS, as root
sudo mysql_secure_installation

# Answer prompts:
# Switch to unix_socket authentication? [Y/n] N
# Change the root password? [Y/n] N  (we just set it)
# Remove anonymous users? [Y/n] Y
# Disable root login remotely? [Y/n] Y
# Remove test database? [Y/n] Y
# Reload privilege tables now? [Y/n] Y
```

### Step 2.2: Create Database & User

```bash
# On VPS, as root
sudo mysql -u root -p

# You'll be at mysql> prompt. Paste these commands:
CREATE DATABASE insurance2026;
CREATE USER 'ins_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON insurance2026.* TO 'ins_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Expected: "Query OK" messages, no errors
```

**⚠️ Important**: Save the password you use for `ins_user`. You'll need it in Step 2.4.

### Step 2.3: Clone Repository

```bash
# On VPS, as root
cd /opt/insurance2026

# Option A: HTTPS (recommended for VPS without SSH keys)
sudo git clone https://github.com/YOUR_ORG/insurance2026.git .

# Option B: SSH (if you've set up SSH keys)
sudo git clone git@github.com:YOUR_ORG/insurance2026.git .

# Verify clone succeeded
ls -la /opt/insurance2026/ | head -20
# Should show: README.md, composer.json, package.json, artisan, etc.
```

### Step 2.4: Configure .env File

```bash
# On VPS, as root
cd /opt/insurance2026
sudo nano .env

# Copy the template below and paste into nano
```

**Paste this template and update the values marked with [REQUIRED]:**

```env
APP_NAME="Insurance2026"
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://lexusforbon.com
APP_ASSET_URL=https://lexusforbon.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=insurance2026
DB_USERNAME=ins_user
DB_PASSWORD=YOUR_STRONG_PASSWORD    # [REQUIRED] Use password from Step 2.2

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie

# Mail Configuration [REQUIRED - Update with your email service]
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=no-reply@lexusforbon.com
MAIL_FROM_NAME="Insurance2026"

# Sanctum/API Configuration
SANCTUM_STATEFUL_DOMAINS=lexusforbon.com
SANCTUM_ENCRYPT_COOKIES=true

# Session Configuration
SESSION_DOMAIN=.lexusforbon.com
SESSION_SECURE_COOKIES=true
SESSION_SAME_SITE_COOKIES=lax

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://lexusforbon.com,https://www.lexusforbon.com

# Reverb WebSocket Configuration
REVERB_APP_ID=insurance2026
REVERB_APP_KEY=insurance2026-app-key-12345
REVERB_APP_SECRET=insurance2026-app-secret-67890
REVERB_HOST=lexusforbon.com
REVERB_PORT=443
REVERB_SCHEME=https

# Frontend URL
FRONTEND_URL=https://lexusforbon.com
VITE_REVERB_HOST=lexusforbon.com
VITE_REVERB_PORT=443

# Feature Flags
BROADCAST_DRIVER=reverb
QUEUE_FAILED_TABLE=failed_jobs
```

**In nano editor**:

- Paste the template
- Edit the [REQUIRED] fields (especially DB_PASSWORD, MAIL credentials)
- Save: `Ctrl+X`, then `Y`, then `Enter`

### Step 2.5: Set .env Permissions

```bash
# On VPS, as root
cd /opt/insurance2026

# Set secure permissions
sudo chmod 640 .env
sudo chown www-data:www-data .env

# Verify
ls -l .env
# Should show: -rw-r----- 1 www-data www-data ... .env
```

### Step 2.6: Run Application Deployment Script

```bash
# On VPS, as root
cd /opt/insurance2026
sudo bash deploy/vps-deploy-app.sh

# Expected output (5-10 minutes):
# ╔════════════════════════════════════════════════════════════════╗
# ║    Insurance2026 Application Deployment                       ║
# ║    Domain: lexusforbon.com                                ║
# ╚════════════════════════════════════════════════════════════════╝
#
# 📦 Phase X: [Configuring X]...
# ✅ [Task] completed successfully
#
# ...continues for 5-10 minutes...
#
# ╔════════════════════════════════════════════════════════════════╗
# ║         ✅ Deployment Complete!                              ║
# ╚════════════════════════════════════════════════════════════════╝
```

### Step 2.7: Verify Application Health

```bash
# On VPS, after deployment script completes
cd /opt/insurance2026

# Check services are running
sudo systemctl status nginx php-fpm mariadb redis supervisord
# Should show: "active (running)" for all services

# Check Horizon worker
sudo supervisorctl status insurance2026-horizon
# Should show: "insurance2026-horizon RUNNING"

# Check database connection
sudo -u www-data php artisan tinker
# Type: DB::connection()->getPdo()
# Should return: object(PDO)
# Type: exit

# Check routes
sudo -u www-data php artisan route:list | head -20
# Should show 10+ routes

# Check logs (should be clean)
tail -20 /opt/insurance2026/storage/logs/laravel.log
# Should show: "Local: true" or deployment success messages
```

✅ **Phases 2-12 Complete** - Application deployed and running

---

## 🌐 PHASE 13: DNS Configuration (5 minutes)

**Time**: 5 minutes | **Location**: Namecheap UI

### Step 13.1: Update DNS Records

1. Go to [Namecheap Dashboard](https://www.namecheap.com/myaccount/login/)
2. Log in with your credentials
3. Click **Domain List** in left sidebar
4. Find **lexusforbon.com** and click **Manage**
5. Go to **Advanced DNS** tab
6. Update these A records:

**For the root domain (@):**

- Type: `A Record`
- Host: `@`
- Value: `66.29.149.94`
- TTL: `3600` (or leave as-is)

**For the www subdomain:**

- Type: `A Record`
- Host: `www`
- Value: `66.29.149.94`
- TTL: `3600`

**Result**: Both records should point to 66.29.149.94

### Step 13.2: Verify DNS Records

```bash
# From your local machine (wait 5-60 seconds for Namecheap to update)
nslookup lexusforbon.com
# Should eventually show: Address: 66.29.149.94

# Check with dig for more detail
dig lexusforbon.com
# Look for: "lexusforbon.com. ... A 66.29.149.94"
```

**Note**: Full DNS propagation takes 24-48 hours, but usually works within 1-5 minutes.

✅ **Phase 13 Complete** - DNS records configured

---

## ⏳ PHASE 14: DNS Propagation (24-48 hours)

**Automatic Process** - No action needed

DNS changes propagate globally over 24-48 hours. During this time:

- Some users may still see old IP
- SSL certificate may not issue (needs DNS to validate)
- App may not be fully accessible

**What happens automatically**:

1. Certbot (SSL) waits for DNS to propagate
2. Once DNS resolves, Certbot validates domain ownership
3. SSL certificate is issued and auto-renewed

**Monitor propagation**:

```bash
# Check if DNS is live globally
nslookup lexusforbon.com
# When it returns 66.29.149.94, DNS is ready

# Check if SSL certificate is issued
curl -I https://lexusforbon.com/
# If you see certificate errors, DNS not ready yet
```

---

## ✅ PHASE 15: Verification & Testing (10 minutes)

**Time**: 10 minutes | **Location**: Local machine (once DNS ready)

### Step 15.1: Wait for DNS & SSL

```bash
# From your local machine, periodically check
nslookup lexusforbon.com

# Keep checking until it returns 66.29.149.94
# Then wait ~5 minutes more for SSL to be ready
```

### Step 15.2: Test HTTP/HTTPS

```bash
# From your local machine, once DNS is live
curl -I https://lexusforbon.com/
# Should return: HTTP/2 200 or 301 (not 502/503)

# Test www subdomain
curl -I https://www.lexusforbon.com/
# Should redirect to https://lexusforbon.com

# Test in browser
# Open: https://lexusforbon.com
# Should load login page (or dashboard if configured)

# Check SSL certificate
curl -vvI https://lexusforbon.com/ 2>&1 | grep -A5 "SSL"
# Should show:
#   subject: CN = lexusforbon.com
#   issuer: CN = R3, O = Let's Encrypt, C = US
```

### Step 15.3: Monitor Logs During First 24 Hours

```bash
# On VPS
tail -f /opt/insurance2026/storage/logs/laravel.log

# Watch for:
# - No ERROR or CRITICAL messages
# - Successful requests logged
# - Database queries working

# If errors appear, check:
tail -f /var/log/nginx/insurance2026-error.log
tail -f /var/log/php-fpm/www-error.log
```

### Step 15.4: Final Checklist

```bash
# On VPS - run comprehensive health checks

# 1. All services running
sudo systemctl status nginx php-fpm mariadb redis supervisord
# All should be "active (running)"

# 2. Application accessible
curl -I https://lexusforbon.com/
# Should return HTTP/2 200

# 3. Database connected
sudo -u www-data php artisan tinker
DB::select('SELECT 1')[0]
# Should return result
exit

# 4. Queue worker running
sudo supervisorctl status insurance2026-horizon
# Should be "RUNNING"

# 5. Logs clean
tail -20 /opt/insurance2026/storage/logs/laravel.log
# Should show normal requests, no errors

# 6. Disk space available
df -h /
# Should show >10GB available

# 7. SSL valid
echo | openssl s_client -servername lexusforbon.com -connect lexusforbon.com:443 2>/dev/null | grep -A2 "subject="
# Should show: CN = lexusforbon.com
```

✅ **Phase 15 Complete** - Application verified and live!

---

## 📊 Deployment Status Dashboard

Copy this table and update as you progress:

```markdown
| Phase | Task | Status | Time | Notes |
|-------|------|--------|------|-------|
| 0 | SSH connection & password | 🔄 | - | In progress |
| 1 | System setup (vps-initial-setup.sh) | ⏳ | - | Awaiting Phase 0 |
| 2-12 | App deployment (vps-deploy-app.sh) | ⏳ | - | Awaiting Phase 1 |
| 13 | DNS configuration | ⏳ | - | Awaiting Phase 2-12 |
| 14 | DNS propagation | ⏳ | 24-48h | Automatic |
| 15 | Verification & testing | ⏳ | - | Awaiting Phase 14 |

**Overall Status**: 🔄 Ready to begin (Phase 0)
**Estimated Total Time**: 40 minutes + 24-48 hour DNS wait
```

---

## 🆘 Troubleshooting Quick Reference

**If you encounter issues:**

| Problem | Cause | Solution |
| --------- | ------- | ---------- |
| SSH connection refused | Network/firewall | Verify IP, check VPS provider |
| Phase 1 fails halfway | Missing yum packages | Run: `sudo yum install -y gcc` |
| "docker: command not found" | Docker script error | Ignore - not needed for this stack |
| 502 Bad Gateway after deployment | PHP-FPM crashed | `sudo systemctl restart php-fpm` |
| Database password incorrect | Typo in .env | Re-edit .env, restart PHP-FPM |
| SSL certificate not issuing | DNS not propagated yet | Wait 5-10 minutes, retry certbot |
| "Connection refused" on port 443 | Nginx not running | `sudo systemctl restart nginx` |

**For detailed help**: See `docs/deployment/DEPLOYMENT-VPS-GUIDE.md` sections "Troubleshooting" and "Useful Commands"

---

## 📞 Support Resources

**Documentation**:

- Detailed manual steps: `docs/deployment/DEPLOYMENT-VPS-GUIDE.md`
- Commands reference: `docs/deployment/DEPLOYMENT-CHECKLIST.md`
- This execution plan: `docs/deployment/DEPLOYMENT-EXECUTION-PLAN.md`

**Useful Commands to Know**:

```bash
# SSH back to VPS anytime
ssh root@66.29.149.94

# View all service statuses
sudo systemctl status nginx php-fpm mariadb redis supervisord

# Restart everything
sudo systemctl restart nginx php-fpm

# View app logs
tail -f /opt/insurance2026/storage/logs/laravel.log

# Connect to database
mysql -u ins_user -p insurance2026

# Test Redis
redis-cli ping
```

---

## ✨ Next Steps After Deployment

Once Phase 15 is complete and verified:

1. **Set up SSH keys** (for password-less login)

   ```bash
   ssh-keygen -t ed25519 -C "your-email@example.com"
   ssh-copy-id -i ~/.ssh/id_ed25519.pub root@66.29.149.94
   ```

2. **Disable password login** (security hardening)

   ```bash
   sudo nano /etc/ssh/sshd_config
   # Set: PermitRootLogin no
   # Set: PasswordAuthentication no
   sudo systemctl restart sshd
   ```

3. **Set up automated backups**
   - See `docs/deployment/DEPLOYMENT-VPS-GUIDE.md` Phase 12

4. **Configure monitoring**
   - Set up uptime monitoring (e.g., UptimeRobot)
   - Configure log rotation
   - Set up alerts for disk space/memory

5. **Update firewall rules**

   ```bash
   sudo firewall-cmd --permanent --add-service=http
   sudo firewall-cmd --permanent --add-service=https
   sudo firewall-cmd --reload
   ```

---

## 📝 Important Reminders

✅ **Security**:

- Never commit `.env` file to Git
- Change root password immediately (Step 0.2)
- Use strong passwords for all services
- Enable SSH key-based auth after deployment
- Keep system updated: `sudo yum update -y`

✅ **Monitoring**:

- Check logs daily for first week
- Monitor disk space (should have >10GB available)
- Monitor memory usage
- Review access logs for unusual patterns

✅ **Maintenance**:

- SSL renews automatically (Certbot)
- Keep dependencies updated
- Backup database daily
- Test restore procedure monthly

---

**🚀 Ready to start? Begin with Phase 0: SSH Connection & Initial Access**

**Questions?** Refer to the detailed guides or check the troubleshooting section.

**Last Updated**: May 29, 2026 | **Status**: ✅ Ready for Production Deployment
