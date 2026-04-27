# Production Deployment Plan — Insurance 2026

> Last updated: 2026-04-27
> Owner: tamincom
> Stack: Laravel 12 · PHP 8.2+ · Vue 3.5 · Vite 8 · MySQL 8 · Redis · Reverb · Horizon

---

## 1. Repository Layout (Canonical)

```
insurance2026/
├── app/                        # Laravel application
│   ├── Casts/
│   ├── Console/Commands/
│   ├── Enums/
│   ├── Events/
│   ├── Http/{Controllers,Middleware,Requests,Resources}
│   ├── Jobs/
│   ├── Mail/
│   ├── Models/
│   ├── Providers/
│   └── Services/
├── bootstrap/                  # Framework bootstrap (do not edit cache/)
├── config/                     # All configuration files
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docker/                     # Infrastructure as code
│   ├── nginx/
│   ├── php/
│   ├── mysql/
│   ├── certbot/                # gitignored content
│   └── secrets/                # gitignored
├── docs/                       # ALL documentation lives here
│   ├── deployment/             # Deployment guides + this plan
│   ├── legal/                  # Privacy, Terms, AUP, DMCA
│   ├── runbooks/               # Incident playbooks
│   ├── bundle-baseline.md
│   └── pricing-payload-schema.json
├── public/                     # Web-accessible ONLY (no PHP scripts!)
│   ├── build/                  # Vite output (gitignored)
│   ├── images/
│   ├── Videos/
│   ├── index.php               # ONLY entry point
│   ├── favicon.ico
│   ├── manifest.json
│   ├── robots.txt
│   └── sitemap.xml
├── resources/
│   ├── css/
│   ├── fonts/
│   ├── images/
│   ├── js/                     # Vue 3 SPA
│   └── views/                  # Blade
├── routes/                     # api.php, web.php, channels.php, console.php
├── scripts/                    # Local dev helpers ONLY
├── storage/                    # Logs, cache, sessions (gitignored)
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── js/
├── .env.example                # Template — never put real secrets
├── .gitignore                  # Hardened (see Section 6)
├── artisan
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── package.json
├── README.md
└── SECURITY.md
```

### Forbidden in `public/`
- ❌ `*setup*.php`, `*fix*.php`, `cleanup.php`, `create-admin.php`, `run-seeder.php`, `show-log.php`, `acme-helper.php`, `mk-symlink.php`
- ❌ Any `.php` file other than `index.php`
- ❌ `.pem`, `.key`, `.crt` files
- ❌ `.env*` files

> **Rationale:** هذه الأنماط هي السبب المباشر لتعليق Namecheap للحساب — ماسحات malware تصنّفها backdoor.

---

## 2. Environments

| Env | Branch | Domain | Server | Purpose |
|-----|--------|--------|--------|---------|
| local | feature/* | localhost | dev machine | development |
| staging | develop | staging.example | VPS | QA + UAT |
| production | main | (TBD) | VPS Hetzner/STC | live |

### `.env` files
| File | Tracked? | Purpose |
|------|----------|---------|
| `.env.example` | ✅ git | template only, no secrets |
| `.env` | ❌ gitignored | local dev |
| `.env.production` | ❌ gitignored | server only, never commit |
| `.env.*.local` | ❌ gitignored | overrides |

---

## 3. Hosting Decision

**Drop Namecheap shared hosting permanently.** Reasons:
1. Account suspended (2026-04-27) for "phishing" — auto-classifier triggered by web-shell PHP scripts.
2. Shared hosting cannot run Reverb/Horizon/queue workers reliably.
3. No Docker support → Dockerfile/compose files were unused.

### Recommended targets
| Provider | Monthly | Pros | Cons |
|----------|---------|------|------|
| **Hetzner Cloud CX22** | ~€5 | Docker, fast EU, KVM | EU latency to KSA |
| **Contabo VPS S** | ~$7 | cheap, generous specs | shared CPU |
| **STC Cloud / Mobily** | varies | KSA residency (Nafath compliance) | costlier |
| **Existing 159.198.43.139** | already paid | known-good Reverb stack | confirm capacity |

> **Recommendation:** ابدأ على VPS موجود (`159.198.43.139`) لأن stack Reverb/Horizon مُختبر مسبقاً. لاحقاً نقل إلى STC Cloud عند الحاجة لـ Nafath compliance.

---

## 4. Secret Rotation (MANDATORY before next deploy)

كل الأسرار التالية **مفترض أنها مكشوفة** بسبب الملفات المحذوفة:

```bash
# 1. APP_KEY
php artisan key:generate --show

# 2. DB password — generate fresh
openssl rand -base64 32

# 3. Reverb credentials
REVERB_APP_ID=$(openssl rand -hex 8)
REVERB_APP_KEY=$(openssl rand -hex 16)
REVERB_APP_SECRET=$(openssl rand -hex 32)

# 4. ADMIN_OTP_SECRET / NAFATH_SECRET / signing keys
openssl rand -hex 32

# 5. Setup token (ins2026setup2026) — MUST NOT BE REUSED
# Replace with a one-time CLI artisan command instead of a public PHP file.

# 6. Admin password (Admin@2026!) — was hardcoded in create-admin.php
php artisan tinker
>>> User::where('email','admin@insurance.com')->first()->update(['password' => Hash::make('NEW_STRONG_PASSWORD')]);
```

Update on server:
```bash
docker compose up -d --force-recreate app horizon reverb scheduler
```
Then flush caches:
```bash
docker exec ins2026-app php artisan config:clear
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan event:cache
docker exec ins2026-app php artisan view:cache
```

---

## 5. Deployment Workflow

### A. Build artifacts locally
```bash
npm ci
npm run build                  # writes public/build/
composer install --no-dev --optimize-autoloader
```

### B. Sync to server (per file type)

| Changed | Steps |
|---------|-------|
| Blade (`resources/views/*.blade.php`) | scp → docker cp → `view:clear` → `view:cache` → `kill -USR2 1` (FPM) |
| Frontend (`resources/js`, `resources/css`) | `npm run build` → tar → scp → docker cp `public/build` |
| PHP source (`app/`, `routes/`, `config/`) | scp → docker cp → `route:cache` (if routes) → `kill -USR2 1` |
| `.env.production` | edit on server → `docker compose up -d --force-recreate <service>` (NEVER `docker restart`) |

### C. Containers requiring env sync
**ALL** PHP containers share the same image with `.env.production` baked at build time. After any env change you MUST recreate:
- `ins2026-app`
- `ins2026-horizon`
- `ins2026-reverb`
- `ins2026-scheduler`

### D. Forbidden
- ❌ `php artisan optimize:clear`
- ❌ `php artisan config:cache` (use `config:clear` instead during deploy)
- ❌ `git push --force` to `main`
- ❌ Editing files inside container without updating local source
- ❌ Creating one-time setup PHP files in `public/`

---

## 6. Hardened `.gitignore` (already applied)

Critical additions:
```
*.pem
*.key
*.crt
ssl_*.pem
cabundle*.pem
.env.*.local
.env.shared-hosting
.env.build-temp

# Banned web-accessible scripts
public/*setup*.php
public/*fix*.php
public/show-log.php
public/run-seeder.php
public/create-admin.php
public/cleanup.php
public/acme-helper.php
public/mk-symlink.php
```

---

## 7. CI / Quality Gates

Before any merge to `main`:
- [ ] `composer install` clean
- [ ] `npm ci` clean
- [ ] `php artisan test` passes (Unit + Feature)
- [ ] `vendor/bin/phpstan analyse` clean
- [ ] `vendor/bin/pint --test` clean
- [ ] `npm run lint` clean
- [ ] `npm run build` succeeds
- [ ] No new files in `public/*.php` except `index.php`
- [ ] No `.pem` / `.key` / `.env*` files added
- [ ] Bundle size delta within `docs/bundle-baseline.md`

Suggested pre-commit (already in `.pre-commit-config.yaml`):
- gitleaks (secrets scan)
- pint (PHP style)
- eslint

---

## 8. Backup & DR

| Item | Frequency | Location |
|------|-----------|----------|
| MySQL dump | daily | off-server (S3-compatible) |
| `storage/app/` | daily | off-server |
| Code | every push | GitHub |
| `.env.production` | on-change | offline encrypted vault |

Restore drill: monthly.

---

## 9. Monitoring (post-deploy)

| Signal | Tool |
|--------|------|
| HTTP 5xx rate | nginx logs + Loki/Grafana |
| Queue length | Horizon UI |
| Reverb connections | Reverb metrics endpoint |
| DB slow queries | MySQL slow log |
| Cert expiry | certbot deploy hook + alert |

---

## 10. Incident Response (link)

See `docs/runbooks/` (to be populated). Follow the strict protocol from user memory:
**Diagnose → Fix → Verify → Stop**. One change at a time. Snapshot before any production edit.

---

## 11. Cleanup Audit Log (this session, 2026-04-27)

Deleted (Group A — security critical):
- `public/{acme-helper,cleanup,create-admin,fix-ins2026,mk-symlink,run-seeder,setup-ins2026,show-log}.php`
- `ssl_key.pem`, `cabundle.pem`, `cabundle_full.pem`, `cert_only.pem`
- `.env.production.local`, `.env.shared-hosting`, `.env.build-temp`
- `scripts/acme-watcher.sh`

Deleted (Group B — regenerable artifacts):
- `_ide_helper.php`, `_ide_helper_models.php`
- `.build-sha`, `.phpunit.result.cache`

Deleted (Group C — orphaned):
- `manual/` (empty)

Moved (Group D — reorganization):
- `DEPLOYMENT.md` → `docs/deployment/DEPLOYMENT.md`

Hardened:
- `.gitignore` (PEM/keys/setup-scripts blocked)
