#!/bin/bash
# Direct SSH deployment to production

export SSHPASS='l1JbZqfUag3CI58A15'
HOST='root@209.74.64.215'
APPDIR='/opt/insurance2026'

echo "════════════════════════════════════════════════════════════════"
echo "PRODUCTION DEPLOYMENT: Remove IP Restrictions"
echo "════════════════════════════════════════════════════════════════"
echo ""

sshpass -e ssh -o StrictHostKeyChecking=no -o ConnectTimeout=20 "$HOST" bash << 'EOF'
set -e
cd /opt/insurance2026

echo "[1/6] Fetching latest code..."
git fetch origin

echo "[2/6] Checking out hardening/clean-rebuild branch..."
git checkout hardening/clean-rebuild

echo "[3/6] Pulling latest changes..."
git pull origin hardening/clean-rebuild

echo "[4/6] Clearing Laravel configuration cache..."
docker compose exec -T app php artisan config:clear || true
docker compose exec -T app php artisan cache:clear || true

echo "[5/6] Building and restarting containers..."
docker compose build app
docker compose up -d app horizon scheduler reverb

echo "[6/6] Waiting 15 seconds for health check..."
sleep 15

echo ""
echo "✅ Deployment completed!"
echo ""
echo "Verifying changes on production:"
echo "- admin.ip in routes: $(grep -c 'admin.ip' routes/api.php || echo '0')"
echo "- admin.ip in config: $(grep -c 'admin.ip' config/horizon.php || echo '0')"
EOF

DEPLOY_EXIT=$?

echo ""
echo "════════════════════════════════════════════════════════════════"
if [ $DEPLOY_EXIT -eq 0 ]; then
    echo "✅ SSH DEPLOYMENT COMPLETED"
    echo "════════════════════════════════════════════════════════════════"
    echo ""
    echo "Testing endpoint in 5 seconds..."
    sleep 5

    RESPONSE=$(curl -s --resolve lexusforbon.com:443:209.74.64.215 \
        -X POST https://lexusforbon.com/api/admin/login \
        -H "Content-Type: application/json" \
        -d '{"email":"test","password":"test"}' \
        -w "\n%{http_code}" 2>&1)

    HTTP_CODE=$(echo "$RESPONSE" | tail -1)
    BODY=$(echo "$RESPONSE" | head -n -1)

    echo "Response: $BODY"
    echo "HTTP Status: $HTTP_CODE"
    echo ""

    if [ "$HTTP_CODE" = "403" ]; then
        echo "❌ Still 403 - checking Nginx logs on server..."
        sshpass -e ssh "$HOST" "tail -20 /opt/insurance2026/storage/logs/laravel.log 2>/dev/null | grep -i admin" || echo "No matching logs"
    else
        echo "✅ SUCCESS - HTTP $HTTP_CODE (IP restriction removed!)"
    fi
else
    echo "❌ DEPLOYMENT FAILED"
    echo "════════════════════════════════════════════════════════════════"
    exit 1
fi
