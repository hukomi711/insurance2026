#!/bin/sh
# ═══════════════════════════════════════════════════════════════════
# Docker entrypoint — Insurance 2026
# ═══════════════════════════════════════════════════════════════════
# Reads Docker secrets (file-based) into environment variables,
# then exec's the original CMD (php-fpm, artisan, etc.).
# ═══════════════════════════════════════════════════════════════════
set -e

# Read DB password from Docker secret if a non-empty secret was mounted.
# An empty secret must not override a valid DB_PASSWORD from the runtime env.
if [ -n "${DB_PASSWORD_FILE:-}" ] && [ -s "$DB_PASSWORD_FILE" ]; then
    # Ensure readable (Docker Compose bind-mounts may preserve host permissions)
    chmod 444 "$DB_PASSWORD_FILE" 2>/dev/null || true
    export DB_PASSWORD="$(cat "$DB_PASSWORD_FILE")"
elif [ -n "${DB_PASSWORD_FILE:-}" ] && [ -e "$DB_PASSWORD_FILE" ]; then
    echo "DB password secret file is empty; keeping existing DB_PASSWORD value." >&2
fi

# Ensure storage directories exist (named volume may be empty on first boot)
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs
chown -R appuser:appuser /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Discover packages if cache is missing (cleared during build to remove dev deps)
if [ ! -f /var/www/html/bootstrap/cache/packages.php ]; then
    su-exec appuser php artisan package:discover --ansi 2>/dev/null || true
fi

# Sync fresh build assets into the persistent public volume.
# /opt/build-assets/ holds the pristine copy from the Docker image,
# immune to the named volume overriding /var/www/html/public/.
if [ -d /opt/build-assets/build ]; then
    rm -rf /var/www/html/public/build
    mkdir -p /var/www/html/public/build
    cp -a /opt/build-assets/build/. /var/www/html/public/build/
    # Clear stale font preload cache so Laravel picks up new hashed filenames
    su-exec appuser php artisan vite:clear-fonts 2>/dev/null || true
fi

# Wait for Redis before caching config (max 30s)
for i in $(seq 1 30); do
    if php -r "@fsockopen('redis', 6379) ? exit(0) : exit(1);" 2>/dev/null; then
        break
    fi
    echo "Waiting for Redis... ($i/30)"
    sleep 1
done

# Build Laravel caches only after file-based secrets have been exported.
# config:cache is intentionally fail-fast: invalid or missing production
# configuration must stop the container instead of booting with stale values.
su-exec appuser php artisan config:cache
su-exec appuser php artisan route:cache 2>/dev/null || true
su-exec appuser php artisan view:cache 2>/dev/null || true
su-exec appuser php artisan event:cache 2>/dev/null || true

exec su-exec appuser "$@"
