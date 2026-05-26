#!/bin/bash
# ════════════════════════════════════════════════════════════════
# Insurance 2026 — Production Server Setup Script
# Run this directly on the production server:
#   ssh root@69.57.161.222
#   sudo bash < production-setup.sh
# ════════════════════════════════════════════════════════════════

set -e

echo "════════════════════════════════════════════════════════════════"
echo "Step 1: SSH Key Setup"
echo "════════════════════════════════════════════════════════════════"

mkdir -p /root/.ssh
chmod 700 /root/.ssh

cat > /root/.ssh/authorized_keys <<'EOF'
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIAma00ldOkCmq30GUvzXLDHOzpAgk335W3Hn3VHc70A+ insurance2026-deploy
EOF

chmod 600 /root/.ssh/authorized_keys
restorecon -Rv /root/.ssh 2>/dev/null || true

sshd -t && systemctl reload sshd
echo "✓ SSH keys configured and SSHD reloaded"

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Step 2: Verify Laravel Project"
echo "════════════════════════════════════════════════════════════════"

cd /opt/insurance2026
echo "✓ Project location: $(pwd)"

echo ""
echo "Docker Containers:"
docker compose ps

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Step 3: Health Check"
echo "════════════════════════════════════════════════════════════════"

HEALTH=$(curl -k -s -w "%{http_code}" -o /tmp/health.out https://tamminzonlinez.online/api/health 2>/dev/null || echo "000")
echo "Website Health: HTTP $HEALTH"

if [ "$HEALTH" = "200" ]; then
    echo "✓ Website is responding correctly"
else
    echo "⚠ Website returned: $(cat /tmp/health.out 2>/dev/null || echo 'unknown')"
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Step 4: List Existing Admin Users"
echo "════════════════════════════════════════════════════════════════"

docker exec ins2026-app php artisan tinker --execute='
use App\Models\User;
echo "\nAdmin Users:\n";
User::query()
    ->select("id", "name", "email", "role", "created_at")
    ->where("role", "admin")
    ->orderBy("id")
    ->get()
    ->each(fn ($u) => echo sprintf("  %d: %s <%s> [%s]\n", $u->id, $u->name, $u->email, $u->role));
' 2>&1

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Setup Complete!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Next: To create admin user 'dr@tamminzonlinez.online'"
echo "Run: /root/create-admin.sh"
echo ""
