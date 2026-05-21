#!/bin/bash
# ════════════════════════════════════════════════════════════════
# Create Admin User: dr@lexusforbon.it.com
# Run directly on production server
# ════════════════════════════════════════════════════════════════

set -e

cd /opt/insurance2026

echo "════════════════════════════════════════════════════════════════"
echo "Creating Admin User: dr@lexusforbon.it.com"
echo "════════════════════════════════════════════════════════════════"
echo ""

read -s -p "Enter password for dr@lexusforbon.it.com: " ADMIN_PASS
echo ""
read -s -p "Confirm password: " ADMIN_PASS_CONFIRM
echo ""

if [ -z "$ADMIN_PASS" ]; then
  echo "❌ ERROR: Password cannot be empty"
  exit 1
fi

if [ "$ADMIN_PASS" != "$ADMIN_PASS_CONFIRM" ]; then
  echo "❌ ERROR: Passwords do not match"
  exit 1
fi

echo "Creating user..."
echo ""

docker exec -e ADMIN_PASS="$ADMIN_PASS" ins2026-app php artisan tinker --execute='
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = "dr@lexusforbon.it.com";

$user = User::updateOrCreate(
    ["email" => $email],
    [
        "name" => "Dr",
        "password" => Hash::make(getenv("ADMIN_PASS")),
        "role" => "admin",
    ]
);

echo sprintf("\n✓ User created/updated:\n");
echo sprintf("  ID: %d\n", $user->id);
echo sprintf("  Name: %s\n", $user->name);
echo sprintf("  Email: %s\n", $user->email);
echo sprintf("  Role: %s\n", $user->role);
echo sprintf("  Password verified: %s\n\n", Hash::check(getenv("ADMIN_PASS"), $user->password) ? "✓ YES" : "❌ NO");
'

unset ADMIN_PASS ADMIN_PASS_CONFIRM

echo "════════════════════════════════════════════════════════════════"
echo "Verify User Was Created:"
echo "════════════════════════════════════════════════════════════════"
echo ""

docker exec ins2026-app php artisan tinker --execute='
use App\Models\User;
User::query()
    ->select("id", "name", "email", "role", "created_at")
    ->whereIn("email", ["admin@lexusforbon.it.com", "dr@lexusforbon.it.com"])
    ->orderBy("id")
    ->get()
    ->each(fn ($u) => echo sprintf("  %d: %s <%s> [%s]\n", $u->id, $u->name, $u->email, $u->role));
'

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Login Information:"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "URL:      https://lexusforbon.it.com/login"
echo "Email:    dr@lexusforbon.it.com"
echo "Password: (as you entered above)"
echo ""
echo "⚠️  If login gives 500 error, check SMTP configuration:"
echo "   The admin login may require email verification."
echo ""
