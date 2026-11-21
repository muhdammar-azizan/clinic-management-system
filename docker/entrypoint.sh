#!/bin/sh
# Runs every time the container starts, then hands over to Apache.
set -e

cd /var/www/html

if [ -z "${APP_KEY}" ]; then
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

# A mounted volume can arrive owned by root. The web workers run as www-data.
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/private
chown -R www-data:www-data storage bootstrap/cache

# Run artisan as www-data so the files it creates can be used by the web workers.
artisan() {
    runuser -u www-data -- php artisan "$@"
}

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    artisan migrate --force
fi

# Adds the demo accounts only when the database has no users yet.
if [ "${RUN_DEMO_SEED:-0}" = "1" ]; then
    artisan demo:seed
fi

# Cache config, routes, views and events for speed. It reads the environment of this container.
artisan optimize

exec "$@"
