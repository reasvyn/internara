# Installation

> Last updated: 2026-06-08

This document covers the prerequisites, command reference, performance tuning, and troubleshooting for installing Internara. For server environment setup (Nginx, Supervisor, Docker, shared hosting), see [Deployment](deployment.md).

For a quick end-to-end walkthrough, see [Getting Started](../getting-started.md).

---

## Prerequisites

| Requirement    | Development         | Production                                |
| -------------- | ------------------- | ----------------------------------------- |
| PHP            | 8.4.0+              | 8.4.0+                                    |
| Composer       | 2.5+                | 2.5+                                      |
| Node.js        | 20+                 | 20+ (build only)                          |
| NPM            | 10+                 | 10+ (build only)                          |
| Database       | SQLite (built-in)   | MySQL 8+ / MariaDB 10.6+ / PostgreSQL 14+ |
| Queue driver   | `sync`              | `redis` (recommended, dual pipelines)     |
| Cache driver   | `file`              | `redis` (recommended)                     |
| Session driver | `database`          | `redis` (recommended)                     |
| Web server     | `php artisan serve` | Nginx or Apache                           |

### Required PHP Extensions

Checked by `php artisan system:health` and the `setup:install` audit:

| Extension       | Purpose                               |
| --------------- | ------------------------------------- |
| `ext-bcmath`    | Grade and score calculations          |
| `ext-ctype`     | Character validation                  |
| `ext-curl`      | Remote media downloads, API calls     |
| `ext-fileinfo`  | MIME type detection for uploads       |
| `ext-gd`        | Image processing (thumbnails, WebP)   |
| `ext-intl`      | Internationalization and localization |
| `ext-mbstring`  | Multibyte string operations           |
| `ext-openssl`   | Encryption, HTTPS, signed URLs        |
| `ext-pdo`       | Database abstraction                  |
| `ext-tokenizer` | Blade template engine                 |
| `ext-xml`       | XML parsing, feed generation          |
| `ext-zip`       | File compression                      |

Database-specific driver (pick one matching your engine): `ext-pdo_sqlite`, `ext-pdo_mysql`, or `ext-pdo_pgsql`.

### Recommended PHP Extensions

| Extension     | Benefit                                               |
| ------------- | ----------------------------------------------------- |
| `ext-opcache` | Bytecode cache — essential for production performance |
| `ext-redis`   | High-performance cache, session, and queue backend    |
| `ext-sockets` | Required by Laravel Reverb WebSocket server           |
| `ext-pcntl`   | Process control for queue worker signals              |
| `ext-posix`   | POSIX system calls for process management             |
| `ext-imagick` | Higher quality image conversions than GD              |

---

## Application Installation Steps

### 1. Clone the Repository

```bash
git clone <repository-url> /path/to/app
cd /path/to/app
```

### 2. Install PHP Dependencies

```bash
composer install --optimize-autoloader --no-dev --no-interaction
```

### 3. Configure Environment

```bash
cp .env.example .env
# Edit .env with your settings
```

Key settings to customize:
- `APP_URL` — the public URL of your application
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_*` — database credentials for your engine
- `MAIL_*` — SMTP settings for email delivery
- `CRON_SECRET` — generate via `php -r "echo bin2hex(random_bytes(16));"`

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations

```bash
php artisan migrate --force
```

### 6. Create Storage Symlink

```bash
php artisan storage:link
```

### 7. Build Frontend Assets

```bash
npm install && npm run build
```

### 8. Run the Setup Wizard

```bash
php artisan setup:install
```

Copy the signed URL from the output and open it in your browser to complete the setup wizard.

### 9. Enable Application Caches

```bash
php artisan optimize
```

### 10. Verify Installation

```bash
php artisan system:health
```

All checks should pass (expected warnings on shared hosting: pcntl/posix unavailable, sync queue driver).

---

## Performance Tuning

### OpCache

Recommended `opcache.ini` settings for production:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=2
```

For development, disable OpCache (`opcache.enable=0`) or set `validate_timestamps=1`.

### Cache Commands

```bash
# Enable all caches at once (recommended after deployment)
php artisan optimize

# Individual caches
php artisan config:cache     # Merge config files
php artisan route:cache      # Cache route registration
php artisan view:cache       # Compile Blade templates
php artisan event:cache      # Cache event discovery

# Cache warming
php artisan system:cache-warm
```

---

## Command Reference

| Command                                         | Purpose                                                                                             |
| ----------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `php artisan setup:install`                     | Audit, provision, generate signed setup URL                                                         |
| `php artisan setup:install --check-only`        | Run environment audit without provisioning                                                          |
| `php artisan setup:install --force`             | Hard reset. Runs `migrate:fresh`, clears setup session data, regenerates token. Production-locked.  |
| `php artisan setup:reset-token`                 | Regenerate setup token (pre-install only)                                                           |
| `php artisan admin:recover`                     | Recover super admin access (auto-detects key from storage file)                                     |
| `php artisan admin:recover --key=<key>`         | Recover super admin access (manual key override)                                                    |
| `php artisan admin:recover --regenerate-file`   | Re-write the recovery key file from a provided `--key`                                              |
| `php artisan admin:recovery-path`               | Show the recovery key file location                                                                 |
| `php artisan admin:recovery-show`               | Display the stored recovery key (with confirmation)                                                 |
| `php artisan system:health`                     | Comprehensive 15-point health check                                                                 |
| `php artisan system:cache-warm`                 | Pre-warm settings, brand values, and application caches                                             |
| `php artisan system:cleanup`                    | Prune stale data (Pulse, activity log, failed jobs)                                                 |
| `php artisan storage:link`                      | Create public storage symlink                                                                       |
| `php artisan queue:work --queue=default`        | Start the default queue worker (emails, alerts, notifications)                                      |
| `php artisan queue:work --queue=documents`      | Start the documents queue worker (PDF certificates, reports)                                        |
| `php artisan optimize`                          | Cache config, routes, views, and events                                                             |
| `php artisan optimize:clear`                    | Clear all cached files                                                                              |

---

## Troubleshooting

| Symptom                   | Cause                                               | Fix                                                                     |
| ------------------------- | --------------------------------------------------- | ----------------------------------------------------------------------- |
| Blank page                | Storage not writable                                | `chmod -R 775 storage bootstrap/cache`                                  |
| 404 on media URLs         | Storage link missing                                | `php artisan storage:link`                                              |
| Vite manifest error       | Assets not built                                    | `npm run build`                                                         |
| Jobs not processing       | Queue worker not running                            | Start worker or check Supervisor config                                 |
| "Database is locked"      | SQLite concurrent writes                            | Switch to MySQL/PostgreSQL in production                                |
| 503 Service Unavailable   | Maintenance mode on                                 | `php artisan up`                                                        |
| Class "Redis" not found   | Missing `ext-redis`                                 | Install PHP Redis extension                                             |
| Setup token expired       | Token older than 60 min                             | Run `php artisan setup:reset-token` (pre-install) or `setup:install`    |
| Setup wizard 403          | Invalid or missing token, single-use consumed       | Use the signed URL from `setup:install` — tokens are single-use         |
| Setup wizard 404          | System already installed                            | System is locked. Use `php artisan setup:install --force` to hard-reset |
| Token consumed before use | Token validated in another browser tab              | Generate a new one with `setup:reset-token` (pre-install)               |

---

## Upgrading from SQLite to MySQL/PostgreSQL

When outgrowing SQLite's concurrent write capacity:

1. Provision a database server (MySQL 8+ or PostgreSQL 14+)
2. Configure `.env` with the new connection credentials
3. Run `php artisan migrate --force` to create the schema
4. Import any existing data using your database's import tools
5. Update `QUEUE_CONNECTION`, `CACHE_STORE`, and `SESSION_DRIVER` to `redis`

All features work identically regardless of database engine; only the `.env` file changes.

---

## References

| Document                                 | Contents                                                    |
| ---------------------------------------- | ----------------------------------------------------------- |
| [Getting Started](../getting-started.md) | End-to-end walkthrough from clone to wizard                 |
| [Deployment](deployment.md)              | Server environment setup (Nginx, Supervisor, Docker)        |
| [Infrastructure](infrastructure.md)      | Deployment tiers, service architecture, component sizing    |
| [Configuration](configuration.md)        | Environment variables and runtime settings                  |
| [Database](database.md)                  | Database design, engine comparison, index strategy          |
| [Backup & Recovery](backup-recovery.md)  | Backup procedures, restoration steps, retention policies    |
