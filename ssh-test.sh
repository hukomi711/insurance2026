#!/bin/bash
# Simple direct commands

HOST="209.74.64.215"

echo "Testing SSH connection..."
echo "If prompted for password, enter: l1JbZqfUag3CI58A15"
echo ""

ssh -v root@$HOST << 'ENDOFCOMMANDS'
cd /opt/insurance2026
echo "Current branch:"
git branch
echo ""
echo "Latest commit:"
git log --oneline -1
echo ""
echo "Fetching..."
git fetch origin
git checkout hardening/clean-rebuild
git pull origin hardening/clean-rebuild
echo ""
echo "admin.ip references in routes:"
grep -c 'admin.ip' routes/api.php || echo "0"
echo ""
echo "admin.ip references in config:"
grep -c 'admin.ip' config/horizon.php || echo "0"
ENDOFCOMMANDS
