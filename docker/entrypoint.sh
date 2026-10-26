#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    database

# Writable dirs when the image runs as root (default apache image).
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache database || true
fi

# Optional one-shot migrate on boot (set CACHET_MIGRATE_ON_BOOT=1).
# Do not reuse CACHET_RUN_MIGRATIONS — that flag controls whether Cachet
# package migrations are registered at all.
if [ "${CACHET_MIGRATE_ON_BOOT:-0}" = "1" ]; then
    echo "Running database migrations..."
    # Keep the web process starting even if migrate fails (e.g. DB not ready yet).
    php artisan migrate --force --no-interaction \
        || echo "Warning: migrate failed; run 'php artisan migrate --force' later."
fi

exec "$@"
