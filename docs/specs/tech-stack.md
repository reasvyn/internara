# Tech Stack — Language, Framework & Infrastructure Configuration

> **Last updated:** 2026-07-24 **Changes:** feat — split from core-foundation.md; PHP/Laravel
> versions, cache, session, queue, database, mail configuration

## Description

Technology stack and infrastructure configuration for Internara. Defines the PHP version,
framework versions, database/cache/session/queue drivers, cache key registry, and session
security configuration. Base classes and shared utilities are separate initiatives — see
[base-classes.md](base-classes.md) and [shared-utilities.md](shared-utilities.md).

---

## 1. Problem Statements

### PS-1 — Version-Pinned Dependencies

A self-hosted application deployed on diverse school infrastructure (shared hosting, VPS,
local servers) must pin its technology versions to avoid "works on my machine" issues. PHP 8.4
features (readonly properties, enums, fibers) are used throughout; deploying on PHP 8.1 causes
silent failures. Framework version mismatches cause breaking changes in middleware registration,
queue configuration, and migration syntax. The stack must be documented as the single source of
truth for deployment requirements.

### PS-2 — Cache Coherence

Caching improves performance but introduces staleness risk. Without a centralized key registry
and invalidation strategy, cached data can silently diverge from the database, causing
hard-to-debug inconsistencies across modules. The system must enforce a single cache key
registry and event-driven invalidation.

### PS-3 — Session Security

Sessions hold authentication state, CSRF tokens, wizard progress, and locale preferences. A
compromised session means a compromised account. Session configuration must enforce encryption,
HTTP-only cookies, SameSite protection, and proper lifetime limits. Default drivers must work
without external services (Redis) for shared hosting deployments.

### PS-4 — Zero-Config Deployment

Indonesian vocational schools typically run on shared hosting or small VPS instances without
Redis, Memcached, or dedicated queue workers. The default configuration must work with zero
external dependencies: SQLite for database, file for cache, database for sessions, sync for
queue. Scaling to Redis/database queue should be a single `.env` change.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Pin PHP 8.4, Laravel 13, Livewire 4, Tailwind CSS v4, DaisyUI v5 as minimum versions |
| G2  | Default to file cache with centralized key registry in `config/cache-keys.php` |
| G3  | Default to database sessions with encryption and SameSite protection |
| G4  | Default to sync queue with database/redis drivers available |
| G5  | Support SQLite (dev), MySQL 8, MariaDB 10.6, PostgreSQL 15 (production) |
| G6  | Provide `system:cache-warm` artisan command for deployment |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time WebSocket infrastructure (out of scope per product definition) |
| NG2  | GraphQL or REST API layer (Livewire-only frontend) |
| NG3  | Message queue abstraction beyond Laravel's built-in queue drivers |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Deploys on Shared Hosting

**Actor:** Developer
**Preconditions:** PHP 8.4+ available, Composer installed
**Flow:**
1. Developer clones repo, runs `composer install --optimize`
2. Copies `.env.example` to `.env`, sets `APP_URL`, `DB_CONNECTION=sqlite`
3. Runs `php artisan setup:install` — creates DB, runs migrations, seeds defaults
4. Cache driver defaults to `file`, session to `database`, queue to `sync`
5. Application works without Redis, Memcached, or queue workers
**Postconditions:** Zero-config deployment on shared hosting

### UC-2 — Cache Invalidates on Settings Change

**Actor:** Super Admin
**Preconditions:** System installed, admin changing a setting
**Flow:**
1. Admin updates setting via Settings UI
2. `SettingObserver` fires on Eloquent model event
3. Observer calls `Cache::forget()` for affected key
4. Next request reads fresh value from database
**Postconditions:** No stale cached values, no full cache flush needed

### UC-3 — Deployment Warms Cache

**Actor:** DevOps / CI pipeline
**Preconditions:** Code deployed, `.env` configured
**Flow:**
1. Pipeline runs `php artisan config:cache route:cache view:cache event:cache`
2. Pipeline runs `php artisan system:cache-warm`
3. First user request hits warm cache, no cold-start penalty
**Postconditions:** First-request latency reduced by ~60%

---

## 4. Functional Requirements

### Technology Versions

| ID     | Requirement |
| ------ | ----------- |
| FR-TS1 | PHP >= 8.4 required (readonly properties, enums, fibers used throughout) |
| FR-TS2 | Laravel >= 13.0 required (Livewire 4 integration, Folio routing, Volt) |
| FR-TS3 | Livewire >= 4.0 required (Livewire::handle(), property binding, polling) |
| FR-TS4 | Tailwind CSS >= 4.3 required (v4 `@theme` directive, CSS-first config) |
| FR-TS5 | DaisyUI >= 5.6 required (v5 themes, `data-theme` attribute) |

### Database

| ID     | Requirement |
| ------ | ----------- |
| FR-DB1 | Default (development): SQLite via `DB_CONNECTION=sqlite` |
| FR-DB2 | Production supported: MySQL >= 8.0, MariaDB >= 10.6, PostgreSQL >= 15 |
| FR-DB3 | UTF-8 charset enforced: `DB_CHARSET=utf8mb4` |
| FR-DB4 | UUID v7 primary keys via `HasUuids` trait — no auto-increment IDs |

### Cache Infrastructure

| ID     | Requirement |
| ------ | ----------- |
| FR-CACHE1 | Default cache driver: `file` (zero-config, shared hosting compatible) |
| FR-CACHE2 | Supported drivers: `file`, `database`, `redis`, `memcached`, `dynamodb`, `array` (testing) |
| FR-CACHE3 | All cache keys MUST be registered in `config/cache-keys.php` (C4 invariant) |
| FR-CACHE4 | Cache key naming: `{module}.{purpose}[.{qualifier}]` |
| FR-CACHE5 | TTL categories: short (<5min), medium (5min–1h), long (1h–24h), forever (explicit invalidation) |
| FR-CACHE6 | Invalidation: event-driven preferred (Command Action → Event → Listener → Cache::forget) |
| FR-CACHE7 | Invalidation: direct inline for simple cases (`Cache::forget(config('cache-keys.xxx'))`) |
| FR-CACHE8 | Application caches: `config:cache`, `route:cache`, `view:cache`, `event:cache` on deployment |
| FR-CACHE9 | Cache warming command: `php artisan system:cache-warm` |
| FR-CACHE10 | Redis prefix: `internara-cache-` (via `CACHE_PREFIX` env) |

### Session Infrastructure

| ID     | Requirement |
| ------ | ----------- |
| FR-SESS1 | Default session driver: `database` (auto-migrated, zero-config) |
| FR-SESS2 | Supported drivers: `database`, `redis`, `file`, `array` (testing) |
| FR-SESS3 | Session lifetime: 120 minutes of inactivity (configurable via `SESSION_LIFETIME`) |
| FR-SESS4 | Session encryption: enabled (`SESSION_ENCRYPT=true`) |
| FR-SESS5 | Cookie flags: HTTP-only, SameSite=lax, secure in production |
| FR-SESS6 | Session fixation prevention: ID regenerated on login/logout and privilege changes |
| FR-SESS7 | Garbage collection: probabilistic `[2, 100]` (2% chance per request) for database driver |
| FR-SESS8 | Redis driver: key expiry handles GC automatically (no application-level GC) |
| FR-SESS9 | Session stores: auth state, CSRF token, locale preference, wizard progress, setup authorization |

### Queue

| ID     | Requirement |
| ------ | ----------- |
| FR-Q1  | Default queue connection: `sync` (synchronous, no worker needed) |
| FR-Q2  | Supported connections: `sync`, `database`, `redis`, `beanstalkd` |
| FR-Q3  | Queue-specific tables auto-created by migration for `database` driver |
| FR-Q4  | Failed jobs table: `failed_jobs` with full exception trace |
| FR-Q5  | Horizon available for Redis queue monitoring (optional) |

### Mail

| ID     | Requirement |
| ------ | ----------- |
| FR-M1  | Default mailer: `log` (development), `smtp` (production) |
| FR-M2  | SMTP configuration via `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` env |
| FR-M3  | `TestMailSettingsAction` validates SMTP config before persisting |
| FR-M4  | Mail from address: `MAIL_FROM_ADDRESS` env, fallback `support_email` setting |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | Session cookie must be HTTP-only, SameSite=lax, secure in production |
| NFR-S2 | Redis connections support retry with backoff (max_retries=3, decorrelated jitter) |
| NFR-P1 | Cache warming reduces first-request latency after deployment |
| NFR-P2 | Application cache (config/route/view/event) reduces bootstrap time by ~60% |
| NFR-P3 | Redis connection pool: persistent connections optional (`REDIS_PERSISTENT`) |
| NFR-R1 | Graceful degradation: cache miss returns fresh data, never cached error |
| NFR-R2 | Redis backoff: decorrelated jitter with 100ms base, 1000ms cap |
| NFR-M1 | Cache key registry in single file (`config/cache-keys.php`) — discoverable, auditable |

---

## 6. API / Data Contracts

### Cache Key Registry

```php
// config/cache-keys.php
return [
    'setup_installed'       => 'setup.is_installed',
    'setup_token_generation'=> 'setup.token.generation',
    'admin_dashboard_stats' => 'sysadmin.dashboard.stats',
    'theme_css_variables'   => 'theme.css_variables',
    'brand_colors'          => 'brand.colors',
    'notification_unread'   => 'notification.unread:',
    'settings_all'          => 'settings.all',
    'settings_group'        => 'settings.group.',
    'school_entity'         => 'academics.school.entity',
    'auth_login_lockout'    => 'auth.login.lockout:',
    'health_check'          => 'system.health_check',
    // ... 25+ registered keys
];
```

### Session Configuration

```php
// config/session.php
return [
    'driver'       => env('SESSION_DRIVER', 'database'),
    'lifetime'     => 120,       // minutes
    'encrypt'      => true,
    'http_only'    => true,
    'secure'       => env('APP_ENV') === 'production',
    'same_site'    => 'lax',
    'lottery'      => [2, 100],  // 2% GC chance per request
];
```

### Queue Configuration

```php
// config/queue.php
return [
    'default' => env('QUEUE_CONNECTION', 'sync'),
    'connections' => [
        'sync' => ['driver' => 'sync'],
        'database' => ['driver' => 'database', 'table' => 'jobs', 'queue' => 'default', 'retry_after' => 90],
        'redis' => ['driver' => 'redis', 'connection' => 'default', 'queue' => 'default', 'retry_after' => 90],
    ],
    'failed' => ['driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'), 'database' => 'failed_jobs', 'table' => 'failed_jobs'],
];
```

### Mail Configuration

```php
// config/mail.php — key env vars
MAIL_MAILER=smtp          // log | smtp | sendmail | ses
MAIL_HOST=smtp.mailtrap.org
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null      // tls | ssl
MAIL_FROM_ADDRESS="hello@internara.example"
MAIL_FROM_NAME="${APP_NAME}"
```

### Environment Variables

```env
# .env.example — key infrastructure vars
APP_NAME=Internara
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=internara
# DB_USERNAME=root
# DB_PASSWORD=

CACHE_STORE=file          // file | database | redis
SESSION_DRIVER=database   // database | redis | file
QUEUE_CONNECTION=sync     // sync | database | redis

MAIL_MAILER=log           // log | smtp
```

---

## 7. Design Decisions

### DD-1 — Centralized Cache Key Registry

**Decision:** All cache keys MUST be declared in `config/cache-keys.php`, never inline.
**Rationale:** Prevents key collisions across modules, makes cache dependencies discoverable,
enables systematic flushing. Without centralized keys, modules would independently invent naming
conventions leading to conflicts.
**Trade-off:** Extra step when adding new cache keys. Mitigated by the clear naming convention
(`{module}.{purpose}[.{qualifier}]`).

### DD-2 — File Cache as Default

**Decision:** Default cache driver is `file`, not Redis.
**Rationale:** Shared hosting deployments cannot install Redis. File cache works without external
services. For Tier 2+ deployments, switching to Redis is a one-line `.env` change.
**Trade-off:** File cache is slower than Redis and doesn't support atomic operations. Acceptable
for single-tenant workloads.

### DD-3 — Database Session as Default

**Decision:** Default session driver is `database`, not `file`.
**Rationale:** Database sessions survive process restarts (important for queue workers), support
multi-process deployments, and the sessions table is auto-created by migration. File sessions
can be lost on deploy.
**Trade-off:** Slightly higher DB load per request. Negligible for single-tenant with <1000
concurrent users.

### DD-4 — Sync Queue as Default

**Decision:** Default queue connection is `sync` (synchronous execution).
**Rationale:** Shared hosting has no queue workers. Sync queue executes jobs inline during the
request. For production, switching to `database` or `redis` queue is a two-line `.env` change
plus `php artisan queue:work`.
**Trade-off:** No background processing on default config. All notifications, backups, and
queued jobs run synchronously. Acceptable for small-scale deployments.

---

## 8. Success Metrics

### Cache

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Key registration | 100% of cache keys in registry | `grep -r "Cache::" app/` → all keys resolve to config |
| Stale data window | < 5 seconds for settings changes | Observer fires on every model event |
| Cache warm time | < 5 seconds | `time php artisan system:cache-warm` |

### Session

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Encryption | Always enabled | `SESSION_ENCRYPT=true` in default config |
| Lifetime | 120 minutes | Default config value |
| Fixation prevention | Regenerated on auth change | `session()->regenerate()` in login/logout flow |

### Deployment

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Zero-config startup | Works with `composer install` + `.env` copy | No Redis/Memcached/queue worker required |
| First-request cold | < 2s without cache warming | `ab -n 1` on fresh deploy |
| First-request warm | < 500ms with cache warming | After `artisan config:cache route:cache view:cache` |

---

## 9. Roadmap

### Prerequisites
No prerequisites — this is a foundational spec.

### Build Guide
This spec establishes the technology platform: PHP 8.4, Laravel 13, database drivers, cache
key registry, session security, queue defaults, and mail configuration. Every other spec in the
system depends on this infrastructure being in place. The next step is to build the base
classes that define the architectural patterns.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [base-classes.md](base-classes.md) | Action Triad, Entity, DTO, Model, Policy base classes extend framework |
| 2 | [shared-utilities.md](shared-utilities.md) | Cross-cutting helpers (AppInfo, Color, PasswordRules) built on PHP/Laravel |

---

## Quick References

- `config/cache-keys.php` — Centralized cache key registry
- `config/cache.php` — Cache store definitions
- `config/session.php` — Session driver and cookie settings
- `config/queue.php` — Queue connection and worker settings
- `config/mail.php` — Mail driver and SMTP configuration
- `.env.example` — Template environment variables
- `composer.json` — PHP and package version constraints
- `docs/architecture/cache-pattern.md` — Cache strategy and key registry
- **Related specs:** [base-classes.md](base-classes.md) — Action Triad, Entity, DTO, Model base classes
- **Related specs:** [shared-utilities.md](shared-utilities.md) — Cross-cutting utility classes
- **Related specs:** [system-requirements.md](system-requirements.md) — Dependencies and platform details
