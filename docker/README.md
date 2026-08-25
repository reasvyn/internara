# Docker — Container Configuration & Setup Environments

> **Last updated:** 2026-08-16
> **Changes:** amend — document low-memory production defaults (scheduler off, MySQL caps, PHP-FPM cap, multi-stage image); add Docker build cache optimization (.dockerignore, lockfile-first layers, BuildKit cache mounts, builder-stage node_modules removal)

## Description
Internara provides three Docker environments for different use cases.


## 1. Development (Laravel Sail)

Full-featured development environment with queue worker, Reverb, and Vite HMR.

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

**Includes:** Queue worker, Reverb WebSocket, PHP 8.4, SQLite, Composer, Node.js.
**URL:** http://localhost

## 2. Production (Docker Compose)

Multi-service production environment with MySQL, Redis, Nginx, and Supervisor.

```bash
docker compose up -d
```

**Low-memory by default (runs on a 1 GB RAM VPS):** the scheduler is **off** unless
`RUN_SCHEDULER=true` (no background processing — `QUEUE_CONNECTION=sync` runs jobs inline), MySQL
runs with capped memory (`innodb_buffer_pool_size=64M`, `performance_schema=OFF`, etc.), each service
has a `mem_limit` (`app` 256m, `db` 384m, `web` 64m), and PHP-FPM is capped at 2 workers
(`docker/php-fpm/www.conf`). The runtime `app` image is multi-stage — it excludes `node_modules` and
the build toolchain.

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
- Prune stale layers periodically: `docker builder prune -af` (or `docker buildx prune`).

See `docker-compose.yml` for service definitions. See `docs/guides/installation.md` for production setup
guide.

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
