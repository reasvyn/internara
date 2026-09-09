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

A small SMK outside Semarang pays $5 a month for a shared-hosting account with no Redis, no Memcached, and no supervisor for workers. The operator clones the repo, runs `composer install`, copies `.env.example`, and runs `php artisan setup:install`. SQLite is created on disk, migrations run, cache resolves to `file`, sessions to `database`, queue to `sync`, and mail to `log`, and the application works end to end without any daemon to babysit. That zero-external-services promise is pinned by a config test asserting `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, and `SESSION_DRIVER=database` under FR-CORE-042 at layer `A`, and the same matrix is repeated in the deploy preset described in [shared-hosting-deployment](06IB6-shared-hosting-deployment.md).

#### UC-CORE-002 — Cache Invalidates on Settings Change

When a super admin presses save in the Settings UI, the request travels through a Command Action that commits the row inside a transaction and only then dispatches its success event. A listener waiting on that event calls `Cache::forget()` against the single affected registered key, so the very next request recomputes fresh data without a full cache flush and without a stale window longer than one request. This is the Stabilize phase of the gradual-migration cache path from the [gradual-migration ADR](../adr/adr-gradual-migration.md), where a trivial single-writer key still uses an inline forget while any key with multiple writers graduates to the event plus listener shape. A feature test exercising the settings update flow at layer `F` proves the old value disappears and the fresh value renders.

#### UC-CORE-003 — Deployment Warms Caches

Picture enrollment Monday at 07:00: three hundred students hit the login page at once and the first request pays the full cold-start bootstrap of config, routes, views, and events. The deploy pipeline avoids that stampede by baking `php artisan config:cache route:cache view:cache event:cache` and then running `php artisan system:cache-warm`, implemented in `SysAdmin/.../Console/Commands/SystemCacheWarmCommand.php`, so application caches are already populated before real traffic arrives. The command simply has to exist and exit clean, which a layer `F` smoke test asserts after every pipeline change.

### 3.2 Admin & End-User Flows

#### UC-CORE-004 — Admin Validates SMTP Before Saving

The probe gate was added after too many schools discovered a mail typo weeks later, when a password-reset email never arrived during placement week. Now the admin types host, port, and credentials into the Settings page and clicks Test, and `TestMailSettingsAction`, a `BaseCommandAction` in the Settings module, sends a probe email using the unsaved values. A failure surfaces the error inline and nothing is persisted, while a success lets the settings save so future mail flows through SMTP. A layer `F` feature test walks the whole probe-then-persist flow to keep that gate honest.

#### UC-CORE-005 — File Upload Through the Media Library

Let any caller write user content with raw `Storage::put` and the failure modes multiply: an executable lands in a web-reachable folder, a thumbnail variant is missing on the certificate page, a disk swap to S3 breaks half the call sites. The single allowed path avoids all of that. A student uploads internship evidence through a Livewire upload field, the Command Action stores it via the media library on the configured disk, and the library registers conversions and serves the file through its secure route. Validation, collections, and conversions are specified in [file-uploads-media](WQGTP-file-uploads-media.md), and a layer `F` feature test on the upload flow confirms nothing bypasses the library.

#### UC-CORE-006 — Cache Store Fails Gracefully

An SMK in Cirebon filled its hosting quota the night before report printing, leaving the file cache directory unwritable at runtime. A request needing a cached value simply missed, recomputed from the database, and completed normally. No error page appeared and no error payload was written back into the cache to poison later reads. A layer `F` feature test simulates that unwritable store so degradation stays invisible to users.

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

At boot Laravel reads `config/database.php`, which defaults to `DB_CONNECTION=sqlite`, while `.env.example` documents the MySQL and PostgreSQL overrides beside it for schools that outgrow the file. A layer `A` config default assertion guards that default so a careless edit cannot silently flip fresh installs to a driver with no server behind it.

#### FR-CORE-002 — Production databases via .env

A school that starts on SQLite and moves to MySQL, MariaDB, or PostgreSQL changes only connection, host, port, database, and credential keys. No application code branches on the driver name, which is why the same binary runs in both places.

The one place this bites is charset: MySQL wants `utf8mb4` while PostgreSQL expects `utf8`, and a student name like "Siti Nurhaliza ♥" will corrupt if the wrong one leaks into a migration. That difference is resolved in config, never in migrations, and a layer `A` config review confirms no migration hardcodes a collation.

#### FR-CORE-003 — UTF-8 charset enforced

The rule exists because school names, street addresses, and student notes routinely mix Indonesian, Arabic script, and emoji. Full emoji and multilingual support means Indonesian locale content never corrupts between form submit and report print, a property a layer `A` config review of the charset settings keeps pinned.

#### FR-CORE-004 — UUID v7 primary keys

Mix one auto-increment table into a UUID-joined schema and every `foreignUuid` join against it breaks at migration time. So every model uses ordered UUID v7 through `HasUuids` with `$incrementing = false` and `$keyType = 'string'`, migrations use `foreignUuid()->constrained()`, and mixed key types are forbidden outright. The sole exception is the `User` model path through `BaseAuthenticatable` described in SE5Q9 DD-BASE-003, governed by the [uuid-primary-keys ADR](../adr/adr-uuid-primary-keys.md) and checked by `scan_conventions.py` plus migration review at layer `A`.

#### FR-CORE-005 — SQLite WAL + busy_timeout

Thirty students tapping clock-in within the same minute is enough to collide writers on single-writer SQLite. WAL journal mode plus `busy_timeout` keeps those concurrent attendance submissions safe on a school install, and a layer `A` review of `config/database.php` confirms both flags stay set.

#### FR-CORE-006 — Explicit FK behavior (D6)

When a partnership row is deleted, the framework default would silently decide what happens to its placements — and silently is exactly the problem. Every foreign key therefore declares its own on-delete and on-update behavior, cascade, restrict, or set-null, so a reviewer can read intent off the migration. The `scan_violations.py` D6 check at layer `A` fails the build if any key leans on the framework default.

#### FR-CORE-007 — Composite indexes (Tier-0)

Consider the `activity_log` table after one placement period: tens of thousands of rows, and every audit view filters by foreign key plus timestamp. Without composite indexes that page crawls at 2,000 users; with them it costs nothing extra at 500 users. Indexes on foreign keys and the high-volume `activity_log` table are therefore enforced at any scale as a no-regret move, confirmed by migration review at layer `A`.

### 4.2 Cache

#### FR-CORE-008 — File cache by default

Early pilots assumed Redis would be available and then met real school hosting, where no daemon can be installed. The default was flipped to `CACHE_STORE=file` so the cheapest account works with nothing to run, while Redis remains a one-line swap described in DD-CORE-001. A layer `A` check of the `config/cache.php` default keeps that promise from drifting.

#### FR-CORE-009 — Supported cache drivers

Let a developer reach for `array` in production and every request silently recomputes everything, turning the morning attendance rush into a database stampede. The `array` driver is therefore reserved for the test suite through `phpunit.xml`, while production chooses `file`, `database`, or `redis`, a boundary a layer `A` config review enforces.

#### FR-CORE-010 — Key registry (C4)

An SMK admin renamed a setting and half the dashboard kept showing the old quota for a day, because the key lived as a string literal in three files and the forget call missed one. The registry fixes that shape: `config/cache-keys.php` exists and holds 25+ registered keys, and no inline key strings survive in application code. The `scan_violations.py` C4 check plus a `grep -r "Cache::" app/` pass that resolves every key to the registry, both at layer `A`, make an unregistered key a build failure.

#### FR-CORE-011 — Key naming convention

At runtime a read builds its key as `{module}.{purpose}` with an optional `.{qualifier}`, so `settings.all` and `notification.unread:{id}` carry their owner in the name. Collisions across eighteen modules become structurally impossible instead of a matter of memory, and a layer `A` registry review confirms every entry follows the namespace.

#### FR-CORE-012 — TTL categories

A dashboard author guessing 47 seconds for one widget and 3600 for another leaves the next maintainer with no idea which values are safe to tune. Authors instead pick a category, short under five minutes, medium five minutes to an hour, long one hour to a day, or forever, and any `forever` key must name its explicit invalidation path so it can never linger by accident. A layer `A` review checks the category choice and the named path together.

#### FR-CORE-013 — Event-driven invalidation

The cache path migrated in phases because ripping every inline forget out on day one would have stalled feature work. The Final phase pairs registry keys with listener-driven invalidation for cross-module keys, while inline `Cache::forget(config('cache-keys.xxx'))` stays legal for simple single-writer cases. That migration story is recorded in the [gradual-migration ADR](../adr/adr-gradual-migration.md), and the settings-update feature test from UC-CORE-002 at layer `F` proves the listener path clears what the writer changed.

#### FR-CORE-014 — Deploy-time caches + warming

Skip the bake step and the first teacher login after a deploy pays full bootstrap while thirty students queue behind her. Framework caches cut that bootstrap time and `system:cache-warm` pre-populates application caches so the first user avoids the cold penalty. The pipeline review confirms the steps run, and the existence of `SystemCacheWarmCommand` behind `system:cache-warm` is itself asserted.

### 4.3 Session

#### FR-CORE-015 — Database sessions by default

A vocational school in Bandung restarted its worker process mid-morning and every file-backed session evaporated, logging out a whole grading room. Database sessions survive those restarts, which starts to matter the moment queue workers exist, and they need no daemon, with the sessions table shipping in migrations. `config/session.php` therefore defaults to `SESSION_DRIVER=database`, a default a layer `A` config check holds in place.

#### FR-CORE-016 — Supported session drivers

At runtime the driver resolves from environment: `file` for single-process development, `redis` as the Tier-2 swap when the school grows onto a VPS, and `array` strictly for tests. A layer `A` config review keeps each driver in its lane so production never accidentally runs on the test driver.

#### FR-CORE-017 — 120-minute lifetime

A teacher grading forty logbooks needs room to work without re-authenticating every few minutes, yet a stolen cookie should not stay useful until tomorrow. One hundred twenty minutes of inactivity threads that needle, set through a single `SESSION_LIFETIME` env key with no code change, and a layer `A` config default test locks the value.

#### FR-CORE-018 — Encryption + cookie flags

Session hijacking used to be a cheap attack: steal the cookie over XSS, ride it over CSRF. Encrypted payloads plus HTTP-only, SameSite, and secure flags close those cheap paths, which is why the defaults demand encryption and hardened cookies from the first boot. A layer `A` config review alongside NFR-CORE-001 confirms the flags are actually set.

#### FR-CORE-019 — Fixation prevention

Hand an attacker a pre-login session ID and, without regeneration, that ID stays valid after the victim logs in. Calling `session()->regenerate()` on every authentication-state transition, login, logout, and privilege change, makes the pre-login ID worthless the moment identity changes. An auth-flow feature test at layer `F` logs in and out to prove the ID actually turns over.

#### FR-CORE-020 — Garbage collection

An SMK with no cron and no scheduler still accumulates abandoned session rows every term. Probabilistic garbage collection keeps the sessions table bounded without any scheduler dependency on shared hosting, while the Redis driver simply relies on key expiry so application GC becomes unnecessary there. A layer `A` config review checks the `[2, 100]` lottery settings and the Redis exemption side by side.

#### FR-CORE-021 — Session contents

Every value placed in the session is read on every request, so the framework resolves only a closed list: auth state, CSRF token, locale preference, wizard progress, and setup authorization. Business data lives in the database, never in the session, which keeps cookie and database rows small and predictable, a boundary a layer `A` review enforces whenever someone proposes stashing a report in the session.

### 4.4 Queue

#### FR-CORE-022 — Sync queue by default

A student submitting an assignment at an SMK on shared hosting cannot wait for a worker daemon that does not exist. Jobs therefore execute inline during the request, with `config/queue.php` defaulting to `QUEUE_CONNECTION=sync`. That inline shape is acceptable at school scale, Tier 2 adds a worker purely through environment, and a layer `A` config default test keeps sync as the starting point.

#### FR-CORE-023 — Supported queue connections

The connection ladder was ordered by operational cost: `database` is the first step off sync because it needs only the migrated tables, and `redis` is the Tier-2 target once a server is available. Worker supervision and retry policy for those drivers live in [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md), and a layer `A` config review confirms the three connections stay wired.

#### FR-CORE-024 — Queue tables auto-migrated

Forgetting to create the jobs table turns the first background dispatch into a midnight 500. Enabling the `database` driver therefore never requires hand-created tables; migrations carry the jobs and batch tables along. A layer `A` migration inventory proves the tables ship with the codebase.

#### FR-CORE-025 — Failed jobs with traces

An SMK operator once lost a whole certificate batch with no trace of why, because failed jobs vanished. Every failed job now keeps its full exception trace in `failed_jobs` for post-mortem, and silent job loss counts as a defect. Migration presence plus a queue-failure feature test, at layers `A` and `F` together, guard that trail.

#### FR-CORE-026 — Documents pipeline

At runtime a batch report fans out dozens of long PDF jobs that would head-of-line-block password resets and notifications if they shared one queue. Long-running batch document generation is therefore isolated on `documents` while everything interactive stays on `default`, dispatched as `dispatch(new GenerateDocumentJob(...))->onQueue('documents')`. A layer `F` dispatch-target feature test asserts each job lands on its intended pipeline.

### 4.5 Mail

#### FR-CORE-027 — Log mailer by default

A fresh install with no SMTP credentials should still boot, register users, and run the setup wizard. The `log` mailer makes that true: `config/mail.php` defaults to `MAIL_MAILER=log` so mail is captured to the log instead of failing, a default a layer `A` config check protects.

#### FR-CORE-028 — SMTP via env

Production mail was always meant to be configuration, never code. Standard Laravel mailer keys carry `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, and `MAIL_PASSWORD`, while `ses` and `sendmail` remain available for schools that need them. A layer `A` review of config plus `.env.example` keeps those keys documented and swappable.

#### FR-CORE-029 — SMTP probe gate

A typo in the SMTP host once silenced a school's notifications for three weeks. The Settings module now refuses to persist untested values: `TestMailSettingsAction`, extending `BaseCommandAction`, sends a probe with the unsaved values and the save is rejected unless the probe succeeds, per DD-CORE-005 and UC-CORE-004. A layer `F` probe-then-persist feature test types bad credentials on purpose to prove the gate holds.

#### FR-CORE-030 — From-address fallback

An SMK secretary who never opens the mail settings still triggers password resets and assignment notices. Transactional mail therefore always carries a valid sender, resolving `MAIL_FROM_ADDRESS` first and falling back to the `support_email` setting when nothing was configured. A layer `A` review confirms no mail path can emit a blank sender.

### 4.6 Filesystem & Storage

#### FR-CORE-031 — Local disk by default

At boot the filesystem resolves through `config/filesystems.php`, which defaults to `FILESYSTEM_DISK=local`. Zero credentials and zero network calls mean uploads work on the cheapest hosting on day one, and a layer `A` config default test prevents that default from quietly changing.

#### FR-CORE-032 — Public disk + install-time link

A teacher sharing a handbook link the day after install should never meet a broken image because someone forgot a symlink step. The `public` disk serves public assets and `storage:link` runs as part of `setup:install`, so assets resolve with no manual intervention. A layer `A` review of the install command keeps that step wired.

#### FR-CORE-033 — S3 for object storage

Object storage was held back until schools actually asked for it, then added as pure configuration. Tier-2 and Tier-3 schools move blobs to the `s3` disk with `AWS_*` env keys only, while application code always addresses the named disk and never a driver name. A layer `A` config review confirms no caller hardcodes the driver.

#### FR-CORE-034 — Media library as the only upload path

Scattered `Storage::put` calls once meant one module validated MIME types while another served uploads executable. Centralizing storage, conversions, and secure serving behind the media library, per DD-CORE-007 and UC-CORE-005, leaves a single reviewed path for every upload. The `scan_violations.py` check plus an upload feature test, at layers `A` and `F`, fail any raw disk write for user content.

#### FR-CORE-035 — Collections declare disk + conversions

An SMK avatar collection that lives on `public` with a thumbnail conversion and an evidence collection that lives on `local` with a preview conversion should declare that difference where it belongs. Ownership is explicit: the Model that owns a collection names where its files live and which conversions exist. A layer `A` model review reads that declaration off each owning Model.

### 4.7 Growth Tiers (ADR-Demanded)

#### FR-CORE-036 — Tier 0 no-regret

Tier 0 never asks a developer to decide: ordered UUID v7 keys keep inserts local, composite indexes keep joins fast, the `config/cache-keys.php` registry keeps invalidation greppable, eager loading avoids N+1, and Read Actions stay transaction-free so reporting never blocks writes. Those always-on structural choices are governed by the [performance-optimization ADR](../adr/adr-performance-optimization.md) and checked by migration review plus the C4 scan plus N+1 review at layer `A`.

#### FR-CORE-037 — Tier-1 defaults

A school unpacking the release on shared hosting gets sync queue, file cache, database session, and local disk with no questions asked. That deployable default matrix, per the [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md), is asserted by the FR-CORE-042 config test at layer `A`, so any drift breaks the suite before it reaches a school.

#### FR-CORE-038 — Tier-2 via config only

Growth was designed as an `.env` swap because every cache, queue, and session call already goes through framework drivers. No code branches on tier; the operator sets `CACHE_STORE=redis` or `QUEUE_CONNECTION=redis` plus a worker and restarts, once sustained load passes roughly 500 users or P95 passes a second, with the full tier table in §6.4. Config reading env keys plus the `.env` variants documented in the [scaling guide](../guides/infra/scaling.md) prove the swap needs no rewrite.

#### FR-CORE-039 — Deferred until measured

Spinning up Octane, sharding, a CDN, or read replicas before any bottleneck is measured burns weeks and complicates every later fix. Adopting any of them ahead of demand violates the no-regret doctrine, so `Planned` here is a deliberate status rather than a gap: no optimization before measurement, before the bottleneck is understood, and before the feature stabilizes.

### 4.8 Supporting Services

#### FR-CORE-040 — One shared Redis, distinct connections

An SMK running its first VPS cannot operate three separate Redis clusters. A single shared Redis server therefore serves cache, queue, and session through distinct connections and prefixes as mapped in §6.3, and a layer `A` check of the redis map in `config/database.php` confirms the separation.

#### FR-CORE-041 — APP_KEY enforced

At boot in production the framework refuses to serve with null encryption: a missing `APP_KEY` fails fast instead of writing session payloads nobody can safely read. That fail-fast behavior, tracked as NFR-CORE-002, is covered by a layer `A` boot assertion.

#### FR-CORE-042 — Defaults asserted by config test

A comment saying the defaults are sync, file, and database rots within a month. The Tier-1 matrix lives as a test instead: drift in any default fails the suite, and a green layer `A` config test is the proof the matrix still holds.

#### FR-CORE-043 — Health reporting

The maintenance story split early: this spec owns the surface, one `SystemHealthCommand` behind `system:health` plus the `/up` endpoint for operators and load balancers, while per-service checks live in the maintenance spec. Existence of the command plus an endpoint smoke at layer `F` keeps that surface from silently disappearing.

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

Leave one cookie flag off and a stolen session rides over plain HTTP or leaks through JavaScript. Reading the `config/session.php` defaults shows encrypt on, http-only on, same-site lax, and the secure flag honored in production, and a layer `A` config assertion keeps all four from regressing.

#### NFR-CORE-002 — APP_KEY fail-fast

An SMK that deploys with an empty `.env` must see a loud crash at startup, never a login page running on null encryption. A production boot without a key therefore fails fast instead of serving, and a layer `A` boot test proves the crash happens.

#### NFR-CORE-003 — Media security by reference

At runtime the disk wiring in this spec hands every upload to the media library, while the actual security bar for validation and script-execution prevention is defined and measured in [file-uploads-media](WQGTP-file-uploads-media.md). That spec's gates are the measurement here, so this requirement never duplicates its thresholds.

### 5.2 Reliability

#### NFR-CORE-004 — Graceful degradation

Picture the cache disk filling up during report week: requests miss, fall through to the database, recompute, and render normally, with error payloads never written back to poison later reads. Cache faults stay invisible to users that way, and the UC-CORE-006 feature test at layer `F` simulates the fault to prove it.

### 5.3 Operability

#### NFR-CORE-005 — Single registry file

The registry survived because there is exactly one file to grep and one file to audit. `config/cache-keys.php` holds every key, so a key that cannot be found there is a defect by definition, and the C4 scan at layer `A` enforces the single-file rule.

#### NFR-CORE-006 — Warm beats cold

Skipping the warm step leaves the first teacher after a deploy waiting on a cold bootstrap while students queue behind her. The deploy pipeline's cache steps must therefore produce a measurably faster first request than a cold boot, compared by a deploy smoke that times cold versus warm.

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

A $5 shared-hosting account in Yogyakarta cannot install Redis, so defaulting to it would exclude the very schools Internara targets. The default cache driver is therefore `file`, which works with no daemon, and switching stays a one-line `.env` change per FR-CORE-008 and FR-CORE-038. File cache runs slower and lacks atomic operations, an accepted cost at single-tenant school scale.

#### DD-CORE-002 — Database Session as Default

At runtime `file` sessions vanish when the process restarts, which starts to hurt the moment queue workers exist and breaks multi-process deployments outright. The default session driver is `database` instead, with the sessions table auto-created by migration per FR-CORE-015. The extra write per request is negligible below a thousand concurrent users.

#### DD-CORE-003 — Sync Queue as Default

Consider a fresh install with no supervisor and no worker: any queued job would sit unprocessed forever. The default queue connection is `sync` so jobs execute inline during the request, and production moves to `database` or `redis` through `.env` plus `php artisan queue:work` per FR-CORE-022. No background processing on the default config is the accepted price for zero-config startup.

#### DD-CORE-004 — SQLite as Default Database

The zero-config story demanded a database that is just a file. The default connection is SQLite for development and shared hosting, file-based and WAL-enabled per FR-CORE-001 and FR-CORE-005, while production uses MySQL 8, MariaDB 10.6, or PostgreSQL 15 through `.env` per FR-CORE-002. Single-writer limits are adequate for a single tenant with light concurrency.

#### DD-CORE-005 — SMTP Validation Gate

A mistyped SMTP password once silenced a school for weeks because nothing validates at save time. `TestMailSettingsAction` now probes SMTP with the unsaved values before settings persist per FR-CORE-029 and UC-CORE-004, surfacing errors at edit time as PS-4 demands. Requiring a reachable SMTP server at save time is accepted because admins save mail settings rarely.

#### DD-CORE-006 — Log Mailer as Default

An SMK secretary running setup for the first time has no SMTP credentials yet. The default mailer is `log` so development and default installs never fail on missing mail, with `smtp` as the documented production choice per FR-CORE-027. Sending no real mail until configured is the expected shape of a zero-config deployment.

#### DD-CORE-007 — Media Library as the Only Upload Path

At runtime every upload flows through the media library on a configured disk: storage, conversions, and secure serving resolve in one place instead of scattered `Storage::put` calls with divergent validation. All user uploads take that path per FR-CORE-034 and UC-CORE-005. The cost is a hard dependency on Spatie MediaLibrary, already pinned in FB792.

#### DD-CORE-008 — Split from the Tech-Stack Spec (Recorded Decision)

Early readers kept tripping over one spec that mixed version pins with driver lifetimes and security flags. Runtime service behavior therefore moved from FB792 into this spec while FB792 retains only the dependency manifest, separating what is installed from how it behaves so each side evolves independently per PS-6. Consulting two specs for service topics is mitigated by explicit cross-references in §9.

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
