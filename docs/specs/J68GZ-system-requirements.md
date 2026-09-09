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

A new contributor on a borrowed Windows laptop is the audience here: she clones the repository, runs `composer install` until all production packages land, copies `.env.example` to `.env` and generates the key, migrates into a zero-config SQLite database carrying the full schema, then runs `npm install && npm run build` so the Vite build completes. When each step succeeds with no extra configuration, the checkout is ready for development. That journey exercises the floor in FR-SYS-001 through FR-SYS-007 and the manifest in FR-SYS-008 through FR-SYS-018, and install CI proves it still works.

#### UC-SYS-002 — School Deploys on Shared Hosting

At boot the school IT staff member has a cPanel login, a MySQL wizard, and no Redis — and no patience for framework internals. Uploaded over FTP, pointed at a freshly created MySQL database via `DB_CONNECTION=mysql` in `.env`, migrated cleanly onto MySQL, the system settles into file cache with database sessions and simply runs. That it needs no Redis or extra service is the whole point, and FR-SYS-020 with FR-SYS-027 records the contract the smoke deploy verifies.

#### UC-SYS-003 — System Checks Requirements on Boot

The edge case this journey owns is the misleading failure: PHP 8.3 installed where 8.4 is required, or the `gd` extension missing after a hoster upgrade, surfacing as a white screen. Boot or `php artisan system:health` instead names the gap plainly — `PHP 8.4.0 or higher required (current: 8.3.x)` — and lists missing required extensions separately from recommended ones. The operator gets an actionable message rather than a stack trace, under the full command contract in [system-maintenance.md](E1MSJ-system-maintenance.md) behind FR-SYS-031 and FR-SYS-032.

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

This floor exists because schools install on unknown hosting, and a silent misbehaviour on PHP 8.3 costs a week of WhatsApp debugging. Anything below the [tech-stack](FB792-tech-stack.md) pin fails fast at boot with the current version echoed, so the operator sees what is running, not a cryptic fatal. The health command's PHP check (layer F) and the `composer.json` `php: ^8.4` pin together prove the gate holds.

#### FR-SYS-002 — Required extensions

If a single required extension is missing, the failure must land at install time, never halfway through grading week as a broken export. The twelve-extension gate — bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip — blocks boot when any member is absent. That the gate fires is proven by the health command's extension check (layer F), which reports each missing member by name.

#### FR-SYS-003 — Recommended extensions

At an SMK in Cirebon the hosting panel offered no redis extension and no shell to install one, and the coordinator assumed the whole system would refuse to run. It does not: without pcntl the queue falls back to sync, without redis the cache falls back to file, and no feature is disabled in any tier. The health command's recommended-extension check reports the absence without blocking, which is exactly the graceful degradation this requirement promises.

#### FR-SYS-004 — Composer floor

At runtime the installer reads the Composer 2 lockfile format to guarantee reproducible installs, and Composer 1 cannot parse it — the failure looks like dependency rot when it is really toolchain rot. Requiring Composer 2.0 or newer keeps every one of the nineteen modules building from the same manifest. A manifest audit (layer A) confirms the floor is declared and honoured.

#### FR-SYS-005 — Node build toolchain

The edge case that taught this rule was a deploy where PHP was perfect but the landing pages rendered unstyled for a day because nobody had run the frontend build. Vite with Tailwind v4 compiles at deploy time, so every Blade, CSS, or JS change only takes effect after `npm run build`. A build smoke run (layer A) proves the Node toolchain is present and the bundle completes.

#### FR-SYS-006 — Writable directories

This requirement exists because shared-hosting FTP uploads routinely land files owned by the wrong user with read-only permissions, and the first symptom is a blank page on logo upload. Writability of `storage/` and `bootstrap/cache/` is therefore checked at boot, never assumed from a fresh clone. The health command's storage check (layer F) exercises a real write and fails loudly when permissions are wrong.

#### FR-SYS-007 — Application key

If session decryption silently fails, students get logged out mid-attendance with no message anyone can act on. A missing or malformed `APP_KEY` — expected as a 32-character base64 string — fails early with a rotation-safe message, and rotation via `APP_PREVIOUS_KEYS` keeps existing sessions valid across the change. The health command's app-key check (layer F) proves both the presence gate and the rotation path.

### 4.2 Dependencies

#### FR-SYS-008 — Framework pin

At SMK Negeri 2 Bandung the pilot stalled for a week when a tutorial written for Laravel 11 was followed on a Laravel 12 checkout and queued events behaved differently. Laravel 13 is the foundation every module builds on, so the major pin is declared once and tightened only through the [tech-stack](FB792-tech-stack.md) spec and the upgrade guide. A `composer.json` audit (layer A) confirms the pin holds.

#### FR-SYS-009 — Livewire pin

Two reactive UI layers on the same page — say a REST controller surface beside Livewire — would split validation and authorization into two dialects the reviewers must hold in their heads. Livewire 4 is therefore the only reactive UI layer, with no parallel REST controller surface for module screens. That exclusivity is proven by a `composer.json` audit (layer A) plus review that no competing UI stack ships.

#### FR-SYS-010 — RBAC package

A teacher covering for an industry supervisor who has not logged in for a week needs the system to know she may verify in his stead, without granting her a second stored role. Spatie permission supplies the role store underneath that flat model, while functional roles stay derived at runtime and are never stored, exactly as [T4B26](T4B26-rbac-and-authorization.md) contracts. The `composer.json` audit plus the RBAC feature tests (layer A with behaviour coverage) verify both the package pin and the derivation rule.

#### FR-SYS-011 — Activity log package

When a disputed grade lands on the coordinator's desk, the question is always who verified what on whose behalf, and a plain `updated_at` cannot answer it. The append-only audit trail carries that answer, with proxy metadata riding in the `properties` JSON so no schema change is ever needed, as [T4B26](T4B26-rbac-and-authorization.md) FR-RBAC-021 contracts. The `composer.json` audit and a review of the composite index on `activity_log` confirm the package pin and the query shape stay fast at 45,000-row scale.

#### FR-SYS-012 — Media library package

This pin exists because certificate photos, handbook attachments, and logbook evidence all funnel through one upload path, and three competing upload stacks would triple the validation audit. File attachments with conversions run through the media library, on local disk by default with S3 optional per tier. The `composer.json` audit (layer A) pins the package while the full behavioural contract lives in [WQGTP](WQGTP-file-uploads-media.md).

#### FR-SYS-013 — Model status package

A placement record drifts through proposed, active, suspended, and completed states, and without a status history the coordinator cannot explain how a student ended up archived. Status tracking gives every lifecycle model that history. Presence of the pin is confirmed by a `composer.json` audit (layer A).

#### FR-SYS-014 — Translation package

At SMK Al Hidayah the staff switch between Indonesian and English mid-morning, and a single hardcoded English button label breaks trust in the whole translation effort. Bilingual `en` plus `id` coverage flows through `__()`, so hardcoded UI English counts as a D3 violation. The `composer.json` audit plus LangChecker together prove the package pin and the mirrored-key coverage.

#### FR-SYS-015 — PDF package

Grade cards, certificates, and official letters must print identically on the school printer and the industry partner's printer, which rules out browser-print CSS as the source of truth. Server-side PDF generation produces those documents deterministically. The `composer.json` audit (layer A) pins the generator while the rendering contract lives in [7UB7S](7UB7S-pdf-generation.md).

#### FR-SYS-016 — Pulse monitoring

The failure this guards against is optimizing blind — adding Redis or Octane because traffic felt slow, without a single measurement. Pulse ingest runs synchronously by default on Tier 1 and becomes a Tier-2 `.env` swap to Redis, and Pulse is the measurement source the performance ADR demands before any optimization lands. A `composer.json` audit (layer A) confirms the monitor ships with the binary.

#### FR-SYS-017 — UI kit pin

Three UI kits in one codebase mean three button styles, three modal dialects, and a new contributor guessing which one to copy. TallstackUI v4 is the only UI kit, and DaisyUI, MaryUI, and PHPFlasher are excluded by the tech-stack decision. The `composer.json` audit (layer A) proves no competing kit drifts back in.

#### FR-SYS-018 — Locked reproducible manifest

If the lockfile is missing, Tuesday's deploy installs different transitive versions than Monday's identical checkout and the diff is invisible. Committing `composer.lock` makes builds reproducible, and a clean `composer audit` with no abandoned production dependencies keeps the supply chain honest. CI proves it by asserting lockfile presence and running `composer audit` (layer A).

### 4.3 Database Portability

#### FR-SYS-019 — SQLite default

A vocational school in Sintuk Toboh Gadang ran its pilot on a borrowed laptop with no database server and no administrator password, yet attendance for sixty students had to be recorded that same morning. SQLite with WAL journal mode, `busy_timeout=5000`, and enforced foreign keys (`DB_FOREIGN_KEYS=true`) handles that single-tenant Tier-1 concurrency with no DBA involved. The honest edge is that SQLite never serves Tier-1 production write concurrency — production runs MySQL or MariaDB — and `config/database.php` audit plus a migrate smoke run (layer F) prove the settings hold.

#### FR-SYS-020 — MySQL support

Shared-hosting panels in Indonesia overwhelmingly offer MySQL and nothing else, so Tier-1 production has to live there or schools cannot deploy at all. MySQL 8.0 and newer is that production engine. A full migration run against MySQL (layer F) proves the schema lands cleanly on the engine schools actually have.

#### FR-SYS-021 — MariaDB support

One district standardizes on MariaDB while the neighbouring district's hoster ships only MySQL, and the same release zip must install on both without a fork. MariaDB 10.6 and newer stays compatible through the identical portable migration set, with no engine branches anywhere. A migration run on MariaDB (layer F) confirms the single migration set serves both engines.

#### FR-SYS-022 — PostgreSQL support

This requirement exists because larger deployments eventually ask for PostgreSQL, and the cheapest time to stay portable is before the first raw query ships. Support is by construction — portable Eloquent with no module-specific SQL — rather than by a verified matrix, since the dedicated PostgreSQL CI run is still open. The pending proof is a CI matrix run against PostgreSQL 15 or newer (layer F), which is why the status stays Planned.

#### FR-SYS-023 — UUID v7 primary keys

Sequential ids leak enrolment order and invite guessing, while random UUIDs scatter B-tree inserts and slow the 45,000-row attendance import to a crawl. Ordered UUID v7 via `BaseModel` with non-incrementing string keys through `HasUuids` solves both, and the `User` model is the sole documented exception — it extends `Authenticatable` and applies `HasUuids` manually. Governance sits with the [uuid-primary-keys ADR](../adr/adr-uuid-primary-keys.md), and `scan_conventions.py` plus the model contract scan (layer A) prove the invariant holds.

#### FR-SYS-024 — Explicit FK behavior

An orphaned attendance row pointing at a deleted placement is the kind of silent corruption that surfaces only during accreditation week. Every foreign key therefore declares its `onDelete` and `onUpdate` behaviour under the D6 invariant, built with `foreignUuid()->constrained()` and composite indexes, with mixed key types forbidden. The `scan_violations.py` D6 check (layer A) proves no bare foreign key slips through.

#### FR-SYS-025 — Layered migrations

At 6 a.m. on enrollment day the migration order is load-bearing: users, settings, cache, and jobs tables must exist before domain tables reference them. Six sequential layers — Foundation, then Auth, then Config, then Internship Core, then Grouping, then Evaluation — enforce that, and no layer ever references a later layer's tables. A migration filename audit (layer A) proves the layering holds.

#### FR-SYS-026 — Domain plus package tables

A fresh clone that migrates only domain tables boots into a system with no sessions table, no job tables, and no audit trail — every login fails. The full schema therefore ships domain tables alongside package tables (media, activity_log, permission pivots, sessions, jobs, notifications, cache, Sanctum tokens, password resets, Pulse) in one database, with each package's own migrations owning its tables as §6.4 lists. A `migrate:fresh` table inventory (layer A) proves nothing is missing.

### 4.4 Deployment Tiers

#### FR-SYS-027 — Tier-1 defaults

The $5 shared-hosting reality is MySQL or MariaDB with file cache, sync queue, database sessions, and local disk — no Redis, no object storage, no daemon the school cannot restart. The `.env.example` matrix runs the entire feature set synchronously on exactly that floor, where emails and media conversions block the response and real-time updates need a refresh without Reverb. Governance is the [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md), and a fresh shared-hosting deploy smoke proves the defaults carry the full product.

#### FR-SYS-028 — Tier-2 trigger and switches

When Pulse shows sustained load above 500 users or P95 latency past a second, the answer must be a config change, not a sprint. Each Tier-2 switch is an env key the config already reads — `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, optional S3 disk — with no code branching on tier. Governance is the [performance-optimization ADR](../adr/adr-performance-optimization.md), and review that config reads those env keys plus a documented `.env` variant proves the switch is real.

#### FR-SYS-029 — Tier-3 trigger and switches

Past roughly 2000 sustained users or 50ms database writes the single-database shape starts to strain, and the team specified the HA answer — read replica, S3 plus CDN, Redis cluster, PHP-FPM tuning, user-aware rate limiting — before the pain arrived. That specification is deliberately still Planned: it is written down but not yet exercised against a live HA instance. The pending proof is a full HA deploy rehearsal.

#### FR-SYS-030 — Config-only growth

If feature code reads the tier, every new feature ships three behaviours and QA triples. Tier therefore stays a deployment concern that application code never branches on, with no feature disabled in any tier. Review asserting no tier conditionals exist under `app/` (layer A) is the whole proof, and it runs on every change.

### 4.5 System Health Check

> **Detail contract:** [system-maintenance.md](E1MSJ-system-maintenance.md) — the `system:health`
> command, its 15-point check, CLI/JSON output, and admin surface are fully specified there
> (Phase 12). This section records the Foundation-level contract.

#### FR-SYS-031 — Fifteen-point coverage

A health check that silently drops its queue-connectivity probe is worse than none, because operators trust the green output. The fifteen checks — environment, setup status, PHP version, required extensions, recommended extensions, memory, database connectivity, migration freshness, storage writability, disk space, queue connectivity, cache connectivity, app key, storage symlink, maintenance mode — are implemented as `SystemHealthCommand` check methods, and the list is frozen here so Phase-12 detail cannot shrink coverage. A command test asserting all fifteen checks run (layer F) locks the coverage in.

#### FR-SYS-032 — CLI plus admin surface

The deploy-time gate runs over SSH, but the school operator who needs reassurance at 7 a.m. has no SSH access — she has a browser. The check is therefore reachable both as `php artisan system:health` on the CLI and as an admin-accessible web surface. Command invocation plus a route smoke (layer F) prove both doors open onto the same checks.

#### FR-SYS-033 — Cached results

Re-running disk-space probes and queue handshakes on every dashboard request would turn monitoring into the load it warns about. Results are cached under the registered key `system.health_check` in `config/cache-keys.php`, so expensive checks never run per request. The cache-key registry audit plus the command test (layer F) prove the key is registered and honoured.

#### FR-SYS-034 — Gated `/up` endpoint

The default Laravel `/up` probe returns 200 while the database is down, which tells the load balancer everything is fine during the exact outage it should catch. The gated endpoint must return 200 only when required extensions and database connectivity pass. No such customization was found in `routes/` yet, so the row stays Planned until the override lands, with an HTTP assertion on `/up` under a broken-database fixture (layer F) as its pending proof.

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

A missing strict-types declaration once let a string placement id slip into an integer comparison and pass silently until accreditation export. `declare(strict_types=1)` is therefore required in every PHP file, with migrations and config the only exemptions under the D1 invariant. The `scan_conventions.py` D1 check (layer A) proves the coverage holds.

#### NFR-SYS-002 — No debug calls

A forgotten `dd()` in a grading action once blanked the assessor's screen during a live review with industry partners in the room. Debug calls — dd, dump, ray, var_dump, print_r, die — must never reach a commit under the D2 invariant. The `scan_conventions.py` D2 check (layer A) is the gate that catches them.

### 5.2 Runtime Integrity

#### NFR-SYS-003 — Key strength and rotation

If key rotation logs everyone out at midnight before certificate downloads, the helpdesk drowns. The key must be a 32-byte base64 string, and `APP_PREVIOUS_KEYS` support keeps rotation non-breaking so old sessions survive the change. The health command's app-key check (layer F) proves both strength and rotation.

#### NFR-SYS-004 — SQLite FK enforcement

SQLite silently accepts orphan rows when foreign-key enforcement is off, and the corruption only surfaces months later as placements pointing at deleted students. Constraints stay on in every environment including tests via `DB_FOREIGN_KEYS=true`, because silent orphans are a defect rather than a dialect quirk. A `config/database.php` audit (layer F) proves enforcement is unconditional.

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

At SMKN 1 Bangil the only machine available for a pilot was an old staff laptop with no database server installed, and the intern-student who volunteered to set things up had never created a MySQL user. That is the exact situation SQLite as default exists for: zero-config development and hosting where no DBA exists, with WAL journal mode carrying concurrent reads comfortably up to about 500 single-tenant users. The cost is honest — no connection pooling and limited concurrent writes — so any deployment that outgrows Tier 1 follows the documented migration path to MySQL or PostgreSQL under FR-SYS-020 and FR-SYS-022 rather than stretching SQLite past its shape.

#### DD-SYS-002 — UUID v7 Primary Keys

A placement coordinator once guessed the next student's record by incrementing the id in the URL from `/users/2` to `/users/3` and landed on another student's profile. UUID v7 primary keys via Laravel's `HasUuids` trait close that hole while staying time-ordered, so B-tree index performance does not collapse the way random UUIDs would, and no migration coordination is needed across environments. Sixteen bytes per key instead of four is the price, and below roughly 100K rows that overhead is negligible for a school deployment.

### 7.2 Growth Strategy

#### DD-SYS-003 — Tiered No-Regret Growth

The team watched an early pilot stall when every growth conversation turned into a rewrite proposal — queue workers, cache servers, read replicas — before the school had even finished enrollment week. Tiered no-regret growth answers that by enforcing the cheap universal wins at every scale while Tier-1 defaults run on MySQL alone and Tier-2/3 stay pure `.env` swaps with an explicit deferral list. The default `.env.example` is therefore deliberately not production-optimal, and deployers override it for Tier 2 and above as the [performance-optimization ADR](../adr/adr-performance-optimization.md) describes.

### 7.3 Schema Design Philosophy

#### DD-SYS-004 — Consolidated Domain Schema

The original domain design sprawled past forty tables, including near-duplicate entities like `mentors` beside user profiles and `absence_requests` beside `attendances` that coordinators could never keep consistent. Consolidation to 37 tables removed nine such redundancies, each owned by its domain spec rather than duplicated here: `mentors` folded into user profiles ([95EVB](95EVB-user-crud-and-status.md)), `schools` into settings ([YB22J](YB22J-settings-infrastructure.md)), `handbooks` into `documents` ([ZUFG8](ZUFG8-handbooks.md)), `absence_requests` into `attendances` ([1KSWL](1KSWL-daily-activity.md)), `rubric_metrics` as JSON ([ARDA6](ARDA6-assessment.md)), `handbook_acknowledgments` into `activity_log` ([ZUFG8](ZUFG8-handbooks.md)), `registration_mentor` into group membership ([IT0OE](IT0OE-internship-groups.md)), `reports` snapshot columns ([R6BMW](R6BMW-reports.md)), and `internship_phases` as JSON ([7C5WM](7C5WM-internship-lifecycle.md)). One table per true entity with cross-references instead of duplication is the standing rule, and the accepted consequence is that JSON columns trade queryability for schema flexibility wherever rubric and phase shapes genuinely vary.

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
