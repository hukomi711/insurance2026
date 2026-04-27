# Migration to VPS 159.198.43.139 — Step-by-Step

> **Context:** Namecheap shared hosting suspended (2026-04-27).
> Existing VPS at `159.198.43.139` already has Reverb stack tested. Reuse it.
> All secrets rotated 2026-04-27 — never use any pre-rotation values.

---

## A. Pre-flight (local machine)

```bash
cd /d/insurance2026

# 1. Verify clean tree
git status

# 2. Verify rotated secrets present
grep -E "^(APP_KEY|REVERB_APP_KEY|STATUS_POLL_SECRET|ADMIN_PASSWORD)=" .env.production

# 3. Build production assets
npm ci
npm run build

# 4. Sanity test
php artisan test --testsuite=Unit
```

---

## B. Server prep (one time per server)

```bash
ssh root@159.198.43.139

# Confirm Docker present
docker --version
docker compose version

# Confirm previous stack location (from prior memory)
ls -la /root/insurance2026 2>/dev/null || mkdir -p /root/insurance2026

# Free port check — Reverb 8080, app 80/443
ss -tlnp | grep -E ':80|:443|:8080'
```

---

## C. Decide: domain

**Current `.env.production` points at `wathiqah.store`.**
- ✅ If you own this domain and DNS works → use it.
- ⚠️ If `daliltameni.online` is what you want → update DNS first, then change `APP_URL` + `SESSION_DOMAIN` + `REVERB_HOST` + `REVERB_ALLOWED_ORIGINS` + `CORS_ALLOWED_ORIGINS` + `MAIL_FROM_ADDRESS` + `VITE_REVERB_HOST` in `.env.production`. Rebuild Vite.

**Recommendation:** abandon `daliltameni.online` (Namecheap-registered, suspended). Use `wathiqah.store` if owned elsewhere, or buy a fresh domain at Cloudflare/Porkbun.

---

## D. Sync code

```bash
# From local
cd /d/insurance2026

# Exclude dev junk
tar --exclude='node_modules' --exclude='vendor' --exclude='.git' \
    --exclude='storage/logs/*' --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' \
    -czf /tmp/ins2026.tar.gz .

scp /tmp/ins2026.tar.gz root@159.198.43.139:/root/

# On server
ssh root@159.198.43.139
cd /root/insurance2026
# Backup existing if present
[ -d /root/insurance2026/.git ] && \
  tar -czf /root/snapshots/$(date +%Y%m%d_%H%M%S)_before_redeploy.tar.gz . 2>/dev/null
mkdir -p /root/snapshots

# Extract fresh
rm -rf /root/insurance2026/* /root/insurance2026/.[!.]*
tar -xzf /root/ins2026.tar.gz -C /root/insurance2026/
```

---

## E. Build & start containers

```bash
cd /root/insurance2026

# .env.production already contains rotated secrets — verify
grep APP_KEY .env.production

# Verify secret files exist with new values
cat docker/secrets/db_password.txt | wc -c        # must be 44
cat docker/secrets/db_root_password.txt | wc -c   # must be 44

# Build image (bakes .env.production as .env)
docker compose build --no-cache

# Bring up everything
docker compose up -d

# Watch logs
docker compose logs -f --tail=50
```

---

## F. Post-start tasks (inside app container)

```bash
docker exec ins2026-app php artisan migrate --force
docker exec ins2026-app php artisan db:seed --class=DatabaseSeeder --force

# Recreate admin with NEW password
docker exec ins2026-app php artisan tinker --execute='
  \App\Models\User::updateOrCreate(
    ["email" => "admin@insurance.com"],
    [
      "name" => "Admin",
      "password" => \Illuminate\Support\Facades\Hash::make(env("ADMIN_PASSWORD")),
      "role" => "admin",
    ]
  );
'

# Cache warm
docker exec ins2026-app php artisan config:clear
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan event:cache
docker exec ins2026-app php artisan view:cache

# Critical: env was changed → force-recreate ALL php containers
docker compose up -d --force-recreate app horizon reverb scheduler
```

---

## G. Verify

```bash
# HTTP
curl -I https://wathiqah.store/

# Reverb
curl -I https://wathiqah.store/app/$REVERB_APP_KEY

# Horizon
docker exec ins2026-app php artisan horizon:status

# Tail app logs
docker exec ins2026-app tail -f storage/logs/laravel.log
```

---

## H. After successful deploy — push to git

```bash
# Local
cd /d/insurance2026
git push origin main
```

Only push AFTER you confirm production is healthy. Until then, the rotated `.env.production` lives only locally + on server.

---

## I. Rollback plan

If E or F fails:
```bash
ssh root@159.198.43.139
cd /root/insurance2026
docker compose down
tar -xzf /root/snapshots/<latest_before_redeploy>.tar.gz -C /root/insurance2026/
docker compose up -d
```

---

## J. Things to remember (from past incidents)

1. **`docker restart` does NOT reload `env_file`** — always `docker compose up -d --force-recreate`.
2. **All PHP containers share the baked .env** — recreate `app + horizon + reverb + scheduler` together after env changes.
3. **Never** `php artisan optimize:clear` or `config:cache` mid-incident.
4. Snapshot before any prod edit: `cp .env /root/snapshots/$(date +%s).env.bak`.
5. Scope discipline: fix only what is broken.
