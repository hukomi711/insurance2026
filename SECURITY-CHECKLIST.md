# Pre-Deployment Security Checklist ✓

## Files Reviewed & Corrected

### ✅ deploy-prod.sh

- Prerequisites validation: OK
- SSH test: OK
- DNS check: OK
- No dangerous commands: **VERIFIED**

### ✅ deploy/new-server/.env.production.template

- **FIXED:** Replaced all exposed secrets with `CHANGE_ME` placeholders:
  - ✅ APP_KEY=CHANGE_ME
  - ✅ DB_PASSWORD=CHANGE_ME
  - ✅ REVERB_APP_ID=CHANGE_ME
  - ✅ REVERB_APP_KEY=CHANGE_ME
  - ✅ REVERB_APP_SECRET=CHANGE_ME
  - ✅ STATUS_POLL_SECRET=CHANGE_ME
  - ✅ ADMIN_PASSWORD=CHANGE_ME

### ✅ DEPLOYMENT-GUIDE.md

- **FIXED:** Step 2 now uses `ssh-copy-id` with password auth (correct method)
- **FIXED:** Secret generation section removed exposed values, moved to server-only process
- ✅ Post-deployment secret update instructions added

---

## Important Notes

### Secrets Handling

1. **NOT generated client-side** — Template uses `CHANGE_ME` placeholders
2. **Generated on server** — `deploy.sh` renders values at deployment time
3. **Stored in Docker secrets** — DB_PASSWORD stored in `/opt/insurance2026/docker/secrets/`
4. **Never committed to git** — `.env.production` is `.gitignore`'d

### SSH Key Installation

- Use: `ssh-copy-id -i ~/.ssh/insurance2026_deploy.pub root@69.57.161.222`
- Enter temporary root password from Namecheap when prompted
- Key-based auth installed after first login

### Dangerous Commands NOT Used

- ❌ `php artisan config:cache` → Not called (uses `config:clear` instead)
- ❌ `php artisan optimize` → Not called
- ❌ `chmod -R 777` → Not used
- ❌ `docker compose down -v` → Not used
- ❌ `docker system prune -a` → Not used
- ❌ `sed -i` without restoration → Not used

---

## Next Steps (When Ready)

### Required from You

- [ ] GitHub repository URL (SSH format preferred)
- [ ] Confirm DNS is properly configured (will verify in deploy-prod.sh)

### What deploy.sh Will Do

1. Pre-flight checks (SSH, DNS, resources)
2. Install Docker on AlmaLinux 9
3. Clone repo from GitHub
4. Render `.env.production` (substitute `__DOMAIN__` placeholder)
5. Generate secrets (not print them)
6. Issue SSL certificate
7. Build and start containers
8. Run migrations
9. Cache config (config:clear, not config:cache)
10. Health checks

### PHP/Docker Production Rules Followed

✅ PHP changes: `docker compose build app` then recreate services
✅ Config changes: `config:clear` (never `config:cache`)
✅ Env changes: Recreate containers with `--force-recreate`
✅ Permissions: Handled by Docker (appuser:appuser, not 777)

---

## Ready to Deploy?

**Tell me:** What's your GitHub repository URL?

- Format: `git@github.com:OWNER/insurance2026.git` (SSH preferred)
- Or: `https://github.com/OWNER/insurance2026.git` (HTTPS)

Once you provide the URL, you can proceed with:

```bash
bash deploy-prod.sh "git@github.com:OWNER/insurance2026.git"
```
