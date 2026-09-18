#!/bin/bash
# ════════════════════════════════════════════════════════════════
# Insurance 2026 — Production Server Setup Script
# Run this directly on the production server:
#   ssh root@69.57.161.222
#   sudo bash /root/production-setup.sh
# ════════════════════════════════════════════════════════════════

set -e

echo "════════════════════════════════════════════════════════════════"
echo "Step 1: SSH Key Setup"
echo "════════════════════════════════════════════════════════════════"

# Append-only key policy: never overwrite existing authorized_keys.
DEPLOY_PUBLIC_KEY="${DEPLOY_PUBLIC_KEY:-ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIAma00ldOkCmq30GUvzXLDHOzpAgk335W3Hn3VHc70A+ insurance2026-deploy}"
FORGE_PUBLIC_KEY="${FORGE_PUBLIC_KEY:-ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDdzg7a3gU8a9+5BvCTh88hw+lQvezFS/URgoNA2WWSrUxqFC6iMyPS/ssEpshnXogZdhV/Ig5Qu8lxc9K4JhxM6+kD5xyAGDSa4/JCMvAD3W4pGKiTFyfMK3TT4sHo3jmRbV1wO226JJljzM3F9vn5IGqRLE48XfzumPV5MUdO1clvf+HMpVXZ9IxetLj29w2zkIalkOt9n+tQVVcqk7zIvbHp1mYu0B9NeD5R/HDjsDMNEX/o3InyTor0leiQnWwNJ3H2q6w7MgE52tyLtCyVKLRDc1myoTSmQoMTm3bEcgt1s5hMAyOWfSJE0tWzLxBmfgir96Qkuw5dpUrNGa0+ryGra0yC68BX7rwr6gKXOzPZJ5di08nKHltfC0//7YAVRpJ3B4FTUswGnHYuwVPXBuF9UGCjeOrvAk9NUA91YSQUrNZIY1QkW7jEFU5zhXv0mEdTsXdbRatT0LyOKr441kB5YUQeqjBUzlaaGH5TCMJ1YihDMb2JF4hBCmpLep2XtyMk6MqFeUPX57fMSf2SNiBjA0dfC3fOzV+jYyj+l0RV+iYvACVxpCm9ODM1QxrBmWyYw1n5XjLhFV7dLlaa29X9T4D6K9xCNQdsyncjA9T2YLlSZCU4r5xKupzosGNJHTiXUBXRmHrLvfg0vDrzW8GEhiOb7wzVQ2GS1G/41Q== worker@forge.laravel.com}"

backup_once() {
    local file="$1"
    if [ -f "$file" ] && [ ! -f "${file}.bak" ]; then
        cp -a "$file" "${file}.bak"
    fi
}

append_key_if_missing() {
    local key="$1"
    local file="$2"
    touch "$file"
    chmod 600 "$file"
    if ! grep -qxF "$key" "$file"; then
        printf '%s\n' "$key" >> "$file"
    fi
}

mkdir -p /root/.ssh
chmod 700 /root/.ssh

backup_once /root/.ssh/authorized_keys
append_key_if_missing "$DEPLOY_PUBLIC_KEY" /root/.ssh/authorized_keys
append_key_if_missing "$FORGE_PUBLIC_KEY" /root/.ssh/authorized_keys

if id forge >/dev/null 2>&1; then
    mkdir -p /home/forge/.ssh
    chmod 700 /home/forge/.ssh
    chown forge:forge /home/forge/.ssh

    backup_once /home/forge/.ssh/authorized_keys
    append_key_if_missing "$FORGE_PUBLIC_KEY" /home/forge/.ssh/authorized_keys

    chown forge:forge /home/forge/.ssh/authorized_keys
else
    echo "⚠ User 'forge' not found; skipped /home/forge/.ssh/authorized_keys"
fi

restorecon -Rv /root/.ssh 2>/dev/null || true
restorecon -Rv /home/forge/.ssh 2>/dev/null || true

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

HEALTH=$(curl -k -s -w "%{http_code}" -o /tmp/health.out https://lexusforbon.com/api/health 2>/dev/null || echo "000")
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
echo "Next: To create admin user 'dr@lexusforbon.com'"
echo "Run: /root/create-admin.sh"
echo ""
