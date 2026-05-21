# Insurance 2026 — VPS Deployment Ready ✓

## 📦 What's Prepared

Your deployment has been **fully configured**. All required scripts, templates, and documentation are ready.

### Files Created

1. **`deploy/new-server/.env.production.template`** — Production environment with auto-generated secrets
2. **`deploy-prod.sh`** — Simple deployment helper script
3. **`DEPLOYMENT-GUIDE.md`** — Complete step-by-step guide

### Pre-Generated Secrets

All cryptographic values have been generated:

- ✅ `APP_KEY` — Laravel encryption
- ✅ `DB_PASSWORD` — Database credentials  
- ✅ `REVERB_APP_ID/KEY/SECRET` — WebSocket configuration
- ✅ `STATUS_POLL_SECRET` — API polling security
- ✅ `ADMIN_PASSWORD` — Initial admin access

---

## 🚀 To Deploy Now

### Quick Version (5 minutes to deployment start)

```bash
# 1. Open Git Bash or WSL and generate SSH key
ssh-keygen -t ed25519 -f ~/.ssh/insurance2026_deploy -C "ins2026-deploy" -N ""

# 2. Install public key on VPS (use temporary root password from Namecheap)
scp -i ~/.ssh/insurance2026_deploy ~/.ssh/insurance2026_deploy.pub root@69.57.161.222:~/pk.pub
ssh root@69.57.161.222 "mkdir -p ~/.ssh && cat ~/pk.pub >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && rm ~/pk.pub"

# 3. Verify SSH works
ssh -i ~/.ssh/insurance2026_deploy root@69.57.161.222 "echo ✓ SSH OK"

# 4. Run deployment
cd d:\insurance2026.worktrees\copilot-vps-magnetar-hosting-details
bash deploy-prod.sh "git@github.com:YOUR_USERNAME/insurance2026.git"
```

### Manual Version (Full Control)

Follow the **DEPLOYMENT-GUIDE.md** file (already created in repo root).

---

## 🔑 Your Deployment Credentials

**Server Information:**

- IP: `69.57.161.222`
- Domain: `lexusforbon.it.com`
- OS: AlmaLinux 9
- Root user: `root`

**Deployment Details:**

- App directory: `/opt/insurance2026`
- Database: `insurance2026` (user: `insurance2026`)
- Frontend build: Embedded in Docker image
- SSL: Auto-provisioned via Let's Encrypt

---

## ⚠️ Important Notes

1. **GitHub Repository URL Required** — You'll be asked for your repo URL (SSH or HTTPS)
2. **DNS Must Resolve First** — Before running deployment, verify:

   ```bash
   nslookup lexusforbon.it.com 8.8.8.8
   # Should return: 69.57.161.222
   ```

3. **Post-Deploy Configuration** — After deployment succeeds, SSH to server and update:
   - Mail server credentials
   - API keys (IP_API_KEY, NEXAFLOW_API_KEY, etc.)

   See "Update Missing Values" in DEPLOYMENT-GUIDE.md

4. **First Admin Login** — Username/email: `admin@lexusforbon.it.com`, password from `.env.production` (ADMIN_PASSWORD)

---

## 📚 Next Steps

1. **Read** `DEPLOYMENT-GUIDE.md` for detailed instructions
2. **Generate SSH key** (commands above)
3. **Install public key** on VPS  
4. **Verify DNS** propagation
5. **Run deployment script** with your GitHub repo URL

---

## ✨ After Deployment

Your app will be available at: `https://lexusforbon.it.com`

Services running:

- ✅ Laravel PHP-FPM backend
- ✅ Nginx reverse proxy (SSL)
- ✅ MariaDB database
- ✅ Redis cache & sessions
- ✅ Laravel Reverb WebSocket server
- ✅ Laravel Horizon queue dashboard
- ✅ Task scheduler

Health endpoints:

- `GET /api/health` — Application status
- `GET /api/health/queues` — Queue health
- `GET /api/health/realtime` — WebSocket status

---

**Questions?** See `DEPLOYMENT-GUIDE.md` troubleshooting section.
