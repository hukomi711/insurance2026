# Laravel + Docker Alpine PHP-FPM Pitfalls — insurance2026

## 1. `.env` ownership trap

PHP-FPM workers run as `appuser`.

If `.env` becomes:

```bash
root:root 600
```

Laravel runtime may silently read `env()` as `null` for all keys, while `php artisan tinker` may still show correct values when executed as root via `docker exec`.

### Symptom

Cryptic production 500s, for example:

```text
Pusher::__construct(): Argument #1 ($auth_key) must be of type string, null given
```

even though `.env` looks correct.

### Cause

The entrypoint modifies `.env` using `sed -i` as root. This can rewrite the file and flip ownership to root.

### Permanent fix

After any entrypoint `.env` mutation:

```bash
chown appuser:appuser /var/www/html/.env
chmod 640 /var/www/html/.env
```

### Emergency recovery

```bash
docker exec -u 0 <container> chown appuser:appuser /var/www/html/.env
docker exec -u 0 <container> chmod 640 /var/www/html/.env
```

Apply to relevant containers if needed:

```bash
app
horizon
scheduler
reverb
```

---

## 2. Build config cache after injecting runtime secrets

Production uses:

```bash
php artisan config:cache
```

The Docker entrypoint exports file-based secrets such as `DB_PASSWORD` first,
then builds the cache. Runtime PHP reads environment-backed values exclusively
through `config()`; direct `env()` calls belong only in `config/*.php`.

### Required order

```bash
# 1. Export Docker secrets into the process environment.
# 2. Validate that .env/runtime environment is readable.
php artisan config:cache
```

`config:cache` is fail-fast. Missing or invalid production configuration must
stop the container instead of silently starting with stale values.

### After changing environment values

```bash
docker compose up -d --force-recreate app horizon reverb scheduler
```

The recreated containers rebuild their own config cache from the new values.

---

## 3. Deploy checklist — insurance2026

### 1. Push local commits

```bash
git push
```

### 2. Build frontend assets locally if JS/Vue/CSS changed

```bash
npm run build
```

### 3. Upload `public/build`

Git Bash on Windows does not have `rsync`, so use tar over SSH:

```bash
tar -czf - public/build | ssh root@server "cd /opt/insurance2026 && rm -rf public/build && tar -xzf -"
```

### 4. Pull code on production

```bash
ssh root@server "cd /opt/insurance2026 && git pull"
```

### 5. Rebuild app image for PHP code changes

The Dockerfile uses `COPY`; there is no bind mount for PHP code.

Therefore, this is required for PHP changes:

```bash
docker compose build app
docker compose up -d app horizon scheduler reverb
```

A simple restart is **not enough** for PHP code changes.

### 6. Asset-only changes

If `public/build` is served from the host/shared volume, a PHP image rebuild is not required.

Depending on the current Nginx/container setup, either no restart is needed or a lightweight reload/restart is enough.

Do not rebuild `app` just for assets unless assets are baked into the image.

### 7. Smoke test

Because the server may fail to resolve its own domain due to DNS issues, use `--resolve`:

```bash
curl -Ik --resolve lexusforbon.com:443:127.0.0.1 https://lexusforbon.com/
```

For a specific route:

```bash
curl -Ik --resolve lexusforbon.com:443:127.0.0.1 https://lexusforbon.com/api/health
```

### 8. Rebuild config cache in deploy steps

```bash
php artisan config:cache
```

---

## 4. DNS chronic issue

`lexusforbon.com` on Namecheap BasicDNS has recurring resolution failures, affecting both server-side checks and Chrome/client resolution.

Production may still work when tested with:

```bash
curl --resolve lexusforbon.com:443:127.0.0.1
```

This confirms the app/Nginx/SSL path works locally on the server even when DNS resolution fails externally or from the server.

### Recommendation

Migrate DNS to Cloudflare if the issue continues.

Cloudflare is recommended for:

```text
stable authoritative DNS
faster propagation
better diagnostics
optional proxy/CDN later
```

Do not introduce Cloudflare proxying until SSL, app headers, WebSocket/Reverb, and real IP forwarding are checked.
