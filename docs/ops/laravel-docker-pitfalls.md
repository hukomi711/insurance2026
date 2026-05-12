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

## 2. Never run `config:cache` on this stack

Do **not** run:

```bash
php artisan config:cache
```

This stack intentionally relies on runtime `.env` access and Docker/runtime secrets.

`config:cache` can freeze null or stale values into Laravel config, including:

```text
DB_PASSWORD
ADMIN_PASSWORD
REVERB / PUSHER keys
VITE-related runtime assumptions
```

### Required behavior

The entrypoint should run:

```bash
php artisan config:clear
```

and should explicitly avoid config caching.

### If config was cached accidentally

```bash
php artisan config:clear
```

Then restart the affected containers.

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
curl -Ik --resolve ttaminctcom.site:443:127.0.0.1 https://ttaminctcom.site/
```

For a specific route:

```bash
curl -Ik --resolve ttaminctcom.site:443:127.0.0.1 https://ttaminctcom.site/api/health
```

### 8. Never add config cache to deploy steps

Do not add:

```bash
php artisan config:cache
```

Use:

```bash
php artisan config:clear
```

---

## 4. DNS chronic issue

`ttaminctcom.site` on Namecheap BasicDNS has recurring resolution failures, affecting both server-side checks and Chrome/client resolution.

Production may still work when tested with:

```bash
curl --resolve ttaminctcom.site:443:127.0.0.1
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

## 5. Browser-extension CSP noise (false alarm)

Chrome consoles sometimes show a flood of `Content Security Policy` violations for blocked scripts/styles from unfamiliar hosts (e.g. `tamifortami.online`). Before changing anything, verify the noise is **not** server-side.

### Verification commands

```bash
# 1) Confirm exactly one CSP header is sent, and it is consistent across routes
curl -skI --resolve tamiicom.site:443:127.0.0.1 https://tamiicom.site/            | grep -ci 'content-security-policy:'
curl -skI --resolve tamiicom.site:443:127.0.0.1 https://tamiicom.site/build/assets/app-*.css | grep -i  'content-security-policy'
curl -skI --resolve tamiicom.site:443:127.0.0.1 https://tamiicom.site/admin/dashboard       | grep -i  'content-security-policy'

# 2) Confirm the build and source contain no stale or foreign domains
grep -rIE 'tamifortami|ttaminctcom' public/build app resources config routes 2>/dev/null
```

### Interpretation

If the count above is `1`, all routes return the same CSP, and the grep is empty, the violations are client-side. Most common cause: a Chrome extension injecting `<link>`/`<script>` from a host the page CSP correctly rejects. CSP is doing its job.

### Confirmation steps for the affected user

```text
1. Reproduce in Incognito with all extensions disabled.
2. If the messages disappear, re-enable extensions one by one to find the culprit.
3. If they persist, inspect the hosts file:
     Windows: C:\Windows\System32\drivers\etc\hosts
     macOS/Linux: /etc/hosts
   and remove any entries for the foreign domain.
```

### Do NOT

```text
- Add the foreign domain to script-src/style-src.
- Widen CSP to '*' or 'unsafe-*' beyond what is already there.
- Touch Nginx security-headers snippet.
- Rebuild assets.
```

