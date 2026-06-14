# Chapter 1: Installation

> **Last updated:** 2026-06-14

In this chapter, you'll prepare your server, install Internara, and run the initial setup command.

By the end of this chapter, you'll have a signed URL to open in your browser — which leads to the
[Setup Wizard](02-setup-wizard.md).

---

## 1.1 Check Requirements

Your server needs these to run Internara:

| Requirement | Minimum Version |
|---|---|
| PHP | 8.4.0 or higher |
| Composer | 2.5 or higher |
| Node.js | 20 or higher |
| npm | 10 or higher |
| Database | SQLite (built-in), MySQL 8+, MariaDB 10+, or PostgreSQL 15+ |

### Required PHP Extensions

These are checked automatically during installation. Most are already enabled on modern PHP setups:

- `bcmath` — score calculations
- `ctype` — input validation
- `curl` — API calls
- `fileinfo` — file uploads
- `gd` — image thumbnails
- `intl` — multi-language support
- `mbstring` — international characters
- `openssl` — encryption
- `pdo` — database access
- `tokenizer` — template engine
- `xml` — document generation
- `zip` — file compression

Plus one database driver matching your chosen engine: `pdo_sqlite`, `pdo_mysql`, or `pdo_pgsql`.

### Optional but Recommended

| Extension | Why |
|---|---|
| `opcache` | Makes your site **much** faster in production |
| `redis` | High-speed cache, session storage, and queue backend |
| `sockets` | Required for real-time WebSocket features (Laravel Reverb) |

---

## 1.2 Get the Code

```bash
git clone <repository-url> /var/www/internara
cd /var/www/internara
```

Replace `<repository-url>` with the actual repository URL you received from your IT team or
downloaded package.

If you don't have `git` installed, extract the downloaded ZIP archive into `/var/www/internara`
(or any directory you prefer).

---

## 1.3 Install Dependencies

Install the PHP packages:

```bash
composer install --no-interaction
```

This downloads and sets up all the libraries Internara needs. It may take a minute or two.

Then install the frontend assets:

```bash
npm install
```

---

## 1.4 Configure Your Environment

Create the environment file from the template:

```bash
cp .env.example .env
```

Open `.env` in a text editor and update these settings:

| Setting | What to Put | Example |
|---|---|---|
| `APP_URL` | The address where Internara will be accessed | `https://internara.sekolah.sch.id` |
| `APP_ENV` | Set to `production` for live use | `production` |
| `APP_DEBUG` | Set to `false` for live use | `false` |
| `DB_*` | Your database credentials | See below |
| `MAIL_*` | SMTP settings for email notifications | (Optional) |
| `CRON_SECRET` | Run `php -r "echo bin2hex(random_bytes(16));"` and paste the output | A random 32-character string |

### Database Quick Start

For **testing or small-scale use**, SQLite works with zero configuration — just leave the `DB_*`
settings as they are.

For **production**, use MySQL or PostgreSQL. Update these in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

## 1.5 Generate the App Key

```bash
php artisan key:generate
```

This creates a unique encryption key for your installation. You only need to do this once.

---

## 1.6 Run Database Migrations

```bash
php artisan migrate --force
```

This creates the database tables. It's safe — no data is lost if you run it again.

---

## 1.7 Create the Storage Link

```bash
php artisan storage:link
```

This makes uploaded files (photos, documents) accessible through the web server.

---

## 1.8 Build Frontend Assets

```bash
npm run build
```

This compiles the CSS and JavaScript. It may take 30–60 seconds.

---

## 1.9 Run the Installer

```bash
php artisan setup:install
```

The installer will:

1. **Audit your environment** — checks PHP version, extensions, permissions
2. **Run migrations** — sets up the database schema
3. **Generate a signed URL** — a one-time link to the setup wizard

Copy the signed URL from the output. It looks like:

```
https://internara.sekolah.sch.id/setup?setup_token=a1b2c3d4e5f6...
```

> **Important:** This URL expires in **60 minutes**. If it expires, run `php artisan setup:reset-token`
> to generate a new one (only before the wizard is completed).

Open this URL in your browser to continue with the [Setup Wizard](02-setup-wizard.md).

> **For production:** After the wizard completes, run `php artisan optimize` to enable all caches.

---

## 1.10 Verify Everything

```bash
php artisan system:health
```

This runs a 15-point health check. All items should show green (pass) or yellow (warning).
Red (fail) items must be resolved before continuing.

---

## Troubleshooting

| Problem | Likely Cause | Solution |
|---|---|---|
| Blank white page | Storage not writable | `chmod -R 775 storage bootstrap/cache` |
| Images not loading | Storage link missing | `php artisan storage:link` |
| "Vite manifest not found" | Assets not built | `npm run build` |
| "Database is locked" | Using SQLite with multiple users | Switch to MySQL or PostgreSQL |
| Setup URL shows 403 | Token invalid or expired | Run `php artisan setup:reset-token` again |
| Setup URL shows 404 | Already installed | Run `php artisan setup:install --force` to reset |

---

---

**← Previous:** [User Manual Index](00-guide-index.md)
**Next →** [Chapter 2: Setup Wizard](02-setup-wizard.md)
