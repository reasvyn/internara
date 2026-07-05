# Deployment — Options, Requirements & CI/CD

> **Last updated:** 2026-06-14 **Changes:** sync — initial metadata sync with new format

## Description

Internara is designed to be installed on the school's own infrastructure. This guide covers the
three supported deployment paths and the operational requirements for each.

For prerequisites and PHP extension requirements, see
[Installation](../guide/01-installation.md#prerequisites). For application installation steps
(migrations, setup wizard, build), see
[Installation](../guide/01-installation.md#application-installation-steps).

---

#Deployment — Options, Requirements & CI/CD Path A: Shared Hosting (Primary)

Shared hosting is the **recommended starting point** for most schools. It handles up to 500
registered users per PKL period with zero devops overhead.

### Supported Providers

Any shared hosting plan with these features works:

| Feature       | Minimum Requirement                             |
| ------------- | ----------------------------------------------- |
| PHP           | 8.4+                                            |
| Database      | MySQL 8+ or MariaDB 10.6+                       |
| Document root | Configurable to `public/` directory             |
| SSH access    | Recommended (cPanel/S FTP fallback available)   |
| Cron          | At least 5-minute interval (1-minute preferred) |
| Disk space    | 5 GB minimum (10 GB recommended)                |

Most Indonesian hosting providers (Niagahoster, Domainesia, Jagoan Hosting, etc.) meet these
requirements.

### Limitations

| Feature                                   | Alternative                                                 |
| ----------------------------------------- | ----------------------------------------------------------- |
| Queue worker (no long-running processes)  | Set `QUEUE_CONNECTION=sync` -- jobs run during HTTP request |
| Reverb WebSocket (no custom servers)      | Page refresh shows new notifications                        |
| Redis / Memcached (not installed)         | Use `file` or `database` driver                             |
| Minute-level cron (min interval 5-15 min) | Hit `/cron/{secret}` web endpoint                           |

### What Still Works

All core features: authentication, registration, attendance, logbook, assignments, assessments,
reports, certificates, mentoring, email notifications.

##Deployment — Options, Requirements & CI/CD Steps

**1. Build locally:**

```bash
composer install --optimize-autoloader --no-dev --no-interaction
npm install && npm run build
rm -rf node_modules/
```

**2. Upload files** to your host's document root. The document root must point to the `public/`
directory.

**3. Configure environment:**

```bash
cp .env.example .env
```

The `.env.example` defaults are already optimized for shared hosting (`QUEUE_CONNECTION=sync`,
`CACHE_STORE=file`, etc.). Key settings to customize: `APP_URL`, `APP_ENV=production`,
`APP_DEBUG=false`, `DB_*` (your host's MySQL/MariaDB credentials), `MAIL_*` (SMTP settings),
`CRON_SECRET`.

**4. Run migrations:**

```bash
php artisan migrate --force
```

**5. Run the installer:**

```bash
php artisan setup:install
```

Copy the signed URL from the output and open it in your browser to complete the setup wizard.

**6. Set up cron** in cPanel to hit the scheduler endpoint:

```cron
* * * * * curl -s https://your-school.sch.id/cron/your-cron-secret-here
```

If your provider limits cron to 5-minute intervals, that is acceptable -- scheduled tasks run with a
slight delay.

**7. Storage link** -- create manually if SSH is not available:

```
public/storage -> storage/app/public
```

### Performance for 500 Users

At 500 registered users (~50-100 peak concurrent), shared hosting handles all operations with these
expected response times:

| Operation                 | Expected Time     |
| ------------------------- | ----------------- |
| Page load (cached)        | < 500ms           |
| Page load (uncached)      | < 1.5s            |
| Email sending (sync)      | 1-3s per message  |
| Media upload + conversion | 1-3s per file     |
| PDF generation            | 2-5s per document |
| Report generation         | 3-8s per report   |

If response times degrade, upgrade to [Tier 2 (VPS)](#deployment-path-b-vps--dedicated-server).

### Upgrading to VPS

When the institution outgrows shared hosting:

1. Set up a VPS with PHP 8.4, Redis, Supervisor
2. Install the same codebase following [Installation](../guide/01-installation.md)
3. Change `.env`:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

4. Configure Supervisor with dual pipeline workers
5. Set up minute-level cron
6. All features become available automatically, including async queue processing

---

#Deployment — Options, Requirements & CI/CD Path B: VPS / Dedicated Server

### 1. Web Server: Nginx

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

For Apache, ensure `mod_rewrite` is enabled -- the included `public/.htaccess` handles URL
rewriting.

### 2. PHP-FPM Tuning

```ini
; /etc/php/8.4/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500
```

Each PHP-FPM process uses ~40-60 MB. With 50 children, reserve at least 3 GB RAM.

### 3. Database Setup

#### MySQL 8+

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=<strong-password>
```

Recommended `my.cnf` tuning:

```ini
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_method = O_DIRECT
max_connections = 200
```

#### MariaDB 10.6+

MariaDB is a drop-in replacement using the same `pdo_mysql` driver:

```env
DB_CONNECTION=mariadb
```

#### PostgreSQL 14+

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=<strong-password>
```

Recommended `postgresql.conf` tuning:

```ini
shared_buffers = 512MB
effective_cache_size = 1.5GB
work_mem = 16MB
maintenance_work_mem = 128MB
random_page_cost = 1.1
```

### 4. Dual Pipeline Supervisor Configuration

Two separate queue pipelines prevent document compilation from blocking notification delivery:

- **`default` queue**: Processes emails, alerts, and general events.
- **`documents` queue**: Dedicated exclusively to compiling PDF certificates and reports.

`/etc/supervisor/conf.d/internara-worker.conf`:

```ini
[program:internara-default-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/default-worker.log
stopwaitsecs=3600

[program:internara-documents-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --queue=documents --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/documents-worker.log
stopwaitsecs=3600
```

`/etc/supervisor/conf.d/internara-scheduler.conf`:

```ini
[program:internara-scheduler]
command=php /path/to/app/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/scheduler.log
```

Alternatively, use a cron entry for the scheduler:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Storage

Create the public storage symlink:

```bash
php artisan storage:link
```

For multi-server deployments, replace local storage with S3-compatible object storage. See
[Media Library](media-library.md#s3-compatible-cloud-storage).

### 6. Complete the Installation

Follow the application installation steps in
[Installation](../guide/01-installation.md#application-installation-steps) -- build assets, run the
setup wizard, enable caches, and verify with `php artisan system:health`.

---

#Deployment — Options, Requirements & CI/CD Path C: Docker

### Docker Compose Services

The project includes a production `docker-compose.yml` with all required services:

| Service     | Image               | Purpose                    | Depends On |
| ----------- | ------------------- | -------------------------- | ---------- |
| `app`       | Custom (Dockerfile) | PHP-FPM application server | db, redis  |
| `queue`     | Custom (Dockerfile) | Laravel queue worker       | db, redis  |
| `scheduler` | Custom (Dockerfile) | Scheduler daemon           | db         |
| `web`       | nginx:alpine        | Reverse proxy              | app        |
| `db`        | mysql:8             | Database                   | --         |
| `redis`     | redis:7-alpine      | Cache, queue, session      | --         |

### Start the Stack

```bash
docker compose up -d
```

The application is served on port 80 (configurable via `NGINX_PORT`). Run
`php artisan setup:install` inside the `app` container to generate the signed setup URL, then open
it in your browser.

### Development with Laravel Sail

```bash
# Start Sail environment (SQLite + queue)
./vendor/bin/sail up -d

# Or with MySQL instead of SQLite:
./vendor/bin/sail up -d -s mysql
```

See `docker-compose.dev.yml` for the Sail configuration.

---

## Production Checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production` in `.env`
- [ ] `APP_KEY` set to a random 32-character base64 string
- [ ] Database migrated: `php artisan migrate --force`
- [ ] Public storage link exists: `php artisan storage:link`
- [ ] Queue workers running (Supervisor with dual pipelines: default + documents) -- Tier 2+ only
- [ ] Scheduler cron entry configured (system cron or webhook)
- [ ] OpCache enabled and configured
- [ ] All caches warmed: `php artisan optimize`
- [ ] Frontend assets built: `npm run build`
- [ ] HTTPS configured at the web server or reverse proxy
- [ ] `php artisan system:health` passes with no FAIL results
- [ ] Backup automation configured (see [Backup & Recovery](backup-recovery.md))
- [ ] Monitoring set up (see [Observability](observability.md))

---

## References

- [Installation](../guide/01-installation.md) -- prerequisites, command reference, troubleshooting
- [Infrastructure](infrastructure.md) -- tier-based infrastructure design, scaling, sizing
- [Configuration](configuration.md) -- environment variables and runtime settings
- [Queue](queue.md) -- worker management, job lifecycle, enterprise scaling
- [Media Library](media-library.md) -- file uploads, S3 storage, image conversions
- [Backup & Recovery](backup-recovery.md) -- database dumps, restoration
- [Observability](observability.md) -- logging, Pulse, health checks
