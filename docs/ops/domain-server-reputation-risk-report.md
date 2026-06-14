# Domain And Server Reputation Risk Report

## Scope

This checklist is for keeping `tttaaammmin.xyz` and the VPS IP in good standing after launch. It focuses on search reputation, browser trust, email reputation, abuse reports, and infrastructure signals that can lead to warnings, blocks, or delisting.

## Current Controls

- HTTPS is issued by Let's Encrypt for the apex and `www` host.
- Nginx redirects `www` to the apex host.
- Security headers are present, including CSP, HSTS, frame protection, and content type protection.
- `/robots.txt`, `/sitemap.xml`, and `/.well-known/security.txt` are served by Laravel and use the active `APP_URL`.
- Admin, checkout, payment, OTP, API, and storage paths are excluded from search crawling.
- Public API endpoints have throttling; sensitive customer workflows remain behind geo/API middleware.
- Newsletter subscription is throttled and does not send bulk mail by itself.

## Main Ban Risks

### DNS And HTTPS Drift

Risk: DNS pointing to parking, stale certificates, or mixed `www`/apex behavior can trigger browser warnings and crawler distrust.

Controls:

- Keep `A @` and `A www` pointed to the VPS IP.
- Renew Let's Encrypt before expiry.
- Keep `APP_URL`, `DOMAIN`, `SESSION_DOMAIN`, `REVERB_HOST`, and `VITE_REVERB_HOST` aligned with the public domain.
- After every deploy, verify:

```bash
curl -I https://tttaaammmin.xyz/
curl https://tttaaammmin.xyz/api/health
curl https://tttaaammmin.xyz/api/health/realtime
```

### Email Reputation

Risk: SMTP failures, missing SPF/DKIM/DMARC, or bulk sends from a fresh domain can trigger spam classification or provider blocks.

Controls:

- Do not send bulk campaigns from the VPS IP.
- Use a reputable transactional email provider.
- Configure SPF, DKIM, and DMARC at DNS before sending production email.
- Keep bounce/complaint rates low; pause sending if SMTP errors spike.
- Keep `abuse@`, `postmaster@`, `security@`, and `privacy@` routed to monitored inboxes.

### Search And Content Quality

Risk: thin pages, placeholder links, duplicate URLs, and indexable private workflows reduce domain trust.

Controls:

- Keep blog article links real; avoid `href="#"`.
- Use canonical URLs for blog index and articles.
- Keep `sitemap.xml` limited to public pages that should be indexed.
- Do not index admin, API, payment, OTP, or customer flow pages.
- Avoid scraped, auto-generated, or misleading article content.

### Bot And Abuse Traffic

Risk: exposed launch servers receive immediate scans for WordPress, `.env`, Vite dev files, and admin endpoints. Excessive abuse can trigger hosting-provider action if logs are ignored.

Controls:

- Keep Nginx denying dotfiles and sensitive paths.
- Monitor repeated 403/404/405 bursts.
- Rate-limit login, OTP, contact, newsletter, and customer tracking endpoints.
- Keep `public/` free of one-off setup scripts.
- Rotate any credential that appears in logs, chat, screenshots, or support tickets.

### Infrastructure Reputation

Risk: a fresh VPS IP can have previous reputation issues or can be listed if compromised.

Controls:

- Keep SSH key access; disable password login once emergency access is confirmed.
- Keep OS and Docker updated.
- Avoid running mail server software on the VPS unless explicitly configured.
- Monitor disk, CPU, memory, and failed jobs.
- Keep backups separate from the public web root.

## Weekly Checks

```bash
curl -I https://tttaaammmin.xyz/
curl https://tttaaammmin.xyz/api/health
curl https://tttaaammmin.xyz/api/health/queues
curl https://tttaaammmin.xyz/api/health/realtime
curl https://tttaaammmin.xyz/robots.txt
curl https://tttaaammmin.xyz/sitemap.xml
```

On the server:

```bash
cd /opt/insurance2026
docker compose ps
docker compose logs --tail=200 nginx app horizon
docker system df
```

## Immediate Escalation Signals

- Browser SSL warning on the apex domain.
- `robots.txt` or `sitemap.xml` returning non-200.
- SMTP `535`, repeated bounces, or provider suspension notices.
- Sudden spikes of `POST` traffic to unknown paths.
- Admin login brute-force spikes.
- Disk usage over 85 percent.
- Containers repeatedly restarting.

## Next Hardening Step

Once the user confirms emergency password access is no longer needed, disable SSH password login and keep key-only access:

```bash
sed -i 's/^#\\?PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config
sshd -t
systemctl reload sshd
```
