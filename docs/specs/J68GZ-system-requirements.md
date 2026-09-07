# System Requirements — Dependencies, Platform & Database

> **Spec ID:** J68GZ

## Description

Specification of Internara's minimum system requirements, third-party dependencies, and database
portability. Defines the PHP version, required extensions, Composer packages, supported database
engines, and migration strategy. Base classes, contracts, middleware, cache, and session are a
separate initiative — see [base-classes.md](SE5Q9-base-classes.md).

---

## 1. Problem Statements

### PS-1 — Dependency Management

The system relies on 12 production packages and 10 dev packages with specific version constraints.
A broken dependency or version mismatch can cascade across all 18 modules. Package selection must
balance feature needs with maintenance burden and security surface.

### PS-2 — Minimum System Requirements

Schools operate on diverse hosting environments — from shared hosting with PHP 8.4 to VPS with
Redis. The system must clearly define what is required vs recommended, and fail gracefully when
requirements aren't met rather than producing cryptic errors.

### PS-3 — Database Portability

Different schools have different database capabilities. SQLite for zero-config development, MySQL
for shared hosting, PostgreSQL for larger deployments. The system must work across all three without
module-specific SQL, using only portable Eloquent queries and migrations.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Define minimum PHP version (8.4) and required extensions with clear error messaging |
| G2  | Support SQLite (default), MySQL 8+, MariaDB 10.6+, PostgreSQL 15+ without module-specific SQL |
| G3  | Provide Composer dependency manifest with locked versions for reproducible builds |
| G4  | Support three deployment tiers: shared hosting (file cache, database session), VPS (Redis), HA (Redis cluster) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-tenant database partitioning (single-tenant design) |
| NG2  | Custom ORM or query builder (Eloquent is the persistence layer) |
| NG3  | Container orchestration (Docker Compose only, no Kubernetes) |

---

## 3. User Stories / Use Cases

| ID | Actor | Action / Expected Outcome |
|----|-------|---------------------------|
| UC-J68GZ-1 | Developer | Clones and installs the project on a compatible environment |
| UC-J68GZ-2 | School | Deploys on shared hosting within requirements |
| UC-J68GZ-3 | System | Checks requirements on boot |

### UC-J68GZ-1 — Developer Clones and Installs the Project

**Actor:** Developer
**Preconditions:** PHP 8.4+, Composer 2.0+, Node.js + npm available
**Flow:**
1. Developer clones repository
2. Runs `composer install` — all 12 production packages install successfully
3. Runs `cp .env.example .env` and `php artisan key:generate`
4. Runs `php artisan migrate` — SQLite database created with 55 tables
5. Runs `npm install && npm run build` — Vite build completes
**Postconditions:** System ready for development without additional configuration

### UC-J68GZ-2 — School Deploys on Shared Hosting

**Actor:** School IT staff
**Preconditions:** Shared hosting with PHP 8.4+, MySQL 8.0+, no Redis
**Flow:**
1. IT staff uploads files via FTP/File Manager
2. Creates MySQL database via hosting control panel
3. Updates `.env` with DB credentials (DB_CONNECTION=mysql)
4. Runs `php artisan migrate` — all migrations execute on MySQL
5. System operates with file cache and database sessions
**Postconditions:** System functional without Redis or additional services

### UC-J68GZ-3 — System Checks Requirements on Boot

**Actor:** System (automated)
**Preconditions:** PHP version or extensions missing
**Flow:**
1. System boot detects PHP version < 8.4.0
2. Throws `InfrastructureException` with clear message: "PHP 8.4.0 or higher required (current: 8.3.x)"
3. Lists missing extensions if applicable
**Postconditions:** Developer receives actionable error message, not cryptic failure

---

## 4. Functional Requirements

### 4.1 Minimum System Requirements

| ID    | Requirement |
| ----- | ----------- |
| FR-J68GZ-SY1 | PHP >= 8.4.0 is required |
| FR-J68GZ-SY2 | Required extensions: bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip |
| FR-J68GZ-SY3 | Recommended extensions: redis, pcntl, posix |
| FR-J68GZ-SY4 | Composer >= 2.0 is required for dependency management |
| FR-J68GZ-SY5 | Node.js + npm required for frontend build (Vite, Tailwind CSS) |
| FR-J68GZ-SY6 | `storage/` and `bootstrap/cache/` directories must be writable |
| FR-J68GZ-SY7 | `APP_KEY` must be set (32-character base64 string) |

### 4.2 Dependencies

| ID    | Requirement |
| ----- | ----------- |
| FR-J68GZ-D1 | `laravel/framework` ^13.0 — core framework |
| FR-J68GZ-D2 | `livewire/livewire` ^4.0 — reactive UI components |
| FR-J68GZ-D3 | `spatie/laravel-permission` ^8.0 — RBAC (roles + permissions) |
| FR-J68GZ-D4 | `spatie/laravel-activitylog` ^5.0 — audit trail logging |
| FR-J68GZ-D5 | `spatie/laravel-medialibrary` ^11.17 — file upload + image conversions |
| FR-J68GZ-D6 | `spatie/laravel-model-status` ^1.18 — model status tracking |
| FR-J68GZ-D7 | `laravel-lang/lang` ^15.26 — bilingual translations (en/id) |
| FR-J68GZ-D8 | `barryvdh/laravel-dompdf` ^3.1 — PDF generation |
| FR-J68GZ-D9 | `laravel/pulse` * — performance monitoring dashboard |
| FR-J68GZ-D10 | `tallstackui/tallstackui` ^4.0 — UI kit (TallstackUI-only, replaces DaisyUI/MaryUI/PHPFlasher, FB792 FR-J68GZ-TS6) |
| FR-J68GZ-D11 | `laravel/tinker` ^3.0 — REPL for debugging |

### 4.3 Database

| ID    | Requirement |
| ----- | ----------- |
| FR-J68GZ-DB1 | SQLite is the default and zero-config database (WAL mode, busy_timeout=5000) |
| FR-J68GZ-DB2 | MySQL 8.0+ supported for shared hosting deployments |
| FR-J68GZ-DB3 | MariaDB 10.6+ supported |
| FR-J68GZ-DB4 | PostgreSQL 15+ supported for larger deployments |
| FR-J68GZ-DB5 | All models use UUID v7 primary keys (time-ordered, via `HasUuids` trait) |
| FR-J68GZ-DB6 | All foreign keys define `onDelete` and `onUpdate` behavior (D6 invariant) |
| FR-J68GZ-DB7 | Migrations organized in 6 sequential layers: Foundation → Auth → Config → Internship Core → Grouping → Evaluation |
| FR-J68GZ-DB8 | 55 tables total: 37 domain + 18 system/package |

### 4.4 Package/Framework Database Tables

The following 18 tables are created by third-party packages and managed by their own migrations.
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

### 4.5 System Health Check

> **Governing spec:** [system-maintenance.md](E1MSJ-system-maintenance.md) — the `system:health` command,
> its 15-point check, CLI/JSON output, and admin-accessible surface are fully specified there
> (Phase 12). This section records the Foundation-level contract referenced by internara-project
> §6.1 (System Health).

| ID    | Requirement |
| ----- | ----------- |
| FR-J68GZ-SY8 | The system MUST provide a 15-point system health check covering: environment, setup status, PHP version, required extensions, recommended extensions, memory, database connectivity, migration freshness, storage writability, disk space, queue connectivity, cache connectivity, app key, storage symlink, and maintenance mode |
| FR-J68GZ-SY9 | The health check MUST be accessible via `php artisan system:health` (CLI) and expose an admin-accessible web surface |
| FR-J68GZ-SY10 | Health check results MUST be cached under the registered cache key (`system.health_check`) to avoid re-running expensive checks on every request |
| FR-J68GZ-SY11 | The `/up` endpoint MUST return 200 only when required extensions and DB connectivity pass | |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID     | Requirement |
| ------ | ----------- |
| NFR-J68GZ-S1 | `declare(strict_types=1)` in every PHP file except migrations and config (D1 invariant) |
| NFR-J68GZ-S2 | No debug calls in committed code: dd, dump, ray, var_dump, print_r, die (D2 invariant) |
| NFR-J68GZ-S3 | APP_KEY must be 32-byte base64 string; rotation supported via `APP_PREVIOUS_KEYS` |

### 5.3 Reliability

| ID     | Requirement |
| ------ | ----------- |
| NFR-J68GZ-R1 | SQLite foreign keys enforced (`DB_FOREIGN_KEYS=true`) |

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

// Redis (multi-service)
'redis' => [
    'default' => ['database' => 0],  // Queue
    'cache'   => ['database' => 1],  // Cache
    // Session uses SESSION_CONNECTION env
],

```

---

## 7. Design Decisions

### DD-1 — SQLite as Default Database

**Decision:** SQLite is the default database driver, not MySQL.
**Rationale:** Zero-config development and shared hosting. Schools often lack DBA expertise. SQLite
with WAL mode handles concurrent reads well for single-tenant workloads up to 500 users.
**Trade-off:** No connection pooling, limited concurrent writes. Mitigated by migration path to
MySQL/PostgreSQL for larger deployments.

### DD-2 — UUID v7 Primary Keys

**Decision:** All models use UUID v7 (time-ordered) primary keys via Laravel's `HasUuids` trait.
**Rationale:** Time-ordered UUIDs improve B-tree index performance. UUIDs eliminate sequential ID
exposure (no user can guess `/users/2` → `/users/3`). No migration coordination needed across
environments.
**Trade-off:** 16 bytes per PK vs 4 bytes for auto-increment. Storage overhead is negligible for
<100K rows.

### 7.3 Schema Design Philosophy

The domain schema consolidates 37 tables from an original 55-table design. Nine optimization
decisions shaped the final schema — each documented in the relevant spec's Design Decisions section.

| # | Optimization | Rationale | Spec Reference |
| - | ------------ | --------- | -------------- |
| 1 | `mentors` table eliminated — `users` profile-based `mentor_type` | Teachers are already users; mentor is a relationship, not an entity | [user-crud-and-status.md](95EVB-user-crud-and-status.md) §2 |
| 2 | `schools` table eliminated — single-tenant via settings key-value | One school per deployment; `settings.name = 'school_name'` suffices | [settings-infrastructure.md](YB22J-settings-infrastructure.md) §3 |
| 3 | `handbooks` merged into `documents` with `type = 'handbook'` | Single document engine with acknowledgement workflow via `activity_log` | [handbooks.md](ZUFG8-handbooks.md) §DD-4 |
| 4 | `absence_requests` merged into `attendances` | Shared table with status enum: `pending_absence` / `approved` / `rejected` | [daily-activity.md](1KSWL-daily-activity.md) §DD-1 |
| 5 | `rubric_metrics` stored as JSON in `assessment_rubrics.metrics_json` | Variable schema per rubric type; avoids rigid column-per-metric | [assessment.md](ARDA6-assessment.md) §DD-1 |
| 6 | `activity_log` replaces `handbook_acknowledgments` | Append-only audit log already captures IP, user agent, timestamp | [handbooks.md](ZUFG8-handbooks.md) §DD-4 |
| 7 | `registration_mentor` eliminated — mentor via `internship_group_members` | Mentor assignment lives in the group membership layer | [internship-groups.md](IT0OE-internship-groups.md) §DD-2 |
| 8 | `reports` uses snapshot columns (`data_snapshot_json`, `grade_breakdown_json`) | Grade cards are immutable point-in-time records; snapshots prevent retroactive drift | [reports.md](R6BMW-reports.md) §DD-1 |
| 9 | `internship_phases` stored as JSON array on `internship_programs` | Phases are a fixed ordered set per program; JSON avoids extra table and JOINs | [internship-lifecycle.md](7C5WM-internship-lifecycle.md) §DD-1 |

---

## 8. Success Metrics

### 8.1 System Requirements

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| PHP version check | Always accurate | `php -v` parse result matches FR-J68GZ-SY1 |
| Extension check | 11 required + 3 recommended | `php -m` comparison against FR-J68GZ-SY2/FR-J68GZ-SY3 |
| First-run provisioning | < 30 seconds | `time php artisan setup:install` |

### 8.2 Dependencies

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Composer install | 100% success on supported PHP | `composer install` exit code |
| No abandoned packages | All 12 production deps maintained | `composer audit` |
| Version lock | `composer.lock` committed | CI check |

### 8.3 Database

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| SQLite out-of-box | Zero-config for dev | `php artisan migrate` without .env DB settings |
| MySQL compatibility | 8.0+ | CI matrix test |
| PostgreSQL compatibility | 15+ | CI matrix test |
| Migration freshness | < 60 seconds | `time php artisan migrate:fresh` on 55 tables |

---

## 9. Roadmap

### Prerequisites
No prerequisites — this is a foundational spec.

### Build Guide
After implementing this spec, the system has a verified dependency manifest, database portability across SQLite/MySQL/PostgreSQL, and clear minimum requirements. This spec is reference material — its requirements are enforced by `composer.json` and `config/database.php`. The next step is to implement module discovery, which scans the module directories defined in this spec's schema.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [module-discovery.md](I1BCV-module-discovery.md) | Uses module directory structure defined in §4.4 |

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
