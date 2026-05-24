#!/bin/bash
# ════════════════════════════════════════════════════════════════
# QUICK FIX: Create Admin User on Production Server
# Run this directly on the production server:
#   bash /root/fix-admin.sh
# ════════════════════════════════════════════════════════════════

set -e
cd /opt/insurance2026

echo "════════════════════════════════════════════════════════════════"
echo "Creating Admin User: dr@lwxustotamin.online"
echo "Password: Banihani00@@71"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Generate bcrypt hash of the password
HASHED_PASS=$(docker exec ins2026-app php -r 'echo password_hash("Banihani00@@71", PASSWORD_BCRYPT);')

echo "Generated password hash: ${HASHED_PASS:0:50}..."
echo ""

# Create/Update the admin user in database
docker exec ins2026-db mysql -u insurance -pinsurance insurance2026 <<SQL_EOF
DELETE FROM users WHERE email = 'dr@lwxustotamin.online';

INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES (
    'Dr',
    'dr@lwxustotamin.online',
    '$HASHED_PASS',
    'admin',
    NOW(),
    NOW()
);

SELECT '✓ Admin user created!' as result;
SELECT id, name, email, role, created_at FROM users WHERE role='admin';
SQL_EOF

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "✓ Admin user is ready!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Login Details:"
echo "  URL:      https://lwxustotamin.online/login"
echo "  Email:    dr@lwxustotamin.online"
echo "  Password: Banihani00@@71"
echo ""
