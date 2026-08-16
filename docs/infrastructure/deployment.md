# Deployment — Options, Requirements & CI/CD

> **Last updated:** 2026-08-16 **Changes:** feat — CI/CD moved back into this repo; direct
> build-and-deploy workflow (`.github/workflows/build-and-deploy.yml`) replaces the private-repo
> `repository_dispatch` pipeline; deploy script prunes build cache to bound VPS disk usage

## Description

Internara is designed to be installed on the school's own infrastructure. This guide covers the
three supported deployment paths and the operational requirements for each.

For prerequisites and PHP extension requirements, see
[Installation](../foundation/installation.md#prerequisites). For application installation steps
(migrations, setup wizard, build), see
[Installation](../foundation/installation.md#run-installer).

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
2. Install the same codebase following [Installation](../foundation/installation.md)
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
[Installation](../foundation/installation.md#run-installer) -- build assets, run the
setup wizard, enable caches, and verify with `php artisan system:health`.

---

## Deployment Path C: Docker

### Git-based Docker Compose (zero-copy deploy)

The production `docker-compose.yml` supports building service images directly from the project's
Git repository. This enables a "single-command" VPS deployment without cloning or uploading source
files manually.

Key environment variables:
- GIT_URL — repository URL with optional ref, e.g. `https://github.com/owner/repo.git#main` or
  `git@github.com:owner/repo.git#main`. Defaults to the canonical repo.
- APP_KEY — required (`base64:`-encoded Laravel key). Compose fails fast when missing.
- DB_PASSWORD — required. Compose fails fast when missing.
- NGINX_PORT — host port for the nginx service (default 80)
- SESSION_SECURE_COOKIE — controls the `secure` flag on session cookies. **Defaults to `true`**
  because the default `APP_URL` is `https://internara.web.id` (HTTPS). Set it to `false` only for
  plain-HTTP deployments (`http://host:port`) — with HTTPS enabled, browsers drop non-secure cookies
  and every request starts a fresh session.
- APP_URL — the public origin of the app. **Defaults to `https://internara.web.id`**; override with
  `${APP_URL:-...}` semantics for other domains.
- RUN_SCHEDULER — set to `true` to start the scheduler daemon inside the `app` container. **Defaults
  to `false`** so the stack idles at a very low memory footprint (fits a 1 GB RAM VPS). For a demo
  deployment, keep it `false` — no background processing runs at all (`QUEUE_CONNECTION=sync` means
  jobs run inline). Enable it only when scheduled tasks (announcements, backups, cache warm) are
  needed.

The stack is minimal: three services — `app`, `web`, and `db`. There is no Redis by default; the app
runs the shared-hosting drivers (`QUEUE_CONNECTION=sync`, `CACHE_STORE=file`,
`SESSION_DRIVER=database`), which is sufficient for single-tenant low-volume workloads. The `app`
entrypoint runs `php artisan migrate --force` followed by
`php artisan db:seed --class=Database\Seeders\SetupSeeder --force` on start (both idempotent — the
roles, default settings, and academic year are seeded so the setup wizard can finalize) and starts
the scheduler daemon only when
`RUN_SCHEDULER=true`; set `RUN_QUEUE=true` to also start a queue worker when a Redis service is
added. Services that build from Git: `app` and `web`. The `web` image is built using
`.docker/nginx.Dockerfile` and the repo's `./.docker/nginx.conf` is copied into the image, so no
host bind-mount is required.

Important: Docker BuildKit is required to build directly from Git. Enable it in the shell:

```bash
export DOCKER_BUILDKIT=1
```

### Start the Stack (public repo)

1. Create an env file on the VPS with runtime secrets (recommended location `/etc/internara.env`):

```bash
sudo tee /etc/internara.env > /dev/null <<'ENV'
DB_PASSWORD=REPLACE_WITH_STRONG_PASSWORD
APP_KEY=base64:REPLACE_WITH_APP_KEY
GIT_URL=https://github.com/owner/repo.git#main
ENV
sudo chmod 600 /etc/internara.env
```

2. Start the stack using the env file:

```bash
DOCKER_BUILDKIT=1 docker compose --env-file /etc/internara.env up --build -d
```

### Serving on a domain over HTTPS (behind a reverse proxy)

The compose stack's `web` service is an nginx container, but in production you typically front it
with a host-level reverse proxy (aaPanel/BT Panel, Caddy, Nginx on the host) that terminates TLS.
The steps below use the Internara production setup (aaPanel vhost `internara.web.id` proxying to
`http://127.0.0.1:8080`) as the reference.

**1. Environment on the VPS:**

```env
APP_URL=https://internara.web.id
SESSION_SECURE_COOKIE=true
NGINX_PORT=8080
```

`APP_URL` defaults to `https://internara.web.id` in `docker-compose.yml`, so it only needs to be
overridden when the domain differs. `SESSION_SECURE_COOKIE=true` is the default; never use
`false` when serving over HTTPS.

**2. aaPanel vhost → Docker:**

Edit `/www/server/panel/vhost/nginx/{domain}.conf`. The `server_name` must list every hostname that
should reach the app (apex + `www`), and the proxy location must forward the original scheme so
Laravel generates HTTPS URLs:

```nginx
server_name internara.web.id www.internara.web.id;

location ^~ / {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;   # required for HTTPS URL generation
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
}
```

If the vhost declares a `proxy_cache_path`, create its directory before reloading nginx
(`mkdir -p /www/wwwroot/{domain}/proxy_cache_dir`) or `nginx -t` will fail.

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

**5. Verify from outside the VPS:**

```bash
curl -skI https://internara.web.id -o /dev/null -w "%{http_code}\n"    # 200
curl -skI https://www.internara.web.id -o /dev/null -w "%{http_code}\n" # 200
curl -s https://internara.web.id | grep -c localhost                     # 0 (app, not default page)
curl -s https://internara.web.id | grep -oE 'https://[^"]+\.(css|js)'    # assets served over https
```

### Continuous deployment (direct build-and-deploy)

Pushes to `docker-deploy` are auto-deployed to the production server by
`.github/workflows/build-and-deploy.yml` in this repo. The workflow has two jobs:

1. **build** — verifies both Docker images (`app` + `web`) compile from the current source.
2. **deploy** — SSHs to the VPS (secrets `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`), syncs the local
   clone (`git fetch origin docker-deploy && git reset --hard origin/docker-deploy`), then runs
   `.github/scripts/deploy.sh`: `docker compose up -d --build --remove-orphans`, followed by image
   pruning and a 60s health check against `HEALTH_URL`.

Build cache is pruned on every deploy (`docker builder prune --keep-storage <limit>`, default `2g`)
so the Docker build cache stays bounded on the low-disk VPS instead of growing into the multi-GB
range — dangling images are pruned too. Only the deploy workflow file and the credentials-free
deploy script are committed here; production secrets live in GitHub Actions secrets, never in the
repo.

### Low-memory profile (1 GB RAM VPS)

The default compose is tuned to run on a **1 GB RAM** VPS:

- **No background processes.** `RUN_SCHEDULER` defaults to `false` and `QUEUE_CONNECTION=sync` runs
  jobs inline — the `app` container only runs PHP-FPM. Enable the scheduler only if scheduled tasks
  are actually needed.
- **MySQL is memory-capped.** The `db` service overrides MySQL defaults (`innodb_buffer_pool_size=64M`,
  `performance_schema=OFF`, `max_connections=50`, etc.) cutting idle memory from ~460 MB to ~125 MB.
- **Per-service memory limits.** `app` is capped at `256m`, `db` at `384m`, `web` at `64m` — the total
  hard ceiling (~700 MB) plus the host OS fits comfortably in 1 GB and no service can OOM the host.
- **PHP-FPM is worker-capped.** `docker/php-fpm/www.conf` limits the pool to `pm.max_children=2`
  (start 1, max spare 2) — enough for demo/school-scale traffic and keeps resident memory small.
- **Multi-stage image.** The runtime `app` image excludes `node_modules`, build toolchain, and the Git
  history, keeping the image lean and build memory low.
- **Build cache stays small.** `.dockerignore` keeps the build context lean; `composer`/`npm`
  dependency layers are copied and installed from lockfiles **before** the rest of the source, so they
  are only rebuilt when a lockfile changes; BuildKit `--mount=type=cache` reuses the Composer and npm
  download caches between builds; `node_modules` is removed at the end of the builder stage so it never
  enters the runtime image. Without these, every source commit re-installs all dependencies and the
  Docker build cache grows into the multi-GB range. Prune stale layers with `docker builder prune -af`.

To opt back in to background processing, export `RUN_SCHEDULER=true` (and add a `redis` service +
`RUN_QUEUE=true` for async queues) when running compose.

### Private repositories (SSH deploy key)

For private repositories, create a read-only deploy key on GitHub and add the private key to the
VPS (owner: root) at `~/.ssh/deploy_key` with permission 600. Then, before `docker compose` run the
ssh-agent so the Docker build process can access the repo:

```bash
eval "$(ssh-agent -s)" && ssh-add ~/.ssh/deploy_key
# Then run the compose command using SSH URL
export GIT_URL='git@github.com:owner/repo.git#main'
DOCKER_BUILDKIT=1 docker compose --env-file /etc/internara.env up --build -d
```

If using a CI/CD runner or systemd unit to start the stack on boot, ensure the ssh-agent is available
to the docker build process (or use a build service with repository access).

### DB_PASSWORD and secrets management

Recommended: store environment secrets in a file outside the project (e.g. `/etc/internara.env`) with
mode 600 and owner root. The compose command above loads that file. Alternatives:
- Docker Secrets (requires swarm/kubernetes)
- External secrets manager (Vault, AWS Secrets Manager)

Generate a secure password on the VPS:

```bash
DB_PASSWORD=$(openssl rand -base64 32)
```

Do not commit `/etc/internara.env` or other secret files to Git.

### Notes & caveats

- The `web` image now builds from the repository and expects `./.docker/nginx.conf` to exist in the
  repo. If you prefer a host-provided nginx.conf, revert to the previous bind-mount approach.
- Build from Git pulls the repository at build-time — changes in the running container won't persist
  unless stored in mounted volumes (e.g. `storage_data` volume is used for uploaded files).
- The compose-based deployment requires Docker Engine and a reasonably recent Docker Compose that
  supports BuildKit git contexts.

### Development with Laravel Sail

```bash
# Start Sail environment (SQLite + queue)
./vendor/bin/sail up -d

# Or with MySQL instead of SQLite:
./vendor/bin/sail up -d -s mysql
```

See `docker-compose.dev.yml` for the Sail configuration.

---

## Production Checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production` in `.env` (`APP_DEBUG` can be left unset — it
  defaults to `false` when `APP_ENV=production`)
- [ ] `APP_KEY` set to a random 32-character base64 string
- [ ] Database migrated: `php artisan migrate --force` (runs automatically via the Docker entrypoint)
- [ ] Public storage link exists: `php artisan storage:link` (created in the Docker build)
- [ ] Scheduler running: set `RUN_SCHEDULER=true` in the Docker env when scheduled tasks are needed (default is `false` — see low-memory profile); or system cron/webhook on shared hosting
- [ ] Queue: `QUEUE_CONNECTION=sync` is the default — no worker needed; enable `RUN_QUEUE=true` + Redis for async workers (Tier 2+)
- [ ] OpCache enabled and configured
- [ ] All caches warmed: `php artisan optimize`
- [ ] Frontend assets built: `npm run build`
- [ ] HTTPS configured at the web server or reverse proxy
- [ ] `php artisan system:health` passes with no FAIL results
- [ ] Backup automation configured (see [Backup & Recovery](../foundation/backup-recovery.md))
- [ ] Monitoring set up (see [Observability](../foundation/system-observability.md))

---

## References

- [Installation](../foundation/installation.md) -- prerequisites, command reference, troubleshooting
- [Infrastructure](infrastructure.md) -- tier-based infrastructure design, scaling, sizing
- [Configuration](configuration.md) -- environment variables and runtime settings
- [Queue](queue.md) -- worker management, job lifecycle, enterprise scaling
- [Media Library](media-library.md) -- file uploads, S3 storage, image conversions
- [Backup & Recovery](../foundation/backup-recovery.md) -- account recovery, database dumps, restoration
- [Observability](../foundation/system-observability.md) -- logging, Pulse, health checks
