#!/bin/bash
# Production Incident Diagnostics - Read-Only
set -e

export SSHPASS='l1JbZqfUag3CI58A15'

HOST='root@209.74.64.215'
APPDIR='/opt/insurance2026'

echo "════════════════════════════════════════════════════════════════"
echo "PRODUCTION DIAGNOSTICS (READ-ONLY)"
echo "════════════════════════════════════════════════════════════════"
echo ""

echo "[1] Container status:"
sshpass -e ssh -o ConnectTimeout=15 "$HOST" "cd $APPDIR && docker compose ps" 2>/dev/null | grep -E 'app|nginx|db' || echo "TIMEOUT"
echo ""

echo "[2] Recent Laravel error log:"
sshpass -e ssh -o ConnectTimeout=15 "$HOST" "tail -30 $APPDIR/storage/logs/laravel.log 2>/dev/null | grep -i 'admin\|ip\|403' || echo 'No matching logs'" 2>/dev/null
echo ""

echo "[3] Routes file - Admin Auth section (production):"
sshpass -e ssh -o ConnectTimeout=15 "$HOST" "sed -n '220,230p' $APPDIR/routes/api.php" 2>/dev/null || echo "TIMEOUT"
echo ""

echo "[4] Admin IP check:"
sshpass -e ssh -o ConnectTimeout=15 "$HOST" "grep 'admin.ip' $APPDIR/routes/api.php | wc -l" 2>/dev/null || echo "TIMEOUT"
echo ""

echo "════════════════════════════════════════════════════════════════"
