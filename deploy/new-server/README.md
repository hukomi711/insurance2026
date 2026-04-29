# Deploy to NEW server (taminsurnce.site)

This folder contains everything needed to deploy the system to a new server with **one command**.

## Files
| File | Purpose |
|---|---|
| `insurance2026.sql.gz` | DB dump from production (45KB, 29 tables) |
| `.env.production.template` | Production env adjusted for new domain |
| `.env.old.reference` | Original env from old server (kept for reference) |
| `deploy.sh` | One-shot deploy script |

## Pre-requisites (NOT automated)

You **must** complete these manually before running `deploy.sh`:

### 1. DNS — point domain at new server
In Namecheap → Advanced DNS:
- Delete any URL Redirect / Parking
- Add `A @ -> 162.0.216.105` and `A www -> 162.0.216.105`

Verify:
```bash
nslookup taminsurnce.site 8.8.8.8
# Must return: 162.0.216.105
```

### 2. Server resources
- **Minimum:** 2 GB RAM, 20 GB disk, 1 vCPU
- **Recommended:** 4 GB RAM, 40 GB disk, 2 vCPU

The current server (162.0.216.105) has only **960 MB RAM** — upgrade before running.

### 3. SSH key auth (already done ✓)
```bash
ssh -i ~/.ssh/id_ed25519 root@162.0.216.105 'echo OK'
```

### 4. Set repo URL in `deploy.sh`
Edit `REPO_URL` at the top of the script to your actual git repo URL.

## Run

```bash
bash deploy/new-server/deploy.sh
```

Override defaults via env vars:
```bash
NEW_IP=1.2.3.4 DOMAIN=example.com REPO_URL=https://github.com/owner/repo.git \
  bash deploy/new-server/deploy.sh
```

## What it does

1. Pre-flight: SSH check, DNS check, file existence
2. Install Docker + git on AlmaLinux (idempotent)
3. `git clone` repo
4. Upload `.env.production` + generate Docker secrets
5. Upload DB dump
6. Issue Let's Encrypt cert (standalone, port 80)
7. `docker compose up -d --build`
8. Run migrations + import dump
9. Health checks (containers, /api/health, /api/health/queues, /api/health/realtime)
10. Public end-to-end probe

## Rollback

If something fails, on the new server:
```bash
cd /opt/insurance2026 && docker compose down -v
```
Then re-run `deploy.sh` after fixing the issue. The script is idempotent.
