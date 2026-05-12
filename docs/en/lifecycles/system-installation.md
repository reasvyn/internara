# System Installation

**Event:** Application installation and environment provisioning.

**Phase:** 0 — System Setup

**Previous Event:** *(none — this is the entry point)*

**Next Event:** [Setup Wizard](setup-wizard.md)

---

## Overview

System Installation is the entry point to the entire Internara lifecycle. It prepares the server environment, creates the database schema, and seeds default configuration. Once complete, the application is ready for the setup wizard.

## Trigger

This event is triggered manually by a system administrator running the installation command:

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan setup:install
```

## Pre-conditions

- **PHP 8.4+** installed and available via CLI
- **Node.js 20+** installed and available via CLI
- **Composer** installed
- **Database** accessible (SQLite, MySQL 8+, MariaDB, or PostgreSQL 14+)
- **Server** meets minimum requirements: BCmath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL, GD, intl, zip extensions
- **Storage directories** are writable (`storage/`, `bootstrap/cache/`)
- No existing setup record with `is_installed = true` (prevents re-installation)

## Actors

| Actor | System Role | Real World |
|---|---|---|
| System Administrator | Server operator | IT staff, DevOps, school technical team |

## Flow

### Step 1: Install Dependencies

```bash
composer install --no-interaction --prefer-dist
npm install
```

Composer installs all PHP packages including Laravel framework, Livewire, Spatie packages, and development tools. npm installs Vite, TailwindCSS, DaisyUI, Prettier, and other frontend assets.

### Step 2: Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

The `.env.example` file is copied to `.env` and the application key is generated. Key default values:

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | Internara | Application display name |
| `APP_ENV` | local | Environment (local/production) |
| `APP_DEBUG` | true | Debug mode (disable in production) |
| `APP_URL` | http://localhost | Application base URL |
| `DB_CONNECTION` | sqlite | Database driver |
| `SESSION_DRIVER` | database | Session storage |
| `CACHE_STORE` | database | Cache backend |
| `QUEUE_CONNECTION` | database | Queue driver |
| `FILESYSTEM_DISK` | local | File storage driver |

### Step 3: Run Audit & Provisioning

```bash
php artisan setup:install
```

The `SetupInstallCommand` orchestrates two phases:

#### Phase A: Environment Audit (`EnvironmentAuditor`)

Before any changes are made, the system verifies PHP version, required extensions, storage permissions, database connectivity, and CLI capabilities. If any critical check fails, installation aborts.

#### Phase B: Provisioning (`ProvisionSystemAction`)

Executed as sequential tasks with CLI progress reporting:

1. **Ensure `.env` File** — copies `.env.example` to `.env` if the file is missing
2. **Generate App Key** — runs `php artisan key:generate` if `APP_KEY` is empty
3. **Run Migrations** — creates all database tables from migration files
4. **Run Seeders** — populates roles, permissions, and default settings
5. **Create Storage Symlink** — `public/storage → storage/app/public`
6. **Clear Caches** — config, cache, route, view caches are cleared

With `--force`, migrations use `migrate:fresh` (drops all tables first).

### Step 4: Generate Setup Token

The system generates a one-time setup URL with an encrypted token. The token expires in **1 hour**.

```
[INFO] Setup URL: http://localhost:8000/setup?setup_token=xxxxx
[INFO] This URL expires in 1 hour.
```

The token is:
- A 64-character random string
- Encrypted with the application key for storage
- Time-limited to 60 minutes from generation

### Step 5: Build Frontend Assets

```bash
npm run build
```

Compiles Vite assets (TailwindCSS, Alpine.js, Cropper.js) for production.

## State Changes

| Component | Before | After |
|---|---|---|
| Database | Empty | 64 tables created, seeded with roles/permissions/settings |
| `.env` | Copied from example | App key generated |
| Storage symlink | Missing | Created (`storage → public/storage`) |
| `setups.is_installed` | `false` | **Not yet set** (setup wizard must complete first via `FinalizeSetupAction`) |
| Setup token | — | Generated with 1-hour expiry, stored encrypted |

## Seeded Default Settings

See `database/seeders/AppSettingSeeder.php` for the authoritative list of seeded settings and their default values. Settings are seeded from the seeder at installation time.

## Seeded Roles & Permissions

See `database/seeders/RolePermissionSeeder.php` for the complete role and permission definitions. The 5 roles (`super_admin`, `admin`, `teacher`, `student`, `supervisor`) are seeded with their associated permissions at installation time.

## Error Handling

| Failure | Detection Point | Behavior |
|---|---|---|
| PHP version too low | Environment audit | Installation aborts with error message |
| Extension missing | Environment audit | Lists missing extensions, aborts |
| Database connection fails | Environment audit | Aborts with connection error details |
| Storage not writable | Environment audit | Aborts with path details |
| `.env.example` not found | Provisioning (ensure_env) | Throws RuntimeException |
| Migration fails | Provisioning (run_migrations) | Throws RuntimeException, rolls back |
| Seeding fails | Provisioning (run_seeders) | Throws RuntimeException |
| Existing installation detected | CLI command startup | Prompts for `--force` flag to reinstall |
| Force in non-local environment | CLI command startup | Rejects with error message |
| Any provisioning exception | CLI command catch block | Logs to SmartLogger, returns FAILURE exit code |

## Post-conditions

- Server environment is verified and ready
- Database has full schema with seed data (64 tables)
- Roles and permissions are configured
- Default application settings are populated
- A one-time setup URL with encrypted token is available (valid for 1 hour)
- `setups.is_installed` is still `false` (set to `true` by setup wizard finalization)
- Frontend assets are compiled (if `npm run build` was run)

## Next Steps

Proceed to [Setup Wizard](setup-wizard.md) — open the setup URL in a browser to configure the school profile, departments, and create the first super administrator.
