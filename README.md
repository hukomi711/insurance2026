# Insurance 2026 (تأمينكم)

Car insurance comparison and purchase platform for the Saudi market.

---

## Tech Stack

| Layer | Technology |
| ----- | ---------- |
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3 (SPA), Vite 7, Tailwind CSS v4, Pinia |
| Real-time | Laravel Reverb (WebSocket), Laravel Echo |
| Queue | Laravel Horizon, Redis |
| Database | MariaDB 11 (production), SQLite :memory: (tests) |
| Auth | Laravel Sanctum |
| i18n | vue-i18n (Arabic primary, English fallback) |
| Containerization | Docker (multi-stage), Nginx, Redis |

## Architecture Overview

```text
Client (HTTPS/WSS)
  │
  ▼
Nginx ─────────────────────────────────────┐
  │  reverse proxy                         │
  ▼                                        ▼
PHP-FPM (Laravel API)              Reverb (WebSocket)
  │                                        ▲
  ├── Redis (cache, sessions, queue) ──────┘
  ├── Horizon (queue workers)
  └── MariaDB (primary database)
```

- **Nginx** — HTTPS termination, static assets, reverse proxy to PHP-FPM and Reverb (WSS).
- **PHP-FPM** — Serves the Laravel API; dispatches jobs to Redis queues.
- **Reverb** — WebSocket server for real-time events (status updates, notifications).
- **Redis** — Shared bus for cache, sessions, queue, and broadcasting.
- **Horizon** — Dedicated worker that processes queued jobs from Redis.
- **MariaDB** — Persistent relational storage.

## Environment Configuration

The project uses environment-based configuration:

| File | Purpose | Committed |
| ---- | ------- | --------- |
| `.env` | Local development | No |
| `.env.production` | Production values (deployed on server) | No |

> **⚠️ Never commit real environment files.**
> Both files are listed in `.gitignore`. If you need to share a clean template,
> strip all secrets and commit a copy as `.env.example`.

Key environment groups:

- **App** — `APP_KEY`, `APP_URL`, `APP_ENV`
- **Database** — `DB_*` (MariaDB credentials, patched from Docker secret at runtime)
- **Redis** — `REDIS_*` (shared for cache, queue, sessions, broadcasting)
- **Reverb** — `REVERB_*`, `VITE_REVERB_*` (WebSocket server + client)
- **Mail** — `MAIL_*` (SMTP credentials)
- **Admin** — `ADMIN_*` (dashboard credentials and allowed IPs)

## Security Notice

- **Do NOT commit** `.env` or `.env.production` — they contain secrets.
- **Rotate secrets immediately** if exposure is suspected (`php artisan key:generate`, update Docker secrets).
- **Docker secrets** are used for `DB_PASSWORD` — the entrypoint patches it at runtime.
- **`.gitleaks.toml`** and **`.pre-commit-config.yaml`** enforce pre-commit secret scanning.
- Run `composer audit` and `npm audit` regularly to check for known vulnerabilities.

## Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `intl`, `zip`, `gd`, `bcmath`, `redis`
- Composer 2
- Node.js 20+, npm
- Redis server
- MariaDB or MySQL

## Local Setup

### Option A: Manual

```bash
# 1. Install backend dependencies (Windows needs pcntl/posix ignore flags)
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

# 2. Install frontend dependencies
npm ci

# 3. Create environment file with local database credentials
cp .env.example .env        # if .env.example exists
# — or — create .env manually from the template in .env.production (strip secrets)

# 4. Generate app key and run migrations
php artisan key:generate
php artisan migrate

# 5. Create storage symlink (once)
php artisan storage:link

# 6. Start all services (server + queue + logs + vite)
composer dev
```

### Option B: Git Bash (Windows)

```bash
bash scripts/bootstrap-gitbash.sh   # first time setup
bash scripts/dev-gitbash.sh         # daily development
```

## Development Commands

| Command | Description |
| ------- | ----------- |
| `composer dev` | Start server + queue + logs + Vite (all-in-one) |
| `composer test` | Clear config cache, run PHPUnit tests |
| `composer format` | Format PHP with Laravel Pint |
| `composer analyse` | Run PHPStan/Larastan (level 5) |
| `npm run dev` | Start Vite dev server only |
| `npm run build` | Build frontend for production |
| `npm run lint` | Run ESLint on Vue/JS files |
| `npm run lint:fix` | Auto-fix ESLint issues |
| `npm test` | Run Vitest frontend tests |
| `npm run test:coverage` | Run Vitest with V8 coverage |

## Testing

```bash
# Backend (119 tests, 331 assertions)
composer test

# Frontend (64 tests)
npm test

# Static analysis (0 errors at level 5)
composer analyse

# Security advisories
composer audit
npm audit
```

## Deployment (Summary)

```bash
# First time — generates secrets, configures .env, builds images
bash docker/scripts/first-deploy.sh

# Subsequent deploys — rebuilds and restarts containers
bash docker/scripts/deploy.sh
```

Post-deploy verification:

1. **Nginx** — `curl -I https://<domain>` returns `200`
2. **App** — `docker exec ins2026-app php artisan about` shows correct env
3. **Horizon** — `docker logs ins2026-horizon` shows workers running
4. **Reverb** — WebSocket connects on `wss://<domain>/app/<key>`

See [DEPLOYMENT.md](DEPLOYMENT.md) for the full deployment runbook (deploy by file type, rollback, forbidden commands).

## Docker Services (Production)

| Service | Container | Port | Role |
| ------- | --------- | ---- | ---- |
| Nginx | ins2026-nginx | 80, 443 | Reverse proxy, TLS, static files |
| PHP-FPM | ins2026-app | 9000 (internal) | Laravel API |
| Horizon | ins2026-horizon | — | Queue worker |
| Reverb | ins2026-reverb | 8080 (internal) | WebSocket server |
| Scheduler | ins2026-scheduler | — | Cron (task scheduling) |
| Redis | ins2026-redis | 6379 (internal) | Cache, queue, sessions |
| MariaDB | ins2026-db | 3306 (internal) | Primary database |

## Production Notes

- WebSockets run via **Reverb** behind Nginx (WSS on port 443).
- **Redis is required** — it backs queue, cache, sessions, and broadcasting.
- **Horizon must be running** for background jobs (email, notifications, status polling).
- After `.env.production` changes: `docker compose up -d --force-recreate`.
- Database password is injected from Docker secret at container start — do not hardcode it in `.env.production`.

## Project Structure

```text
app/              # Laravel application (Models, Controllers, Services, Events)
config/           # Application configuration
database/         # Migrations, factories, seeders
docker/           # Docker configs (nginx, php, mysql, scripts)
  scripts/        # Deployment and server setup scripts
resources/
  js/             # Vue 3 SPA (pages, components, stores, i18n)
  css/            # Tailwind CSS entry point
  views/          # Blade templates (SPA shell)
routes/           # API and web routes
tests/
  Unit/           # PHPUnit unit tests
  Feature/        # PHPUnit feature tests
  js/             # Vitest frontend tests
```

## Troubleshooting

### WebSocket not connecting

- Verify the Reverb container is running: `docker ps | grep reverb`
- Check allowed origins in `.env` (`REVERB_ALLOWED_ORIGINS`)
- Ensure Nginx proxies `/app/*` to port 8080 (see `docker/nginx/`)

### Queue not processing

- Verify Horizon is running: `docker logs ins2026-horizon --tail 20`
- Confirm Redis is reachable: `docker exec ins2026-app php artisan tinker --execute="Redis::ping()"`

### Vite build fails

```bash
rm -rf node_modules
npm ci
npm run build
```

### Database connection refused

- Ensure MariaDB container is healthy: `docker ps | grep db`
- Confirm `DB_HOST`, `DB_PORT`, `DB_DATABASE` match `docker-compose.yml`
- Check Docker secret exists: `docker secret ls | grep db`

## Platform Notes

- `laravel/horizon` requires `ext-pcntl` and `ext-posix` (Linux only).
  On native Windows PHP: `composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`
- Production Docker image includes all required extensions.
- Dependencies use deterministic installs (`npm ci`, `composer install --prefer-dist`).
- `storage:link` is automated in Docker deployment via `docker/scripts/deploy.sh`.

## License

Proprietary — All rights reserved.
