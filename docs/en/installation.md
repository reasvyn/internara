# Installation

## Prerequisites

| Requirement | Minimum | Recommendation |
|---|---|---|
| PHP | 8.4.0 | 8.4+ |
| Composer | 2.5 | Latest |
| Node.js | 20 | 22 LTS |
| NPM | 10 | Latest |
| Database | SQLite (dev) | MySQL 8 / PostgreSQL 14 (prod) |
| Queue | `database` driver | Redis (production) |

## Quick Start

```bash
# 1. Clone and install PHP dependencies
git clone <repo-url> internara
cd internara
composer install

# 2. Environment configuration
cp .env.example .env
php artisan key:generate

# 3. Database (SQLite for development)
touch database/database.sqlite
php artisan migrate --seed

# 4. Frontend assets
npm install
npm run build

# 5. Storage link
php artisan storage:link

# 6. Start development server
php artisan serve
# In separate terminal:
php artisan queue:work
# In separate terminal:
npm run dev
```

Or use the all-in-one command:
```bash
composer run dev
```

## Setup Wizard

After installation, visit the application URL. The setup wizard will guide you through:
1. Database verification
2. School configuration
3. Department setup
4. Super admin account creation
5. Internship program configuration

## Production Deployment

### Required Background Processes

```bash
# Queue worker (required for notifications, media, mail)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# Scheduler cron entry (runs every minute)
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1

# Reverb WebSocket server (for real-time features)
php artisan reverb:start
```

### Production Database

SQLite is suitable for development only. For production, configure MySQL 8+ or
PostgreSQL 14+ in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=root
DB_PASSWORD=
```

### Supervisor Configuration

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

```ini
[program:internara-reverb]
command=php /path/to/app/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/reverb.log
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name internara.example.com;
    root /path/to/app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

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

## Docker Deployment

### Dockerfile

See the generated `Dockerfile` in the project root. It installs system
dependencies, Composer, and npm, then builds the application:

```dockerfile
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip \
    libpq-dev libzip-dev nodejs npm \
    && docker-php-ext-install pdo_mysql pdo_pgsql bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && npm install && npm run build \
    && chown -R www-data:www-data storage bootstrap/cache public/storage \
    && php artisan storage:link

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

### Docker Compose

See the generated `docker-compose.yml` in the project root. Key services:

| Service | Purpose | Depends On |
|---|---|---|
| `app` | PHP-FPM application server | db, redis |
| `queue` | Laravel queue worker | db, redis |
| `reverb` | WebSocket server (real-time) | db, redis |
| `scheduler` | Laravel scheduler (`schedule:work`) | db |
| `web` | Nginx reverse proxy | app |
| `db` | MySQL 8 database | — |
| `redis` | Redis 7 cache/queue | — |

Run with:
```bash
docker compose up -d
```

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Blank page | Storage not writable | `chmod -R 775 storage bootstrap/cache` |
| 404 on media URLs | Storage link missing | `php artisan storage:link` |
| Vite manifest error | Assets not built | `npm run build` |
| Jobs not processing | Queue worker not running | `php artisan queue:work` |
| WebSocket not connecting | Reverb not running | `php artisan reverb:start` |
| "Database is locked" | SQLite concurrent writes | Switch to MySQL/PG in production |
