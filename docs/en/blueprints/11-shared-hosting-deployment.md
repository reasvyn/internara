# Blueprint 11: Shared Hosting Deployment

## Prerequisites

| Requirement | Minimal | Notes |
|---|---|---|
| PHP | 8.4 | Most hosts support 8.x; verify with your provider |
| Database | MySQL or MariaDB | Most shared hosts provide one or both via cPanel |
| Web server | Apache with `mod_rewrite` | Standard on cPanel shared hosting |
| Storage | 200 MB + uploads | Application is ~80 MB after build |

## What Does NOT Work on Shared Hosting

| Feature | Why | Alternative |
|---|---|---|
| Queue worker | No long-running processes | `QUEUE_CONNECTION=sync` — jobs run during HTTP request |
| Reverb WebSocket | No custom servers | Page refresh shows new notifications |
| Minute-level cron | Min interval often 5–15 min | Hit `/cron/{secret}` via cron, see below |
| Redis / Memcached | Not installed | Use `file` or `database` driver instead |
| Real-time broadcasts | No WebSocket | In-app database channel works on refresh |
| ImageMagick | Usually not installed | Falls back to GD (built into PHP) |

## What Still Works

All core Internara features except those listed above. Authentication,
registration, attendance, logbook, assignments, assessments, reports,
certificates, mentoring, email notifications — all fully functional.

## Deployment Steps

### Step 1: Build Locally

On your development machine (has PHP, Composer, Node.js):

```bash
# Install production dependencies only
composer install --optimize-autoloader --no-dev

# Build frontend assets
npm install && npm run build

# Clean up dev files
rm -rf node_modules/
```

### Step 2: Upload Files

Upload the entire project to your shared hosting document root via
FTP, SFTP, or cPanel File Manager. The document root should point to
the `public/` directory. If your host uses `public_html`, either:
- Upload everything to a subdirectory and symlink `public_html` → `public`, or
- Set the document root to the `public/` folder in cPanel

### Step 3: Configure Environment

Copy `.env.sharing` to `.env` and customize:

```bash
# If you have SSH access:
cp .env.sharing .env
nano .env

# If using FTP:
# Download .env.sharing, rename to .env, edit with a text editor, upload
```

Key settings to customize:
- `APP_URL` — your domain
- `DB_*` — MySQL/MariaDB credentials (provided by your host)
- `MAIL_*` — SMTP settings for email
- `CRON_SECRET` — generate: `php -r "echo bin2hex(random_bytes(16));"`

### Step 4: Run Migrations

If your host provides SSH or a PHP CLI option in cPanel:

```bash
php artisan migrate --force
```

If not, use the web installer (if available) or run migrations locally against
the same database (for SQLite, just upload the `.sqlite` file).

### Step 5: Set Up Cron

Shared hosting cron is usually in cPanel under "Cron Jobs". Set the
minimum allowed interval to hit this URL:

```cron
GET https://your-domain.com/cron/your-cron-secret-here
```

Or use `wget`/`curl` if your host supports it:

```cron
* * * * * /usr/bin/curl -s https://your-domain.com/cron/your-cron-secret-here
* * * * * /usr/bin/wget -q -O /dev/null https://your-domain.com/cron/your-cron-secret-here
```

The `/cron/{secret}` endpoint runs the Laravel scheduler and Pulse recording.

### Step 6: Storage Link

If SSH is available:
```bash
php artisan storage:link
```

If not, create the symlink manually in cPanel File Manager or FTP:
```
public/storage → storage/app/public
```

## Environment Preset

The `.env.sharing` file contains all settings optimized for shared hosting:

```env
QUEUE_CONNECTION=sync       # Jobs run immediately
CACHE_STORE=file            # No Redis needed
SESSION_DRIVER=file         # No Redis needed
BROADCAST_CONNECTION=log    # WebSocket disabled
```

## Health Check

```bash
# Verify your setup (if SSH available)
php artisan system:health

# Expected warnings on shared hosting:
# - Recommended extensions: pcntl, posix (not available, harmless)
# - Queue: sync driver (expected, not a problem)
```

## Upgrading to VPS

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
4. Configure Supervisor for queue worker + Reverb
5. Set up minute-level cron for scheduler
6. All features become available automatically

## References

- `docs/en/adr/adr-017-shared-hosting-deployment.md` — architecture decision
- `.env.sharing` — shared hosting configuration template
- `routes/web/cron.php` — web-based scheduler trigger
- `docs/en/blueprints/02-server-deployment.md` — full VPS setup guide
