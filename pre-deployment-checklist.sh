#!/bin/bash
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Insurance 2026 — Pre-Deployment Checklist for New VPS
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Run this locally before deploying to your new VPS

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Insurance 2026 — Pre-Deployment Checklist"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Color output functions
check_pass() {
  echo -e "${GREEN}✅ PASS:${NC} $1"
}

check_fail() {
  echo -e "${RED}❌ FAIL:${NC} $1"
  return 1
}

check_warn() {
  echo -e "${YELLOW}⚠️  WARN:${NC} $1"
}

check_info() {
  echo -e "ℹ️  INFO: $1"
}

# ─────────────────────────────────────────────────────────────────────────────
# 1. LOCAL ENVIRONMENT
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "1️⃣  LOCAL ENVIRONMENT"
echo "───────────────────────────────────────────────────────────────────────────"

# Check if we're in the right directory
if [[ -f "docker-compose.yml" && -f "Dockerfile" && -d "deploy/new-server" ]]; then
  check_pass "Working directory is insurance2026 project root"
else
  check_fail "Not in insurance2026 root. Current: $(pwd)" || exit 1
fi

# Check Git status
if git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
  check_pass "Git repository found"

  if [[ -z $(git status --porcelain | grep -v "^??") || $(git status --porcelain | grep -v "^??") == "" ]]; then
    check_pass "Working tree is clean (no uncommitted changes)"
  else
    check_warn "Working tree has uncommitted changes:"
    git status --short | sed 's/^/  /'
  fi

  BRANCH=$(git rev-parse --abbrev-ref HEAD)
  check_info "Current branch: $BRANCH"

  COMMIT=$(git rev-parse --short HEAD)
  check_info "Latest commit: $COMMIT"
else
  check_fail "Not a Git repository" || exit 1
fi

# Check required files
echo ""
echo "Required files:"
for file in docker-compose.yml Dockerfile .dockerignore .env.production.example; do
  if [[ -f "$file" ]]; then
    check_pass "$file"
  else
    check_fail "$file missing" || exit 1
  fi
done

# ─────────────────────────────────────────────────────────────────────────────
# 2. DEPLOYMENT CONFIGURATION
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "2️⃣  DEPLOYMENT CONFIGURATION"
echo "───────────────────────────────────────────────────────────────────────────"

# Check deploy/new-server directory
if [[ -d "deploy/new-server" ]]; then
  check_pass "deploy/new-server directory exists"

  for file in deploy.sh .env.production.template RUNBOOK.md README.md; do
    if [[ -f "deploy/new-server/$file" ]]; then
      check_pass "deploy/new-server/$file"
    else
      check_fail "deploy/new-server/$file missing" || exit 1
    fi
  done
else
  check_fail "deploy/new-server directory not found" || exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# 3. ENVIRONMENT VARIABLES
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "3️⃣  ENVIRONMENT VARIABLES REQUIRED FOR DEPLOYMENT"
echo "───────────────────────────────────────────────────────────────────────────"

required_vars=("INS_SERVER_IP" "INS_DOMAIN" "INS_REPO_URL" "INS_BRANCH")
unset_vars=()

for var in "${required_vars[@]}"; do
  if [[ -z "${!var:-}" ]]; then
    unset_vars+=("$var")
  else
    check_pass "$var = ${!var}"
  fi
done

if [[ ${#unset_vars[@]} -gt 0 ]]; then
  echo ""
  check_warn "Not all required environment variables are set."
  echo "   Set them before running deploy/new-server/deploy.sh:"
  echo ""
  for var in "${unset_vars[@]}"; do
    echo "   export $var=\"<value>\""
  done
  echo ""
fi

# Optional variables
optional_vars=(
  "SSH_KEY:$HOME/.ssh/id_ed25519"
  "INS_DEPLOY_USER:root"
  "INS_DEPLOY_DIR:/opt/insurance2026"
  "LE_EMAIL:admin@\$INS_DOMAIN"
)

echo ""
echo "Optional environment variables (defaults shown):"
for item in "${optional_vars[@]}"; do
  var="${item%%:*}"
  default="${item##*:}"
  value="${!var:-$default}"
  check_info "$var = $value"
done

# ─────────────────────────────────────────────────────────────────────────────
# 4. SSH KEY
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "4️⃣  SSH KEY VERIFICATION"
echo "───────────────────────────────────────────────────────────────────────────"

SSH_KEY="${SSH_KEY:-$HOME/.ssh/id_ed25519}"

if [[ -f "$SSH_KEY" ]]; then
  check_pass "SSH key found: $SSH_KEY"

  if [[ -r "$SSH_KEY" ]]; then
    check_pass "SSH key is readable"
  else
    check_fail "SSH key is not readable (permission denied)" || exit 1
  fi

  # Show key fingerprint (non-sensitive)
  if command -v ssh-keygen &> /dev/null; then
    fingerprint=$(ssh-keygen -l -f "$SSH_KEY" 2>/dev/null | awk '{print $2}')
    check_info "SSH key fingerprint: $fingerprint"
  fi
else
  check_fail "SSH key not found: $SSH_KEY" || exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# 5. DOCKER & DEPENDENCIES
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "5️⃣  LOCAL DOCKER & DEPENDENCIES (Optional)"
echo "───────────────────────────────────────────────────────────────────────────"

if command -v docker &> /dev/null; then
  docker_version=$(docker --version)
  check_pass "$docker_version"
else
  check_warn "Docker not installed locally (not required for deployment)"
fi

if command -v docker-compose &> /dev/null; then
  compose_version=$(docker-compose --version)
  check_pass "$compose_version"
elif command -v docker &> /dev/null; then
  check_info "docker-compose not found, but 'docker compose' may be available"
fi

# ─────────────────────────────────────────────────────────────────────────────
# 6. CLEANUP VERIFICATION
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "6️⃣  CLEANUP STATUS"
echo "───────────────────────────────────────────────────────────────────────────"

# Check for old server references
old_ip="203.161.38.43"
old_domain="taminatssak.com"

echo "Searching for old server references..."
old_refs=$(grep -r "$old_ip" --include="*.sh" --include="*.php" --include="*.js" --include="*.yml" . 2>/dev/null | grep -v ".git" | grep -v "node_modules" | grep -v "vendor" | grep -v "deploy/admin/deploy-local-to-vps-docker.sh" || true)

if [[ -z "$old_refs" ]]; then
  check_pass "No remaining references to old IP $old_ip in code"
else
  check_warn "Found references to old IP $old_ip (excluding deprecated deploy script):"
  echo "$old_refs" | sed 's/^/  /'
fi

# Check cleanup documentation
if [[ -f "CLEANUP-COMPLETE.md" && -f "DEPLOYMENT-TO-NEW-VPS.md" ]]; then
  check_pass "Cleanup documentation completed (CLEANUP-COMPLETE.md)"
  check_pass "Deployment guide ready (DEPLOYMENT-TO-NEW-VPS.md)"
else
  check_warn "Cleanup documentation may be incomplete"
fi

# ─────────────────────────────────────────────────────────────────────────────
# FINAL SUMMARY
# ─────────────────────────────────────────────────────────────────────────────
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "NEXT STEPS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1. Set required environment variables (if not already set):"
echo "   export INS_SERVER_IP=\"<new-vps-ip>\""
echo "   export INS_DOMAIN=\"<your-domain.com>\""
echo "   export INS_REPO_URL=\"git@github.com:<user>/insurance2026.git\""
echo "   export INS_BRANCH=\"hardening/clean-rebuild\""
echo ""
echo "2. Verify DNS and SSH access to new VPS:"
echo "   nslookup \$INS_DOMAIN 8.8.8.8"
echo "   ssh -i \$SSH_KEY root@\$INS_SERVER_IP echo 'SSH works'"
echo ""
echo "3. (Optional) Prepare database dump from old server:"
echo "   ssh -i <old-key> root@<old-ip> 'cd /opt/insurance2026 && php artisan db:dump --compress'"
echo "   scp ... deploy/new-server/insurance2026.sql.gz"
echo ""
echo "4. Run the deployment:"
echo "   bash deploy/new-server/deploy.sh"
echo ""
echo "5. Monitor deployment progress and follow prompts"
echo ""
echo "For detailed instructions, see:"
echo "  → DEPLOYMENT-TO-NEW-VPS.md (quick start)"
echo "  → deploy/new-server/RUNBOOK.md (detailed runbook)"
echo "  → deploy/new-server/deploy.sh (deployment script)"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
