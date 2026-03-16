---
name: production-incident-fix
description: "Fix production incidents with strict scope discipline. Use when: server is down, service is broken, debugging production errors, Docker containers failing, WebSocket/Reverb issues, database connection problems, deployment failures, 500 errors, queue failures, or any live production issue. Enforces Diagnose → Fix → Verify → Stop workflow. Prevents scope creep, hardening, and cleanup during incident response."
argument-hint: "Describe the production incident or error you're seeing"
---

# Production Incident Fix

Strict, scoped workflow for fixing production incidents. One problem, one fix, one verification, then stop.

## Terminal Rules

Use Git Bash terminal only.
Do not use PowerShell, pwsh, Invoke-SSHCommand, Write-Host, or Windows cmdlets.
All commands must be valid bash commands.

Allowed commands: `ssh`, `scp`, `rsync`, `grep`, `sed`, `awk`, `curl`, `docker`, `docker compose`, `git`, `rm`, `cp`, `cat`, `echo`, `ls`, `tail`, `head`, `less`, `find`, `wc`, `diff`, `chmod`, `chown`, `mkdir`, `mv`, `tar`, `date`, `systemctl`, `journalctl`.

## Production Safety Rules

Never modify SSH, firewall, cron, swap, backups, SSL, users, keys, or secrets unless the user explicitly requests that exact change.
Never mix incident response with cleanup, hardening, or infrastructure improvements.
Perform verification before suggesting cleanup.
After success, stop.

### Forbidden Targets (unless explicitly requested by name)

- `/etc/ssh/*`, `sshd_config`, SSH keys
- `/root/.ssh/*`, `/home/*/.ssh/*`, `authorized_keys`
- `crontab`, `/etc/cron*`
- `/etc/fstab`
- `sysctl`, swap configuration
- certbot hooks, SSL certificates
- firewall, UFW, iptables
- user/group management
- secrets/keys creation or download
- Rebuilding unrelated containers
- Any "best practices", hardening, DevOps cleanup, or improvements

## Default Workflow

### Step 1 — Define Incident Scope

Before anything else, state clearly:

1. **What is the exact problem?** (e.g., "500 error on /api/quotes", "Reverb WebSocket not connecting")
2. **What service/file/container is affected?**
3. **What is NOT in scope?** (everything else)

Lock the scope. Do not expand it during the incident.

### Step 2 — Collect Read-Only Diagnostics

**No changes allowed in this step.** Only observe.

```bash
# Examples of read-only diagnostics:
docker ps -a                          # Container status
docker logs <container> --tail 100    # Recent logs
docker compose config                 # Verify compose config
cat .env                              # Check environment
curl -I https://domain.com            # HTTP response headers
tail -50 storage/logs/laravel.log     # Application logs
```

Gather enough data to form a hypothesis. Do not guess — read the actual error messages.

### Step 3 — Snapshot Before Any Change

**Mandatory before ANY modification:**

```bash
TS="$(date +%Y%m%d_%H%M%S)"
mkdir -p /root/debug-snapshots/$TS
```

Then copy the specific files you plan to change:

```bash
cp .env /root/debug-snapshots/$TS/.env.bak
cp docker-compose.yml /root/debug-snapshots/$TS/docker-compose.yml.bak
# Copy whatever file you're about to modify
```

Never skip this step.

### Step 4 — Identify Root Cause

Based on diagnostics from Step 2:

1. State the most likely root cause
2. State which single file or config needs to change
3. State why this change (and only this change) will fix the problem

**Decision branch:**

- **Root cause is clear** → proceed to Step 5
- **Root cause is unclear** → collect more diagnostics (return to Step 2), do NOT guess-and-fix
- **Multiple possible causes** → test the most likely one first, do NOT fix all of them at once

### Step 5 — Apply Minimal Fix

**Before making the change, announce:**

1. What file/service will change
2. What the exact change is
3. Why this fixes the problem

Rules:

- Change ONE thing at a time
- No refactoring alongside the fix
- No "while we're here" improvements
- No cleanup of unrelated code
- If the fix requires restarting a container, restart ONLY the affected container

```bash
# Example: fix one environment variable
sed -i 's/OLD_VALUE/NEW_VALUE/' .env

# Restart only the affected container
docker compose up -d --no-deps <container-name>
```

### Step 6 — Verify Expected Behavior

Run a deterministic test that proves the fix works:

```bash
# Examples:
curl -I https://domain.com/api/endpoint    # Check HTTP status
docker logs <container> --tail 20           # Confirm no errors
docker ps                                    # Confirm container is running
```

**Decision branch:**

- **Verification passes** → proceed to Step 7
- **Verification fails** → rollback (see Rollback section), re-diagnose from Step 2
- **Partial fix** → document what's fixed, re-enter Step 4 for remaining issue

### Step 7 — Stop

**Report exactly three things:**

1. **Root cause** — what was wrong
2. **Fix applied** — the exact change made
3. **Proof** — the verification output

Then **STOP**. Do not suggest improvements, hardening, or cleanup.

### Step 8 — Optional Cleanup (only if user asks)

Only after the incident is resolved and the user explicitly requests it:

- Suggest cleanup as a **separate, clearly scoped task**
- Never bundle cleanup with the incident fix
- Each cleanup item must be individually approved

## Rollback Guidance

If a fix fails or makes things worse:

```bash
# Restore from snapshot
TS="<timestamp-from-step-3>"
cp /root/debug-snapshots/$TS/.env.bak .env
cp /root/debug-snapshots/$TS/docker-compose.yml.bak docker-compose.yml

# Restart affected container with restored config
docker compose up -d --no-deps <container-name>
```

Then return to Step 2 with fresh diagnostics.

## Scope Creep Prevention

At every step, ask: **"Is this directly related to the reported incident?"**

- If YES → proceed
- If NO → stop, do not make the change
- If MAYBE → ask the user before proceeding

### Historical Lesson

> During a WebSocket/Reverb fix, scope discipline was violated: SSH keys were created, sshd_config was modified, swap was added, backup cron and health cron were created, and a certbot hook was added. The actual fix was ONE line: `REVERB_ALLOWED_ORIGINS`. Never repeat this. The fix scope must match the incident scope.

## Anti-Patterns (Never Do These)

- Fixing multiple things at once hoping one of them works
- Adding "best practices" during an incident
- Rebuilding containers that aren't related to the incident
- Creating backups/crons/monitors as part of incident response
- Modifying SSH, firewall, or system config during an application incident
- Using PowerShell or Windows cmdlets to manage Linux servers
- Skipping the snapshot step
- Continuing after a successful fix
