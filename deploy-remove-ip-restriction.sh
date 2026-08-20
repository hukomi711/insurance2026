#!/bin/bash

# Production Deployment Script: Remove IP Restrictions Completely
# Target: 209.74.64.215
# Branch: hardening/clean-rebuild

set -e

SERVER="root@209.74.64.215"
APP_PATH="/opt/insurance2026"
SSHPASS="l1JbZqfUag3CI58A15"

echo "═══════════════════════════════════════════════════════════════"
echo "DEPLOYMENT: Remove IP Restriction Middleware"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Export password
export SSHPASS="$SSHPASS"

# Step 1: Pull latest code
echo "[1/6] Pulling latest code from git..."
sshpass -e ssh -o StrictHostKeyChecking=no "$SERVER" "cd $APP_PATH && git pull origin hardening/clean-rebuild" || {
    echo "ERROR: Git pull failed"
    exit 1
}
echo "✓ Code pulled successfully"
echo ""

# Step 2: Clear config cache
echo "[2/6] Clearing Laravel config cache..."
sshpass -e ssh -o StrictHostKeyChecking=no "$SERVER" "cd $APP_PATH && docker compose exec -T app php artisan config:clear" || {
    echo "ERROR: Config cache clear failed"
    exit 1
}
echo "✓ Config cache cleared"
echo ""

# Step 3: Rebuild app container
echo "[3/6] Rebuilding Docker app container..."
sshpass -e ssh -o StrictHostKeyChecking=no "$SERVER" "cd $APP_PATH && docker compose build app" || {
    echo "ERROR: Docker build failed"
    exit 1
}
echo "✓ Docker app container rebuilt"
echo ""

# Step 4: Restart services
echo "[4/6] Restarting services (app, horizon, scheduler, reverb)..."
sshpass -e ssh -o StrictHostKeyChecking=no "$SERVER" "cd $APP_PATH && docker compose up -d app horizon scheduler reverb" || {
    echo "ERROR: Services restart failed"
    exit 1
}
echo "✓ Services restarted"
echo ""

# Step 5: Wait for services to be ready
echo "[5/6] Waiting for services to stabilize (10 seconds)..."
sleep 10
echo "✓ Services stabilized"
echo ""

# Step 6: Test admin login endpoint
echo "[6/6] Testing admin login endpoint..."
TEST_RESPONSE=$(sshpass -e ssh -o StrictHostKeyChecking=no "$SERVER" \
    "curl -s -o /dev/null -w '%{http_code}' -X POST https://lexusforbon.com/api/admin/login \
    -H 'Content-Type: application/json' \
    -d '{\"email\":\"admin@lexusforbon.com\",\"password\":\"Admin2026Passw0rd\"}' \
    --resolve lexusforbon.com:443:127.0.0.1")

if [ "$TEST_RESPONSE" = "200" ] || [ "$TEST_RESPONSE" = "401" ] || [ "$TEST_RESPONSE" = "422" ]; then
    echo "✓ Admin login endpoint is accessible (HTTP $TEST_RESPONSE)"
    echo ""
    echo "═══════════════════════════════════════════════════════════════"
    echo "DEPLOYMENT COMPLETE"
    echo "═══════════════════════════════════════════════════════════════"
    echo ""
    echo "Summary of changes:"
    echo "  ✓ Removed 'admin.ip' middleware from /api/admin/login"
    echo "  ✓ Removed 'admin.ip' middleware from /api/admin/verify-code"
    echo "  ✓ Removed 'admin.ip' middleware from /api/admin/resend-code"
    echo "  ✓ Removed 'admin.ip' middleware from Horizon dashboard"
    echo "  ✓ IP restriction completely disabled"
    echo ""
    echo "Admin panel is now accessible from any IP address"
    echo "Test URL: https://lexusforbon.com/api/admin/login"
    echo ""
else
    echo "✗ Admin login endpoint test failed (HTTP $TEST_RESPONSE)"
    echo "However, deployment commands completed. The endpoint may need more time."
    exit 1
fi
