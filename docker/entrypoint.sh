#!/bin/sh
set -e

if [ ! -f /app/artisan ]; then
    echo "[entrypoint] application files are missing from /app"
    exit 1
fi

# /app/public is an app_data volume that persists between deploys; refresh it from
# the image every boot. Runtime uploads (branding, media) remain in the volume.
cp -a /opt/app-public/. /app/public/

mkdir -p /app/storage/framework/cache/data /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs /app/bootstrap/cache
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
