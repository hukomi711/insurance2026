# Deployment Strategy Decision: GitHub vs. Archive

## Current Situation

The `deploy-prod.sh` script expects a **GitHub repository URL** as input:

```bash
bash deploy-prod.sh "git@github.com:OWNER/insurance2026.git"
```

It passes this to `deploy/new-server/deploy.sh` which clones via SSH on the VPS:

```bash
git clone --branch '$BRANCH' '$REPO_URL' $DEPLOY_DIR
```

## The Problem: Separate SSH Keys Needed

**Your local SSH key** (`~/.ssh/insurance2026_deploy`) authenticates **your PC to the VPS**.

**The VPS needs its own SSH key** to authenticate **VPS to GitHub**.

These are **two different SSH keys**:

- Local key: PC → VPS (69.57.161.222)
- VPS key: VPS → GitHub API

---

## Two Deployment Paths

### Path A: GitHub Deploy Key (Recommended for Git-based CI/CD)

**Pros:**

- Keeps deployment fully git-based
- Easy future updates: `git pull origin main`
- Standard for CI/CD pipelines
- Good if other systems also deploy from this repo

**Cons:**

- Requires adding a deploy key to GitHub repo settings
- VPS needs SSH access to github.com (outbound:22)
- Extra setup step

**Steps:**

1. Generate key on VPS: `ssh-keygen -t ed25519 -f /root/.ssh/deploy_github -C "ins2026-vps-deploy" -N ""`
2. Print public key: `cat /root/.ssh/deploy_github.pub`
3. Add to GitHub: Repo → Settings → Deploy keys → Add deploy key
4. Test from VPS: `ssh -T git@github.com`
5. Test clone: `git ls-remote git@github.com:OWNER/insurance2026.git`
6. Deploy: `bash deploy-prod.sh "git@github.com:OWNER/insurance2026.git"`

**Firewall requirement:** VPS needs outbound access to github.com:22

---

### Path B: Local Archive Upload (No VPS→GitHub)

**Pros:**

- Zero GitHub access from VPS (more secure)
- Works on air-gapped VPS
- No deploy key management
- Can include uncommitted changes
- Faster (no network git operations)

**Cons:**

- Not git-based on server (harder to debug/trace commits)
- Manual archive on each deploy (workflow change)
- Harder to coordinate multi-environment deployments

**Steps:**

1. Package locally: `tar -czf insurance2026.tar.gz --exclude=.git --exclude=node_modules --exclude=storage .`
2. Upload: `scp insurance2026.tar.gz root@69.57.161.222:/opt/`
3. Extract on VPS: `cd /opt && tar -xzf insurance2026.tar.gz && rm insurance2026.tar.gz`
4. Deploy: `cd /opt/insurance2026 && docker compose build && docker compose up -d`

**No firewall requirement**

---

## Recommendation: **Path A (GitHub Deploy Key)**

**Why:**

1. Your repo is on GitHub → use it
2. Simpler ongoing maintenance
3. VPS can pull updates without manual archives
4. Standard practice for Laravel deployments
5. Aligns with your existing deploy.sh infrastructure

**Action Items:**

1. After VPS is ready, SSH in and generate the deploy key
2. Add public key to GitHub repo Deploy Keys
3. Test git access from VPS
4. Then use `deploy-prod.sh` normally

---

## If You Need Path B Instead

If your VPS cannot reach github.com (air-gapped, restrictive firewall, security policy), tell me and I'll create an alternate `deploy-archive.sh` script that:

- Packages the local worktree
- Uploads via scp
- Extracts and deploys on VPS

But for now, **assume Path A is the plan**.
