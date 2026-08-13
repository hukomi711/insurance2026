# Deployment Runbook

> **Note:** Replace `<SERVER_IP>` with your actual production server IP throughout this document.

## Server Info

| Key | Value |
| --- | ----- |
| Server | `${INS_DEPLOY_USER:-root}@${INS_SERVER_IP}` |
| Path | `${INS_DEPLOY_DIR:-/opt/insurance2026}` |
| Domain | `${INS_DOMAIN}` |
| App container | `ins2026-app` |
| Nginx container | `ins2026-nginx` |

> All values come from environment variables. Never hardcode an IP or
> domain in this repo. Set them locally before running deploy scripts:
> `export INS_SERVER_IP=… INS_DOMAIN=…`.

## Deploy by File Type

### Blade Templates (`resources/views/*.blade.php`)

```bash
./docker/scripts/deploy-blade.sh resources/views/app.blade.php
```

Manual steps:

```bash
scp <file> root@<SERVER_IP>:/tmp/<file>
ssh root@<SERVER_IP> "\
  docker cp /tmp/<file> ins2026-app:/var/www/html/resources/views/<file> && \
  rm /tmp/<file> && \
  docker exec ins2026-app php artisan view:clear && \
  docker exec ins2026-app php artisan view:cache && \
  docker exec ins2026-app kill -USR2 1"
```

Why all 3 steps:

1. `view:clear` + `view:cache` — refreshes compiled Blade cache in `storage/framework/views/`
2. `kill -USR2 1` — restarts PHP-FPM workers to clear OPcache (PID 1 = FPM master in this container)

### Frontend JS/CSS (`resources/js/**`, `resources/css/**`)

```bash
./docker/scripts/deploy-build.sh
```

Manual steps:

```bash
npm run build
tar czf /tmp/build.tar.gz -C public build
scp /tmp/build.tar.gz root@<SERVER_IP>:/tmp/
ssh root@<SERVER_IP> "\
  cd /tmp && tar xzf build.tar.gz && \
  docker cp build ins2026-app:/var/www/html/public/ && \
  rm -rf build build.tar.gz"
```

No cache flush needed — Vite uses content-hashed filenames.

### PHP Source (`app/**`, `routes/**`)

```bash
./docker/scripts/deploy-php.sh app/Http/Middleware/CountryRestriction.php
```

Manual steps:

```bash
scp <file> root@<SERVER_IP>:/tmp/<basename>
ssh root@<SERVER_IP> "\
  docker cp /tmp/<basename> ins2026-app:/var/www/html/<file> && \
  rm /tmp/<basename> && \
  docker exec ins2026-app php artisan route:cache && \
  docker exec ins2026-app kill -USR2 1"
```

- `route:cache` only needed if routes changed
- `kill -USR2 1` always needed for OPcache

### Config (`config/**`)

```bash
scp config/<file> root@<SERVER_IP>:/tmp/<file>
ssh root@<SERVER_IP> "\
  docker cp /tmp/<file> ins2026-app:/var/www/html/config/<file> && \
  rm /tmp/<file> && \
  docker exec ins2026-app php artisan config:cache && \
  docker exec ins2026-app kill -USR2 1"
```

## Deployment Commands

| Command | Why |
| ------- | --- |
| `config:cache` | Required after Docker secrets are exported and after any config/env change |
| `config:clear` | Diagnostic only; do not leave production running without rebuilding config cache |
| `docker restart` | Does NOT reload `env_file` — use `docker compose up -d --force-recreate` instead |

## Golden Rule

> Edit local source first → deploy to server → flush the correct cache layer.
>
> Never edit inside the container only — next deploy will overwrite it.

## Cache Chain (Why Changes "Disappear")

```text
Source file (resources/views/app.blade.php)
    ↓ php artisan view:cache
Compiled view (storage/framework/views/*.php)
    ↓ PHP-FPM loads once
OPcache (in-memory)
```

If you update the source but don't flush all 3 layers, Laravel serves the old version.
This is NOT a file rollback — the source is correct, the cache is stale.

## Blade `@` Escaping

In Blade templates, `@word` is parsed as a directive. For JSON-LD structured data:

```blade
{{-- WRONG — causes ParseError --}}
"@context": "https://schema.org"

{{-- CORRECT — @@ outputs literal @ --}}
"@@context": "https://schema.org"
```

Affected directives: `@context` (Laravel 12 contextual data sharing).
