#!/bin/sh
set -e

if [ ! -f /app/artisan ]; then
    echo "[entrypoint] seeding /app from /opt/app-src"
    cp -a /opt/app-src/. /app/
fi

chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public/storage

echo "[entrypoint] running migrations"
su -s /bin/sh www-data -c "php /app/artisan migrate --force"

echo "[entrypoint] running seeders"
su -s /bin/sh www-data -c "php /app/artisan db:seed --class=Database\\\\Seeders\\\\SetupSeeder --force"

if [ "$RUN_SCHEDULER" = "true" ]; then
    echo "[entrypoint] starting scheduler"
    su -s /bin/sh www-data -c "php /app/artisan schedule:work >> /app/storage/logs/scheduler.log 2>&1 &"
fi

if [ "$RUN_QUEUE" = "true" ]; then
    echo "[entrypoint] starting queue worker"
    su -s /bin/sh www-data -c "php /app/artisan queue:work --sleep=3 --tries=3 >> /app/storage/logs/queue.log 2>&1 &"
fi

exec "$@"
