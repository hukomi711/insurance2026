# Insurance 2026 — Production Server Fix Guide

## 🔴 Current Issue: 500 Error on tamlexus.sbs

**Root Cause:** `MAIL_MAILER=smtp` configuration requires valid SMTP credentials. Placeholder values cause validation failure.

---

## ✅ What Was Fixed

✓ Updated `.env` and `.env.production` with proper SMTP configuration  
✓ Changed `MAIL_MAILER` from placeholder to `smtp`  
✓ Updated MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD structure  

---

## ⚠️ What Still Needs to Be Done

### 1. **SSH Connection Issue**

After adding SSH keys, the connection was locked. You have three options:

**Option A: Use Web Console (Easiest)**

- Contact your hosting provider (Linode, DigitalOcean, etc.)
- Use their web-based SSH console/terminal

**Option B: Re-authorize SSH from Server**

```bash
# On the server directly, restore original SSH config
rm /root/.ssh/authorized_keys
systemctl restart sshd
```

**Option C: Use the Deployment Scripts**

I've created two bash scripts in the project root:

```bash
ls -la production-setup.sh create-admin.sh
```

### 2. **Apply the Production Configuration**

**Copy script to server:**

```bash
scp -i ~/.ssh/insurance2026_deploy production-setup.sh root@162.254.35.48:/root/
scp -i ~/.ssh/insurance2026_deploy create-admin.sh root@162.254.35.48:/root/
```

**Run setup script:**

```bash
ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48 bash /root/production-setup.sh
```

### 3. **Create Admin User**

```bash
ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48 bash /root/create-admin.sh
```

---

## 🔧 Manual Steps on Production Server

If scripts don't work, SSH into the server and run manually:

```bash
cd /opt/insurance2026

# Verify containers
docker compose ps

# Check health
curl -k https://tamlexus.sbs/api/health

# Clear Laravel caches
docker exec ins2026-app php artisan config:clear
docker exec ins2026-app php artisan cache:clear
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan route:cache

# Restart containers
docker compose restart app horizon reverb scheduler

# Check logs
docker compose logs -f app
```

---

## 📝 Critical SMTP Configuration

Before the site can send emails, update your `.env.production` on the server:

```bash
ssh root@162.254.35.48
cd /opt/insurance2026
nano .env.production
```

Replace these placeholders:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com (or your provider)
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

**Recommended SMTP Providers:**

- **Gmail**: smtp.gmail.com:587 (use App Password)
- **SendGrid**: smtp.sendgrid.net:587
- **AWS SES**: email-smtp.{region}.amazonaws.com:587
- **Mailgun**: smtp.mailgun.org:587
- **Postmark**: smtp.postmarkapp.com:587

Then restart:

```bash
docker compose restart app horizon reverb scheduler
```

---

## 🔍 Verification Checklist

- [ ] SSH connection works: `ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48 "pwd"`
- [ ] Docker containers running: `docker compose ps`
- [ ] Website responds: `curl -k https://tamlexus.sbs/api/health` returns `200`
- [ ] Admin users exist: Check database for `admin@tamlexus.sbs` and `dr@tamlexus.sbs`
- [ ] SMTP configured: No placeholder values in `.env.production`
- [ ] Logs show no errors: `docker compose logs app` is clean

---

## 📞 If Something Still Fails

1. **Check Docker logs:**

   ```bash
   docker compose logs app | tail -50
   ```

2. **Check Laravel logs:**

   ```bash
   docker exec ins2026-app tail -50 storage/logs/laravel.log
   ```

3. **Verify database connection:**

   ```bash
   docker exec ins2026-db mysql -u insurance -p${DB_PASSWORD} insurance2026 -e "SELECT 1"
   ```

4. **Check Redis connectivity:**

   ```bash
   docker exec ins2026-redis redis-cli PING
   ```

5. **Verify certificates:**

   ```bash
   ls -la /etc/letsencrypt/live/tamlexus.sbs/
   ```

---

## 📋 Files Modified

- `.env` — SMTP configuration updated
- `.env.production` — SMTP configuration updated  
- `production-setup.sh` — Full setup script
- `create-admin.sh` — Admin user creation script

---

## 🚀 Expected End Result

```
URL: https://tamlexus.sbs/login
Admin Email 1: admin@tamlexus.sbs
Admin Email 2: dr@tamlexus.sbs
Password: (as configured)

HTTP Status: 200
Docker Containers: All running ✓
Database: Connected ✓
Redis: Connected ✓
SMTP: Configured ✓
```
