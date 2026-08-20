#!/bin/bash
# Simpler, more direct deployment
# No sshpass - using SSH key auth directly

echo "=== MANUAL DEPLOYMENT (NO SSHPASS) ==="
echo ""

# Get current local state
echo "[1] Local routes/api.php - checking admin.ip references:"
grep "admin.ip" /d/insurance2026/routes/api.php | wc -l
echo "     (Should be 0 - no admin.ip in clean version)"
echo ""

# Use SSH directly (assuming key is configured)
HOST="root@209.74.64.215"

echo "[2] Production routes/api.php - checking admin.ip references:"
ssh -o ConnectTimeout=10 -o BatchMode=yes "$HOST" 'grep -c "admin.ip" /opt/insurance2026/routes/api.php 2>/dev/null || echo "TIMEOUT"'
echo ""

echo "[3] Checking current production git commit:"
ssh -o ConnectTimeout=10 -o BatchMode=yes "$HOST" 'cd /opt/insurance2026 && git rev-parse --short HEAD 2>/dev/null' || echo "TIMEOUT"
echo ""

echo "[4] Testing endpoint on production:"
curl -s --resolve lexusforbon.com:443:209.74.64.215 -X POST https://lexusforbon.com/api/admin/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test","password":"test"}' \
    -m 10 -w "\nStatus: %{http_code}\n" 2>&1 | tail -5
