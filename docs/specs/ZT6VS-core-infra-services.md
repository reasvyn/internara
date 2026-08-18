# Core & Infrastructure Services — Cache, Session, Database, Queue, Mail, Storage

> **Spec ID:** ZT6VS
> **Last updated:** 2026-08-16 **Changes:** add — split from tech-stack (FB792): runtime service
> behavior (database, cache, session, queue, mail, filesystem/storage) moves here so the tech-stack
> spec focuses on the dependency manifest; adds filesystem/storage (FR-FS) and supporting services
> (FR-SVC).

## Description

Defines the **runtime infrastructure services** Internara consumes: database connections, cache,
session, queue, mail, and filesystem/storage — plus supporting services (Redis, encryption, health
reporting). Each service is zero-config by default and scalable via a single `.env` change.

The [tech-stack](FB792-tech-stack.md) spec is the dependency manifest (versions and packages);
this spec owns the **configuration and behavior** of those services. Logging/error handling is
owned by [logging-and-error-handling](89SRA-logging-and-error-handling.md); the job/queue
lifecycle is owned by [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md).

---

## 1. Problem Statements

### PS-1 — Zero-Config Shared Hosting

Indonesian vocational schools typically deploy on shared hosting or small VPS instances without
Redis, Memcached, or dedicated queue workers. The default configuration must work with zero
external dependencies: SQLite for database, file for cache, database for sessions, sync for
queue, log for mail. Scaling any service must be a single `.env` change.

### PS-2 — Cache Coherence

Caching improves performance but introduces staleness risk. Without a centralized key registry
and invalidation strategy, cached data silently diverges from the database, causing
hard-to-debug inconsistencies across modules. The system enforces a single cache key registry and
event-driven invalidation.

### PS-3 — Session Security

Sessions hold authentication state, CSRF tokens, wizard progress, and locale preferences. A
compromised session means a compromised account. Session configuration enforces encryption,
HTTP-only cookies, SameSite protection, and proper lifetime limits. Default drivers must work
without external services.

### PS-4 — Mail Reliability

Mail settings are entered by non-technical admins and are only noticed when they are wrong. A
wrong SMTP host or password silently breaks notifications, password resets, and PDF delivery.
SMTP configuration must be validated before it is persisted, and the default mailer must never
block development.

### PS-5 — Secure File Storage

Uploads (avatars, evidence, media) are user-controlled content. They must be stored on
configured filesystem disks through the media library, never written ad-hoc to disk, and must
never be served in a way that allows script execution.

### PS-6 — Infra Behavior Buried in the Dependency Spec

Previously the tech-stack spec mixed version pins with runtime behavior (drivers, lifetimes,
security flags). That made it hard to reason about a single service and hard to evolve either
side independently. This spec separates **what is installed** (FB792) from **how services
behave at runtime** (this spec).

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Zero-config defaults: SQLite (DB), file (cache), database (session), sync (queue), log (mail) |
| G2  | Every service overridable via `.env` without code changes |
| G3  | Centralized cache key registry in `config/cache-keys.php` |
| G4  | Secure session defaults (encryption, HTTP-only, SameSite, lifetime) |
| G5  | SMTP validated before persist via `TestMailSettingsAction` |
| G6  | Filesystem disks wired to the media library for all uploads |
| G7  | Graceful degradation when a service fails (never serve stale/cached errors) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time WebSocket/broadcasting infrastructure (out of scope per product definition) |
| NG2  | Multi-tenant service isolation |
| NG3  | Queue worker management / Horizon / job lifecycle (see [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md)) |
| NG4  | Logging pipelines and error handling (see [logging-and-error-handling](89SRA-logging-and-error-handling.md)) |
| NG5  | Mail providers beyond Laravel mailers (`log`, `smtp`, `ses`, etc.) |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Deploys on Shared Hosting

**Actor:** Developer
**Preconditions:** PHP 8.4+ available, Composer installed
**Flow:**
1. Developer clones repo, runs `composer install --optimize`
2. Copies `.env.example` to `.env`, sets `APP_URL`, `DB_CONNECTION=sqlite`
3. Runs `php artisan setup:install` — creates DB, runs migrations, seeds defaults
4. Cache defaults to `file`, session to `database`, queue to `sync`, mail to `log`
5. Application works without Redis, Memcached, or queue workers
**Postconditions:** Zero-config deployment on shared hosting

### UC-2 — Cache Invalidates on Settings Change

**Actor:** Super Admin
**Preconditions:** System installed, admin changing a setting
**Flow:**
1. Admin updates a setting via the Settings UI
2. The Command Action dispatches an event on success
3. The Listener calls `Cache::forget()` for the affected registered key
4. Next request reads fresh data from the database
**Postconditions:** No stale cached values, no full cache flush needed

### UC-3 — Deployment Warms Cache

**Actor:** DevOps / CI pipeline
**Preconditions:** Code deployed, `.env` configured
**Flow:**
1. Pipeline runs `php artisan config:cache route:cache view:cache event:cache`
2. Pipeline runs `php artisan system:cache-warm`
3. First user request hits warm cache, no cold-start penalty
**Postconditions:** First-request latency reduced by ~60%

### UC-4 — Admin Validates SMTP Before Saving

**Actor:** School Admin
**Preconditions:** Settings page open
**Flow:**
1. Admin enters SMTP host/port/credentials
2. Admin clicks "Test" — `TestMailSettingsAction` sends a probe email
3. On failure, the form shows the error and settings are NOT persisted
4. On success, settings are saved and future mail uses SMTP
**Postconditions:** Mail configuration is verified before it takes effect

### UC-5 — File Upload Through the Media Library

**Actor:** Student / Admin
**Preconditions:** Upload feature (avatar, evidence, media) available
**Flow:**
1. User uploads a file through a Livewire upload field
2. The Command Action stores it via the media library on the configured disk
3. The media library registers the file and serves it through its secure route
**Postconditions:** Uploads are governed by disk config and media policies (see [file-uploads-media](WQGTP-file-uploads-media.md))

### UC-6 — Cache Store Fails Gracefully

**Actor:** Runtime system
**Preconditions:** Cache store unavailable (e.g. file store unwritable)
**Flow:**
1. A cached value is requested but misses
2. The application computes the fresh value from the database
3. The miss never surfaces as an error; the request completes
**Postconditions:** Graceful degradation — fresh data, never a cached error

---

## 4. Functional Requirements

### Database

| ID     | Requirement |
| ------ | ----------- |
| FR-DB1 | Default (development): SQLite via `DB_CONNECTION=sqlite` |
| FR-DB2 | Production supported: MySQL >= 8.0, MariaDB >= 10.6, PostgreSQL >= 15 |
| FR-DB3 | UTF-8 charset enforced: `DB_CHARSET=utf8mb4` (PostgreSQL: `utf8`) |
| FR-DB4 | UUID v7 primary keys via `HasUuids` trait — no auto-increment IDs |
| FR-DB5 | SQLite: WAL journal mode and `busy_timeout` enabled for concurrency safety |
| FR-DB6 | Foreign keys MUST declare `onDelete`/`onUpdate` behavior (D6 invariant) |

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
| FR-Q6  | Separate `default` and `documents` queue pipelines — batch document generation dispatches to `documents`, all other jobs to `default` (internara-project §8 NFR-Q1; see [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md) and [official-documents](7H5D6-official-documents.md)) |

### Mail

| ID     | Requirement |
| ------ | ----------- |
| FR-M1  | Default mailer: `log` (development), `smtp` (production) |
| FR-M2  | SMTP configuration via `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` env |
| FR-M3  | `TestMailSettingsAction` validates SMTP config before persisting |
| FR-M4  | Mail from address: `MAIL_FROM_ADDRESS` env, fallback `support_email` setting |

### Filesystem & Storage

| ID     | Requirement |
| ------ | ----------- |
| FR-FS1 | Default disk: `local` (via `FILESYSTEM_DISK=local`) |
| FR-FS2 | `public` disk serves publicly accessible assets; `storage:link` is part of `setup:install` |
| FR-FS3 | `s3` disk available for object-storage deployments (via `AWS_*` env) |
| FR-FS4 | All user uploads go through the media library on a configured disk — never raw `Storage::put` (see [file-uploads-media](WQGTP-file-uploads-media.md)) |
| FR-FS5 | Media collections declare their storage disk and conversion presets in the owning Model |

### Supporting Services

| ID     | Requirement |
| ------ | ----------- |
| FR-SVC1 | Redis, when enabled, is one shared server with distinct connections: `cache`, `queue`, `session`, `default` (`REDIS_HOST`/`REDIS_PORT`/`REDIS_PASSWORD` env) |
| FR-SVC2 | `APP_KEY` must be present and non-empty (`base64:` value) in all environments — encryption at rest for sessions and data |
| FR-SVC3 | System health reporting via `php artisan system:health` and the `/up` endpoint surfaces service status (see [system-maintenance](E1MSJ-system-maintenance.md)) |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | Session cookie must be HTTP-only, SameSite=lax, secure in production |
| NFR-S2 | Redis connections support retry with backoff (max_retries=3, decorrelated jitter) |
| NFR-S3 | `APP_KEY` is enforced — missing key fails fast at boot in production |
| NFR-S4 | Upload validation and media security follow [file-uploads-media](WQGTP-file-uploads-media.md) |
| NFR-P1 | Cache warming reduces first-request latency after deployment |
| NFR-P2 | Application cache (config/route/view/event) reduces bootstrap time by ~60% |
| NFR-P3 | Redis connection pool: persistent connections optional (`REDIS_PERSISTENT`) |
| NFR-R1 | Graceful degradation: cache miss returns fresh data, never a cached error |
| NFR-R2 | Redis backoff: decorrelated jitter with 100ms base, 1000ms cap |
| NFR-M1 | Cache key registry in a single file (`config/cache-keys.php`) — discoverable, auditable |

---

## 6. API / Data Contracts

### Environment Variables (by service)

```env
# Database
DB_CONNECTION=sqlite        # sqlite | mysql | mariadb | pgsql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=

# Cache
CACHE_STORE=file            # file | database | redis | memcached | dynamodb | array
CACHE_PREFIX=internara-cache-

# Session
SESSION_DRIVER=database     # database | redis | file | array
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Queue
QUEUE_CONNECTION=sync       # sync | database | redis | beanstalkd
QUEUE_FAILED_DRIVER=database-uuids

# Mail
MAIL_MAILER=log             # log | smtp | ses | sendmail
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@internara.example"
MAIL_FROM_NAME="${APP_NAME}"

# Filesystem
FILESYSTEM_DISK=local       # local | public | s3

# Redis (optional, shared server, distinct connections)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_PERSISTENT=false
```

### Cache Key Registry

```php
// config/cache-keys.php
return [
    'setup_installed'        => 'setup.is_installed',
    'settings_all'           => 'settings.all',
    'settings_group'         => 'settings.group.',
    'admin_dashboard_stats'  => 'sysadmin.dashboard.stats',
    'notification_unread'    => 'notification.unread:',
    'school_entity'          => 'academics.school.entity',
    'auth_login_lockout'     => 'auth.login.lockout:',
    'health_check'           => 'system.health_check',
    // ... 25+ registered keys
];
```

### Redis Connection Map

| Connection | Purpose | Prefix |
| ---------- | ------- | ------ |
| `default` | Generic / rate limiting | `laravel_database_` |
| `cache`   | Cache store (when `CACHE_STORE=redis`) | `internara-cache-` |
| `queue`   | Queue driver (when `QUEUE_CONNECTION=redis`) | `laravel_database_queue_` |
| `session` | Session driver (when `SESSION_DRIVER=redis`) | `laravel_database_session_` |

### Queue Pipeline Contract

```php
// Batch document generation uses the 'documents' pipeline (FR-Q6):
dispatch(new GenerateDocumentJob(...))->onQueue('documents');
```

---

## 7. Design Decisions

### DD-1 — File Cache as Default

**Decision:** Default cache driver is `file`, not Redis.
**Rationale:** Shared hosting cannot install Redis; file cache works without external services.
Switching to Redis is a one-line `.env` change (FR-CACHE1, G2).
**Trade-off:** File cache is slower and lacks atomic operations — acceptable for single-tenant
workloads.

### DD-2 — Database Session as Default

**Decision:** Default session driver is `database`, not `file`.
**Rationale:** Database sessions survive process restarts (important for queue workers) and
support multi-process deployments; the sessions table is auto-created by migration (FR-SESS1).
**Trade-off:** Slightly higher DB load per request — negligible for <1000 concurrent users.

### DD-3 — Sync Queue as Default

**Decision:** Default queue connection is `sync` (synchronous execution).
**Rationale:** Shared hosting has no queue workers; sync executes jobs inline. Production switches
to `database`/`redis` via `.env` plus `php artisan queue:work` (FR-Q1).
**Trade-off:** No background processing on default config — all jobs run synchronously.
Acceptable for small-scale deployments.

### DD-4 — SQLite as Default Database

**Decision:** Default connection is SQLite for development and shared hosting.
**Rationale:** Zero-config, file-based, WAL-enabled (FR-DB1, FR-DB5). Production uses MySQL 8 /
MariaDB 10.6 / PostgreSQL 15 via `.env` (FR-DB2).
**Trade-off:** SQLite is single-writer; adequate for single-tenant with low concurrency.

### DD-5 — SMTP Validation Gate

**Decision:** `TestMailSettingsAction` probes SMTP before settings are persisted (FR-M3).
**Rationale:** Wrong mail config silently breaks notifications; validating before persist surfaces
errors at edit time (PS-4).
**Trade-off:** Requires a working SMTP at save time — acceptable for a school admin workflow.

### DD-6 — Log Mailer as Default

**Decision:** Default mailer is `log`.
**Rationale:** Development and default installs never fail on missing SMTP; `smtp` is the
documented production choice (FR-M1).
**Trade-off:** Default install does not send real mail until configured — expected for zero-config
deployment.

### DD-7 — Media Library as the Only Upload Path

**Decision:** All user uploads go through the media library on a configured disk (FR-FS4).
**Rationale:** Centralizes storage, conversions, and serving behind a security-reviewed package
instead of ad-hoc `Storage::put` calls.
**Trade-off:** Adds Spatie MediaLibrary as a hard dependency (already pinned in FB792).

### DD-8 — Split from the Tech-Stack Spec (Recorded Decision)

**Decision:** Runtime service behavior moved from FB792 into this spec; FB792 retains only the
dependency manifest.
**Rationale:** Separate "what is installed" from "how it behaves" so each side evolves
independently and service docs stay navigable (PS-6).
**Trade-off:** Two specs to consult for service topics — mitigated by explicit cross-references
and the split contract below.

---

## 8. Success Metrics

### Cache

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Key registration | 100% of cache keys in registry | `grep -r "Cache::" app/` → all keys resolve to config |
| Stale data window | < 5 seconds for settings changes | Listener fires on every settings change |
| Cache warm time | < 5 seconds | `time php artisan system:cache-warm` |

### Session

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Encryption | Always enabled | `SESSION_ENCRYPT=true` in default config |
| Lifetime | 120 minutes | Default config value |
| Fixation prevention | Regenerated on auth change | `session()->regenerate()` in login/logout flow |

### Mail & Storage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| SMTP validation | 100% of saved SMTP configs tested | `TestMailSettingsAction` before persist |
| Uploads via media library | 100% | No raw `Storage::put` in `app/` upload paths |

### Deployment

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Zero-config startup | Works with `composer install` + `.env` copy | No Redis/Memcached/queue worker required |
| First-request cold | < 2s without cache warming | `ab -n 1` on fresh deploy |
| First-request warm | < 500ms with cache warming | After `artisan config:cache route:cache view:cache` |

---

## 9. Roadmap

### Prerequisites

- [tech-stack.md](FB792-tech-stack.md) — pins the package versions these services rely on
- [architecture-design](D2FT3-architecture.md) — places these services in the Framework/Infra layer

### Build Guide

This spec governs configuration files (`config/cache.php`, `config/session.php`,
`config/queue.php`, `config/mail.php`, `config/database.php`, `config/filesystems.php`),
`config/cache-keys.php`, and the helper Actions built on them (e.g. `TestMailSettingsAction`).
Per-service requirements are satisfied by those configs plus the consuming modules' features.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [base-classes.md](SE5Q9-base-classes.md) | Base classes (BaseModel, actions) consume cache/session/queue/mail services |
| 2 | [file-uploads-media.md](WQGTP-file-uploads-media.md) | Media library storage on configured disks (FR-FS4) |
| 3 | [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) | Queue lifecycle, retries, batches (FR-Q) |

---

## Quick References

- `config/cache.php`, `config/cache-keys.php` — cache stores and registry
- `config/session.php` — session driver, cookie settings, lifetime
- `config/queue.php` — queue connections and worker settings
- `config/mail.php` — mail driver and SMTP configuration
- `config/database.php` — database connections
- `config/filesystems.php` — filesystem disks
- `.env.example` — template environment variables
- `docs/architecture/cache-pattern.md` — cache strategy and key registry
- **Related specs:** [tech-stack.md](FB792-tech-stack.md) — dependency manifest; [architecture-design](D2FT3-architecture.md) — layer placement; [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) — queue lifecycle; [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) — logging; [file-uploads-media.md](WQGTP-file-uploads-media.md) — media; [system-maintenance.md](E1MSJ-system-maintenance.md) — health; [system-requirements.md](J68GZ-system-requirements.md) — platform requirements
