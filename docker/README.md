# Docker — Container Configuration & Setup Environments

> **Last updated:** 2026-06-10
> **Changes:** sync — initial metadata sync with new format

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

See `docker-compose.yml` for service definitions. See `docs/guide/01-installation.md` for production setup
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
