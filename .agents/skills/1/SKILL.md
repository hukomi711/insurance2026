---
name: insurance2026-workspace
description: Workspace guidance for the insurance2026 Laravel/Vue application. Use for debugging, security hardening, feature work, testing, database changes, deployment, Docker, Nginx, Redis, Horizon, Reverb, and production validation in this repository.
---

# Insurance2026 Workspace Guidance

Use this skill whenever working inside the `insurance2026` repository.

## Project context

- Local project path:

  `D:/insurance2026`

- Production project path:

  `/opt/insurance2026`

- Main stack:
  - Laravel
  - PHP
  - Vue
  - Vite
  - MySQL in production
  - SQLite may be used locally
  - Redis
  - Horizon
  - Reverb
  - Nginx
  - Docker Compose in production

- Local Windows development may use:
  - SQLite
  - `QUEUE_CONNECTION=sync`
  - local Reverb
  - Laravel development server
  - Vite development server

- Do not assume local SQLite/sync behavior proves production MySQL/Redis/Horizon behavior.

## Configuration policy

Production uses:

```bash
php artisan config:cache
```

after runtime environment values and Docker secrets have been injected.

Runtime PHP outside `config/` must read environment-dependent values using:

```php
config(...)
```

and must not call:

```php
env(...)
```

outside configuration files.

After changing production environment variables or secrets, recreate/restart the affected containers in a way that rebuilds their Laravel configuration cache.

Do not assume changing `.env` alone updates an already cached runtime configuration.

## Secrets policy

Never expose, print, log, commit, or embed:

- passwords
- database credentials
- SMTP credentials
- API secrets
- private keys
- OTP values
- production tokens
- Docker secrets

Do not include `.env`, `.env.production`, or production secrets in Docker image layers.

Runtime secrets must be supplied through the project's approved runtime environment or Docker secrets mechanism.

Treat `VITE_*` values as public browser-visible values.

If a secret is discovered in Git history, consider it compromised until it has been rotated.

Removing a secret from the current source does not revoke the old credential or erase it from Git history.

## Core working rules

- Prefer minimal, targeted changes.
- Preserve behavior outside the requested scope.
- Do not perform unrelated cleanup.
- Do not reformat large files unnecessarily.
- Do not perform broad refactors to fix a small issue.
- Inspect the current implementation before modifying it.
- Verify behavior after changes.
- Prefer targeted tests before broader test suites.
- Keep unrelated pre-existing changes intact.

## Dirty working tree safety

The repository may contain many pre-existing modified, deleted, or untracked files.

Treat every pre-existing change as belonging to the user unless direct evidence proves it was created during the current task.

Never use the following merely to simplify the workspace:

```bash
git reset --hard
git clean -fd
git checkout .
git restore .
git add .
git add -A
```

Do not discard, overwrite, stage, commit, or attribute pre-existing changes to yourself.

Do not automatically clean untracked diagnostic files.

When a file contains both relevant and unrelated changes, inspect the diff carefully.

When staging is explicitly requested, prefer:

```bash
git add -p <file>
```

for mixed files.

Do not perform:

```text
git add
commit
push
deploy
```

unless explicitly requested.

## Accuracy and verification rules

Do not confuse reading a file with editing it.

Do not claim a file was changed unless a real write/edit operation occurred during the current task.

Do not treat:

```bash
git status
git diff
```

as proof that you authored the displayed changes.

`git diff` answers:

> What currently differs from the Git reference?

It does not answer:

> What was changed during this session?

If a modification existed before the current task, describe it as pre-existing.

If no write operation occurred, state:

> I have not made any file changes yet.

After making a real edit:

1. reread the changed area;
2. inspect the targeted diff;
3. confirm only intended behavior changed;
4. run relevant validation.

Never claim:

```text
fixed
implemented
committed
pushed
deployed
production-ready
```

without direct evidence supporting the exact claim.

## Source versus runtime

Always distinguish between:

```text
source state
Git state
local runtime
Docker image
container runtime
database schema
production runtime
production HTTP behavior
```

Do not rely only on documentation when runtime validation is possible.

For production investigations, inspect the actual server/runtime directly where appropriate.

Useful examples:

```bash
git status --short
git diff -- <file>

docker compose ps
docker compose exec -T app php artisan about
docker compose exec -T app php artisan route:list
docker compose exec -T app php artisan migrate:status
```

Use `-T` for Docker Compose commands in non-interactive shells when TTY allocation can fail.

## PHP changes

For modified PHP source:

1. run PHP syntax validation;
2. run targeted tests;
3. run the full PHP test suite when practical;
4. run PHPStan when relevant;
5. run targeted Pint only when appropriate;
6. run:

```bash
git diff --check
```

Do not run broad Pint formatting across unrelated source files.

Do not manually format generated files under:

```text
bootstrap/cache/
```

Generated caches are runtime artifacts, not source-formatting targets.

## JavaScript and Vue changes

For JS/Vue changes:

1. run targeted ESLint;
2. run relevant Vitest tests;
3. run the full JavaScript test suite when practical;
4. run:

```bash
npm run build
```

5. run:

```bash
git diff --check
```

Do not run broad:

```bash
eslint --fix
```

unless explicitly required and scoped.

Treat Tailwind canonical-class suggestions as cleanup warnings rather than runtime failures unless demonstrated otherwise.

Do not change unrelated Tailwind classes or styling while fixing functional code.

## Database and migrations

Never assume local migrations have been applied in production.

Before deployment inspect:

```bash
docker compose exec -T app php artisan migrate:status
```

Before applying production migrations:

- inspect every pending migration;
- understand `up()` and `down()`;
- identify affected tables;
- check table size where relevant;
- consider locking and downtime;
- ensure a usable database backup exists;
- test the migration where practical.

Do not deploy application code that requires schema not yet present in production.

Do not run:

```bash
php artisan migrate --force
```

blindly.

Verify the resulting schema after migrations.

## Docker rules

Do not assume Docker is installed or available locally.

Local non-Docker validation does not prove Docker production readiness.

For Docker changes inspect:

- published ports
- healthchecks
- restart policies
- container users
- CPU limits
- memory limits
- PID limits
- log rotation
- secrets
- volumes
- Redis exposure
- database exposure
- Reverb exposure
- Nginx configuration

Do not invent resource limits without measuring actual production usage first.

Useful production measurements may include:

```bash
free -h
nproc
docker stats --no-stream
ps -eo pid,comm,rss,%mem,%cpu --sort=-rss
```

Build a resource budget before setting restrictive limits.

## Docker image security

Production images must not contain runtime secrets.

Verify that:

```text
.env
.env.production
database passwords
SMTP passwords
API credentials
private keys
```

are absent from the final image and build layers.

Do not rely solely on reading `.dockerignore` or `Dockerfile`; validate the resulting image when Docker is available.

Public frontend `VITE_*` values may be supplied during build, but sensitive runtime values must not be exposed to Vite.

## Nginx and traffic hardening

For bot, abuse, slow-client, or load-hardening work, review Nginx before relying only on Laravel throttling.

Evaluate:

- request rate limits
- concurrent connection limits
- request body size
- header timeout
- body timeout
- send timeout
- WebSocket handling
- request buffering
- client IP correctness
- access/error log volume

When introducing rate or connection limiting, prefer measurement or dry-run behavior before aggressive enforcement.

Do not apply arbitrary limits without checking legitimate traffic patterns.

## Security and resource-exhaustion reviews

Do not consider a route protected merely because it has a throttle middleware.

Evaluate:

```text
request count
cost per request
payload size
concurrency
database queries
external HTTP calls
queue jobs
Redis key cardinality
WebSocket connections
filesystem writes
log growth
CPU cost
memory cost
```

Prefer:

```text
cheap rejection
→ validation
→ authorization
→ rate limiting
→ expensive work
```

where architecture permits.

Avoid performing expensive DB, Redis, external HTTP, or queue work before cheap rejection controls.

## Request payload limits

Expensive endpoints must have bounded payload complexity.

For arrays and nested data, validate:

- maximum item count
- maximum nested structure
- maximum string size
- maximum upload size
- allowed keys and types

Do not allow a client to amplify one request into an unbounded amount of CPU, DB, queue, or external-service work.

## Rate limiting

Do not rely solely on client-controlled session identifiers.

For sensitive functionality consider independent limits based on the appropriate combination of:

```text
IP
authenticated user
customer ID
session
phone/account hash
endpoint-specific identity
```

A client rotating `session_id` must not bypass an IP-wide protection.

Be careful with automatic retries after `429`.

Non-idempotent operations must not be blindly retried.

## Redis

Do not assume cache, sessions, queues, and rate limiting are isolated.

Identify the actual Redis connection/store used by:

- cache
- sessions
- rate limiting
- queues
- Horizon
- application locks

before changing Redis behavior.

Be cautious with eviction policies such as:

```text
allkeys-lru
```

when security state, sessions, queues, or rate-limit state share the same Redis instance.

Resource-exhaustion hardening should consider Redis isolation where justified.

## Queue and Horizon

Maintain this invariant:

```text
worker timeout < retry_after
```

with a deliberate safety margin.

Do not increase timeout or retry values without evidence that jobs require it.

Review jobs for:

- `$tries`
- timeout
- backoff
- uniqueness
- overlap protection
- exception handling
- atomicity
- external API calls
- duplicate dispatch
- queue amplification

A job that catches an exception without rethrowing it may disable Laravel retry behavior.

For Horizon configuration changes in production, restart workers appropriately, such as:

```bash
docker compose exec -T app php artisan horizon:terminate
```

when that is the correct runtime action.

## Jobs generated from public traffic

Public GET/POST traffic must not create unlimited queue jobs.

Where appropriate use:

- atomic deduplication
- `Cache::add`
- uniqueness
- minimum event intervals
- coalescing
- retention policies

Do not let repeated bot traffic amplify into unlimited DB writes, geolocation calls, emails, broadcasts, or jobs.

## Reverb and WebSocket

Do not use IP address alone as customer identity.

Do not expose sensitive customer identifiers in public broadcast payloads.

Use authenticated/private channels where appropriate.

Keep customers behind the same NAT/IP isolated.

For customer-targeted events prefer stable server-controlled identity such as:

```text
customer_id
+
server-issued/session-bound channel identity
```

rather than IP-only targeting.

Review:

- origin restrictions
- connection limits
- reconnect behavior
- authorization retry behavior
- idle connections
- WebSocket versus polling fallback

Permanent HTTP errors such as:

```text
401
403
422
```

should not normally enter unlimited retry loops.

`429` must respect backoff/rate-limit policy.

Retry only appropriate transient network/5xx failures.

## Polling and heartbeat behavior

Polling should not continue unnecessarily when WebSocket is healthy unless deliberately required.

Use in-flight guards where repeated requests can overlap.

Background/hidden pages should not continue expensive heartbeat writes without a clear requirement.

Unload/pagehide handlers must avoid duplicate beacons.

## Geographic restrictions

Keep an explicit route policy for which resources are:

```text
Saudi-only
global/public
admin-only
```

Do not assume middleware attachment alone proves the policy because middleware may contain exclusions.

Public blog/content access and Saudi-only insurance functionality must remain deliberately separated.

Do not identify a customer using IP alone.

Never fall back to "latest customer with this IP" for sensitive data.

## Customer identity and shared IPs

Multiple users can share one public IP through:

- home NAT
- corporate networks
- mobile providers
- carrier-grade NAT

Therefore:

```text
same IP != same customer
```

IP can be a risk signal but must not be used alone for destructive merging or sensitive customer targeting.

## Data integrity

Do not deduplicate records based on weak identifiers if collisions are possible.

Examples:

```text
same card last4 != same card
same IP != same customer
```

Prefer stable unique fingerprints, hashes, IDs, or database constraints.

For prefix matching such as BIN resolution, use deterministic longest-prefix behavior when overlapping prefixes exist.

## Concurrency

Review operations where a value is:

```text
checked/generated
```

in one transaction and:

```text
written
```

in another.

Application-level existence checks are not sufficient protection from races.

Use database-level uniqueness and atomic operations where required.

## Mail

Production mail configuration must not silently fall back to logging sensitive message content.

SMTP/TLS configuration must match the project's current configuration contract.

Do not expose SMTP credentials during diagnostics.

## Health endpoints

Keep public liveness checks cheap.

Do not make a public health endpoint execute expensive checks on every request if unnecessary.

Detailed readiness checks involving:

- database
- Redis
- Horizon
- external services

should be protected, cached, or separated as appropriate.

## Logging and retention

Prevent unbounded growth of:

- Docker logs
- Laravel logs
- pricing logs
- audit logs
- email logs
- funnel events
- failed jobs
- livechat data
- generated exports
- temporary files

Docker production logging should use an explicit rotation strategy.

Do not allow bot traffic to fill the host filesystem through logs.

## Sensitive exports

Do not pass database passwords directly on command lines if they may appear in process listings or shell history.

Sensitive production exports should have:

- restricted permissions
- defined retention
- encryption where appropriate
- safe cleanup

Do not leave database exports or snapshots in public or build contexts.

## Dependencies

For security advisories, prefer targeted updates.

Do not blindly run:

```bash
composer update
npm audit fix --force
```

against the entire project.

For each affected dependency determine:

```text
installed version
fixed version
direct/transitive dependency
breaking-change risk
minimal safe update
```

Then rerun the relevant:

```bash
composer audit
npm audit --omit=dev
php artisan test
npm test
npm run build
```

## Deployment safety

Do not deploy from an unknown or dirty working tree.

Do not use production working directories containing manual changes as disposable build workspaces.

Avoid destructive deployment behavior such as:

```bash
git reset --hard
```

against a dirty production tree.

Prefer a clean release/image-based workflow where practical.

Before deployment establish:

```text
exact release/commit
clean release source
required migrations
runtime secrets
config cache behavior
database backup
rollback procedure
service restart requirements
```

Do not claim deployment success from build output alone.

## Production deployment validation

After deployment validate the relevant subset of:

```text
docker compose ps
container health
Laravel boot
config cache
migration status
database schema
Redis
Horizon
Reverb
WebSocket 101
health endpoints
critical API endpoints
quote flow
admin authentication
production logs
```

Check source state and runtime state separately.

## Asset-only changes

For frontend asset-only changes:

- run relevant JS/Vue validation;
- rebuild Vite assets;
- avoid unnecessary PHP/database/container operations.

Do not restart unrelated containers merely because frontend assets changed.

## Production PHP/config changes

For PHP/config changes:

- rebuild/recreate/restart only the affected services as required;
- remember production uses `config:cache`;
- verify runtime configuration after restart;
- verify health and logs.

## Documentation and scripts

Treat deployment scripts as production code.

Review commands such as:

```bash
docker compose down -v
git reset --hard
rm -rf
```

carefully because they may destroy production state.

Old IP addresses, domains, credentials, and deployment instructions must not be assumed current.

Diagnostic scripts handling passwords or OTP data should not accidentally enter Git or Docker build contexts.

## Generated and local artifacts

Directories such as:

```text
output/
storage/
local backups
diagnostic outputs
```

must not automatically be treated as source.

Determine whether each artifact should be:

- retained
- ignored by Git
- ignored by Docker
- encrypted
- deleted according to retention policy

Never delete user artifacts merely because they are untracked.

## Completion standard

Before declaring a task complete, explicitly distinguish:

```text
Changed
Verified
Pre-existing
Not verified
Not committed
Not pushed
Not deployed
```

A successful test does not prove production deployment.

A successful local SQLite/sync run does not prove MySQL/Redis/Horizon production readiness.

A successful build does not prove database schema compatibility.

A clean `git diff --check` does not prove you authored all current diffs.

Always state the strongest conclusion directly supported by the evidence.

## Typical tasks

- Laravel debugging
- Vue/Vite debugging
- Security hardening
- Bot/resource-exhaustion protection
- Nginx hardening
- Docker configuration
- Redis/Horizon troubleshooting
- Reverb/WebSocket troubleshooting
- Database migration review
- Feature implementation
- Regression testing
- Dependency security review
- Deployment preparation
- Production validation
- Review of existing local or production changes
