#!/bin/bash
# ════════════════════════════════════════════════════════════════
# Diagnostic Script for Admin Login 422 Error
# Run on production server or locally
# ════════════════════════════════════════════════════════════════

set -euo pipefail

APP_URL="${APP_URL:-https://ttamikomzz.com}"
: "${ADMIN_EMAIL:?Set ADMIN_EMAIL before running this diagnostic}"
: "${ADMIN_PASSWORD:?Set ADMIN_PASSWORD before running this diagnostic}"

echo "════════════════════════════════════════════════════════════════"
echo "Insurance 2026 — Admin Login Diagnostic"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Check if we can reach the server
echo "1. Testing API connectivity..."
HEALTH=$(curl -k -s -w "%{http_code}" -o /tmp/health_check.json "${APP_URL}/api/health")
if [ "$HEALTH" = "200" ]; then
    echo "   ✓ API is responding (HTTP 200)"
    cat /tmp/health_check.json | head -100
else
    echo "   ❌ API returned: HTTP $HEALTH"
    exit 1
fi

echo ""
echo "2. Testing login endpoint with credentials..."
echo "   Email: ${ADMIN_EMAIL}"
echo "   Password: [provided via ADMIN_PASSWORD]"
echo ""

# Send one test request without placing credentials in this tracked file.
LOGIN_PAYLOAD=$(jq -nc --arg email "$ADMIN_EMAIL" --arg password "$ADMIN_PASSWORD" \
  '{email: $email, password: $password}')

curl -k -X POST "${APP_URL}/api/admin/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  --data "$LOGIN_PAYLOAD" \
  -s -w "\nHTTP Status: %{http_code}\n"

echo ""
echo ""
echo "════════════════════════════════════════════════════════════════"
echo "3. Checking admin users in database (if accessible)..."
echo "════════════════════════════════════════════════════════════════"
echo ""

# Try to connect to database directly (if on server)
if command -v mysql &> /dev/null; then
    echo "MySQL found - attempting database query..."
    # This will fail if you don't have mysql installed locally
    echo "   Run on production server:"
    echo "   docker exec ins2026-db mysql -u insurance -p -D insurance2026 -e 'SELECT id, name, email, role FROM users;'"
else
    echo "MySQL client not available - run this on the production server:"
    echo ""
    echo "docker exec ins2026-db mysql -u insurance -p -D insurance2026 -e 'SELECT id, name, email, role FROM users WHERE role=\"admin\";'"
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "4. Possible Issues:"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "❌ 422 Validation Error typically means:"
echo "   • Admin user ${ADMIN_EMAIL} doesn't exist in database"
echo "   • Password field is missing or empty"
echo "   • Email format is invalid"
echo "   • CSRF token missing (shouldn't be for /api/ endpoints)"
echo ""
echo "📋 To Fix:"
echo "   1. SSH into production server"
echo "   2. Run: docker exec ins2026-db mysql -u insurance -p insurance2026"
echo "   3. Check that the configured ADMIN_EMAIL exists and has role=admin."
echo "   4. If needed, run deploy/admin/fix-admin.sh with ADMIN_EMAIL and ADMIN_PASSWORD set."
echo ""
