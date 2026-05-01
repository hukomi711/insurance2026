#!/bin/bash
# Deploy helper — run after copying updated PHP files into containers
# Usage: ${INS_DEPLOY_DIR:-/opt/insurance2026}/deploy-refresh.sh

set -e

CONFIG_DIR="$HOME/deploy-config"

echo "==> Syncing config files to all containers..."
for c in ins2026-app ins2026-horizon ins2026-scheduler ins2026-reverb; do
    if [ -d "$CONFIG_DIR" ]; then
        docker cp "$CONFIG_DIR/." "$c:/var/www/html/config/"
    fi
done

echo "==> Clearing config cache..."
for c in ins2026-app ins2026-horizon ins2026-scheduler ins2026-reverb; do
    docker exec "$c" php /var/www/html/artisan config:clear 2>/dev/null
    docker exec "$c" php /var/www/html/artisan config:cache 2>/dev/null
    echo "    $c config OK"
done

echo "==> Clearing route & view cache..."
docker exec ins2026-app php /var/www/html/artisan route:clear
docker exec ins2026-app php /var/www/html/artisan route:cache
docker exec ins2026-app php /var/www/html/artisan view:clear

echo "==> Resetting OPcache (PHP-FPM reload)..."
docker exec ins2026-app kill -USR2 1

echo "==> Restarting Horizon workers..."
docker restart ins2026-horizon

echo "==> Deploy refresh complete!"
