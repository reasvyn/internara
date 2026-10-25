# Docker — Container Configuration & Setup Environments

## Description
Internara provides three Docker environments for different use cases.

## 1. Development (Laravel Sail)

Full-featured development environment with a database queue worker and Vite HMR.

```bash
# Start with Sail
docker compose -f docker-compose.dev.yml up -d

# Or run Sail shorthand (if alias configured)
./vendor/bin/sail up

# Run migrations
docker compose -f docker-compose.dev.yml exec laravel.test php artisan migrate --seed

# Build frontend
docker compose -f docker-compose.dev.yml exec laravel.test npm run dev
```

**Includes:** `laravel.test` (PHP 8.4) + `queue` worker, SQLite, Composer, Node.js.
**URL:** http://localhost (override with `APP_PORT`)

## 2. Production (Docker Compose)

Single-container production environment: PHP-FPM + SQLite, no MySQL/Redis/Nginx containers.

```bash
docker compose --env-file /etc/internara.env up --build -d
```

**Low-memory by default (runs on a 1 GB RAM VPS):** one `app` service capped at `mem_limit: 384m`
/ `cpus: 1.0`, with `DB_CONNECTION=sqlite`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, and the
scheduler **off** unless `RUN_SCHEDULER=true`. PHP-FPM is capped at 4 workers
(`docker/php-fpm/www.conf`) with a 128 MB per-request `memory_limit`. The runtime `app` image is
multi-stage — it excludes `node_modules` and the build toolchain.

The app publishes **FastCGI on `127.0.0.1:9000`** (not HTTP), so a host web server must terminate
TLS and `fastcgi_pass` to that port. `storage_data` holds the SQLite database, uploads, and logs;
`app_data` holds `/app/public`. See `docs/guides/infra/deployment.md` for the host nginx vhost.

**Build cache stays small (no multi-GB bloat):**

- `.dockerignore` excludes `.git`, `node_modules`, `vendor`, `tests`, `docs`, and local `storage`
  artifacts from the build context — the daemon only receives what the image needs.
- `composer.json`/`composer.lock` are copied and installed **before** `package.json`/`package-lock.json`
  (and both before the rest of the source), so dependency layers are only rebuilt when a lockfile
  changes — not on every source commit.
- BuildKit cache mounts (`--mount=type=cache`) persist the Composer and npm download caches between
  builds, so repeated builds reuse already-downloaded packages.
- `node_modules` is removed at the end of the **builder** stage, so it never enters the runtime image
  (deleting it in the runtime stage would still leave its bytes in an earlier layer).
- Prune stale Docker build cache and unused images weekly with `.github/scripts/cleanup-docker-cache.sh`.
  The scheduled workflow uses a 168-hour age filter and the deployment lock, so it cannot overlap
  with a release or hotfix.

See `docker-compose.yml` for service definitions. See `docs/guides/infra/deployment.md` for the
production setup guide.

## 3. Shared Hosting Simulation

Lightweight environment simulating a basic shared hosting plan.

```bash
cd docker/shared-hosting
docker compose up -d
# App: http://localhost:8080
```

**Constraints:**

- Apache (not Nginx)
- MariaDB database (simulates shared hosting MySQL)
- No Composer at runtime
- No Node.js / npm
- No Redis / Memcached
- No queue worker (sync driver)
- No WebSocket / Reverb
- File-based cache and sessions

Use this for testing how the application behaves under shared hosting limitations before deploying
to an actual shared hosting provider.
