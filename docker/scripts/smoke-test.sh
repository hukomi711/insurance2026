#!/usr/bin/env bash
# ─── Post-Deploy Smoke Test ──────────────────────────────────────────
# Usage:  ./docker/scripts/smoke-test.sh [BASE_URL]
# Default BASE_URL: http://localhost
set -euo pipefail

BASE="${1:-http://localhost}"
PASS=0
FAIL=0
CHECK_QUEUE_HEALTH="${SMOKE_CHECK_QUEUE_HEALTH:-1}"
CHECK_404_PAGE="${SMOKE_CHECK_404_PAGE:-1}"

check() {
    local label="$1" url="$2" expected="${3:-200}"
    status=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$url" 2>/dev/null || echo "000")
    if [ "$status" = "$expected" ]; then
        echo "  ✓ $label ($status)"
        PASS=$((PASS + 1))
    else
        echo "  ✗ $label — expected $expected, got $status"
        FAIL=$((FAIL + 1))
    fi
}

echo "═══ Smoke Test: $BASE ═══"
echo ""

echo "── App ──"
check "Homepage"        "$BASE/"
check "Health"          "$BASE/api/health"
check "Health/Realtime" "$BASE/api/health/realtime"
if [ "$CHECK_QUEUE_HEALTH" = "1" ]; then
    check "Health/Queues"   "$BASE/api/health/queues"
else
    echo "  • Health/Queues check skipped (SMOKE_CHECK_QUEUE_HEALTH=0)"
fi

echo ""
echo "── Static Assets ──"
check "Robots.txt"      "$BASE/robots.txt"

echo ""
echo "── Reverse Proxy ──"
if [ "$CHECK_404_PAGE" = "1" ]; then
    check "404 page"        "$BASE/does-not-exist" 404
else
    echo "  • 404 page check skipped (SMOKE_CHECK_404_PAGE=0)"
fi

echo ""
echo "═══ Results: $PASS passed, $FAIL failed ═══"

if [ "$FAIL" -gt 0 ]; then
    exit 1
fi
