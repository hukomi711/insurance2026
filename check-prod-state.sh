#!/bin/bash

SSHPASS='l1JbZqfUag3CI58A15'
export SSHPASS
HOST='root@209.74.64.215'
CD='cd /opt/insurance2026'

echo "════════════════════════════════════════════════════════════════"
echo " Production Server State Check"
echo "════════════════════════════════════════════════════════════════"
echo ""

echo "[1] Current git commit:"
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$HOST" "$CD && git log --oneline -1"
echo ""

echo "[2] Does routes/api.php contain 'admin.ip'? (should be 0):"
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$HOST" "$CD && grep -c 'admin.ip' routes/api.php || echo '0'"
echo ""

echo "[3] Running container status:"
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$HOST" "$CD && docker compose ps --format='table {{.Names}}\t{{.Status}}'" | grep -E 'app|horizon'
echo ""

echo "[4] Test endpoint (should return 401/422, not 403):"
sshpass -e ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$HOST" 'curl -s -X POST https://localhost/api/admin/login -H "Content-Type: application/json" -d "{}" -w "\nHTTP %{http_code}\n" -k' 2>&1 | tail -3
echo ""

echo "════════════════════════════════════════════════════════════════"
