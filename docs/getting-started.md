# Getting Started

> **Last updated:** 2026-06-08
>
> End-to-end walkthrough from cloning the repository to completing the setup wizard. Everything you need to get Internara running on your server.

---

## Prerequisites

| Requirement | Development         | Production                |
| ----------- | ------------------- | ------------------------- |
| PHP         | 8.4.0+              | 8.4.0+                    |
| Composer    | 2.5+                | 2.5+                      |
| Node.js     | 20+                 | 20+ (build only)          |
| NPM         | 10+                 | 10+ (build only)          |
| Database    | SQLite (built-in)   | MySQL 8+ / PostgreSQL 14+ |
| Queue       | `database` driver   | Redis (recommended)       |
| Cache       | `database` driver   | Redis (recommended)       |
| Web server  | `php artisan serve` | Nginx / Apache            |

**Required PHP extensions:** `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip`, plus the database driver matching your chosen engine (`pdo_sqlite`, `pdo_mysql`, or `pdo_pgsql`).

---

## Quick Start (Development)

```bash
# 1. Clone and enter the repository
git clone https://github.com/your-org/internara.git
cd internara

# 2. Install PHP dependencies
composer install

# 3. Install and build frontend assets
npm install && npm run build

# 4. Run the installer
php artisan setup:install

# 5. Copy the signed URL from the output, then start the server
php artisan serve
# In another terminal:
php artisan queue:work
```

Open the signed URL from step 4 in your browser to complete the 6-step setup wizard.

---

## Step-by-Step Guide

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-org/internara.git
cd internara
```

### Step 2: Install PHP Dependencies

```bash
# Development
composer install

# Production
composer install --optimize-autoloader --no-dev
```

### Step 3: Build Frontend Assets

```bash
npm install
npm run build

# For development with hot module replacement:
npm run dev
```

### Step 4: Run the Installer

The `setup:install` command handles the entire technical installation:

```bash
php artisan setup:install
```

This command will:

1. **Audit the environment** — checks PHP version (≥ 8.4.0), required extensions, storage permissions, database connectivity, and CLI tools. Stops with detailed output if any check fails.
2. **Provision the system** — creates `.env` from `.env.example` if missing, generates `APP_KEY`, runs database migrations, creates the storage symlink, and seeds initial data.
3. **Generate a setup URL** — produces a one-time signed URL with an expiring token, scoped to your `APP_URL`:

    ```
    https://internara.sekolah.sch.id/setup?setup_token=a1b2c3d4...
    ```

After the command completes, you will see the URL printed in the terminal along with the token and its expiry time (default: 60 minutes).

> **Options:**
> - `--check-only` — run the environment audit without installing
> - `--force` — reinstall even if already installed (development only)

### Step 5: Start Background Processes

```bash
# Development — run these in separate terminals:
php artisan serve       # Web server
php artisan queue:work  # Queue worker
```

Or use the all-in-one command:

```bash
composer run dev
```

**Production** requires Supervisor (or systemd) for queue workers, the scheduler cron entry, and optionally Reverb for WebSocket support. See [Installation](infrastructure/installation.md#required-background-processes).

### Step 6: Complete the Setup Wizard

Open the signed URL from Step 4 in your browser. The URL includes a `setup_token` parameter that authorizes access. The 6-step wizard guides you through:

| Step | What You Configure                          |
| ---- | ------------------------------------------- |
| 1    | Environment audit review                    |
| 2    | Super admin account (email + password)      |
| 3    | School details (name, NPSN, address, email) |
| 4    | First department / study program            |
| 5    | Final review and confirm                    |
| 6    | Setup complete                              |
| 7    | Recovery key — **save this securely**       |

Follow the [Setup Wizard](foundation/setup-wizard.md) guide for a detailed walkthrough.

---

## Command Reference

| Command                                 | Purpose                                                         |
| --------------------------------------- | --------------------------------------------------------------- |
| `php artisan setup:install`             | Audit, provision, and generate setup URL                        |
| `php artisan setup:reset-token`         | Regenerate setup token (before installation)                    |
| `php artisan admin:recover`             | Recover super admin access (auto-detects key from storage file) |
| `php artisan admin:recover --key=<key>` | Recover super admin access (manual key override)                |
| `php artisan admin:recovery-path`       | Show the recovery key file location                             |
| `php artisan admin:recovery-show`       | Display the stored recovery key (with confirmation)             |
| `php artisan system:health`             | Verify all system requirements                                  |

---

## Next Steps

| Document                                           | What It Covers                                              |
| -------------------------------------------------- | ----------------------------------------------------------- |
| [Installation](infrastructure/installation.md)     | Detailed deployment options, server config, troubleshooting |
| [Setup Wizard](foundation/setup-wizard.md)                    | Complete walkthrough of all 7 wizard steps                  |
| [Post-Setup](foundation/post-setup.md)                        | First actions after the wizard completes                    |
| [Architecture](architecture.md)                    | System design, modules, layers                              |
| [Infrastructure](infrastructure/infrastructure.md) | Deployment options, background processes, storage           |
