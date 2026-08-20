#!/bin/bash
# Production Deployment Script - Direct Fix
# Deploys branch hardening/clean-rebuild to production

set -e

export SSHPASS='l1JbZqfUag3CI58A15'
HOST='root@209.74.64.215'
APPDIR='/opt/insurance2026'

echo "════════════════════════════════════════════════════════════════"
echo " PRODUCTION DEPLOYMENT: Remove IP Restrictions"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Try to deploy via SSH
if command -v sshpass &> /dev/null; then
    echo "[DEPLOY] Using sshpass to execute on server..."

    sshpass -e ssh -o ConnectTimeout=15 -o BatchMode=no "$HOST" << 'REMOTECOMMANDS'
set -e
cd /opt/insurance2026

echo "[1/6] Pulling latest code from origin/hardening/clean-rebuild..."
git fetch origin
git checkout hardening/clean-rebuild
git pull origin hardening/clean-rebuild

echo "[2/6] Clearing Laravel caches..."
docker compose exec -T app php artisan config:clear 2>/dev/null || true
docker compose exec -T app php artisan view:clear 2>/dev/null || true
docker compose exec -T app php artisan cache:clear 2>/dev/null || true

echo "[3/6] Rebuilding app container..."
docker compose build app

echo "[4/6] Restarting services..."
docker compose up -d app horizon scheduler reverb

echo "[5/6] Waiting 15 seconds for services to become healthy..."
sleep 15

echo "[6/6] Verifying deployment..."
ADMIN_IP_COUNT=$(grep -c 'admin.ip' /opt/insurance2026/routes/api.php || echo "0")
if [ "$ADMIN_IP_COUNT" = "0" ]; then
    echo "✅ SUCCESS: admin.ip removed from routes"
else
    echo "❌ FAILED: admin.ip still present ($ADMIN_IP_COUNT times)"
    exit 1
fi

HORIZON_CONFIG=$(grep -c "admin.ip" /opt/insurance2026/config/horizon.php || echo "0")
if [ "$HORIZON_CONFIG" = "0" ]; then
    echo "✅ SUCCESS: admin.ip removed from Horizon config"
else
    echo "❌ FAILED: admin.ip still in Horizon ($HORIZON_CONFIG times)"
    exit 1
fi

echo ""
echo "Deployment completed successfully!"
REMOTECOMMANDS

    DEPLOY_STATUS=$?
else
    echo "⚠️  sshpass not available - cannot deploy via SSH"
    echo ""
    echo "Please run these commands manually on the production server:"
    echo ""
    echo "  ssh root@209.74.64.215"
    echo "  cd /opt/insurance2026"
    echo "  git fetch origin && git checkout hardening/clean-rebuild && git pull"
    echo "  docker compose exec -T app php artisan config:clear"
    echo "  docker compose build app"
    echo "  docker compose up -d app horizon scheduler reverb"
    echo "  sleep 15"
    echo "  grep -c 'admin.ip' routes/api.php  # Should show 0"
    echo ""
    exit 1
fi

echo ""
if [ $DEPLOY_STATUS -eq 0 ]; then
    echo "════════════════════════════════════════════════════════════════"
    echo "✅ DEPLOYMENT SUCCESSFUL"
    echo "════════════════════════════════════════════════════════════════"
    sleep 3
    echo ""
    echo "Testing endpoint..."
    RESPONSE=$(curl -s --resolve lexusforbon.com:443:209.74.64.215 -X POST https://lexusforbon.com/api/admin/login \
        -H "Content-Type: application/json" \
        -d '{"email":"test","password":"test"}' \
        -w "\n%{http_code}")

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)

    if [ "$HTTP_CODE" = "403" ]; then
        echo "❌ Still getting 403 - investigate Nginx layer"
    else
        echo "✅ HTTP $HTTP_CODE - IP restriction removed!"
    fi
else
    echo "════════════════════════════════════════════════════════════════"
    echo "❌ DEPLOYMENT FAILED"
    echo "════════════════════════════════════════════════════════════════"
    exit 1
fi
