# System Requirements — Dependencies, Platform & Database

> **Spec ID:** J68GZ

## Description

Pins the runtime floor every Internara instance must meet: PHP 8.4 with required extensions, the
locked Composer dependency manifest, and portable database support across SQLite, MySQL, MariaDB,
and PostgreSQL. Tier-1 shared-hosting defaults run with zero external services while Tier-2/3
growth is a configuration-only switch, and a 15-point `system:health` check makes the floor
verifiable at boot. Base classes, contracts, middleware, cache, and session are a separate
initiative — see [base-classes.md](SE5Q9-base-classes.md).

---

## 1. Problem Statements

### PS-1 — Dependency Management

The system relies on a dozen production packages with specific version constraints. A broken
dependency or version mismatch cascades across all 19 modules, so package selection must balance
feature needs against maintenance burden and security surface, with locked versions for
reproducible builds.
**→ Requirement:** FR-SYS-008/009/010 (framework, UI, RBAC pins), FR-SYS-018 (locked manifest).

### PS-2 — Minimum System Requirements

Schools operate on diverse hosting — from shared hosting with plain PHP 8.4 to a VPS with Redis.
The system must state clearly what is required versus recommended, and fail with an actionable
message when the floor is not met rather than producing cryptic errors.
**→ Requirement:** FR-SYS-001/002/003 (PHP floor and extensions), FR-SYS-031/032 (health check).

### PS-3 — Database Portability

Different schools have different database capabilities: SQLite for zero-config development, MySQL
or MariaDB for shared hosting, PostgreSQL for larger deployments. The system must work across all
four without module-specific SQL — portable Eloquent queries and migrations only.
**→ Requirement:** FR-SYS-019/020/021/022 (engine support), FR-SYS-023/024 (UUID keys, FK behavior).

### PS-4 — Growth Without Rewrite

An instance that starts on $3–15/month shared hosting must be able to grow past 500 and 2000
users without a code rewrite. Tier transitions therefore have to be `.env` swaps, with explicit
triggers for when each tier applies and an explicit list of what stays deferred until measured.
**→ Requirement:** FR-SYS-027 (Tier-1 defaults), FR-SYS-028/029 (Tier-2/3 triggers), DD-SYS-003.

---

## 2. Goals & Non-Goals

### Goals

- **Pinned runtime floor** — PHP 8.4 plus a required/recommended extension split with clear boot-time errors. *Why:* schools self-install on unknown hosting; an explicit floor turns cryptic fatals into actionable messages.
- **Portable database** — SQLite default with MySQL, MariaDB, and PostgreSQL support via portable Eloquent only. *Why:* one codebase must run on shared hosting today and a larger database tomorrow.
- **Locked dependency manifest** — every production package pinned with a committed lockfile. *Why:* reproducible builds across 19 modules; a drifting transitive dependency must never break an install silently.
- **Tiered deployment** — Tier-1 shared-hosting defaults with zero external services; Tier-2/3 as configuration-only switches. *Why:* MVP velocity now, growth without rewrites later (per the performance-optimization ADR).
- **Verifiable health** — a 15-point `system:health` check covering environment, database, cache, queue, and storage. *Why:* the floor is only real if a single command can prove it on any instance.

### Non-Goals

- **Multi-tenant database partitioning**. *Why:* single-tenant by product definition (per the self-hosted single-tenant ADR); one school per instance needs no tenant isolation.
- **Custom ORM or query builder**. *Why:* Eloquent is the persistence layer; portability comes from avoiding raw module-specific SQL, not from a new abstraction.
- **Container orchestration**. *Why:* Docker Compose only — no Kubernetes; the deployment matrix in [conditional-deployment.md](06IB6-deployment.md) stays at Compose plus shared hosting.
- **Load-test CI gates (k6 thresholds, uptime-SLA monitoring)**. *Why:* post-MVP operational depth; the performance-optimization ADR defers optimization until Pulse measurement shows a bottleneck.

---

## 3. User Stories / Use Cases

Three environmental journeys. The first two are verified by install CI and deploy smoke rather than
pest layers, so `Layer`/`Status` stay `—`; the boot check is backed by the health command.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SYS-001 | Developer clones the repository and provisions a working instance on a compatible environment | P0 | — | — |
| UC-SYS-002 | School IT staff deploys the system on shared hosting within the Tier-1 minimums | P0 | — | — |
| UC-SYS-003 | System checks requirements on boot and reports failures with actionable messages | P0 | F | Full |

### 3.1 Provisioning Journeys

#### UC-SYS-001 — Developer Clones and Installs the Project

**Actor:** Developer
**Preconditions:** PHP 8.4+, Composer 2.0+, Node.js + npm available.
**Flow:**
1. Developer clones the repository
2. Runs `composer install` — all production packages install successfully
3. Runs `cp .env.example .env` and `php artisan key:generate`
4. Runs `php artisan migrate` — SQLite database created with the full schema
5. Runs `npm install && npm run build` — Vite build completes
**Postconditions:** System ready for development without additional configuration.
**Governing guidance:** FR-SYS-001–FR-SYS-007 (floor), FR-SYS-008–FR-SYS-018 (manifest).

#### UC-SYS-002 — School Deploys on Shared Hosting

**Actor:** School IT staff
**Preconditions:** Shared hosting with PHP 8.4+, MySQL 8.0+, no Redis.
**Flow:**
1. IT staff uploads files via FTP/File Manager
2. Creates a MySQL database via the hosting control panel
3. Updates `.env` with DB credentials (`DB_CONNECTION=mysql`)
4. Runs `php artisan migrate` — all migrations execute on MySQL
5. System operates with file cache and database sessions
**Postconditions:** System functional without Redis or additional services.
**Governing guidance:** FR-SYS-020 (MySQL), FR-SYS-027 (Tier-1 defaults).

#### UC-SYS-003 — System Checks Requirements on Boot

**Actor:** System (automated)
**Preconditions:** PHP version or extensions missing.
**Flow:**
1. Boot or `php artisan system:health` detects PHP < 8.4.0
2. Reports a clear message: "PHP 8.4.0 or higher required (current: 8.3.x)"
3. Lists missing required versus recommended extensions separately
**Postconditions:** Operator receives an actionable error message, not a cryptic failure.
**Governing guidance:** FR-SYS-031/032; full command contract in
[system-maintenance.md](E1MSJ-system-maintenance.md).

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch
(structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SYS-001 | PHP >= 8.4.0 is required | P0 | F | Full |
| FR-SYS-002 | Required extensions: bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip | P0 | F | Full |
| FR-SYS-003 | Recommended extensions: redis, pcntl, posix | P2 | F | Full |
| FR-SYS-004 | Composer >= 2.0 is required for dependency management | P0 | A | Full |
| FR-SYS-005 | Node.js + npm is required for the frontend build (Vite, Tailwind CSS) | P0 | A | Full |
| FR-SYS-006 | `storage/` and `bootstrap/cache/` directories must be writable | P0 | F | Full |
| FR-SYS-007 | `APP_KEY` must be set (32-character base64 string) | P0 | F | Full |
| FR-SYS-008 | `laravel/framework` ^13.0 — core framework | P0 | A | Full |
| FR-SYS-009 | `livewire/livewire` ^4.0 — reactive UI components | P0 | A | Full |
| FR-SYS-010 | `spatie/laravel-permission` ^8.0 — RBAC (roles + permissions) | P0 | A | Full |
| FR-SYS-011 | `spatie/laravel-activitylog` ^5.0 — audit trail logging | P0 | A | Full |
| FR-SYS-012 | `spatie/laravel-medialibrary` ^11.17 — file upload + image conversions | P0 | A | Full |
| FR-SYS-013 | `spatie/laravel-model-status` ^1.18 — model status tracking | P1 | A | Full |
| FR-SYS-014 | `laravel-lang/lang` ^15.26 — bilingual translations (en/id) | P0 | A | Full |
| FR-SYS-015 | `barryvdh/laravel-dompdf` ^3.1 — PDF generation | P0 | A | Full |
| FR-SYS-016 | `laravel/pulse` * — performance monitoring dashboard | P1 | A | Full |
| FR-SYS-017 | `tallstackui/tallstackui` ^4.0 — UI kit (TallstackUI-only; replaces DaisyUI/MaryUI/PHPFlasher per FB792) | P0 | A | Full |
| FR-SYS-018 | `laravel/tinker` ^3.0 — REPL for debugging; `composer.lock` committed for reproducible builds | P1 | A | Full |
| FR-SYS-019 | SQLite is the default zero-config database (WAL mode, busy_timeout=5000, FK constraints on) | P0 | F | Full |
| FR-SYS-020 | MySQL 8.0+ is supported for shared-hosting deployments | P0 | F | Full |
| FR-SYS-021 | MariaDB 10.6+ is supported | P1 | F | Full |
| FR-SYS-022 | PostgreSQL 15+ is supported for larger deployments via portable Eloquent only | P1 | F | Planned |
| FR-SYS-023 | All models use UUID v7 primary keys (time-ordered, via `HasUuids`) | P0 | A | Full |
| FR-SYS-024 | All foreign keys define explicit `onDelete` and `onUpdate` behavior (D6 invariant) | P0 | A | Full |
| FR-SYS-025 | Migrations are organized in sequential layers: Foundation → Auth → Config → Internship Core → Grouping → Evaluation | P1 | A | Full |
| FR-SYS-026 | Full schema ships both domain tables and package tables (framework, Spatie, Sanctum, Pulse) in one database | P0 | A | Full |
| FR-SYS-027 | Tier 1 (shared hosting, ≤500 users) runs on MySQL/MariaDB + file cache + sync queue + database session + local disk with zero external services | P0 | A | Full |
| FR-SYS-028 | Tier 2 (VPS, 500–2000 users) is a configuration-only switch: `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, optional S3 disk | P1 | A | Full |
| FR-SYS-029 | Tier 3 (HA, 2000+ users) is a configuration-only switch: read replica, S3+CDN, Redis cluster, PHP-FPM tuning, user-aware rate limiting | P2 | A | Planned |
| FR-SYS-030 | Tier transitions require zero code changes; no feature is disabled in any tier | P0 | A | Full |
| FR-SYS-031 | The system MUST provide a 15-point health check covering: environment, setup status, PHP version, required extensions, recommended extensions, memory, database connectivity, migration freshness, storage writability, disk space, queue connectivity, cache connectivity, app key, storage symlink, and maintenance mode | P0 | F | Full |
| FR-SYS-032 | The health check MUST be accessible via `php artisan system:health` (CLI) and expose an admin-accessible web surface | P0 | F | Full |
| FR-SYS-033 | Health check results MUST be cached under the registered cache key (`system.health_check`) to avoid re-running expensive checks on every request | P1 | F | Full |
| FR-SYS-034 | The `/up` endpoint MUST return 200 only when required extensions and DB connectivity pass | P1 | F | Planned |

### 4.1 Minimum System Requirements

#### FR-SYS-001 — PHP 8.4 floor

- The floor follows the [tech-stack](FB792-tech-stack.md) pin; anything lower fails fast with the
  current version echoed.
- **Verification:** health-command PHP check (layer `F`) + `composer.json` `php: ^8.4`.

#### FR-SYS-002 — Required extensions

- The 12-extension list is the install gate; a missing required extension blocks boot, never a
  single feature at runtime.
- **Verification:** health-command extension check (layer `F`).

#### FR-SYS-003 — Recommended extensions

- Recommended extensions degrade gracefully (sync queue without pcntl, file cache without redis);
  no feature is disabled in any tier.
- **Verification:** health-command recommended-extension check reports, never blocks.

#### FR-SYS-004 — Composer floor

- Reproducible installs depend on Composer 2 lockfile handling.
- **Verification:** manifest audit (layer `A`).

#### FR-SYS-005 — Node build toolchain

- Vite + Tailwind v4 build runs at deploy time; Blade/CSS/JS changes rebuild via `npm run build`.
- **Verification:** build smoke (layer `A`).

#### FR-SYS-006 — Writable directories

- `storage/` and `bootstrap/cache/` writability is checked, not assumed — shared-hosting FTP
  uploads routinely break permissions.
- **Verification:** health-command storage check (layer `F`).

#### FR-SYS-007 — Application key

- A missing or malformed `APP_KEY` fails with a rotation-safe message; rotation via
  `APP_PREVIOUS_KEYS` keeps existing sessions valid.
- **Verification:** health-command app-key check (layer `F`).

### 4.2 Dependencies

#### FR-SYS-008 — Framework pin

- Laravel 13 is the foundation every module builds on; the major pin is tightened only via the
  [tech-stack](FB792-tech-stack.md) spec and the upgrade guide.
- **Verification:** `composer.json` audit (layer `A`).

#### FR-SYS-009 — Livewire pin

- Livewire 4 is the only reactive UI layer; no parallel REST controller surface for module UI.
- **Verification:** `composer.json` audit (layer `A`).

#### FR-SYS-010 — RBAC package

- Spatie permission provides the role store underneath the flat-RBAC model; functional roles are
  derived at runtime, never stored (see [T4B26](T4B26-rbac-and-authorization.md)).
- **Verification:** `composer.json` audit + RBAC feature tests.

#### FR-SYS-011 — Activity log package

- Append-only audit trail; proxy metadata rides in `properties` JSON with no schema change (see
  [T4B26](T4B26-rbac-and-authorization.md) FR-RBAC-021).
- **Verification:** `composer.json` audit + composite index review on `activity_log`.

#### FR-SYS-012 — Media library package

- File attachments with conversions; local disk default, S3 optional per tier.
- **Verification:** `composer.json` audit; contract in [WQGTP](WQGTP-file-uploads-media.md).

#### FR-SYS-013 — Model status package

- Status tracking for models with lifecycle states.
- **Verification:** `composer.json` audit (layer `A`).

#### FR-SYS-014 — Translation package

- Bilingual `en` + `id` coverage via `__()`; hardcoded UI English is a D3 violation.
- **Verification:** `composer.json` audit + LangChecker.

#### FR-SYS-015 — PDF package

- Server-side PDF generation for grade cards, certificates, and official documents.
- **Verification:** `composer.json` audit; contract in [7UB7S](7UB7S-pdf-generation.md).

#### FR-SYS-016 — Pulse monitoring

- Pulse ingest is sync by default (Tier 1); Redis ingest is a Tier-2 `.env` swap. Pulse is the
  measurement source the performance ADR requires before any optimization.
- **Verification:** `composer.json` audit (layer `A`).

#### FR-SYS-017 — UI kit pin

- TallstackUI v4 is the only UI kit; DaisyUI, MaryUI, and PHPFlasher are excluded per the
  tech-stack decision.
- **Verification:** `composer.json` audit (layer `A`).

#### FR-SYS-018 — Locked reproducible manifest

- `composer.lock` is committed; `composer audit` runs clean with no abandoned production deps.
- **Verification:** lockfile presence in CI + `composer audit` (layer `A`).

### 4.3 Database Portability

#### FR-SYS-019 — SQLite default

- WAL journal mode plus `busy_timeout=5000` and enforced foreign keys (`DB_FOREIGN_KEYS=true`);
  handles single-tenant Tier-1 concurrency without a DBA.
- **Edge case:** SQLite is unsuitable for production write concurrency — Tier-1 production uses
  MySQL/MariaDB, never SQLite.
- **Verification:** `config/database.php` audit + migrate smoke (layer `F`).

#### FR-SYS-020 — MySQL support

- MySQL 8.0+ is the Tier-1 production engine on shared hosting.
- **Verification:** migration run on MySQL (layer `F`).

#### FR-SYS-021 — MariaDB support

- MariaDB 10.6+ stays compatible through the same portable migration set; no engine branches.
- **Verification:** migration run on MariaDB (layer `F`).

#### FR-SYS-022 — PostgreSQL support

- Support is by construction (portable Eloquent, no module-specific SQL) rather than by verified
  matrix — the dedicated PostgreSQL CI run is still open.
- **Verification (pending):** CI matrix run against PostgreSQL 15+ (layer `F`); status `Planned`.

#### FR-SYS-023 — UUID v7 primary keys

- `BaseModel` applies ordered `HasUuids` with non-incrementing string keys; the `User` model is
  the sole documented exception (extends `Authenticatable`, applies `HasUuids` manually).
- **Governance:** [uuid-primary-keys ADR](../adr/adr-uuid-primary-keys.md); D4-adjacent model contract.
- **Verification:** `scan_conventions.py` + model contract scan (layer `A`).

#### FR-SYS-024 — Explicit FK behavior

- Every foreign key declares `onDelete`/`onUpdate` (D6); `foreignUuid()->constrained()` with
  composite indexes; mixed key types forbidden.
- **Verification:** `scan_violations.py` D6 check (layer `A`).

#### FR-SYS-025 — Layered migrations

- Six sequential layers keep foundation tables (users, settings, cache, jobs) before domain
  tables; a layer never references a later layer's tables.
- **Verification:** migration filename audit (layer `A`).

#### FR-SYS-026 — Domain plus package tables

- Package tables (media, activity_log, permission pivots, sessions, jobs, notifications, cache,
  Sanctum tokens, password resets, Pulse) coexist in the same database, owned by their packages'
  own migrations — full listing in §6.4.
- **Verification:** `migrate:fresh` table inventory (layer `A`).

### 4.4 Deployment Tiers

#### FR-SYS-027 — Tier-1 defaults

- The `.env.example` matrix runs the full feature set synchronously (emails and media conversions
  block the response; real-time updates need a refresh without Reverb).
- **Governance:** [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md).
- **Verification:** fresh shared-hosting deploy smoke.

#### FR-SYS-028 — Tier-2 trigger and switches

- Trigger: sustained > 500 users or P95 > 1s as shown by Pulse. Every switch is an env key the
  config already reads — no code branches on tier.
- **Governance:** [performance-optimization ADR](../adr/adr-performance-optimization.md).
- **Verification:** config reads env keys; documented `.env` variant.

#### FR-SYS-029 — Tier-3 trigger and switches

- Trigger: sustained > 2000 users or DB write > 50ms. Status is `Planned` — specified, not yet
  exercised against a live HA instance.
- **Verification (pending):** HA deploy rehearsal.

#### FR-SYS-030 — Config-only growth

- Tier is a deployment concern, never an application branch; feature code never reads the tier.
- **Verification:** review — no tier conditionals in `app/` (layer `A`).

### 4.5 System Health Check

> **Detail contract:** [system-maintenance.md](E1MSJ-system-maintenance.md) — the `system:health`
> command, its 15-point check, CLI/JSON output, and admin surface are fully specified there
> (Phase 12). This section records the Foundation-level contract.

#### FR-SYS-031 — Fifteen-point coverage

- The 15 checks are implemented as `SystemHealthCommand` check methods (environment through
  maintenance mode); the list is frozen here so Phase-12 detail cannot silently shrink coverage.
- **Verification:** command test asserting all 15 checks run (layer `F`).

#### FR-SYS-032 — CLI plus admin surface

- CLI is the deploy-time gate; the web surface lets an admin re-verify without SSH.
- **Verification:** command invocation + route smoke (layer `F`).

#### FR-SYS-033 — Cached results

- Key `system.health_check` is registered in `config/cache-keys.php`; expensive checks never run
  per request.
- **Verification:** cache-key registry audit + command test (layer `F`).

#### FR-SYS-034 — Gated `/up` endpoint

- No gated `/up` customization was found in `routes/` — the default Laravel probe does not yet
  enforce the extension + DB gates. Status `Planned` until the override lands.
- **Verification (pending):** HTTP assertion on `/up` under broken-DB fixture (layer `F`).

---

## 5. Non-Functional Requirements

`Target` = `N/A` means the requirement is enforced structurally and verified via scans rather
than a runtime measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SYS-001 | `declare(strict_types=1)` in every PHP file except migrations and config (D1 invariant) | N/A | P0 | A | Full |
| NFR-SYS-002 | No debug calls in committed code: dd, dump, ray, var_dump, print_r, die (D2 invariant) | N/A | P0 | A | Full |
| NFR-SYS-003 | APP_KEY must be a 32-byte base64 string; rotation supported via `APP_PREVIOUS_KEYS` | N/A | P0 | F | Full |
| NFR-SYS-004 | SQLite foreign keys enforced (`DB_FOREIGN_KEYS=true`) | N/A | P0 | F | Full |

### 5.1 Code Hygiene

#### NFR-SYS-001 — Strict types everywhere

- D1 invariant; migrations and config are the only exemptions.
- **Verification:** `scan_conventions.py` D1 (layer `A`).

#### NFR-SYS-002 — No debug calls

- D2 invariant; `dd()`/`dump()`/`ray()` never reach a commit.
- **Verification:** `scan_conventions.py` D2 (layer `A`).

### 5.2 Runtime Integrity

#### NFR-SYS-003 — Key strength and rotation

- 32-byte base64 identity; previous-keys support keeps rotation non-breaking.
- **Verification:** health-command app-key check (layer `F`).

#### NFR-SYS-004 — SQLite FK enforcement

- Foreign-key constraints are on in every environment including tests; silent orphan rows are a
  defect, not a dialect quirk.
- **Verification:** `config/database.php` audit (layer `F`).

---

## 6. API / Data Contracts

### 6.1 Production Dependencies

```json
{
  "php": "^8.4",
  "laravel/framework": "^13.0",
  "livewire/livewire": "^4.0",
  "spatie/laravel-permission": "^8.0",
  "spatie/laravel-activitylog": "^5.0",
  "spatie/laravel-medialibrary": "^11.17",
  "spatie/laravel-model-status": "^1.18",
  "laravel-lang/lang": "^15.26",
  "barryvdh/laravel-dompdf": "^3.1",
  "laravel/pulse": "*",
  "tallstackui/tallstackui": "^4.0",
  "laravel/tinker": "^3.0"
}
```

### 6.2 Database Configuration

```php
// config/database.php — key settings
'default' => env('DB_CONNECTION', 'sqlite'),

// SQLite (default)
'sqlite' => [
    'foreign_key_constraints' => true,
    'busy_timeout' => 5000,
    'journal_mode' => 'wal',
],

// Redis (multi-service, Tier 2+)
'redis' => [
    'default' => ['database' => 0],  // Queue
    'cache'   => ['database' => 1],  // Cache
    // Session uses SESSION_CONNECTION env
],
```

### 6.3 Deployment Tier Matrix

| Tier | Users | Database | Queue | Cache | Session | Storage | Trigger |
|------|-------|----------|-------|-------|---------|---------|---------|
| 1 Shared | ≤ 500 | MySQL/MariaDB | sync | file | database | local | default |
| 2 VPS | 500–2000 | MySQL | redis | redis | redis | local + S3 | sustained > 500 users or P95 > 1s |
| 3 HA | 2000+ | MySQL + replica | redis | redis cluster | redis cluster | S3 | sustained > 2000 users or DB write > 50ms |

Tier 0 no-regret rules (always on, any tier): UUID v7 PKs, composite indexes on FKs and
`activity_log`, cache-key registry, eager loading (no N+1), Read Actions avoiding transaction
overhead. Explicitly deferred until measured: Octane, horizontal auto-scaling, CDN for static
assets, sharding, job batching.

### 6.4 Package / Framework Database Tables

The following tables are created by third-party packages and managed by their own migrations.
They are not part of the domain schema but coexist in the same database.

| Table | Package | Purpose |
| ----- | ------- | ------- |
| `media` | spatie/laravel-medialibrary | File attachments with conversions |
| `activity_log` | spatie/laravel-activitylog | Audit trail (append-only) |
| `model_has_permissions` | spatie/laravel-permission | Permission ↔ polymorphic model pivot |
| `model_has_roles` | spatie/laravel-permission | Role ↔ polymorphic model pivot |
| `permissions` | spatie/laravel-permission | Permission definitions |
| `roles` | spatie/laravel-permission | Role definitions |
| `role_has_permissions` | spatie/laravel-permission | Role ↔ permission pivot |
| `sessions` | laravel/framework | Database driver session storage |
| `jobs` | laravel/framework | Queued job storage |
| `job_batches` | laravel/framework | Batched job tracking |
| `failed_jobs` | laravel/framework | Failed job records |
| `notifications` | laravel/framework | Database notification storage |
| `cache` | laravel/framework | Cache store (database driver) |
| `cache_locks` | laravel/framework | Cache lock store |
| `personal_access_tokens` | laravel/sanctum | API token storage |
| `password_reset_tokens` | laravel/framework | Password reset tracking |
| `migrations` | laravel/framework | Migration version tracking |
| `pulse_*` | laravel/pulse | Performance metrics (10+ tables) |

### 6.5 Health Check List

```
system:health — 15 checks, in order:
 1. environment          6. memory               11. queue connectivity
 2. setup status         7. database connectivity 12. cache connectivity
 3. PHP version          8. migration freshness   13. app key
 4. required extensions  9. storage writability   14. storage symlink
 5. recommended exts    10. disk space            15. maintenance mode
```

Results cached under `system.health_check` (`config/cache-keys.php`).

---

## 7. Design Decisions

Decisions are recorded rationale, not test rows — `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SYS-001 | SQLite as the default database | P0 | — | — |
| DD-SYS-002 | UUID v7 primary keys | P0 | — | — |
| DD-SYS-003 | Tiered no-regret growth with configuration-only tier switches | P0 | — | — |
| DD-SYS-004 | Consolidated domain schema (37 domain tables; nine cross-spec optimizations) | P1 | — | — |

### 7.1 Platform Choices

#### DD-SYS-001 — SQLite as Default Database

**Decision:** SQLite is the default database driver, not MySQL.
**Rationale:** Zero-config development and shared hosting. Schools often lack DBA expertise.
SQLite with WAL mode handles concurrent reads well for single-tenant workloads up to 500 users.
**Trade-off:** No connection pooling, limited concurrent writes. Mitigated by the migration path
to MySQL/PostgreSQL for larger deployments (FR-SYS-020/022).

#### DD-SYS-002 — UUID v7 Primary Keys

**Decision:** All models use UUID v7 (time-ordered) primary keys via Laravel's `HasUuids` trait.
**Rationale:** Time-ordered UUIDs improve B-tree index performance. UUIDs eliminate sequential ID
exposure (no user can guess `/users/2` → `/users/3`). No migration coordination needed across
environments.
**Trade-off:** 16 bytes per PK vs 4 bytes for auto-increment. Storage overhead is negligible for
<100K rows.

### 7.2 Growth Strategy

#### DD-SYS-003 — Tiered No-Regret Growth

**Decision:** Enforce cheap universal wins at any scale; Tier-1 defaults run on MySQL alone;
Tier-2/3 are `.env` swaps with an explicit deferral list.
**Rationale:** MVP velocity is preserved while the same binary runs at 500 and 2000 users; the
deferred list removes ambiguity about premature needs.
**Trade-off:** Default `.env.example` is not production-optimal; deployers must override for
Tier 2+. See the [performance-optimization ADR](../adr/adr-performance-optimization.md).

### 7.3 Schema Design Philosophy

#### DD-SYS-004 — Consolidated Domain Schema

**Decision:** The domain schema consolidates 37 tables from a larger original design; nine
optimization decisions shaped the final schema, each owned by its domain spec (not duplicated
here): `mentors` eliminated into user profiles ([95EVB](95EVB-user-crud-and-status.md)),
`schools` eliminated into settings ([YB22J](YB22J-settings-infrastructure.md)), `handbooks`
merged into `documents` ([ZUFG8](ZUFG8-handbooks.md)), `absence_requests` merged into
`attendances` ([1KSWL](1KSWL-daily-activity.md)), `rubric_metrics` as JSON
([ARDA6](ARDA6-assessment.md)), `handbook_acknowledgments` replaced by `activity_log`
([ZUFG8](ZUFG8-handbooks.md)), `registration_mentor` eliminated into group membership
([IT0OE](IT0OE-internship-groups.md)), `reports` snapshot columns ([R6BMW](R6BMW-reports.md)),
`internship_phases` as JSON ([7C5WM](7C5WM-internship-lifecycle.md)).
**Rationale:** One table per true entity; cross-references replace duplication.
**Trade-off:** JSON columns trade queryability for schema flexibility — accepted for variable
rubric/phase shapes.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| PHP version check accuracy | Always accurate | `php -v` parse matches FR-SYS-001 |
| Extension check coverage | 12 required + 3 recommended | `php -m` comparison against FR-SYS-002/003 |
| Composer install success | 100% on supported PHP | `composer install` exit code |
| Abandoned production deps | 0 | `composer audit` |
| Lockfile committed | present | CI check |
| SQLite zero-config migrate | No `.env` DB settings needed | `php artisan migrate` smoke |
| Health check coverage | 15/15 checks run | `php artisan system:health` |

---

## 9. Roadmap

### Prerequisites

None — this is a foundational spec. It must be read before the module-discovery and
installation specs.

### Build Guide

This spec is satisfied structurally: the floor is enforced by `composer.json`,
`config/database.php`, and `SystemHealthCommand`. Its requirements are reference material —
verified by manifest audit, migration smoke, and the health command rather than per-feature work.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [module-discovery.md](I1BCV-module-discovery.md) | Uses the module directory structure on top of this platform floor |
| 2 | [system-maintenance.md](E1MSJ-system-maintenance.md) | Owns the full `system:health` command detail (Phase 12) behind FR-SYS-031/032 |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume portable Eloquent with no module-specific SQL keeps all four engines compatible until the PostgreSQL matrix run proves it | Accepted | Maintainer | — |
| R-1 | If PostgreSQL diverges (JSON operators, collation), then MySQL-first migrations may need engine branches, mitigated by keeping the matrix run before any PG production deploy | Open | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Tech stack](FB792-tech-stack.md) — dependency pins this floor implements
- [Base classes](SE5Q9-base-classes.md) — contracts outside this spec's scope
- [Module discovery](I1BCV-module-discovery.md) — registry built on this platform
- [System maintenance](E1MSJ-system-maintenance.md) — full `system:health` command contract
- [Conditional deployment](06IB6-deployment.md) — deployment matrix per tier
- [ADR: Self-hosted single-tenant](../adr/adr-self-hosted-single-tenant.md) — zero-external-services defaults
- [ADR: Performance optimization](../adr/adr-performance-optimization.md) — tier triggers and deferral list
- [ADR: UUID primary keys](../adr/adr-uuid-primary-keys.md) — key-type rationale
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements these rows serve
