#!/bin/bash
# ════════════════════════════════════════════════════════════════
# Diagnostic Script for Admin Login 422 Error
# Run on production server or locally
# ════════════════════════════════════════════════════════════════

echo "════════════════════════════════════════════════════════════════"
echo "Insurance 2026 — Admin Login Diagnostic"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Check if we can reach the server
echo "1. Testing API connectivity..."
HEALTH=$(curl -k -s -w "%{http_code}" -o /tmp/health_check.json https://tamminzonlinez.online/api/health)
if [ "$HEALTH" = "200" ]; then
    echo "   ✓ API is responding (HTTP 200)"
    cat /tmp/health_check.json | head -100
else
    echo "   ❌ API returned: HTTP $HEALTH"
    exit 1
fi

echo ""
echo "2. Testing login endpoint with credentials..."
echo "   Email: dr@tamminzonlinez.online"
echo "   Password: Banihani00@@71 (14 chars - check password min length!)"
echo ""

# Send test login request
curl -k -X POST https://tamminzonlinez.online/api/admin/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "dr@tamminzonlinez.online",
    "password": "Banihani00@@71"
  }' \
  -s -w "\nHTTP Status: %{http_code}\n" | jq . 2>/dev/null || echo "(Could not parse JSON - raw output below)"

curl -k -X POST https://tamminzonlinez.online/api/admin/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "dr@tamminzonlinez.online",
    "password": "Banihani00@@71"
  }' -s

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
echo "   • Admin user dr@tamminzonlinez.online doesn't exist in database"
echo "   • Password field is missing or empty"
echo "   • Email format is invalid"
echo "   • CSRF token missing (shouldn't be for /api/ endpoints)"
echo ""
echo "📋 To Fix:"
echo "   1. SSH into production server"
echo "   2. Run: docker exec ins2026-db mysql -u insurance -p insurance2026"
echo "   3. Run: SELECT * FROM users WHERE email='dr@tamminzonlinez.online';"
echo "   4. If user doesn't exist, create with:"
echo ""
echo "      docker exec -e PASS='YourPassword123' ins2026-app php artisan tinker --execute='"
echo "      use App\Models\User; use Illuminate\Support\Facades\Hash;"
echo "      User::create(["
echo "        \"name\" => \"Dr\","
echo "        \"email\" => \"dr@tamminzonlinez.online\","
echo "        \"password\" => Hash::make(getenv(\"PASS\")),"
echo "        \"role\" => \"admin\","
echo "      ]);"
echo "      '"
echo ""
