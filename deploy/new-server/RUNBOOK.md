# Insurance 2026 — New Production Server Runbook

This document is the **single source of truth** for spinning up the application
on a fresh AlmaLinux 9 server using the Docker Compose stack defined in this
repository. Follow sections in order. Every command is idempotent unless noted.

> **No real IPs or secrets appear in this document.**
> Replace placeholders before running:
>
> - `tamifortami.online` → your apex domain
> - `${INS_SERVER_IP}` → your server's public IPv4
> - `CHANGE_ME` → any value flagged in `.env.production.example`

---

<!-- markdownlint-disable MD022 MD031 MD032 MD060 -->

## A) Server Requirements

| Resource   | Minimum             | Recommended        |
| ---------- | ------------------- | ------------------ |
| OS         | AlmaLinux 9 (x86_64) | AlmaLinux 9 / RHEL 9 |
| vCPU       | 1                   | 2+                 |
| RAM        | 2 GB                | 4 GB               |
| Disk       | 20 GB SSD           | 40 GB SSD          |
| Network    | Public IPv4         | Public IPv4 + IPv6 |
| Open ports | 22, 80, 443         | 22, 80, 443        |

Outbound access required to: `download.docker.com`, `github.com`, GHCR, `letsencrypt.org`, your SMTP provider, and the NexaFlow API host.

---

## B) DNS

Before any deployment, configure A records at your registrar:

```text
A     tamifortami.online          → ${INS_SERVER_IP}    TTL 300
A     www.tamifortami.online      → ${INS_SERVER_IP}    TTL 300
```

Verify propagation from your local machine:

```bash
nslookup tamifortami.online 8.8.8.8
nslookup www.tamifortami.online 8.8.8.8
```

Both must resolve to `${INS_SERVER_IP}` **before** Let's Encrypt issuance (section L), otherwise certbot will fail.

---

## C) SSH Access

1. Generate a deploy key on your local machine (one-time):

   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/ins2026_deploy -C "ins2026-deploy"
   ```

2. Install the public key on the server (root or sudo user):

   ```bash
   ssh-copy-id -i ~/.ssh/ins2026_deploy.pub root@${INS_SERVER_IP}
   ```

3. Verify:

   ```bash
   ssh -i ~/.ssh/ins2026_deploy root@${INS_SERVER_IP} 'echo OK'
   ```
4. Recommended (manual): disable password auth in `/etc/ssh/sshd_config`
   (`PasswordAuthentication no`) and `systemctl reload sshd`. Do this **only**
   after key auth is confirmed working.

---

## D) Install Docker

On the server (one-time):

```bash
dnf -y install dnf-plugins-core
dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
dnf -y install docker-ce docker-ce-cli containerd.io \
               docker-buildx-plugin docker-compose-plugin \
               git curl tar gzip
systemctl enable --now docker
docker --version && docker compose version
```

Open firewall ports:

```bash
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --reload
```

---

## E) Clone Repository

```bash
mkdir -p /opt/insurance2026
cd /opt/insurance2026
git clone <your-git-remote-url> .
git checkout main          # or the deployment branch
git log -1 --oneline
```

---

## F) Copy `.env.production.example` to `.env.production`

```bash
cd /opt/insurance2026
cp .env.production.example .env.production
chmod 600 .env.production
```

---

## G) Fill in Real Values

Edit `.env.production` and replace **every** `CHANGE_ME` and every
`tamifortami.online` occurrence. Required substitutions:

| Key                        | How to generate                                                             |
| -------------------------- | --------------------------------------------------------------------------- |
| `APP_KEY`                  | `docker run --rm -v $PWD:/app -w /app php:8.3-cli php artisan key:generate --show` |
| `DB_PASSWORD`              | `openssl rand -hex 32`                                                     |
| `REVERB_APP_ID`            | `openssl rand -hex 12`                                                     |
| `REVERB_APP_KEY`           | `openssl rand -hex 12`                                                     |
| `REVERB_APP_SECRET`        | `openssl rand -hex 32`                                                     |
| `STATUS_POLL_SECRET`       | `openssl rand -hex 32`                                                     |
| `ADMIN_PASSWORD`           | `openssl rand -base64 24`                                                  |
| Mail / NexaFlow / IP_API_KEY | from each provider's dashboard                                         |

Mirror `DB_PASSWORD` into the Docker secret file:

```bash
mkdir -p docker/secrets
grep '^DB_PASSWORD=' .env.production | sed 's/^DB_PASSWORD=//' > docker/secrets/db_password.txt
openssl rand -hex 32 > docker/secrets/db_root_password.txt
chmod 600 docker/secrets/*.txt
```

---

## H) Build and Start Containers

```bash
cd /opt/insurance2026
docker compose build --pull
docker compose up -d
docker compose ps
```

Expected services healthy: `ins2026-nginx`, `ins2026-app`, `ins2026-horizon`,
`ins2026-reverb`, `ins2026-scheduler`, `ins2026-redis`, `ins2026-db`.

The Vite frontend is built **inside the Docker image** at build time
(`Dockerfile` runs `npm ci && npm run build`). No separate `npm` step is
needed on the host.

---

## I) Migrate Database

Fresh install:

```bash
docker exec ins2026-app php artisan migrate --force
```

Restore from existing dump (optional):

```bash
zcat backup.sql.gz | docker exec -i ins2026-db sh -c \
  'mariadb -uroot -p$(cat /run/secrets/db_root_password) insurance2026'
docker exec ins2026-app php artisan migrate --force
```

---

## J) Cache config / routes / views / events

```bash
docker exec ins2026-app php artisan config:clear
docker exec ins2026-app php artisan route:cache
docker exec ins2026-app php artisan view:cache
docker exec ins2026-app php artisan event:cache
```

> Never use `config:cache` or `optimize` on this codebase — runtime services
> read env at boot. Use `config:clear` only.

---

## K) Frontend Assets

The frontend is baked into the application image during `docker compose build`.
There is **no host-side `npm run build`** step. To rebuild after a code change:

```bash
git pull
docker compose build --pull app
docker compose up -d --force-recreate app horizon reverb scheduler
```

---

## L) SSL / Nginx

The nginx container reads cert files from `docker/certbot/conf/live/${INS_DOMAIN}/`.

Issue certificates with certbot in standalone mode (run **before** nginx is
exposed on :80):

```bash
docker compose stop nginx || true
docker run --rm -p 80:80 \
  -v $PWD/docker/certbot/conf:/etc/letsencrypt \
  -v $PWD/docker/certbot/www:/var/www/certbot \
  certbot/certbot certonly --standalone --non-interactive --agree-tos \
  -m admin@tamifortami.online \
  -d tamifortami.online -d www.tamifortami.online
docker compose up -d nginx
```

Renewal (cron, monthly):

```bash
docker run --rm -v $PWD/docker/certbot/conf:/etc/letsencrypt \
  certbot/certbot renew --quiet
docker exec ins2026-nginx nginx -s reload
```

---

## M) Reverb / Horizon / Scheduler

These three services run from the **same image** as `app` and therefore use
the same `.env`. Verification:

```bash
docker exec ins2026-horizon php artisan horizon:status
docker exec ins2026-reverb sh -c 'php -r "exit(@fsockopen(\"localhost\",8080)?0:1);" && echo OK'
docker logs --tail 50 ins2026-scheduler
```

After **any** `.env.production` change, recreate all three (a plain `restart`
will not reload `env_file`):

```bash
docker compose up -d --force-recreate app horizon reverb scheduler
```

---

## N) Smoke Checks

From the server:

```bash
curl -sk https://tamifortami.online/api/health         | jq .
curl -sk https://tamifortami.online/api/health/queues  | jq .
curl -sk https://tamifortami.online/api/health/realtime | jq .
```

From your local machine:

```bash
for path in / /login /sitemap.xml /robots.txt; do
  printf '  %s  https://tamifortami.online%s\n' \
    "$(curl -sk -o /dev/null -w '%{http_code}' https://tamifortami.online$path)" "$path"
done
```

Expected: `200` for `/`, `/login`, `/sitemap.xml`, `/robots.txt`.

WebSocket smoke from a browser console on `https://tamifortami.online`:

```js
new WebSocket('wss://tamifortami.online/app/' + import.meta.env.VITE_REVERB_APP_KEY)
  .addEventListener('open', () => console.log('WS OK'));
```

---

## O) Rollback

Each deploy keeps the previous image tagged. To roll back:

1. Identify the previous image SHA:

   ```bash
   docker images insurance2026-app --format '{{.ID}} {{.CreatedAt}}'
   ```

2. Re-tag the previous image and recreate:

   ```bash
   docker tag <previous-sha> insurance2026-app:rollback
   sed -i 's/image: insurance2026-app$/image: insurance2026-app:rollback/' docker-compose.yml
   docker compose up -d --force-recreate app horizon reverb scheduler
   ```

3. If a migration was applied that must be reverted:

   ```bash
   docker exec ins2026-app php artisan migrate:rollback --step=1
   ```

4. Restore the working tree afterwards:

   ```bash
   git checkout -- docker-compose.yml
   ```

For a catastrophic rollback, restore from the last DB dump (section I) and
re-run `docker compose up -d --force-recreate`.

---

## P) Troubleshooting

### Redis: `getaddrinfo for redis failed`
Only happens **outside** the Docker network. Inside containers, `redis` is
resolved by Docker's internal DNS. If you see this error inside a container,
check `docker compose ps redis` is `healthy`.

### Reverb: clients can't connect / 403 origin
- Verify `REVERB_ALLOWED_ORIGINS` includes the exact scheme + host
  (e.g. `https://tamifortami.online`, no trailing slash).
- After editing `.env.production`, **force-recreate** reverb (a `restart`
  re-uses stale env): `docker compose up -d --force-recreate reverb`.
- Confirm nginx proxies `/app/*` and `/apps/*` to `reverb:8080` with WebSocket
  upgrade headers.

### Horizon: `MAIL_MAILER=log` after env change
All PHP containers share the **same baked image**. After editing
`.env.production`, recreate `horizon` and `scheduler` too — not just `app`:
```bash
docker compose up -d --force-recreate app horizon reverb scheduler
```

### Permissions: `storage/` or `bootstrap/cache` not writable
```bash
docker exec ins2026-app sh -c 'chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache'
```

### Nginx: `host not found in upstream`
A backing service is down. Check `docker compose ps`; restart the failing
service. Nginx will recover automatically once the upstream is healthy.

### MariaDB: `Access denied for user`
- `DB_PASSWORD` in `.env.production` must equal the contents of
  `docker/secrets/db_password.txt`.
- If you changed the password after the volume was initialised, either:
  - reset it inside the container (`ALTER USER 'insurance2026'@'%' IDENTIFIED BY '...'`), or
  - destroy the data volume and re-import from dump:
    ```bash
    docker compose down
    docker volume rm insurance2026_db-data
    docker compose up -d db
    # then re-run section I
    ```
  Volume removal is **destructive** — back up first.

### Certbot: rate-limit / DNS validation fail
- Confirm DNS resolves to `${INS_SERVER_IP}` from a public resolver.
- Use the staging endpoint while debugging: append
  `--server https://acme-staging-v02.api.letsencrypt.org/directory` to the
  certbot command.

### Logs
```bash
docker logs --tail 200 ins2026-app
docker logs --tail 200 ins2026-horizon
docker logs --tail 200 ins2026-reverb
docker logs --tail 200 ins2026-nginx
docker exec ins2026-app tail -f storage/logs/laravel.log
```

---

## Q) Routine Maintenance (reference)

| Task | Command | Cadence |
|---|---|---|
| DB backup | `bash docker/scripts/backup-db.sh` | daily (cron) |
| TLS renewal | `certbot renew` (section L) | monthly |
| Image rebuild after code push | `docker compose build app && docker compose up -d --force-recreate` | per release |
| Disk usage check | `df -h /opt/insurance2026 && docker system df` | weekly |
| Prune unused images | `docker image prune -f` | monthly |

---

<!-- markdownlint-enable MD022 MD031 MD032 MD060 -->

**End of runbook.**
