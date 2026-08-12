#!/bin/sh
set -e

if [ ! -f /app/artisan ]; then
    echo "[entrypoint] seeding /app from /opt/app-src"
    cp -a /opt/app-src/. /app/
fi

chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public/storage

exec "$@"
