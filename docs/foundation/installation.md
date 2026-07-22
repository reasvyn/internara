# Installation — Server Preparation & CLI Provisioning

> **Last updated:** 2026-07-22 **Changes:** feat — rewrite to developer reference; merge from `docs/guide/01-installation.md`

## Description

CLI-driven installation procedure for Internara. Covers prerequisite verification, dependency
installation, environment configuration, database provisioning, and signed URL generation for the
[Setup Wizard](setup-wizard.md).

---

## Prerequisites

### Runtime Requirements

| Requirement | Minimum | Recommended |
| ----------- | ------- | ----------- |
| PHP | 8.4.0 | 8.4+ with OpCache |
| Composer | 2.0 | 2.5+ |
| Node.js | 20 | 20+ LTS |
| npm | 10 | 10+ |
| Database | SQLite (built-in) | MySQL 8+ / PostgreSQL 15+ |

### Required PHP Extensions

| Extension | Purpose |
| --------- | ------- |
| `bcmath` | Score calculations |
| `ctype` | Input validation |
| `curl` | API calls |
| `fileinfo` | File uploads |
| `gd` | Image thumbnails |
| `intl` | Multi-language support |
| `mbstring` | International characters |
| `openssl` | Encryption |
| `pdo` | Database access (plus `pdo_sqlite`, `pdo_mysql`, or `pdo_pgsql`) |
| `tokenizer` | Template engine |
| `xml` | Document generation |
| `zip` | File compression |

### Optional Extensions

| Extension | Impact |
| --------- | ------ |
| `opcache` | Significant production performance improvement |
| `redis` | High-speed cache, session, queue backend |
| `sockets` | Required for Laravel Reverb WebSocket support |

---

## Installation Procedure

### Clone & Install Dependencies

```bash
git clone <repository-url> /var/www/internara
cd /var/www/internara
composer install --no-interaction
npm install
```

### Environment Configuration

```bash
cp .env.example .env
```

Key `.env` variables:

| Variable | Required | Example | Notes |
| -------- | -------- | ------- | ----- |
| `APP_URL` | Yes | `https://internara.sekolah.sch.id` | Must match web server configuration |
| `APP_ENV` | Yes | `production` | `local` for development |
| `APP_DEBUG` | Yes | `false` | `true` only in development |
| `DB_CONNECTION` | Yes | `sqlite` | `sqlite`, `mysql`, or `pgsql` |
| `DB_HOST` | MySQL/PG | `127.0.0.1` | Not needed for SQLite |
| `DB_PORT` | MySQL/PG | `3306` / `5432` | Not needed for SQLite |
| `DB_DATABASE` | MySQL/PG | `internara` | Database name |
| `DB_USERNAME` | MySQL/PG | `root` | Database user |
| `DB_PASSWORD` | MySQL/PG | `***` | Database password |
| `MAIL_HOST` | Optional | `smtp.gmail.com` | For email notifications |
| `CRON_SECRET` | Yes | *(random hex)* | `php -r "echo bin2hex(random_bytes(16));"` |

### Provisioning

```bash
php artisan key:generate          # Generate APP_KEY
php artisan migrate --force       # Run migrations (idempotent)
php artisan storage:link          # Create public/storage symlink
npm run build                     # Compile Vite assets
```

### Run Installer

```bash
php artisan setup:install
```

The installer performs:

1. **Environment audit** — PHP version, extensions, permissions
2. **Migration run** — ensures all migrations are current
3. **Signed URL generation** — one-time URL for the setup wizard (60-minute TTL)

Output:

```
https://internara.sekolah.sch.id/setup?setup_token=a1b2c3d4e5f6...
```

If the URL expires: `php artisan setup:reset-token`

### Post-Install (Production)

```bash
php artisan optimize              # Cache config, routes, views, events
```

### Verification

```bash
php artisan system:health         # 15-point health check
php artisan setup:install --check-only  # Audit without provisioning
```

---

## Database Quick Start

| Engine | Setup |
| ------ | ----- |
| **SQLite** | Zero-config. Leave `DB_*` as defaults. Database auto-created on first migration. |
| **MySQL** | Set `DB_CONNECTION=mysql` and fill `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| **PostgreSQL** | Set `DB_CONNECTION=pgsql` and fill the same variables. Port default: `5432`. |

---

## Installer Commands

| Command | Description |
| ------- | ----------- |
| `php artisan setup:install` | Full provision: audit + migrate + signed URL |
| `php artisan setup:install --force` | Wipe database and start fresh |
| `php artisan setup:install --check-only` | Audit environment without installing |
| `php artisan setup:reset-token` | Regenerate signed URL (before wizard completion) |

---

## Troubleshooting

| Problem | Likely Cause | Resolution |
| ------- | ------------ | ---------- |
| Blank white page | Storage not writable | `chmod -R 775 storage bootstrap/cache` |
| Images not loading | Storage link missing | `php artisan storage:link` |
| "Vite manifest not found" | Assets not built | `npm run build` |
| "Database is locked" | SQLite concurrent writes | Switch to MySQL/PostgreSQL |
| Setup URL 403 | Token invalid or expired | `php artisan setup:reset-token` |
| Setup URL 404 | Already installed | `php artisan setup:install --force` |
| "Class not found" | Dependencies not installed | `composer install --no-interaction` |
| "APP_KEY not set" | Key not generated | `php artisan key:generate` |
| "PHP version X required" | Wrong PHP version | Install PHP 8.4+ |
| "Extension X not found" | Missing PHP extension | Install the listed extension |

---

## Quick References

- `app/Setup/Console/Commands/SetupInstallCommand.php` — Installer command
- `app/Setup/Console/Commands/SetupResetTokenCommand.php` — Token regeneration
- `app/Setup/Services/EnvironmentAuditor.php` — Environment audit logic
- `.env.example` — Default configuration template
- [Setup Wizard](setup-wizard.md) — Next step after installation
- [Post-Setup](post-setup.md) — Configuration after wizard completion
- [System Health](system-health.md) — Health check reference
- `docs/specs/installation.md` — Feature specification
