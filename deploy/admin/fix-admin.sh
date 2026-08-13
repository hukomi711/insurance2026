#!/bin/bash
# ════════════════════════════════════════════════════════════════
# QUICK FIX: Create Admin User on Production Server
# Run this directly on the production server:
#   bash /root/fix-admin.sh
# ════════════════════════════════════════════════════════════════

set -euo pipefail
cd /opt/insurance2026

: "${ADMIN_EMAIL:?Set ADMIN_EMAIL before running this script}"
: "${ADMIN_PASSWORD:?Set ADMIN_PASSWORD before running this script}"
ADMIN_NAME="${ADMIN_NAME:-Administrator}"

echo "════════════════════════════════════════════════════════════════"
echo "Creating or updating admin user: ${ADMIN_EMAIL}"
echo "Password: [provided via ADMIN_PASSWORD]"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Use Laravel so hashing and model behavior remain consistent. Credentials are
# provided at runtime and never embedded in Git or passed to the database CLI.
docker exec \
  -e ADMIN_EMAIL="$ADMIN_EMAIL" \
  -e ADMIN_PASSWORD="$ADMIN_PASSWORD" \
  -e ADMIN_NAME="$ADMIN_NAME" \
  ins2026-app php artisan tinker --execute='
    $user = App\Models\User::updateOrCreate(
        ["email" => getenv("ADMIN_EMAIL")],
        [
            "name" => getenv("ADMIN_NAME"),
            "password" => Illuminate\Support\Facades\Hash::make(getenv("ADMIN_PASSWORD")),
            "role" => "admin",
        ],
    );
    echo "Admin user ready: {$user->email}\n";
  '

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "✓ Admin user is ready!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Login Details:"
echo "  Email:    ${ADMIN_EMAIL}"
echo "  Password: [not displayed]"
echo ""
