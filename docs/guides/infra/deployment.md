# Deployment — Options, Requirements & CI/CD

## Description

Internara is designed to be installed on the school's own infrastructure. This guide covers the
three supported deployment paths and the operational requirements for each.

For prerequisites and PHP extension requirements, see
[Installation](../installation.md#prerequisites). For application installation steps
(migrations, setup wizard, build), see
[Installation](../installation.md#run-installer).

---

## Deployment Path A: Shared Hosting (Primary)

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

### Steps

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
2. Install the same codebase following [Installation](../installation.md)
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

## Deployment Path B: VPS / Dedicated Server

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
[Installation](../installation.md#run-installer) -- build assets, run the
setup wizard, enable caches, and verify with `php artisan system:health`.

---

## Deployment Path C: Docker

### Checkout-based Docker Compose

The production `docker-compose.yml` builds the application image directly from the checkout that owns
the compose file. This enables a "single-command" deploy from any clone path without uploading source
files manually.

Key environment variables:
- BUILD_CONTEXT — build context for the `app` image. Defaults to `.` (the current checkout);
  override to build from somewhere else (e.g. another local path).
- APP_KEY — required (`base64:`-encoded Laravel key). Compose fails fast when missing.
- APP_ENV — defaults to `production`.
- APP_URL — the public origin of the app. **Defaults to `https://internara.web.id` (product demo)**;
  override with `${APP_URL:-...}` semantics for other domains.
- APP_LOCALE / APP_FAKER_LOCALE / APP_TIMEZONE — default to `id`, `id_ID`, and `Asia/Jakarta`.
- SESSION_DRIVER — defaults to `file`.
- SESSION_SECURE_COOKIE — controls the `secure` flag on session cookies. **Defaults to `true`**
  because the default `APP_URL` is `https://internara.web.id` (product demo, HTTPS). Set it to
  `false` only for plain-HTTP deployments (`http://host:port`) — with HTTPS enabled, browsers drop
  non-secure cookies and every request starts a fresh session.
- RUN_SCHEDULER — set to `true` to start the scheduler daemon inside the `app` container. **Defaults
  to `false`** so the stack idles at a very low memory footprint (fits a 1 GB RAM VPS). For a demo
  deployment, keep it `false` — no background processing runs (`QUEUE_CONNECTION=sync` means jobs
  run inline). Enable it only when scheduled tasks (announcements, backups, cache warm) are needed.
- RUN_QUEUE — set to `true` to start a queue worker inside the same container. Only useful after
  switching `QUEUE_CONNECTION` away from `sync`.

The stack is a **single `app` service** — there is no separate `web`, `db`, or `redis` container.
The compose file pins the shared-hosting drivers (`DB_CONNECTION=sqlite`,
`DB_DATABASE=/app/storage/database.sqlite`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`,
`BROADCAST_CONNECTION=log`), so the SQLite database, uploads, and logs live in the `storage_data`
volume while compiled frontend assets live in the `app_data` volume (`/app/public`). The container
caps itself at `mem_limit: 384m` / `cpus: 1.0` and restarts `unless-stopped`.

The runtime image is built from the repo-root `Dockerfile` (multi-stage `php:8.4-fpm`): the builder
stage runs `composer install --no-dev`, `npm ci`, and `npm run build`; the runtime stage ships
`/app` plus `docker/entrypoint.sh`, `docker/fpm-healthcheck`, `docker/php-fpm/www.conf`, and
`docker/php-fpm/opcache.ini`. On every start the entrypoint writes a stub `/app/.env` when missing,
refreshes `/app/public` from the image snapshot in `/opt/app-public`, repairs storage ownership,
runs `php artisan optimize:clear`, then `php artisan migrate --force` followed by
`php artisan db:seed --class=Database\Seeders\SetupSeeder --force` (both idempotent — roles, default
settings, and academic year are seeded so the setup wizard can finalize), and finally starts the
scheduler and/or queue worker when `RUN_SCHEDULER` / `RUN_QUEUE` are `true`.

### Start the Stack

1. Create an env file with runtime secrets (recommended location `/etc/internara.env`):

```bash
sudo tee /etc/internara.env > /dev/null <<'ENV'
APP_KEY=base64:REPLACE_WITH_APP_KEY
# Optional overrides (defaults are the product demo values)
APP_URL=https://your-school.sch.id
ENV
sudo chmod 600 /etc/internara.env

```

2. Start the stack using the env file:

```bash
docker compose --env-file /etc/internara.env up --build -d

```

### Serving on a domain over HTTPS

The compose stack ships **no web-server container**. The `app` service publishes PHP-FPM
(FastCGI) on `127.0.0.1:9000`, so a host-level web server (aaPanel/BT Panel, Nginx, Caddy) must
terminate TLS and speak FastCGI to that port.

> **FastCGI, not HTTP.** Do not `proxy_pass` to `127.0.0.1:9000` — that port speaks the FastCGI
> protocol, not HTTP. Use `fastcgi_pass` from a host Nginx vhost, or add your own nginx sidecar
> that shares the `app_data` / `storage_data` volumes.

The steps below use the Internara product demo setup (aaPanel vhost `internara.web.id`) as the
reference.

**1. Environment on the VPS (product demo):**

```env
APP_URL=https://internara.web.id  # product demo; override for your domain
SESSION_SECURE_COOKIE=true

```

`APP_URL` defaults to `https://internara.web.id` (product demo) in `docker-compose.yml`, so it only needs to be
overridden when the domain differs. `SESSION_SECURE_COOKIE=true` is the default; never use
`false` when serving over HTTPS.

**2. aaPanel vhost → container FastCGI:**

Resolve the volume mount points once (the project prefix matches the compose project directory):

```bash
docker volume inspect internara_app_data    -f '{{ .Mountpoint }}'   # /app/public
docker volume inspect internara_storage_data -f '{{ .Mountpoint }}'  # /app/storage

```

Then edit `/www/server/panel/vhost/nginx/{domain}.conf`. `server_name` must list every hostname
that should reach the app (apex + `www`), and the FastCGI params must forward the original scheme so
Laravel generates HTTPS URLs:

```nginx
server_name internara.web.id www.internara.web.id;

root /var/lib/docker/volumes/internara_app_data/_data;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_param SCRIPT_FILENAME /app/public/index.php;
    fastcgi_param SCRIPT_NAME /index.php;
    fastcgi_param HTTPS on;
    fastcgi_param HTTP_X_FORWARDED_PROTO https;
    include fastcgi_params;
}

location /storage/ {
    alias /var/lib/docker/volumes/internara_storage_data/_data/app/public/;
}

```

Adjust the `/var/lib/docker/volumes/...` prefixes to the paths printed by `docker volume inspect`.
Compiled assets are served from the `app_data` volume; uploaded media lives in `storage_data`
(`/app/storage/app/public`), which the `/storage/` alias exposes directly.

**3. TLS certificate covering apex + `www`:**

Let's Encrypt single-domain certs only cover the apex. Re-issue with both names (using the VPS's
acme.sh — new root account so no other site's state is touched):

```bash
export HOME=/root
acme.sh --home /root/.acme.sh --issue \
  -d internara.web.id -d www.internara.web.id \
  --webroot /www/wwwroot/internara.web.id --force

acme.sh --home /root/.acme.sh --install-cert -d internara.web.id \
  --fullchain-file /www/server/panel/vhost/cert/internara.web.id/fullchain.pem \
  --key-file /www/server/panel/vhost/cert/internara.web.id/privkey.pem \
  --reloadcmd "nginx -s reload"

```

Verify the SAN before reloading: `openssl x509 -in .../fullchain.pem -noout -text | grep -A1
"Subject Alternative Name"` must list both `DNS:internara.web.id, DNS:www.internara.web.id`.

**4. DNS:** both the apex and `www` must have `A` records pointing at the VPS public IP
(`www.internara.web.id` is a CNAME to the apex).

**5. Verify from outside the VPS (product demo):**

```bash
curl -skI https://internara.web.id -o /dev/null -w "%{http_code}\n"    # 200 — product demo
curl -skI https://www.internara.web.id -o /dev/null -w "%{http_code}\n" # 200
curl -s https://internara.web.id | grep -c localhost                     # 0 (app, not default page)
curl -s https://internara.web.id | grep -oE 'https://[^"]+\.(css|js)'    # assets served over https

```

### Continuous deployment (tag-driven release pipeline)

Pushing a SemVer tag (`v*.*.*`) triggers `.github/workflows/release.yml` — a CI/CD orchestrator
following a two-tier release lifecycle: `development` (`vX.Y.Z-dev.N`) and `production` (`vX.Y.Z`).
Tags with `alpha`, `beta`, or `rc` suffixes indicate testing versions that are not yet fully verified
and run QA checks on CI without deploying:

- `vX.Y.Z-dev.<N>` → **development**: Pint + frontend build (CI only)
- `vX.Y.Z-alpha.<N>` → **testing (alpha)**: Pint + Pest + frontend build (CI milestone only)
- `vX.Y.Z-beta.<N>` → **testing (beta)**: Pint + Pest suite with coverage gate + composer audit (CI milestone only)
- `vX.Y.Z-rc.<N>` → **testing (RC)**: Pint + Pest + architecture guards (`scan_violations`/`scan_security`/`scan_conventions`) + smoke test + release notes preview (CI milestone only)
- `vX.Y.Z` (final) → **release (production)**: all of the above + release notes artifact, then deploy to production VPS (`https://internara.web.id`)

`deploy.sh` builds from the checkout it runs in (the deploy host must be on the tag the workflow
checked out), resolves environment variables via `--env-file .env.production` (or `.env`), runs
`docker compose up -d --remove-orphans`, prunes dangling images, and gates success
on a 60s health check against `HEALTH_URL` (`https://internara.web.id` for production).

Only the workflow file and the credentials-free deploy script are committed here; production
secrets live in GitHub Actions secrets, while host runtime configs live in uncommitted `.env.production` files on the VPS.

### Hotfix branch — automated fast-track deploy

The tag-driven pipeline enforces full QA before production, which is right for ordinary releases but
takes several minutes when a critical bug needs to reach the live site immediately. For that case Internara
provides an automated **`hotfix` workflow** (`.github/workflows/hotfix.yaml`) that fast-tracks the deploy
directly to the production VPS — with automated syntax checks, code formatting, health-check verification,
and auto-rollback.

> **When to use `hotfix`:** a critical fix that must reach production *now* (e.g. a live-site outage or a
> regression blocking real users). **When NOT to use it:** for features, refactors, or anything that
> should pass the staged QA gates — those belong on `main` and a regular `v*.*.*` release.

The flow:

1. **Commit the fix on `main`** and merge to `hotfix`:

   ```bash
   git checkout hotfix && git merge main
   git push origin hotfix
   ```

2. **GitHub Actions executes automatically:**
   - **`lint`**: Validates PHP syntax, Pint formatting, and Prettier assets.
   - **`deploy`**: Deploys to the production VPS via SSH with `VERSION_TAG=hotfix` and health check.

3. **Verify:**
   `deploy.sh` reports success once `HEALTH_URL` responds 200 within 60s. Auto-rollback kicks in if the health check fails.

Because `deploy.sh` builds from the checkout, the rebuilt image always reflects the current
`hotfix` branch; no `composer.json` version bump or `v*.*.*` tag is required. The fix is still
committed to `main` first so the branch does not drift.

### Low-memory profile (1 GB RAM VPS)

The default compose is tuned to run on a **1 GB RAM** VPS:

- **Single container.** There is no MySQL, Redis, nginx, or queue container — PHP-FPM runs in the
  `app` service and the database is a SQLite file in the `storage_data` volume.
- **No background processes.** `RUN_SCHEDULER` defaults to `false` and `QUEUE_CONNECTION=sync` runs
  jobs inline — the `app` container only runs PHP-FPM. Enable the scheduler only if scheduled tasks
  are actually needed.
- **Container memory cap.** `app` is capped at `384m` with `cpus: 1.0`, so a runaway request cannot
  OOM the host; the cap plus the host OS fits comfortably in 1 GB.
- **PHP-FPM is worker-capped.** `docker/php-fpm/www.conf` limits the pool to `pm.max_children=4`
  (start 3, min spare 2, max spare 3). Each worker's
  `php_admin_value[memory_limit] = 128M` bounds a single request's ceiling.
- **OpCache is tuned.** `docker/php-fpm/opcache.ini` enables OpCache with a 64 MB shared segment and
  `validate_timestamps=1` (revalidate every request), so image rebuilds take effect without a
  container restart.
- **Storage permissions are repaired at boot.** The entrypoint creates the framework dirs
  (`storage/framework/cache/data`, `sessions`, `views`, `logs`, `bootstrap/cache`) and `chown`s
  `/app/storage` to `www-data` before PHP-FPM starts, fixing the intermittent cache write
  `Permission denied` seen when those dirs were created root-owned.
- **Multi-stage image.** The runtime `app` image excludes `node_modules`, build toolchain, and the Git
  history, keeping the image lean and build memory low.
- **Build cache stays small.** `.dockerignore` keeps the build context lean; `composer`/`npm`
  dependency layers are copied and installed from lockfiles **before** the rest of the source, so they
  are only rebuilt when a lockfile changes; BuildKit `--mount=type=cache` reuses the Composer and npm
  download caches between builds; `node_modules` is removed at the end of the builder stage so it never
  enters the runtime image. The Docker build cache is cleaned weekly by
  `.github/workflows/docker-cache-cleanup.yml`, which removes only BuildKit cache and unused images
  older than 168 hours, under the same deployment lock.

To opt back in to background processing, export `RUN_SCHEDULER=true` (and `RUN_QUEUE=true` after
changing `QUEUE_CONNECTION` away from `sync`) when running compose.

### Private repositories (SSH deploy key)

The images build from the checkout, so a private repository only needs its clone on the deploy host
with a read-only deploy key on GitHub. Add the private key to the host (owner: root) at
`~/.ssh/deploy_key` with permission 600, then clone the repo normally:

```bash
eval "$(ssh-agent -s)" && ssh-add ~/.ssh/deploy_key
git clone git@github.com:owner/repo.git
cd repo && docker compose --env-file /etc/internara.env up --build -d

```

If using a CI/CD runner or systemd unit to start the stack on boot, ensure the ssh-agent is available
to the docker build process (or use a build service with repository access).

### Secrets management

Recommended: store environment secrets in a file outside the project (e.g. `/etc/internara.env`) with
mode 600 and owner root. The compose command above loads that file. Alternatives:
- Docker Secrets (requires swarm/kubernetes)
- External secrets manager (Vault, AWS Secrets Manager)

Generate the application key on the VPS:

```bash
APP_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")

```

Since the container uses SQLite, no database password is required. Do not commit
`/etc/internara.env` or other secret files to Git.

### Notes & caveats

- The stack no longer builds a separate `web` image. `.docker/nginx.conf` and `.docker/nginx.Dockerfile`
  remain in the repo as a reference sidecar config, but the production compose publishes FastCGI on
  `127.0.0.1:9000` and expects a host web server to terminate TLS.
- Build from Git pulls the repository at build-time — changes in the running container won't persist
  unless stored in mounted volumes (`storage_data` holds the SQLite database, uploads, and logs;
  `app_data` holds `/app/public`).
- The compose-based deployment requires Docker Engine and a reasonably recent Docker Compose that
  supports BuildKit.

### Development with Laravel Sail

```bash
# Start Sail environment (SQLite + database queue worker)
./vendor/bin/sail up -d

```

`docker-compose.dev.yml` defines two services — `laravel.test` (PHP 8.4, port `APP_PORT`, default 80)
and `queue` (`queue:work` against the database queue). Both use SQLite; there is no MySQL service.

See `docker-compose.dev.yml` for the Sail configuration.

---

## Production Checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production` in `.env` (`APP_DEBUG` can be left unset — it
  defaults to `false` when `APP_ENV=production`)
- [ ] `APP_KEY` set to a random 32-character base64 string
- [ ] Database migrated: `php artisan migrate --force` (runs automatically via the Docker entrypoint)
- [ ] Public storage link exists: `php artisan storage:link` (created in the Docker build)
- [ ] Scheduler running: set `RUN_SCHEDULER=true` in the Docker env when scheduled tasks are needed (default is `false` — see low-memory profile); or system cron/webhook on shared hosting
- [ ] Queue: `QUEUE_CONNECTION=sync` is the default — no worker needed; add a queue backend and set `RUN_QUEUE=true` for async workers (Tier 2+)
- [ ] OpCache enabled and configured
- [ ] All caches warmed: `php artisan optimize`
- [ ] Frontend assets built: `npm run build`
- [ ] HTTPS configured at the web server or reverse proxy
- [ ] `php artisan system:health` passes with no FAIL results
- [ ] Backup automation configured (see [Backup & Recovery](../backup-recovery.md))
- [ ] Monitoring set up (see [Observability](../system-observability.md))

---

## References

- [Installation](../installation.md) -- prerequisites, command reference, troubleshooting
- [Infrastructure](infrastructure.md) -- tier-based infrastructure design, scaling, sizing
- [Configuration](configuration.md) -- environment variables and runtime settings
- [Queue](queue.md) -- worker management, job lifecycle, enterprise scaling
- [Media Library](media-library.md) -- file uploads, S3 storage, image conversions
- [Backup & Recovery](../backup-recovery.md) -- account recovery, database dumps, restoration
- [Observability](../system-observability.md) -- logging, Pulse, health checks
---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `502 Bad Gateway` from the host web server | Host nginx cannot reach the container's FastCGI port | Check `docker compose ps` shows `app` healthy, then confirm `fastcgi_pass 127.0.0.1:9000` and that port `9000` is published |
| Assets return 404 while pages render | `root` in the host vhost does not point at the `app_data` volume | Resolve it with `docker volume inspect internara_app_data -f '{{ .Mountpoint }}'` and use that path |
| "Database is locked" | SQLite concurrent writes | Keep `QUEUE_CONNECTION=sync`, reduce concurrent writers, or move to MySQL/PostgreSQL |
| Sessions reset on every request | `SESSION_SECURE_COOKIE=true` served over plain HTTP | Serve over HTTPS or set `SESSION_SECURE_COOKIE=false` for HTTP-only demos |
| `419 Page Expired` | Session cookie not persisted (driver, domain, or secure flag) | Verify `SESSION_DRIVER`, `APP_URL`, and `SESSION_SECURE_COOKIE` match the public origin |
| `Permission denied` writing to `storage/` | Volume created root-owned | Restart the container (entrypoint re-`chown`s `/app/storage`) or fix ownership on the volume |
| Stale config/views after a deploy | Cached config or compiled views | The entrypoint runs `optimize:clear`; run it manually with `docker compose exec app php artisan optimize:clear` |
| Uploads 404 but exist in storage | Host nginx only serves `app_data` | Add the `/storage/` alias to the `storage_data` volume (see the HTTPS section above) |
