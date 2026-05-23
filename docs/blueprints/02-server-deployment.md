# Blueprint 02: Server Deployment

## Production Web Server

### Nginx

```nginx
server {
    listen 80;
    server_name internara.example.com;
    root /path/to/app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Apache

Ensure `mod_rewrite` is enabled and use the included `public/.htaccess` file.

## PHP-FPM Configuration

```ini
; /etc/php/8.4/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500
```

Adjust `pm.max_children` based on server memory. Each PHP-FPM process consumes
approximately 40–60 MB. With 50 children, reserve at least 3 GB of RAM.

## Required Background Processes

Three processes must run at all times in production:

### 1. Queue Worker

Processes queued jobs: notifications, media conversions, mail delivery,
and deferred operations. Without it, jobs pile up and are never executed.

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### 2. Scheduler

Triggers daily cleanup, cache warming, Pulse data recording, and log pruning.
Must be configured as a cron entry:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Reverb WebSocket Server

Handles real-time broadcasting for in-app notifications. Without it,
notifications still work but require a page refresh to appear.

```bash
php artisan reverb:start
```

## Supervisor Configuration

Create `/etc/supervisor/conf.d/internara-worker.conf`:

```ini
[program:internara-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/worker.log
stopwaitsecs=3600
```

Create `/etc/supervisor/conf.d/internara-reverb.conf`:

```ini
[program:internara-reverb]
command=php /path/to/app/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/reverb.log
```

## Docker Deployment

See `Dockerfile` and `docker-compose.yml` in the project root for a complete
containerized setup with MySQL, Redis, Nginx, queue worker, scheduler, and
Reverb.

## Storage

The public storage symlink must exist for uploaded file access:

```bash
php artisan storage:link
```

For multi-server deployments, replace local storage with S3-compatible object
storage (`config/filesystems.php`).

## Health Verification

```bash
php artisan system:health
```

This single command validates all server prerequisites and identifies common
misconfigurations.

## References

- `Dockerfile` — containerized application
- `docker-compose.yml` — multi-service orchestration
- `.docker/nginx.conf` — Nginx configuration for Docker
- `app/Domain/Core/Console/Commands/HealthCommand.php` — health checks
- `docs/infrastructure.md` — deployment options overview
