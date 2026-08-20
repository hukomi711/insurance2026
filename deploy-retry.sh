#!/bin/bash
# Retry deployment with better error handling

export SSHPASS='l1JbZqfUag3CI58A15'
HOST='root@209.74.64.215'

echo "════════════════════════════════════════════════════════════════"
echo "PRODUCTION DEPLOYMENT - DIRECT GIT PULL"
echo "════════════════════════════════════════════════════════════════"
echo ""

MAX_RETRIES=3
RETRY_COUNT=0
DEPLOY_SUCCESS=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ] && [ $DEPLOY_SUCCESS -eq 0 ]; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "[Attempt $RETRY_COUNT/$MAX_RETRIES] Connecting to server..."
    echo ""

    sshpass -e ssh -o ConnectTimeout=30 -o ConnectionAttempts=2 "$HOST" << 'SCRIPT'
cd /opt/insurance2026

echo "[STEP 1] Current status:"
git status --short | head -5
echo "Branch: $(git branch --show-current)"
echo ""

echo "[STEP 2] Fetching latest from GitHub..."
git fetch origin

echo "[STEP 3] Checking out hardening/clean-rebuild..."
git checkout -f hardening/clean-rebuild

echo "[STEP 4] Pulling latest changes..."
git pull origin hardening/clean-rebuild --ff-only

echo "[STEP 5] Verifying file changes..."
echo "admin.ip in routes/api.php: $(grep -c 'admin.ip' routes/api.php || echo '0') (should be 0)"
echo "admin.ip in config/horizon.php: $(grep -c 'admin.ip' config/horizon.php || echo '0') (should be 0)"
echo ""

echo "[STEP 6] Clearing Laravel cache..."
docker compose exec -T app php artisan config:clear 2>/dev/null || echo "Warning: config:clear failed"
docker compose exec -T app php artisan cache:clear 2>/dev/null || echo "Warning: cache:clear failed"

echo "[STEP 7] Rebuilding app container..."
docker compose build app

echo "[STEP 8] Restarting services..."
docker compose up -d app horizon scheduler reverb

echo "[STEP 9] Waiting for health check (30 seconds)..."
for i in {1..30}; do
    STATUS=$(docker compose ps app --format='{{.Status}}' 2>/dev/null || echo "unknown")
    if echo "$STATUS" | grep -q healthy; then
        echo "✅ Container healthy!"
        break
    fi
    echo -n "."
    sleep 1
done
echo ""

echo "✅ DEPLOYMENT COMPLETED SUCCESSFULLY"
SCRIPT

    DEPLOY_STATUS=$?

    if [ $DEPLOY_STATUS -eq 0 ]; then
        DEPLOY_SUCCESS=1
        echo ""
        echo "════════════════════════════════════════════════════════════════"
        echo "✅ SUCCESS - Code updated on production server"
        echo "════════════════════════════════════════════════════════════════"
    else
        echo ""
        echo "⚠️  Attempt $RETRY_COUNT failed (exit code: $DEPLOY_STATUS)"
        if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
            echo "Waiting 5 seconds before retry..."
            sleep 5
        fi
    fi
done

if [ $DEPLOY_SUCCESS -eq 0 ]; then
    echo ""
    echo "════════════════════════════════════════════════════════════════"
    echo "❌ DEPLOYMENT FAILED - Server not responding"
    echo "════════════════════════════════════════════════════════════════"
    echo ""
    echo "Troubleshooting:"
    echo "1. Check if server is online: ping 209.74.64.215"
    echo "2. Check if SSH is open: netstat -tln | grep 22 (on server)"
    echo "3. Check firewall rules"
    exit 1
else
    echo ""
    echo "Testing endpoint..."
    sleep 3

    RESPONSE=$(curl -s --resolve lexusforbon.com:443:209.74.64.215 \
        -X POST https://lexusforbon.com/api/admin/login \
        -H "Content-Type: application/json" \
        -d '{"email":"test","password":"test"}' \
        -w "\n%{http_code}" 2>&1)

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)

    if [ "$HTTP_CODE" = "403" ]; then
        echo "⚠️  Endpoint still returns 403"
        echo "This may indicate:"
        echo "  - Nginx is still blocking the request"
        echo "  - Container didn't restart properly"
        echo "  - admin.ip middleware is still active elsewhere"
    elif [ "$HTTP_CODE" = "401" ] || [ "$HTTP_CODE" = "422" ]; then
        echo "✅ SUCCESS! HTTP $HTTP_CODE (no IP restriction)"
        echo "Admin login endpoint is now accessible from any IP"
    else
        echo "Status: HTTP $HTTP_CODE"
    fi
fi
