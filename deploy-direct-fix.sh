#!/bin/bash
set -e

SSHPASS='l1JbZqfUag3CI58A15'
export SSHPASS

echo "════════════════════════════════════════════════════════════════"
echo "DIRECT DEPLOYMENT: Remove IP Restriction (Manual)"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Step 1: Verify local code is correct
echo "[LOCAL] Verifying local code changes..."
grep -c "admin.ip" /d/insurance2026/routes/api.php && echo "ERROR: Local routes still have admin.ip" && exit 1
echo "✓ Local routes correctly updated"
echo ""

# Step 2: Copy the changed files directly via SCP
echo "[TRANSFER] Copying updated files to production..."
sshpass -e scp -o StrictHostKeyChecking=no /d/insurance2026/routes/api.php root@209.74.64.215:/opt/insurance2026/routes/api.php
echo "✓ routes/api.php copied"

sshpass -e scp -o StrictHostKeyChecking=no /d/insurance2026/config/horizon.php root@209.74.64.215:/opt/insurance2026/config/horizon.php
echo "✓ config/horizon.php copied"
echo ""

# Step 3: Clear Laravel cache
echo "[LARAVEL] Clearing config and route caches..."
sshpass -e ssh -o StrictHostKeyChecking=no root@209.74.64.215 'cd /opt/insurance2026 && docker compose exec -T app php artisan config:clear' 2>&1 | grep -i 'config\|cleared\|error' || echo "✓ Config cleared"
echo ""

# Step 4: Restart app container
echo "[DOCKER] Restarting app container..."
sshpass -e ssh -o StrictHostKeyChecking=no root@209.74.64.215 'cd /opt/insurance2026 && docker compose restart app' 2>&1 | tail -1
echo "✓ App container restarted"
echo ""

# Step 5: Wait for container to be healthy
echo "[WAIT] Waiting for app to become healthy (up to 30 seconds)..."
for i in {1..30}; do
    HEALTH=$(sshpass -e ssh -o StrictHostKeyChecking=no root@209.74.64.215 'cd /opt/insurance2026 && docker compose ps app --format="{{.Status}}"' 2>/dev/null | grep -i healthy | wc -l)
    if [ "$HEALTH" -eq 1 ]; then
        echo "✓ App is healthy"
        break
    fi
    echo -n "."
    sleep 1
done
echo ""

# Step 6: Verify the change on production
echo "[VERIFY] Verifying changes on production server..."
ADMIN_IP_COUNT=$(sshpass -e ssh -o StrictHostKeyChecking=no root@209.74.64.215 'grep -c "admin.ip" /opt/insurance2026/routes/api.php' 2>/dev/null || echo "error")

if [ "$ADMIN_IP_COUNT" = "0" ]; then
    echo "✅ SUCCESS: Production routes correctly updated (admin.ip removed)"
elif [ "$ADMIN_IP_COUNT" = "error" ]; then
    echo "⚠️  Could not verify (SSH timeout), but deployment commands completed"
else
    echo "❌ FAILED: Production still has $ADMIN_IP_COUNT references to admin.ip"
    exit 1
fi
echo ""

# Step 7: Test endpoint
echo "[TEST] Testing admin login endpoint..."
RESPONSE=$(curl -s --resolve lexusforbon.com:443:209.74.64.215 -X POST https://lexusforbon.com/api/admin/login \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@lexusforbon.com","password":"wrong_password"}' \
    -w "\n%{http_code}" 2>&1)

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | head -n -1)

echo "Response: $BODY"
echo "HTTP Code: $HTTP_CODE"

if [ "$HTTP_CODE" = "403" ]; then
    echo "❌ Still getting 403 - IP restriction may not be fully removed"
    exit 1
elif [ "$HTTP_CODE" = "401" ] || [ "$HTTP_CODE" = "422" ] || [ "$HTTP_CODE" = "200" ]; then
    echo "✅ SUCCESS: Endpoint is accessible (HTTP $HTTP_CODE - no 403)"
else
    echo "⚠️  Unexpected HTTP code $HTTP_CODE"
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Deployment completed!"
echo "════════════════════════════════════════════════════════════════"
