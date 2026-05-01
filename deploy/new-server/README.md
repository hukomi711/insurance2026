# Deploy to NEW server

This folder contains the one-shot deployment for a fresh AlmaLinux 9 server.

## Files
| File | Purpose |
|---|---|
| `deploy.sh` | One-shot deploy script (idempotent) |
| `.env.production.template` | (untracked) Production env adjusted for new domain |
| `insurance2026.sql.gz` | (untracked) DB dump from production |

## Required env vars (no hardcoded IPs/domains in repo)

```bash
export INS_SERVER_IP=1.2.3.4              # target server IPv4
export INS_DOMAIN=example.com             # primary domain
export INS_REPO_URL=https://github.com/owner/repo.git
export INS_DEPLOY_USER=root               # optional, defaults to root
export INS_DEPLOY_DIR=/opt/insurance2026  # optional
export SSH_KEY=$HOME/.ssh/id_ed25519      # optional
export LE_EMAIL=admin@example.com         # optional, Let's Encrypt
```

## Pre-requisites (NOT automated)

1. **DNS** — point `$INS_DOMAIN` and `www.$INS_DOMAIN` (A records) to `$INS_SERVER_IP`.
   Verify: `nslookup $INS_DOMAIN 8.8.8.8`.

2. **Server resources** — minimum 2 GB RAM / 20 GB disk / 1 vCPU. Recommended 4 GB / 40 GB / 2 vCPU.

3. **SSH key auth** — `ssh -i $SSH_KEY $INS_DEPLOY_USER@$INS_SERVER_IP echo OK` must succeed.

## Run

```bash
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
cd $INS_DEPLOY_DIR && docker compose down -v
```
Then re-run `deploy.sh` after fixing the issue. The script is idempotent.
