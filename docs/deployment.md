# Deployment

Internara is designed to be installed on the school's own infrastructure. This guide covers the three supported deployment paths and the operational requirements for each.

## Prerequisites

| Requirement | Development | Production |
|---|---|---|
| PHP | 8.4.0+ | 8.4.0+ |
| Composer | 2.5+ | 2.5+ |
| Node.js | 20+ | 20+ (build only) |
| NPM | 10+ | 10+ (build only) |
| Database | SQLite (built-in) | MySQL 8+ / MariaDB 10.6+ / PostgreSQL 14+ |
| Queue driver | `database` | `redis` (recommended) |
| Cache driver | `database` | `redis` (recommended) |
| Session driver | `database` | `redis` (recommended) |
| Web server | `php artisan serve` | Nginx or Apache |

### Required PHP Extensions

| Extension | Purpose |
|---|---|
| `ext-bcmath` | Grade and score calculations |
| `ext-ctype` | Character validation |
| `ext-curl` | Remote media downloads, API calls |
| `ext-fileinfo` | MIME type detection for uploads |
| `ext-gd` | Image processing (thumbnails, WebP) |
| `ext-intl` | Internationalization and localization |
| `ext-mbstring` | Multibyte string operations |
| `ext-openssl` | Encryption, HTTPS, signed URLs |
| `ext-pdo` | Database abstraction |
| `ext-tokenizer` | Blade template engine |
| `ext-xml` | XML parsing, feed generation |
| `ext-zip` | File compression |

Database-specific driver (pick one matching your engine):
`ext-pdo_sqlite`, `ext-pdo_mysql`, or `ext-pdo_pgsql`.

### Recommended PHP Extensions

| Extension | Benefit |
|---|---|
| `ext-opcache` | Bytecode cache — essential for production performance |
| `ext-redis` | High-performance cache, session, and queue backend |
| `ext-sockets` | Required by Laravel Reverb WebSocket server |
| `ext-pcntl` | Process control for queue worker signals |
| `ext-posix` | POSIX system calls for process management |
| `ext-imagick` | Higher quality image conversions than GD |

### Verification

```bash
php artisan system:health
```

This validates all requirements and identifies common misconfigurations. Use
`--json` for machine-readable output.

---

## Deployment Path A: VPS / Dedicated Server

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

For Apache, ensure `mod_rewrite` is enabled — the included `public/.htaccess`
handles URL rewriting.

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

Each PHP-FPM process uses approximately 40–60 MB. With 50 children, reserve
at least 3 GB of RAM.

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

### 4. Background Processes with Supervisor

Three processes must run continuously in production. Create these Supervisor
configuration files:

`/etc/supervisor/conf.d/internara-worker.conf`:
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

`/etc/supervisor/conf.d/internara-reverb.conf`:
```ini
[program:internara-reverb]
command=php /path/to/app/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/reverb.log
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

For multi-server deployments, replace local storage with S3-compatible
object storage. See [Media Library](media-library.md#s3-compatible-cloud-storage).

---

## Deployment Path B: Docker

### Docker Compose Services

The project includes a production `docker-compose.yml` with all required
services:

| Service | Image | Purpose | Depends On |
|---|---|---|---|
| `app` | Custom (Dockerfile) | PHP-FPM application server | db, redis |
| `queue` | Custom (Dockerfile) | Laravel queue worker | db, redis |
| `reverb` | Custom (Dockerfile) | WebSocket server | db, redis |
| `scheduler` | Custom (Dockerfile) | Scheduler daemon | db |
| `web` | nginx:alpine | Reverse proxy | app |
| `db` | mysql:8 | Database | — |
| `redis` | redis:7-alpine | Cache, queue, session | — |

### Start the Stack

```bash
docker compose up -d
```

The application is served on port 80 (configurable via `NGINX_PORT`).
Run `php artisan setup:install` inside the `app` container to generate
the signed setup URL, then open it in your browser.

### Development with Laravel Sail

```bash
# Start Sail environment (SQLite + queue + Reverb)
./vendor/bin/sail up -d

# Or with MySQL instead of SQLite:
./vendor/bin/sail up -d -s mysql
```

---

## Deployment Path C: Shared Hosting

### Limitations

| Feature | Why It Doesn't Work | Alternative |
|---|---|---|
| Queue worker | No long-running processes | Set `QUEUE_CONNECTION=sync` — jobs run during HTTP request |
| Reverb WebSocket | No custom servers | Page refresh shows new notifications |
| Redis / Memcached | Not installed | Use `file` or `database` driver |
| Minute-level cron | Min interval often 5–15 min | Hit `/cron/{secret}` web endpoint |

### What Still Works

All core features: authentication, registration, attendance, logbook,
assignments, assessments, reports, certificates, mentoring, email
notifications.

### Deployment Steps

**1. Build locally:**

```bash
composer install --optimize-autoloader --no-dev --no-interaction
npm install && npm run build
rm -rf node_modules/
```

**2. Upload files** to your host's document root. The document root must
point to the `public/` directory.

**3. Configure environment:**

```bash
cp .env.example .env
```

Key settings to customize: `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`,
`DB_*` (your host's MySQL/MariaDB credentials), `MAIL_*` (SMTP settings),
`CRON_SECRET` (run `php -r "echo bin2hex(random_bytes(16));"`).

**4. Run migrations:**

```bash
php artisan migrate --force
```

**5. Run the installer:**

```bash
php artisan setup:install
```

Copy the signed URL from the output and open it in your browser.

**6. Set up cron** in cPanel to hit the scheduler endpoint:

```cron
* * * * * curl -s https://your-domain.com/cron/your-cron-secret-here
```

**7. Storage link** — create manually if SSH is not available:

```
public/storage → storage/app/public
```

### Upgrading to VPS

When the institution outgrows shared hosting:

1. Set up a VPS with PHP 8.4, Redis, Supervisor
2. Install the same codebase
3. Change `.env`:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
BROADCAST_CONNECTION=reverb
```

4. Configure Supervisor for queue worker + Reverb + scheduler
5. Set up minute-level cron
6. All features become available automatically

---

## Production Checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production` in `.env`
- [ ] `APP_KEY` set to a random 32-character base64 string
- [ ] Database migrated: `php artisan migrate --force`
- [ ] Public storage link exists: `php artisan storage:link`
- [ ] Queue worker running (Supervisor or systemd)
- [ ] Scheduler cron entry configured
- [ ] OpCache enabled and configured
- [ ] All caches warmed: `php artisan optimize`
- [ ] Frontend assets built: `npm run build`
- [ ] HTTPS configured at the web server or reverse proxy
- [ ] `php artisan system:health` passes with no FAIL results
- [ ] Backup automation configured (see [Backup & Recovery](backup-recovery.md))
- [ ] Monitoring set up (see [Observability](observability.md))

---

## References

- [Configuration](configuration.md) — environment variables and runtime settings
- [Infrastructure](infrastructure.md) — tier-based infrastructure design, scaling, sizing
- [Queue](queue.md) — worker management, job lifecycle, enterprise scaling
- [Media Library](media-library.md) — file uploads, S3 storage, image conversions
- [Backup & Recovery](backup-recovery.md) — database dumps, restoration
- [Observability](observability.md) — logging, Pulse, health checks
