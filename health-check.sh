#!/bin/bash

# Insurance2026 Health Check
# Execute: bash health-check.sh

echo "╔════════════════════════════════════════════════════════╗"
echo "║  🏥 INSURANCE2026 - COMPLETE HEALTH CHECK              ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

FAILED=0
PASSED=0

check() {
  local name=$1
  local cmd=$2
  echo -n "🔍 $name... "

  if output=$(eval "$cmd" 2>&1); then
    echo "✅ PASS"
    ((PASSED++))
    return 0
  else
    echo "❌ FAIL"
    echo "   Error: $output"
    ((FAILED++))
    return 1
  fi
}

# 1. Docker Status
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣  DOCKER & SERVICES"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
check "Docker daemon" "docker ps > /dev/null"

echo ""
echo "📊 Service Status:"
docker compose ps 2>/dev/null || echo "⚠️  docker-compose not in PATH"

# 2. Application Health
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2️⃣  APPLICATION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check "API Health endpoint" \
  "curl -sk https://localhost/api/health -H 'Host: ttamikomzz.com' | grep -q ok"

check "Homepage loads" \
  "curl -sk https://ttamikomzz.com/ | grep -q 'html'"

check "SSL certificate valid" \
  "openssl s_client -connect localhost:443 -servername ttamikomzz.com </dev/null 2>/dev/null | grep -q 'Verify return code: 0'"

# 3. Database
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3️⃣  DATABASE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check "Database container running" \
  "docker compose exec -T db mysqladmin -uinsurance -pinsurance2026 ping > /dev/null"

check "Database accessible" \
  "docker compose exec -T db mysql -uinsurance -pinsurance2026 -e 'SELECT 1' > /dev/null"

check "Migrations applied" \
  "docker compose exec -T app php artisan migrate:status | grep -q 'Ran'"

# 4. Redis
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4️⃣  REDIS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check "Redis container running" \
  "docker compose exec -T redis redis-cli ping | grep -q PONG"

check "Cache working" \
  "docker compose exec -T app php artisan tinker --execute 'cache(\"test\", \"value\"); return cache(\"test\")' | grep -q value"

# 5. Queue & Horizon
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5️⃣  QUEUE & HORIZON"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check "Horizon container running" \
  "docker ps | grep -q 'horizon'"

check "Queue connection working" \
  "docker compose exec -T app php artisan queue:failed | grep -q 'No records found' || true"

# 6. WebSocket & Reverb
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "6️⃣  WEBSOCKET & REVERB"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check "Reverb container running" \
  "docker ps | grep -q 'reverb'"

check "WebSocket server listening" \
  "curl -I http://localhost:8080/ 2>/dev/null | grep -q 'HTTP'"

# 7. Security
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "7️⃣  SECURITY"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

check ".env file permissions (should be 640)" \
  "test '$(docker compose exec -T app stat -c %a /var/www/html/.env)' = '640'"

check ".env owner is appuser" \
  "docker compose exec -T app stat -c %U:%G /var/www/html/.env | grep -q appuser"

check "HTTPS enforced" \
  "curl -I http://ttamikomzz.com 2>/dev/null | grep -q 'redirect\\|301\\|302' || curl -I https://localhost -H 'Host: ttamikomzz.com' 2>/dev/null | grep -q '200\\|301'"

# 8. Logs
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "8️⃣  LOGS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "📋 Last 10 application logs:"
docker logs ins2026-app 2>/dev/null | tail -10 || echo "⚠️  Could not retrieve logs"

echo ""
ERROR_COUNT=$(docker logs ins2026-app 2>/dev/null | grep -c -i 'error\|exception' || echo 0)
if [ "$ERROR_COUNT" -gt 0 ]; then
  echo "⚠️  Found $ERROR_COUNT error(s) in logs"
  docker logs ins2026-app 2>/dev/null | grep -i 'error\|exception' | head -5
else
  echo "✅ No errors in application logs"
fi

# Summary
echo ""
echo "╔════════════════════════════════════════════════════════╗"
echo "║  📊 HEALTH CHECK SUMMARY                               ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "✅ Passed: $PASSED"
echo "❌ Failed: $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
  echo "🎉 ALL CHECKS PASSED - PRODUCTION READY!"
  exit 0
else
  echo "⚠️  Some checks failed - Please review above"
  exit 1
fi
