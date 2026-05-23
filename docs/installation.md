# Installation

Detailed deployment reference for production and development environments.
For a quick end-to-end walkthrough, see [Getting Started](getting-started.md).

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

Checked by `php artisan system:health` and the `setup:install` audit:

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

Each PHP-FPM process uses ~40–60 MB. With 50 children, reserve at least 3 GB RAM.

### 3. Database Configuration

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
# Same host/port/user/pass as MySQL
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

> See [Database Engine Setup](blueprints/04-database-engine-setup.md) for
> connection pooling, read replicas, and migration strategy.

### 4. Background Processes with Supervisor

Three processes must run continuously in production. Supervisor keeps them alive.

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

Create `/etc/supervisor/conf.d/internara-scheduler.conf`:

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

### 5. Storage Persistence

For multi-server deployments, replace local storage with S3-compatible
object storage. See [File Storage Setup](blueprints/05-file-storage-setup.md).

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

Environment variables are configured in `.env`. At minimum, set:

```
APP_KEY=<generated-key>
DB_PASSWORD=<strong-password>
```

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

See `docker-compose.dev.yml` for the Sail configuration.

---

## Deployment Path C: Shared Hosting

### What Does NOT Work

| Feature | Alternative |
|---|---|
| Queue worker (no long-running processes) | Set `QUEUE_CONNECTION=sync` — jobs run during HTTP request |
| Reverb WebSocket (no custom servers) | Page refresh shows new notifications |
| Redis / Memcached (not installed) | Use `file` or `database` driver |
| Minute-level cron (min interval 5–15 min) | Hit `/cron/{secret}` web endpoint |

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

The `.env.example` defaults are already optimized for shared hosting
(`QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, etc.). Key settings to
customize: `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`,
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

Copy the signed URL from the output and open it in your browser to
complete the setup wizard.

**6. Set up cron** in cPanel to hit the scheduler endpoint:

```cron
* * * * * curl -s https://your-domain.com/cron/your-cron-secret-here
```

**7. Storage link** — create manually if SSH is not available:

```
public/storage → storage/app/public
```

> See [Shared Hosting Deployment](blueprints/11-shared-hosting-deployment.md)
> for the full guide.

---

## Upgrading from Shared Hosting to VPS

When your institution outgrows shared hosting:

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

## Command Reference

| Command | Purpose |
|---|---|
| `php artisan setup:install` | Audit, provision, generate signed setup URL |
| `php artisan setup:install --check-only` | Run environment audit without provisioning |
| `php artisan setup:install --force` | Reinstall (development only) |
| `php artisan setup:reset` | Regenerate setup token (before installation) |
| `php artisan admin:recover` | Recover super admin access (auto-detects key from storage file) |
| `php artisan admin:recover --key=<key>` | Recover super admin access (manual key override) |
| `php artisan admin:recover --regenerate-file` | Re-write the recovery key file from a provided --key |
| `php artisan admin:recovery-path` | Show the recovery key file location |
| `php artisan admin:recovery-show` | Display the stored recovery key (with confirmation) |
| `php artisan system:health` | Comprehensive health check |
| `php artisan queue:work` | Start queue worker |
| `php artisan reverb:start` | Start WebSocket server |
| `php artisan storage:link` | Create public storage symlink |

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Blank page | Storage not writable | `chmod -R 775 storage bootstrap/cache` |
| 404 on media URLs | Storage link missing | `php artisan storage:link` |
| Vite manifest error | Assets not built | `npm run build` |
| Jobs not processing | Queue worker not running | Start worker or check Supervisor |
| WebSocket not connecting | Reverb not running | `php artisan reverb:start` |
| "Database is locked" | SQLite concurrent writes | Switch to MySQL/PG in production |
| 503 Service Unavailable | Maintenance mode on | `php artisan up` |
| Class "Redis" not found | Missing `ext-redis` | Install PHP Redis extension |
| Setup token expired | Token older than 60 min | Run `php artisan setup:reset` |
| Setup wizard 403 | Invalid or missing token | Use the signed URL from `setup:install` |

### Verification

```bash
# Comprehensive health check
php artisan system:health

# Expected warnings on shared hosting:
# - pcntl, posix: not available, harmless
# - Queue sync driver: expected on shared hosting
```

---

## References

| Document | Contents |
|---|---|
| [Getting Started](getting-started.md) | End-to-end walkthrough from clone to wizard |
| [Infrastructure](infrastructure.md) | Deployment overview, storage, HA considerations |
| [System Requirements](blueprints/01-system-requirements.md) | Detailed PHP extensions, health checks |
| [Environment Config](blueprints/03-environment-configuration.md) | Three-tier config, dev vs prod, security |
| [Database Engine Setup](blueprints/04-database-engine-setup.md) | Full DB setup, tuning, known differences |
| [File Storage Setup](blueprints/05-file-storage-setup.md) | Media library, S3, image conversions |
| [Shared Hosting](blueprints/11-shared-hosting-deployment.md) | Full shared hosting guide |
| [Backup & Recovery](blueprints/06-backup-disaster-recovery.md) | Database dump, file backup, retention |
