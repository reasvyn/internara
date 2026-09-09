# Core & Infrastructure Services — Cache, Session, Database, Queue, Mail, Storage

> **Spec ID:** ZT6VS
> **Status:** Full
> **Owner:** Core
> **Depends on:** FB792

## Description

Defines the runtime infrastructure services Internara consumes — database, cache, session, queue, mail, and filesystem/storage — with zero-config Tier-1 defaults that run on shared hosting and Tier-2 growth as pure `.env` swaps. The [tech-stack](FB792-tech-stack.md) spec pins dependency versions; this spec owns configuration and behavior. Logging is owned by [logging-and-error-handling](89SRA-logging-and-error-handling.md); the job lifecycle by [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md).

---

## 1. Problem Statements

### PS-1 — Zero-Config Shared Hosting

Indonesian vocational schools typically deploy on shared hosting or small VPS instances without Redis, Memcached, or dedicated queue workers. The default configuration must work with zero external dependencies — SQLite for database, file for cache, database for sessions, sync for queue, log for mail — and scaling any service must be a single `.env` change.
**→ Requirement:** FR-CORE-036 (Tier-1 defaults), FR-CORE-008/015/022/027/031 (per-service defaults).

### PS-2 — Cache Coherence

Caching improves performance but introduces staleness risk. Without a centralized key registry and invalidation strategy, cached data silently diverges from the database, causing hard-to-debug inconsistencies across modules. The system enforces a single cache key registry and event-driven invalidation.
**→ Requirement:** FR-CORE-010/011 (registry + naming), FR-CORE-013 (event-driven invalidation).

### PS-3 — Session Security

Sessions hold authentication state, CSRF tokens, wizard progress, and locale preferences. A compromised session means a compromised account. Session configuration enforces encryption, HTTP-only cookies, SameSite protection, and lifetime limits, with defaults that work without external services.
**→ Requirement:** FR-CORE-017/018/019 (lifetime, encryption, fixation), NFR-CORE-001.

### PS-4 — Mail Reliability

Mail settings are entered by non-technical admins and failures surface only when something is already broken — notifications, password resets, PDF delivery silently stop. SMTP configuration must be validated before it is persisted, and the default mailer must never block a fresh install.
**→ Requirement:** FR-CORE-027/028 (log default + SMTP probe gate).

### PS-5 — Secure File Storage

Uploads (avatars, evidence, media) are user-controlled content. They must be stored on configured filesystem disks through the media library, never written ad-hoc to disk, and never served in a way that allows script execution.
**→ Requirement:** FR-CORE-033/034 (media-library-only path), NFR-CORE-003.

### PS-6 — Infra Behavior Buried in the Dependency Spec

Previously the tech-stack spec mixed version pins with runtime behavior (drivers, lifetimes, security flags), making it hard to reason about a single service and hard to evolve either side independently. This spec separates what is installed (FB792) from how services behave at runtime (this spec).
**→ Requirement:** DD-CORE-008 (recorded split), FR-CORE-001–043 (per-service behavior).

---

## 2. Goals & Non-Goals

### Goals

- **Zero-config Tier-1 defaults** — SQLite/file/database-session/sync-queue/log-mail work out of the box with no external services. *Why:* schools deploy on $5 shared hosting; anything requiring Redis or a worker daemon on day one excludes them.
- **Every service overridable via `.env` without code changes** — cache, session, queue, mail, disk, and Redis all read driver and credential from environment. *Why:* Tier-2 growth must be a config swap, not a rewrite (per [performance-optimization ADR](../adr/adr-performance-optimization.md)).
- **Centralized cache key registry in `config/cache-keys.php`** — every cached value resolves to a registered key. *Why:* greppable, auditable invalidation; stale keys become visible instead of silent.
- **Secure session defaults** — encryption on, HTTP-only, SameSite=lax, 120-minute lifetime, ID regeneration on auth change. *Why:* sessions carry auth state; the default must be safe before any admin touches a setting.
- **SMTP validated before persist via `TestMailSettingsAction`** — a probe email must succeed before settings take effect. *Why:* a typo in mail config otherwise fails silently for weeks.
- **Filesystem disks wired to the media library for all uploads** — no ad-hoc `Storage::put` for user content. *Why:* one security-reviewed path for storage, conversions, and serving (see [file-uploads-media](WQGTP-file-uploads-media.md)).
- **Graceful degradation when a service fails** — a cache miss returns fresh data, never a cached error. *Why:* infrastructure hiccups must not become user-facing outages.

### Non-Goals

- **Real-time WebSocket/broadcasting infrastructure**. *Why:* out of scope per product definition; the broadcast default stays `log`.
- **Multi-tenant service isolation**. *Why:* single-tenant by product definition; one instance per school needs no tenant-scoped drivers.
- **Queue worker management, Horizon, and job lifecycle**. *Why:* owned by [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md); this spec owns only the connection defaults.
- **Logging pipelines and error handling**. *Why:* owned by [logging-and-error-handling](89SRA-logging-and-error-handling.md).
- **Mail providers beyond Laravel mailers (`log`, `smtp`, `ses`, `sendmail`)**. *Why:* the framework mailers cover school needs; exotic providers add config surface with no demand.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled only where the UC has a verifiable, code-testable consequence at this spec's level.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-CORE-001 | Developer deploys on shared hosting with zero external services | P0 | A | Full |
| UC-CORE-002 | System invalidates cache on settings change via event + listener | P0 | F | Full |
| UC-CORE-003 | Deployer warms caches during deployment | P1 | F | Full |
| UC-CORE-004 | Admin validates SMTP before saving | P0 | F | Full |
| UC-CORE-005 | User uploads files through the media library | P0 | F | Full |
| UC-CORE-006 | System degrades gracefully when the cache store fails | P1 | F | Full |

### 3.1 Deployment & Operations

#### UC-CORE-001 — Deploy on Shared Hosting With Zero External Services

A developer clones the repo on a plain shared-hosting account — no Redis, no Memcached, no worker supervisor. They run `composer install`, copy `.env.example`, and run `php artisan setup:install`: SQLite is created, migrations run, cache resolves to `file`, sessions to `database`, queue to `sync`, mail to `log`. The application works end to end. **Verification:** config defaults assert `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `SESSION_DRIVER=database` (FR-CORE-042, layer `A`); the shared-hosting deploy preset pins the same matrix (see [shared-hosting-deployment](06IB6-shared-hosting-deployment.md)).

#### UC-CORE-002 — Cache Invalidates on Settings Change

A super admin updates a setting in the Settings UI. The Command Action dispatches an event on success; the listener calls `Cache::forget()` against the affected registered key. The next request reads fresh data — no full cache flush, no stale window beyond the request. This is the Stabilize phase of the gradual-migration cache path ([gradual-migration ADR](../adr/adr-gradual-migration.md)): inline forget where trivial, event + listener where multiple writers touch the key. **Verification:** feature test on the settings update flow (layer `F`).

#### UC-CORE-003 — Deployment Warms Caches

After code lands, the pipeline runs `php artisan config:cache route:cache view:cache event:cache` followed by `php artisan system:cache-warm` (implemented in `SysAdmin/.../Console/Commands/SystemCacheWarmCommand.php`). The first real user request hits warm caches instead of paying cold-start bootstrap. **Verification:** the artisan command exists and exits clean (layer `F`).

### 3.2 Admin & End-User Flows

#### UC-CORE-004 — Admin Validates SMTP Before Saving

A school admin types SMTP host, port, and credentials into the Settings page and clicks "Test". `TestMailSettingsAction` (a `BaseCommandAction` in the Settings module) sends a probe email with the unsaved values. On failure the form shows the error and nothing is persisted; on success the settings save and future mail uses SMTP. This gate exists because mail misconfiguration is otherwise discovered weeks later via a missing password-reset email. **Verification:** feature test on the probe-then-persist flow (layer `F`).

#### UC-CORE-005 — File Upload Through the Media Library

A student uploads internship evidence through a Livewire upload field. The Command Action stores it via the media library on the configured disk; the library registers conversions and serves the file through its secure route. No caller writes user content with raw `Storage::put`. Full upload mechanics (validation, collections, conversions) live in [file-uploads-media](WQGTP-file-uploads-media.md). **Verification:** feature test on the upload flow (layer `F`).

#### UC-CORE-006 — Cache Store Fails Gracefully

The file cache directory becomes unwritable at runtime. A request needing a cached value misses, recomputes from the database, and completes normally — the miss never surfaces as an error page, and no error payload is ever written back into the cache. **Verification:** feature test simulating an unwritable store (layer `F`).

---

## 4. Functional Requirements

Per-service behavior contracts. Defaults are Tier 1 (shared hosting, ≤500 users); Tier 2+ is config-only (§4.7).

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-CORE-001 | Default connection is SQLite via `DB_CONNECTION=sqlite` (development and shared hosting) | P0 | A | Full |
| FR-CORE-002 | Production supports MySQL ≥ 8.0, MariaDB ≥ 10.6, PostgreSQL ≥ 15 via `.env` | P0 | A | Full |
| FR-CORE-003 | UTF-8 charset enforced: `DB_CHARSET=utf8mb4` (PostgreSQL: `utf8`) | P0 | A | Full |
| FR-CORE-004 | UUID v7 primary keys via `HasUuids` — no auto-increment IDs ([uuid ADR](../adr/adr-uuid-primary-keys.md)) | P0 | A | Full |
| FR-CORE-005 | SQLite runs with WAL journal mode and `busy_timeout` for concurrency safety | P1 | A | Full |
| FR-CORE-006 | Foreign keys declare `onDelete`/`onUpdate` behavior (D6 invariant) | P0 | A | Full |
| FR-CORE-007 | Composite indexes on foreign keys and `activity_log` (Tier-0 no-regret) | P0 | A | Full |
| FR-CORE-008 | Default cache driver is `file` (zero-config, shared-hosting compatible) | P0 | A | Full |
| FR-CORE-009 | Supported drivers: `file`, `database`, `redis`, `memcached`, `array` (testing) | P1 | A | Full |
| FR-CORE-010 | All cache keys are registered in `config/cache-keys.php` (C4 invariant) | P0 | A | Full |
| FR-CORE-011 | Cache key naming: `{module}.{purpose}[.{qualifier}]` | P1 | A | Full |
| FR-CORE-012 | TTL categories: short (<5min), medium (5min–1h), long (1h–24h), forever (explicit invalidation only) | P1 | A | Full |
| FR-CORE-013 | Invalidation is event-driven where multiple writers touch a key (Action → Event → Listener → `Cache::forget`); direct inline forget only for trivial single-writer cases | P0 | A | Full |
| FR-CORE-014 | Deployments bake `config:cache`, `route:cache`, `view:cache`, `event:cache` and run `php artisan system:cache-warm` | P1 | A | Full |
| FR-CORE-015 | Default session driver is `database` (auto-migrated, zero-config) | P0 | A | Full |
| FR-CORE-016 | Supported drivers: `database`, `redis`, `file`, `array` (testing) | P1 | A | Full |
| FR-CORE-017 | Session lifetime is 120 minutes of inactivity (via `SESSION_LIFETIME`) | P0 | A | Full |
| FR-CORE-018 | Sessions are encrypted (`SESSION_ENCRYPT=true`); cookies are HTTP-only, SameSite=lax, secure in production | P0 | A | Full |
| FR-CORE-019 | Session ID regenerates on login, logout, and privilege changes (fixation prevention) | P0 | F | Full |
| FR-CORE-020 | Garbage collection is probabilistic `[2, 100]` for the database driver; the Redis driver relies on key expiry (no application GC) | P1 | A | Full |
| FR-CORE-021 | Sessions carry auth state, CSRF token, locale preference, wizard progress, and setup authorization | P0 | A | Full |
| FR-CORE-022 | Default queue connection is `sync` (synchronous, no worker needed) | P0 | A | Full |
| FR-CORE-023 | Supported connections: `sync`, `database`, `redis` | P1 | A | Full |
| FR-CORE-024 | The `database` driver tables (jobs, batches) are auto-created by migration | P1 | A | Full |
| FR-CORE-025 | Failed jobs land in `failed_jobs` with the full exception trace | P0 | A | Full |
| FR-CORE-026 | Batch document generation dispatches to the `documents` pipeline; all other jobs to `default` (see [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md), [official-documents](7H5D6-official-documents.md)) | P1 | F | Full |
| FR-CORE-027 | Default mailer is `log` (development); `smtp` is the production choice | P0 | A | Full |
| FR-CORE-028 | SMTP configuration arrives via `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` env keys | P0 | A | Full |
| FR-CORE-029 | `TestMailSettingsAction` probes SMTP before settings persist — failed probes block the save | P0 | F | Full |
| FR-CORE-030 | Mail from-address resolves from `MAIL_FROM_ADDRESS`, falling back to the `support_email` setting | P1 | A | Full |
| FR-CORE-031 | Default disk is `local` via `FILESYSTEM_DISK=local` | P0 | A | Full |
| FR-CORE-032 | The `public` disk serves public assets; `storage:link` runs as part of `setup:install` | P0 | A | Full |
| FR-CORE-033 | The `s3` disk is available for object-storage deployments via `AWS_*` env | P2 | A | Full |
| FR-CORE-034 | All user uploads go through the media library on a configured disk — never raw `Storage::put` (see [file-uploads-media](WQGTP-file-uploads-media.md)) | P0 | A | Full |
| FR-CORE-035 | Media collections declare their storage disk and conversion presets in the owning Model | P0 | A | Full |
| FR-CORE-036 | Tier 0 no-regret optimizations are enforced at any scale: composite FK/activity-log indexes, cache-key registry, eager loading (no N+1), Read Actions avoiding transaction overhead | P0 | A | Full |
| FR-CORE-037 | Tier 1 (shared hosting, ≤500 users) runs on MySQL/MariaDB + file cache + sync queue + database session + local disk with zero external services | P0 | A | Full |
| FR-CORE-038 | Tier 2 (VPS, 500–2000 users) transitions are `.env` swaps with zero code changes (`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` + worker, `SESSION_DRIVER=redis`, optional `FILESYSTEM_DISK=s3`) | P1 | A | Full |
| FR-CORE-039 | Deferred until measured: Octane, horizontal auto-scaling, CDN for static assets, sharding, queue batching — adopted only when Pulse shows a bottleneck | P2 | — | Planned |
| FR-CORE-040 | Redis, when enabled, is one shared server with distinct connections: `cache`, `queue`, `session`, `default` (`REDIS_HOST`/`REDIS_PORT`/`REDIS_PASSWORD`) | P1 | A | Full |
| FR-CORE-041 | `APP_KEY` is present and non-empty (`base64:`) in all environments — encryption at rest for sessions and data | P0 | A | Full |
| FR-CORE-042 | Post-install defaults are asserted by config test: `queue=sync`, `cache=file`, `session=database` | P0 | A | Full |
| FR-CORE-043 | System health surfaces via `php artisan system:health` and the `/up` endpoint (detail in [system-maintenance](E1MSJ-system-maintenance.md)) | P1 | F | Full |

### 4.1 Database

#### FR-CORE-001 — SQLite by default

- `config/database.php` defaults to `DB_CONNECTION=sqlite`; `.env.example` documents the MySQL/PostgreSQL overrides beside it.
- **Verification:** config default assertion (layer `A`).

#### FR-CORE-002 — Production databases via .env

- Switching to MySQL/MariaDB/PostgreSQL is connection, host, port, database, and credential keys only — no code branch on driver.
- **Edge case:** charset/collation differences (utf8mb4 vs utf8) are handled in config, not in migrations.
- **Verification:** config review (layer `A`).

#### FR-CORE-003 — UTF-8 charset enforced

- Full emoji and multilingual school-name support; Indonesian locale content must never corrupt.
- **Verification:** config review (layer `A`).

#### FR-CORE-004 — UUID v7 primary keys

- Every model uses ordered UUID v7 (`HasUuids`, `$incrementing = false`, `$keyType = 'string'`); migrations use `foreignUuid()->constrained()`; mixed key types are forbidden. The sole exception is the `User` model path via `BaseAuthenticatable` (see SE5Q9 DD-BASE-003).
- **Governance:** [uuid-primary-keys ADR](../adr/adr-uuid-primary-keys.md).
- **Verification:** `scan_conventions.py` + migration review (layer `A`).

#### FR-CORE-005 — SQLite WAL + busy_timeout

- Single-writer SQLite stays safe under the light concurrency of a school install (concurrent attendance submissions).
- **Verification:** `config/database.php` review (layer `A`).

#### FR-CORE-006 — Explicit FK behavior (D6)

- Every foreign key declares what happens on delete/update (cascade, restrict, set-null) — no silent framework default.
- **Verification:** `scan_violations.py` D6 check (layer `A`).

#### FR-CORE-007 — Composite indexes (Tier-0)

- Indexes on FKs and the high-volume `activity_log` table are a no-regret move enforced at any scale — they cost nothing at 500 users and prevent rewrites at 2,000.
- **Verification:** migration review (layer `A`).

### 4.2 Cache

#### FR-CORE-008 — File cache by default

- `CACHE_STORE=file` needs no daemon and survives on the cheapest hosting. Redis is a one-line swap (DD-CORE-001).
- **Verification:** `config/cache.php` default (layer `A`).

#### FR-CORE-009 — Supported cache drivers

- `array` is reserved for the test suite (`phpunit.xml`); production chooses `file`, `database`, or `redis`.
- **Verification:** config review (layer `A`).

#### FR-CORE-010 — Key registry (C4)

- `config/cache-keys.php` exists and holds 25+ registered keys; no inline key strings in application code.
- **Verification:** `scan_violations.py` C4 check; `grep -r "Cache::" app/` resolves every key to the registry.

#### FR-CORE-011 — Key naming convention

- Namespaced keys (`settings.all`, `notification.unread:{id}`) keep ownership obvious and collisions impossible across modules.
- **Verification:** registry review (layer `A`).

#### FR-CORE-012 — TTL categories

- Authors pick a category instead of inventing seconds; `forever` keys must name their explicit invalidation path.
- **Verification:** review (layer `A`).

#### FR-CORE-013 — Event-driven invalidation

- The Final phase of the gradual-migration cache path: registry keys plus listener-driven invalidation for cross-module keys; inline `Cache::forget(config('cache-keys.xxx'))` stays legal for simple single-writer cases.
- **Governance:** [gradual-migration ADR](../adr/adr-gradual-migration.md).
- **Verification:** settings-update feature test (UC-CORE-002, layer `F`).

#### FR-CORE-014 — Deploy-time caches + warming

- Framework caches cut bootstrap time; `system:cache-warm` pre-populates application caches so the first user avoids the cold penalty.
- **Verification:** `SystemCacheWarmCommand` exists (`system:cache-warm`); pipeline review.

### 4.3 Session

#### FR-CORE-015 — Database sessions by default

- Database sessions survive process restarts (matters once queue workers exist) and need no daemon; the sessions table ships in migrations. `config/session.php` defaults to `SESSION_DRIVER=database`.
- **Verification:** config default (layer `A`).

#### FR-CORE-016 — Supported session drivers

- `file` suits single-process dev; `redis` is the Tier-2 swap; `array` is test-only.
- **Verification:** config review (layer `A`).

#### FR-CORE-017 — 120-minute lifetime

- Long enough for a teacher grading session, short enough to bound a stolen cookie. One env key, no code.
- **Verification:** config default (layer `A`).

#### FR-CORE-018 — Encryption + cookie flags

- Encrypted payloads plus HTTP-only/SameSite/secure flags close the cheap session attacks (XSS cookie theft, CSRF riding).
- **Verification:** config review + NFR-CORE-001 (layer `A`).

#### FR-CORE-019 — Fixation prevention

- `session()->regenerate()` runs on every authentication-state transition so a pre-login session ID is worthless after login.
- **Verification:** auth-flow feature test (layer `F`).

#### FR-CORE-020 — Garbage collection

- Probabilistic GC keeps the sessions table bounded without a scheduler dependency on shared hosting; Redis expiry makes application GC unnecessary there.
- **Verification:** config review (layer `A`).

#### FR-CORE-021 — Session contents

- The closed list (auth, CSRF, locale, wizard, setup gate) keeps sessions small and cookie/database rows predictable; business data lives in the database, not the session.
- **Verification:** review (layer `A`).

### 4.4 Queue

#### FR-CORE-022 — Sync queue by default

- Shared hosting runs no daemon, so jobs execute inline during the request. `config/queue.php` defaults to `QUEUE_CONNECTION=sync`. Acceptable at school scale; Tier 2 adds a worker via env.
- **Verification:** config default (layer `A`).

#### FR-CORE-023 — Supported queue connections

- `database` is the first step off sync (needs only the migrated tables); `redis` is the Tier-2 target. Worker supervision and retry policy live in [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md).
- **Verification:** config review (layer `A`).

#### FR-CORE-024 — Queue tables auto-migrated

- Enabling the `database` driver never requires hand-created tables.
- **Verification:** migration inventory (layer `A`).

#### FR-CORE-025 — Failed jobs with traces

- Every failed job keeps its exception trace for post-mortem; silent job loss is a defect.
- **Verification:** migration + queue-failure feature test (layer `A`/`F`).

#### FR-CORE-026 — Documents pipeline

- Long-running batch document generation is isolated on `documents` so a report batch never head-of-line-blocks interactive jobs on `default`: `dispatch(new GenerateDocumentJob(...))->onQueue('documents')`.
- **Verification:** dispatch-target feature test (layer `F`).

### 4.5 Mail

#### FR-CORE-027 — Log mailer by default

- A fresh install never fails on missing SMTP; `config/mail.php` defaults to `MAIL_MAILER=log`.
- **Verification:** config default (layer `A`).

#### FR-CORE-028 — SMTP via env

- Standard Laravel mailer keys; `ses`/`sendmail` remain available for schools that need them.
- **Verification:** config + `.env.example` review (layer `A`).

#### FR-CORE-029 — SMTP probe gate

- The Settings module's `TestMailSettingsAction` (extends `BaseCommandAction`) sends a probe with the unsaved values; the save is rejected unless the probe succeeds (DD-CORE-005, UC-CORE-004).
- **Verification:** probe-then-persist feature test (layer `F`).

#### FR-CORE-030 — From-address fallback

- Transactional mail always carries a valid sender even when the admin never configured one.
- **Verification:** review (layer `A`).

### 4.6 Filesystem & Storage

#### FR-CORE-031 — Local disk by default

- `config/filesystems.php` defaults to `FILESYSTEM_DISK=local`; zero credentials, zero network.
- **Verification:** config default (layer `A`).

#### FR-CORE-032 — Public disk + install-time link

- Public assets resolve without manual symlink steps after install.
- **Verification:** `setup:install` review (layer `A`).

#### FR-CORE-033 — S3 for object storage

- Tier-2/3 schools can move blobs to S3 with env keys only; application code always addresses the named disk, never a driver.
- **Verification:** config review (layer `A`).

#### FR-CORE-034 — Media library as the only upload path

- Centralizes storage, conversions, and secure serving behind a reviewed package instead of ad-hoc disk writes (DD-CORE-007, UC-CORE-005).
- **Verification:** `scan_violations.py` + upload feature test (layer `A`/`F`).

#### FR-CORE-035 — Collections declare disk + conversions

- Ownership is explicit: the Model that owns a collection names where its files live and which conversions exist.
- **Verification:** model review (layer `A`).

### 4.7 Growth Tiers (ADR-Demanded)

#### FR-CORE-036 — Tier 0 no-regret

- Always-on costs that never need a rewrite: UUID v7 locality, indexes, registry, eager loading, transaction-free reads. None requires a developer decision; all are structural.
- **Governance:** [performance-optimization ADR](../adr/adr-performance-optimization.md).
- **Verification:** migration review + C4 scan + N+1 review (layer `A`).

#### FR-CORE-037 — Tier-1 defaults

- The deployable default matrix, asserted by config test: sync queue, file cache, database session, local disk. Per [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md).
- **Verification:** FR-CORE-042 config test (layer `A`).

#### FR-CORE-038 — Tier-2 via config only

- No code branches on tier; every cache/queue/session call already goes through framework drivers, so the swap is env keys plus a worker process. Trigger: sustained >500 users or P95 > 1s. Full tier table in §6.4.
- **Verification:** config reads env keys; `.env` variants documented in [scaling guide](../guides/infra/scaling.md).

#### FR-CORE-039 — Deferred until measured

- Adopting any of these ahead of demand violates the no-regret doctrine; `Planned` is the deliberate status, not a gap. Before measurement, before the bottleneck is understood, before the feature stabilizes — no optimization.

### 4.8 Supporting Services

#### FR-CORE-040 — One shared Redis, distinct connections

- A single Redis server serves all three drivers with separate connections and prefixes (§6.3) — no per-driver servers to operate at school scale.
- **Verification:** `config/database.php` redis map (layer `A`).

#### FR-CORE-041 — APP_KEY enforced

- Missing key fails fast at boot in production (NFR-CORE-002); no encrypted payload may exist without it.
- **Verification:** boot assertion (layer `A`).

#### FR-CORE-042 — Defaults asserted by config test

- The Tier-1 matrix is a test, not a comment: drift in any default fails the suite.
- **Verification:** config test green (layer `A`).

#### FR-CORE-043 — Health reporting

- Operators and load balancers read service status from one command and one endpoint; per-service checks live in the maintenance spec.
- **Verification:** `SystemHealthCommand` (`system:health`) exists; endpoint smoke (layer `F`).

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO; `N/A` means enforced structurally via scans/tests rather than measured at runtime.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-CORE-001 | Session cookie is HTTP-only, SameSite=lax, secure in production, payload encrypted | Flags set in default config | P0 | A | Full |
| NFR-CORE-002 | `APP_KEY` is enforced — missing key fails fast at boot in production | Boot fails without key | P0 | A | Full |
| NFR-CORE-003 | Upload validation and media security follow [file-uploads-media](WQGTP-file-uploads-media.md) | 0 unvalidated upload paths | P0 | A | Full |
| NFR-CORE-004 | Graceful degradation: cache miss or store failure returns fresh data, never a cached error | 0 error pages caused by cache faults | P1 | F | Full |
| NFR-CORE-005 | Cache key registry stays a single discoverable file (`config/cache-keys.php`) | 100% of keys registered | P0 | A | Full |
| NFR-CORE-006 | Deployment caches + warming beat a cold first request | Warm first request < cold first request | P1 | A | Full |

### 5.1 Security

#### NFR-CORE-001 — Session cookie hardening

- Verified by reading `config/session.php` defaults: encrypt on, http-only on, same-site lax, secure flag honored in production. **Measurement:** config assertion (layer `A`).

#### NFR-CORE-002 — APP_KEY fail-fast

- A production boot without a key must crash loudly at startup, never serve with null encryption. **Measurement:** boot test (layer `A`).

#### NFR-CORE-003 — Media security by reference

- This spec owns the disk wiring; the security bar (validation, script-execution prevention) is defined and measured in [file-uploads-media](WQGTP-file-uploads-media.md). **Measurement:** that spec's gates.

### 5.2 Reliability

#### NFR-CORE-004 — Graceful degradation

- Cache faults are invisible to users: misses recompute, failures fall through to the database, and error payloads are never cached. **Measurement:** UC-CORE-006 feature test (layer `F`).

### 5.3 Operability

#### NFR-CORE-005 — Single registry file

- One file to grep, one file to audit; a key that cannot be found in the registry is a defect. **Measurement:** C4 scan (layer `A`).

#### NFR-CORE-006 — Warm beats cold

- The deploy pipeline's cache steps must produce a measurably faster first request than a cold boot. **Measurement:** deploy smoke comparing cold vs warm first request.

---

## 6. API / Data Contracts

### 6.1 Environment Variables (by Service)

```env
# Database
DB_CONNECTION=sqlite        # sqlite | mysql | mariadb | pgsql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=
DB_CHARSET=utf8mb4

# Cache
CACHE_STORE=file            # file | database | redis | memcached | array
CACHE_PREFIX=internara-cache-

# Session
SESSION_DRIVER=database     # database | redis | file | array
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Queue
QUEUE_CONNECTION=sync       # sync | database | redis
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
```

### 6.2 Cache Key Registry

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

### 6.3 Redis Connection Map

| Connection | Purpose | Prefix |
| ---------- | ------- | ------ |
| `default` | Generic / rate limiting | `laravel_database_` |
| `cache`   | Cache store (when `CACHE_STORE=redis`) | `internara-cache-` |
| `queue`   | Queue driver (when `QUEUE_CONNECTION=redis`) | `laravel_database_queue_` |
| `session` | Session driver (when `SESSION_DRIVER=redis`) | `laravel_database_session_` |

### 6.4 Tier-1 Defaults vs Tier-2 Swaps

| Service | Tier 1 (default, zero external services) | Tier 2 (VPS growth, `.env` swap) |
| ------- | ---------------------------------------- | -------------------------------- |
| Database | SQLite (dev) / MySQL-MariaDB (prod) | MySQL + optional read replica |
| Cache | `file` | `redis` |
| Session | `database` | `redis` |
| Queue | `sync` (no worker) | `redis` + `queue:work` |
| Mail | `log` (dev) / `smtp` (prod) | `smtp` / `ses` |
| Storage | `local` (+ `public` assets) | `local` + optional `s3` |

Trigger for Tier 2: sustained > 500 users or P95 > 1s. Zero code changes — every call already goes through framework drivers.

### 6.5 Queue Pipeline Contract

```php
// Batch document generation uses the 'documents' pipeline (FR-CORE-026):
dispatch(new GenerateDocumentJob(...))->onQueue('documents');
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless a decision has a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-CORE-001 | File cache as default over Redis | P0 | — | — |
| DD-CORE-002 | Database session as default over file | P0 | — | — |
| DD-CORE-003 | Sync queue as default over workers | P0 | — | — |
| DD-CORE-004 | SQLite as default database | P0 | — | — |
| DD-CORE-005 | SMTP validation gate before persist | P0 | — | — |
| DD-CORE-006 | Log mailer as default | P1 | — | — |
| DD-CORE-007 | Media library as the only upload path | P0 | — | — |
| DD-CORE-008 | Runtime service behavior split from the tech-stack spec | P0 | — | — |

### 7.1 Service Defaults

#### DD-CORE-001 — File Cache as Default

**Decision:** Default cache driver is `file`, not Redis.
**Rationale:** Shared hosting cannot install Redis; file cache works without external services. Switching is a one-line `.env` change (FR-CORE-008, FR-CORE-038).
**Trade-off:** File cache is slower and lacks atomic operations — acceptable for single-tenant workloads.

#### DD-CORE-002 — Database Session as Default

**Decision:** Default session driver is `database`, not `file`.
**Rationale:** Database sessions survive process restarts (important once queue workers exist) and support multi-process deployments; the sessions table is auto-created by migration (FR-CORE-015).
**Trade-off:** Slightly higher DB load per request — negligible below 1,000 concurrent users.

#### DD-CORE-003 — Sync Queue as Default

**Decision:** Default queue connection is `sync` (synchronous execution).
**Rationale:** Shared hosting has no queue workers; sync executes jobs inline. Production switches to `database`/`redis` via `.env` plus `php artisan queue:work` (FR-CORE-022).
**Trade-off:** No background processing on default config — acceptable for small-scale deployments.

#### DD-CORE-004 — SQLite as Default Database

**Decision:** Default connection is SQLite for development and shared hosting.
**Rationale:** Zero-config, file-based, WAL-enabled (FR-CORE-001, FR-CORE-005). Production uses MySQL 8 / MariaDB 10.6 / PostgreSQL 15 via `.env` (FR-CORE-002).
**Trade-off:** SQLite is single-writer; adequate for single-tenant with low concurrency.

#### DD-CORE-005 — SMTP Validation Gate

**Decision:** `TestMailSettingsAction` probes SMTP before settings persist (FR-CORE-029).
**Rationale:** Wrong mail config silently breaks notifications; validating before persist surfaces errors at edit time (PS-4).
**Trade-off:** Requires reachable SMTP at save time — acceptable for a school admin workflow.

#### DD-CORE-006 — Log Mailer as Default

**Decision:** Default mailer is `log`.
**Rationale:** Development and default installs never fail on missing SMTP; `smtp` is the documented production choice (FR-CORE-027).
**Trade-off:** Default install sends no real mail until configured — expected for zero-config deployment.

#### DD-CORE-007 — Media Library as the Only Upload Path

**Decision:** All user uploads go through the media library on a configured disk (FR-CORE-034).
**Rationale:** Centralizes storage, conversions, and serving behind a security-reviewed package instead of ad-hoc `Storage::put` calls.
**Trade-off:** Adds Spatie MediaLibrary as a hard dependency (already pinned in FB792).

#### DD-CORE-008 — Split from the Tech-Stack Spec (Recorded Decision)

**Decision:** Runtime service behavior moved from FB792 into this spec; FB792 retains only the dependency manifest.
**Rationale:** Separate "what is installed" from "how it behaves" so each side evolves independently and service docs stay navigable (PS-6).
**Trade-off:** Two specs to consult for service topics — mitigated by explicit cross-references in §9.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Key registration | 100% of cache keys in registry | `grep -r "Cache::" app/` → all keys resolve to config |
| Stale data window | No stale reads after settings change | UC-CORE-002 feature test |
| Cache warm time | `system:cache-warm` completes clean | `time php artisan system:cache-warm` |
| Session encryption | Always enabled | `SESSION_ENCRYPT=true` in default config |
| Session lifetime | 120 minutes | Default config value |
| Fixation prevention | Regenerated on auth change | `session()->regenerate()` in login/logout flow |
| SMTP validation | 100% of saved SMTP configs probed | `TestMailSettingsAction` before persist |
| Zero-config startup | Works with `composer install` + `.env` copy | No Redis/Memcached/worker required |
| Warm beats cold | Warm first request faster than cold | Deploy smoke after cache steps |

---

## 9. Roadmap

### Prerequisites

- [tech-stack.md](FB792-tech-stack.md) — pins the package versions these services rely on
- [architecture-design](D2FT3-architecture.md) — places these services in the Framework/Infra layer

### Build Guide

This spec governs configuration files (`config/cache.php`, `config/session.php`, `config/queue.php`, `config/mail.php`, `config/database.php`, `config/filesystems.php`), `config/cache-keys.php`, and the helper Actions built on them (e.g. `TestMailSettingsAction`). Per-service requirements are satisfied by those configs plus the consuming modules' features.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [base-classes.md](SE5Q9-base-classes.md) | Base classes (BaseModel, Actions) consume cache/session/queue/mail services |
| 2 | [file-uploads-media.md](WQGTP-file-uploads-media.md) | Media library storage on configured disks (FR-CORE-034) |
| 3 | [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) | Queue lifecycle, retries, batches (FR-CORE-022–026) |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | `docker/shared-hosting/Dockerfile` sets `SESSION_DRIVER=file`, diverging from the FR-CORE-015 `database` default — if the image is authoritative, the default or the Dockerfile needs alignment | Open | Maintainer | — |
| A-1 | We assume Tier-1 defaults are continuously verified by the FR-CORE-042 config test plus the shared-hosting deploy preset | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture design](D2FT3-architecture.md) — places these services in the Framework/Infra layer
- [Tech stack](FB792-tech-stack.md) — dependency manifest these services are configured from
- [Base classes](SE5Q9-base-classes.md) — BaseModel and Actions consuming these services
- [Performance ADR](../adr/adr-performance-optimization.md) — Tier 0/1/2/3 growth doctrine
- [UUID ADR](../adr/adr-uuid-primary-keys.md) — why UUID v7 primary keys
- [Gradual migration ADR](../adr/adr-gradual-migration.md) — cache-invalidation migration path
- [Cache guide](../guides/infra/cache.md) — driver configuration and invalidation
- [Session guide](../guides/infra/session.md) — driver configuration and GC
- [Queue guide](../guides/infra/queue.md) — sync default and Tier-2 workers
- [Scaling guide](../guides/infra/scaling.md) — Tier-2 `.env` swap table
