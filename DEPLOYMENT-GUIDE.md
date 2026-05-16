# VPS Deployment Guide — Insurance 2026

## 🚀 Quick Start

### Prerequisites Checklist

- [ ] SSH key generated: `~/.ssh/insurance2026_deploy`
- [ ] Public key installed on VPS root user
- [ ] SSH access verified
- [ ] DNS A records pointing `tamiikom.online` → `162.254.35.48` (verify with `nslookup`)
- [ ] GitHub repository URL available

### Step 1: Generate SSH Key (Local Machine)

Run this in **Git Bash, WSL, or terminal**:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/insurance2026_deploy -C "ins2026-deploy" -N ""
cat ~/.ssh/insurance2026_deploy.pub
```

### Step 2: Install Public Key on VPS

Use `ssh-copy-id` with the temporary root password from Namecheap:

```bash
ssh-copy-id -i ~/.ssh/insurance2026_deploy.pub root@162.254.35.48
```

When prompted, enter the temporary root password from your Namecheap hosting details.

### Step 3: Verify SSH Access

```bash
ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48 'echo "✓ SSH OK"'
```

### Step 4: Verify DNS

```bash
nslookup tamiikom.online 8.8.8.8
# Should show: Address: 162.254.35.48
```

### Step 5: Run Deployment

From the repository root:

```bash
# Set your GitHub repo URL
export INS_REPO_URL="git@github.com:owner/insurance2026.git"

# Run deployment
bash deploy-prod.sh "$INS_REPO_URL"
```

**Or manually with full control:**

```bash
cd deploy/new-server

# Set environment variables
export INS_SERVER_IP="162.254.35.48"
export INS_DOMAIN="tamiikom.online"
export INS_REPO_URL="git@github.com:owner/insurance2026.git"
export SSH_KEY="$HOME/.ssh/insurance2026_deploy"

# Run the deployment script
bash deploy.sh
```

---

## 📋 What the Deployment Script Does

The `deploy/new-server/deploy.sh` script automates these steps:

1. **Pre-flight checks** — Verify SSH, DNS, system resources
2. **Install Docker** — AlmaLinux 9 package manager (dnf)
3. **Clone repo** — GitHub via SSH deploy key
4. **Render .env.production** — Substitute domain placeholder
5. **Extract DB secrets** — Store DB password in Docker secret file
6. **Issue SSL cert** — Let's Encrypt via certbot standalone
7. **Build & start stack** — `docker compose up -d`
8. **Run migrations** — `php artisan migrate --force`
9. **Cache config** — Route, view, event caching
10. **Health checks** — Verify endpoints and WebSocket

**Total time:** ~10 minutes

---

## 🔐 Secret Generation (On Server Only)

**IMPORTANT:** Do NOT commit secrets to git or print in chat. Secrets are generated on the server during deployment and stored securely in Docker secrets.

The deployment script (`deploy.sh`) handles secret generation:
1. Renders `.env.production` from template
2. Extracts `DB_PASSWORD` into Docker secret file
3. Generates `db_root_password` secret if needed

**After deployment**, if you need to change secrets:
- Edit `.env.production` directly on server
- Store DB password in `docker/secrets/db_password.txt`
- Recreate containers: `docker compose up -d --force-recreate app horizon reverb scheduler`

### Update Missing Values (After Deployment)

SSH to the server and edit:

```bash
ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48

# Edit the environment file
nano /opt/insurance2026/.env.production

# Recreate containers to load new env
cd /opt/insurance2026
docker compose up -d --force-recreate app horizon reverb scheduler
```

---

## ✅ Post-Deployment Verification

### SSH to Server

```bash
ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48
cd /opt/insurance2026
```

### Check Container Status

```bash
docker compose ps
```

Expected healthy status: `ins2026-nginx`, `ins2026-app`, `ins2026-db`, `ins2026-redis`, `ins2026-horizon`, `ins2026-reverb`, `ins2026-scheduler`

### Check Health Endpoints

From the server:

```bash
curl -s https://tamiikom.online/api/health | jq .
curl -s https://tamiikom.online/api/health/queues | jq .
curl -s https://tamiikom.online/api/health/realtime | jq .
```

From your local machine:

```bash
curl -k https://tamiikom.online/
curl -k https://tamiikom.online/login
```

### Test WebSocket Connection

In browser console on `https://tamiikom.online`:

```javascript
new WebSocket('wss://tamiikom.online/app/a6e649918d7ad2d178125dde')
  .addEventListener('open', () => console.log('✓ WebSocket OK'));
```

### View Logs

```bash
docker logs -f ins2026-app      # Application logs
docker logs -f ins2026-nginx    # Web server logs
docker logs -f ins2026-db       # Database logs
docker exec ins2026-app tail -f storage/logs/laravel.log
```

---

## 🔄 Routine Maintenance

### Update Application (New Deploy)

```bash
cd /opt/insurance2026
git pull origin main
docker compose build --pull app
docker compose up -d --force-recreate app horizon reverb scheduler
```

### View Database

```bash
docker exec ins2026-db mariadb -uroot -p$(cat /run/secrets/db_root_password) insurance2026
```

### Backup Database

```bash
docker exec ins2026-db sh -c 'mariadb-dump -uroot -p$(cat /run/secrets/db_root_password) insurance2026 | gzip' > insurance2026.sql.gz
```

### Renew SSL Certificate (Monthly)

```bash
cd /opt/insurance2026
docker run --rm -v $PWD/docker/certbot/conf:/etc/letsencrypt \
  certbot/certbot renew --quiet
docker exec ins2026-nginx nginx -s reload
```

---

## 🆘 Troubleshooting

### SSH Connection Refused

```bash
# Verify key is installed on VPS
cat ~/.ssh/insurance2026_deploy.pub

# SSH to server manually with password and install key
ssh root@162.254.35.48
# Paste key into ~/.ssh/authorized_keys
exit

# Retry
ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48
```

### DNS Not Resolving

```bash
# Check DNS records at your registrar
nslookup tamiikom.online
nslookup tamiikom.online 8.8.8.8

# If still not resolved, wait for DNS propagation (can take 15-30 min)
# Then re-run deployment
```

### SSL Certificate Issuance Failed

```bash
# Make sure nginx is not running on port 80
docker compose stop nginx || true

# Manually issue cert
docker run --rm -p 80:80 \
  -v /opt/insurance2026/docker/certbot/conf:/etc/letsencrypt \
  certbot/certbot certonly --standalone --non-interactive --agree-tos \
  -m admin@tamiikom.online \
  -d tamiikom.online -d www.tamiikom.online

# Start nginx
docker compose up -d nginx
```

### Container Won't Start / Connection Refused

```bash
# Check logs
docker logs ins2026-app
docker logs ins2026-db

# Wait for DB to be ready
docker exec ins2026-db sh -c 'mariadb -uroot -p$(cat /run/secrets/db_root_password) -e "SELECT 1"'

# Recreate if needed
docker compose down
docker compose up -d
```

### Mail Not Sending

The default `.env.production` uses `MAIL_MAILER=log` (stores mail to logs for testing). Update after deployment:

```bash
ssh -i ~/.ssh/insurance2026_deploy root@162.254.35.48
cd /opt/insurance2026
nano .env.production

# Change:
# MAIL_MAILER=log
# to:
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password

# Recreate containers
docker compose up -d --force-recreate app horizon reverb scheduler
```

---

## 📞 Support

For issues, check:

- `RUNBOOK.md` — Detailed setup instructions
- `docker-compose.yml` — Service definitions
- `.env.production.example` — Configuration reference
- Container logs: `docker logs <service-name>`
