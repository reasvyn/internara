# AGENTS.md — Project Guidelines for AI Agents

> **Last updated:** 2026-07-01
> **Changes:** add Metacognitive Loop section (Construct → Evaluate → Verify → Decide) as universal agent thinking framework

Provides the essential mental model, quick-reference essentials, and project-specific rules needed
when first opening the project. Do NOT duplicate content already covered in `docs/` — refer to it.

**Reading order for new agents:** Project Identity → Tech Stack → Architecture Core → Module Map →
Directory Map → Dev Commands → Critical Rules → Doc Navigation → Quick Reference

---

## Metacognitive Loop — Construct, Evaluate, Verify, Decide

Every task — whether fixing a bug, adding a feature, refactoring code, or writing docs — should
follow this four-phase loop. It prevents blind execution, surface-level fixes, and premature
closure.

```
┌─────────────────────────────────────────────────────────────────────┐
│                    METACOGNITIVE LOOP                                │
│                                                                     │
│  1. CONSTRUCT ──► 2. EVALUATE ──► 3. VERIFY ──► 4. DECIDE          │
│       │                │               │             │              │
│       │ Build /        │ Assess         │ Test,       │ Determine   │
│       │ implement      │ against        │ lint,       │ next step:  │
│       │ with context   │ requirements,  │ analyze,    │ accept,     │
│       │ awareness      │ conventions,   │ confirm     │ revise,     │
│       │                │ trade-offs      │             │ or escalate │
│       └────────────────┴───────────────┴─────────────┘              │
│                                                                     │
│  ↻ Loop until DECIDE → "Done" or "Escalate"                        │
└─────────────────────────────────────────────────────────────────────┘
```

### 1. CONSTRUCT — Build with Context

Before writing code or making changes:

- **Understand the problem domain** — read the relevant docs, module references, and ADRs
- **Read the existing code** — understand patterns, conventions, and current implementation
- **Check for stale information** — verify file paths, class names, signatures, and counts in the
  actual codebase; do NOT trust what docs or skills say without verification
- **Consider alternatives** — at least two approaches before committing to one
- **Follow architecture** — stay within the 4-layer dependency rules, Action Triad, DTO boundaries

Output: A change (code, doc, config, migration, etc.)

### 2. EVALUATE — Assess What Was Built

After constructing, assess the result against multiple dimensions:

- **Requirements match** — does the change actually solve the stated problem?
- **Convention compliance** — `declare(strict_types=1)`, `#[Fillable]`, naming rules, base classes
- **Architecture fit** — does the change respect layer boundaries, dependency direction, data flow?
- **Trade-off awareness** — what was sacrificed? (performance for readability? speed for safety?)
- **Scope discipline** — does the change do ONE thing? If it grew beyond the original scope, split it

Output: A judgment about the change's quality and completeness.

### 3. VERIFY — Confirm It Works

Run the appropriate verification steps before declaring done:

- **Read what you wrote** — review the diff for obvious issues before running anything
- **Run relevant tests** — at minimum the tests for the changed files: `php artisan test --compact --filter={TestName}`
- **Run static analysis** — `vendor/bin/phpstan analyse --no-progress` for PHP changes
- **Check for regressions** — run the full test suite for anything beyond a trivial change
- **Verify conventions** — no `dd()/dump()/ray()`, `__()` for user strings, `declare(strict_types=1)`

Output: A pass/fail signal. If any check fails, return to CONSTRUCT with new information.

### 4. DECIDE — Determine Next Action

Based on evaluation and verification, pick ONE:

| Decision | When | Action |
|----------|------|--------|
| **Accept** | Change is correct, complete, and verified | Commit, push, close issue |
| **Revise** | Change works but has quality/structure issues | Return to CONSTRUCT with specific feedback |
| **Split** | Change grew beyond original scope | Create separate task for the extra scope, commit original |
| **Escalate** | Change reveals a deeper problem or dependency | File a new issue, link it, note it as blocker |
| **Defer** | Change is lower priority than other open work | Move to backlog, document why |

The loop continues until DECIDE → "Accept" or "Escalate".

### When to Use

- **Every task, every time.** Even a one-line fix benefits from Evaluate + Verify before commit.
- **The loop is recursive.** Subtasks discovered during CONSTRUCT get their own mini-loop.
- **Speed vs rigor.** A typo fix needs seconds per phase. A new feature needs hours. Adapt the
  depth to the scope.

---

=== foundation ===

# Project Identity

**Internara** is a self-hosted, single-tenant vocational fieldwork (PKL — _Praktik Kerja Lapangan_)
management system for Indonesian SMA/SMK and technical education institutions. It manages the entire
PKL lifecycle: student enrollment, slot-based company placement, geofenced attendance, reflective
logbooks, competency assessments, report revisions, and cryptographic certificate issuance.

- **Author:** Reas Vyn (reasvyn@gmail.com)
- **License:** MIT
- **Repository:** `reasvyn/internara`
- **Version:** 0.1.0 (composer.json)

# Tech Stack

| Layer         | Technology                                                                                                                                                                                       |
| ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Language      | PHP                                                                                                                                                                                              |
| Framework     | Laravel                                                                                                                                                                                          |
| Frontend      | Livewire, Alpine.js, maryUI, DaisyUI, Tailwind CSS                                                                                                                                               |
| Build         | Vite                                                                                                                                                                                             |
| Database      | SQLite (default), MySQL, MariaDB, PostgreSQL                                                                                                                                                     |
| Queue/Cache   | Redis / Database                                                                                                                                                                                 |
| WebSockets    | Laravel Reverb (optional)                                                                                                                                                                        |
| Observability | Laravel Pulse, SmartLogger (dual-channel)                                                                                                                                                        |
| Testing       | Pest, PHPStan, Laravel Pint                                                                                                                                                                      |
| Quality       | Larastan, Mockery                                                                                                                                                                                |
| Key packages  | `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `spatie/laravel-activitylog`, `spatie/laravel-model-status`, `livewire/livewire`, `php-flasher/flasher-laravel`, `barryvdh/laravel-dompdf` |

# Architecture Core

## Action-Based MVC (Vertical Slicing)

Code is organized by **business module**, not by technical layer. Each module is a vertical slice
through all 4 layers (Framework/Infra → Data/Persistent → Business/Domain Ops → Presentation/UI).

**4-Layer Architecture** — strict downward-only dependency:

```
Layer 4: Presentation/UI (Livewire 4, Blade, maryUI/DaisyUI, Controllers,
         Middleware, Policies, Routes, Console)
Layer 3: Business/Domain Ops (Actions: Command/Read/Process, Events,
         Listeners, Notifications)
Layer 2: Data/Persistent (Models, Entities, DTOs, Enums, Database, Config)
Layer 1: Framework/Infrastructure/Utilities (PHP 8.4, Laravel 13, Core
         base classes, Contracts, Services, Support, packages)
```

## Action Triad

| Type        | Base Class          | Transaction | Logging     | Events         | Use Case                                 |
| ----------- | ------------------- | ----------- | ----------- | -------------- | ---------------------------------------- |
| **Command** | `BaseCommandAction` | ✅ Required | ✅ Required | ❌ Only if listener exists | All mutations (CUD, state transitions)   |
| **Read**    | `BaseReadAction`    | ❌          | ❌          | ❌             | Complex queries, aggregation, dashboards |
| **Process** | `BaseProcessAction` | ✅ Level    | ✅ Level    | ❌ Only if listener exists | Multi-step orchestration                 |

**Key rules:**

- Every Action has **exactly one public `execute()` method**
- Actions are the **only** entry point for mutations — Livewire never calls `Model::create()`
  directly
- Simple queries (`Model::find()`, `Model::where()->get()`) stay in Livewire
- Complex queries go in Read Actions
- `BaseAction::transaction()` auto-detects nesting, queues events until commit, retries on deadlock
  (3 attempts)
- **Command/Process Actions SHOULD accept a DTO (`BaseData`) for 3+ params** — simple ops may use typed scalars. Never raw `array`.
- **Command/Process Actions SHOULD return `ActionResponse`** for structured feedback. Simple create/update may return Model directly.
- **Actions MUST delegate business rules to Entities** — throw `RejectedException` on violation
- **Events are for async communication only.** Do NOT create events unless a listener exists
  (cache invalidation, cross-module notification). Simple CRUD + `$this->log()` is sufficient.
  See `docs/architecture/event-pattern.md`.
- **Livewire may use Entities for READ-ONLY UI checks** (show/hide buttons). WRITE decisions go through Actions.

## Base Class Mandate

| You Need...          | Use This                                        | Not This                    |
| -------------------- | ----------------------------------------------- | --------------------------- |
| Database table       | `extends BaseModel`                             | `extends Model`             |
| Mutation             | `extends BaseCommandAction`                     | Custom service              |
| Complex query        | `extends BaseReadAction`                        | Using `transaction()`       |
| Orchestration        | `extends BaseProcessAction`                     | Duplicating logic           |
| Infrastructure logic | `app/Core/Services/` with constructor injection | Custom support class        |
| Business rules       | `extends BaseEntity` (final readonly)           | Inline in model             |
| Authorization        | `extends BasePolicy`                            | Custom closure              |
| CRUD table UI        | `extends BaseRecordManager`                     | Bespoke Livewire            |
| DTO/Value object     | `extends BaseData` (final readonly)             | Raw arrays                  |
| Event                | `extends BaseEvent`                             | Implements `ShouldDispatch` |
| Enum                 | `implements LabelEnum`                          | Plain PHP enum              |
| State machine        | `implements StatusEnum` + `LabelEnum`           | Boolean field               |
| Exception            | `extends AppException` or `ModuleException`     | `\Exception`                |
| Action return        | `ActionResponse`                               | Returning Model directly     |
| Action input         | `BaseData` DTO (for 3+ params)                 | Raw `array` parameter        |

## 4-Layer Data Flow (DTO Boundaries)

```
UI LAYER (Livewire/Controller/Console)
    │  receives: FormRequest/LivewireForm (validated)
    │  outputs: DTO (BaseData) ── IMMUTABLE BOUNDARY
    ▼
BUSINESS LAYER (Action/Service/Support)
    │  receives: DTO (BaseData) ONLY
    │  delegates: business rules → Entity
    │  persists: via Model (DTO values → Model attributes)
    │  returns: ActionResponse ── IMMUTABLE BOUNDARY
    ▼
DOMAIN LAYER (Entity/Event)
    │  created from: Model record (via fromModel())
    │  answers: boolean/enum business questions (canBeDeleted, isActive)
    │  dispatches: Events (after transaction commit)
    ▼
DATA LAYER (Model)
    │  Eloquent persistence — knows nothing about layers above
```

**Boundary rules:**
- UI → Business: ALWAYS via DTO (for 3+ params), typed scalars OK for simple. Never Request, never Model.
- Business → Domain: Entity created FROM Model WITHIN Action (never passed from UI for writes)
- Business → Data: Model::create/update with DTO values only
- Livewire MUST NOT call `Model::create/update/delete` directly
- Livewire may access Entity methods for READ-ONLY UI checks (e.g., `canBeDeleted()` to hide a button)
- Entity MUST NOT import Action, Service, Livewire, or Controller

## Circular Dependency Prevention

Dependencies flow ONE direction: UI → Business → Domain → Data

| Entity type | May depend on | Must NOT depend on |
|-------------|--------------|-------------------|
| DTO (BaseData) | Core BaseData, scalars, enums, Carbon | Models, Actions, Entities, Livewire, HTTP |
| Entity (BaseEntity) | Core BaseEntity, Carbon, enums | Actions, Services, Livewire, HTTP |
| Model (BaseModel) | Core BaseModel, Eloquent | Actions, Livewire, HTTP (except asEntity bridges) |
| Action | Models, Entities, DTOs, other Actions | Livewire, Controllers, HTTP |
| Livewire | Actions (via injection), Models (read-only) | Entity directly, Model::create/update/delete |

## Cross-Module Communication

Direct imports are **allowed** (no strict boundaries). Four patterns:

1. **Direct import** — no side effects (e.g., `use App\Academics\Models\AcademicYear;`)
2. **Action call** — cross-module business operation (inject and call `->execute()`)
3. **Module event** — fire-and-forget side effects
4. **Core contract** — broad abstractions (`LabelEnum`, `SendsNotifications`)

## Exception Hierarchy

```
RuntimeException
├── AppException (abstract)       ← Infrastructure failures
│   ├── ActionException
│   │   ├── ValidationFailedException  (422)
│   │   └── ConflictException          (409)
│   ├── InfrastructureException
│   │   └── RateLimitException         (429)
│   └── PresentationException
│       ├── NotFoundException          (404)
│       └── UnauthorizedException      (403)
└── ModuleException (abstract)    ← Business rule violations
    └── RejectedException
```

## Data Flow

**Mutations:** Livewire/Controller → Policy → Command Action (DTO → Entity check → Transaction → Log → Event) → DB
**Simple reads:** Livewire → Model::query() → DB
**Complex reads:** Livewire → Read Action (DTO) → Model → DB

# Module Map

| #   | Module            | Purpose                                             | Key Models                                  | Depends On                                  | Used By                          |
| --- | ----------------- | --------------------------------------------------- | ------------------------------------------- | ------------------------------------------- | -------------------------------- |
| 1   | **Core**          | Base classes, contracts, utilities, exceptions      | BaseModel, BaseAction, ActivityLog          | —                                           | All modules                      |
| 2   | **Auth**          | Login, password, RBAC, recovery, super admin        | User (via Authenticatable)                  | Core, User                                  | All modules                      |
| 3   | **User**          | Profiles, notifications, dashboards, account status | User                                        | Core, SysAdmin                              | All modules                      |
| 4   | **SysAdmin**      | User management, announcements, audit, Pulse        | —                                           | User, Academics, Core                       | User                             |
| 5   | **Setup**         | One-time install wizard, environment audit          | Setup (entity)                              | Core, Academics                             | — (one-time)                     |
| 6   | **Settings**      | Config, branding, feature flags, locale             | Setting                                     | Core, Academics                             | All modules                      |
| 7   | **Academics**     | School profile, departments, academic years         | Department, AcademicYear, School            | Core                                        | Program, Enrollment, Assessment  |
| 8   | **Program**       | Internship lifecycle, groups, phases                | Internship, InternshipGroup                 | Academics, Partners, Core                   | Enrollment, Journals, Evaluation |
| 9   | **Enrollment**    | Registration, placement, change requests            | Registration, Placement, AccountApplication | User, Program, Academics, Core              | Journals, Assessment, Evaluation |
| 10  | **Assessment**    | Rubrics, evaluation, grading                        | Rubric, Assessment                          | Core                                        | Evaluation                       |
| 11  | **Evaluation**    | Feedback forms, surveys, auto-scoring               | EvaluationForm, Section, Question           | User, Assessment, Program, Core             | Certification                    |
| 12  | **Assignment**    | Tasks, submissions, grading                         | Assignment, Submission                      | User, Program, Core                         | —                                |
| 13  | **Journals**      | Logbooks, attendance, absence                       | Logbook, Attendance, AbsenceRequest         | Enrollment, Program, Core                   | Evaluation                       |
| 14  | **Guidance**      | Supervision logs, mentoring                         | SupervisionLog                              | User, Program, Core                         | —                                |
| 15  | **Incident**      | Issue reporting, resolution                         | IncidentReport                              | User, Program, Core                         | —                                |
| 16  | **Partners**      | Companies, partnerships, MoU                        | Company, Partnership                        | Core                                        | Program, Guidance                |
| 17  | **Certification** | Certificates, templates, QR                         | Certificate, CertificateTemplate            | User, Evaluation, Program, Core             | —                                |
| 18  | **Reports**       | Grade cards, score aggregation                      | FinalGradeCard                              | User, Program, Assessment, Enrollment, Core | —                                |
| 19  | **Document**      | Templates, handbooks, rendering                     | OfficialDocument                            | Core, User                                  | —                                |

# Directory Map

```
app/
├── {Module}/                     # 19 modules (Auth, User, Setup, Settings, ...)
│   ├── {SubModule}/              # Business subdomain (e.g., Profile, Internship)
│   │   ├── Actions/              # Command, Read, Process actions
│   │   ├── Models/               # Eloquent models (40+ total)
│   │   ├── Policies/             # Authorization policies (28+ total)
│   │   ├── Entities/             # Pure business rules (final readonly)
│   │   ├── Enums/                # Module-specific enums
│   │   ├── Livewire/             # UI components
│   │   │   └── Forms/            # Form Objects (extends Livewire\Form)
│   │   ├── Events/               # Module events
│   │   ├── Listeners/            # Event subscribers
│   │   ├── Notifications/        # Multi-channel alerts
│   │   └── Http/                 # Controllers, Middleware, Requests
│   ├── Types/                    # Shared value objects, flat enums
│   ├── Actions/                  # Cross-submodule actions
│   ├── Http/                     # Cross-submodule controllers
│   ├── Console/                  # Artisan commands
│   ├── Livewire/                 # Cross-submodule UI
│   └── Support/                  # Module utilities
├── Core/                         # Foundation layer
│   ├── Actions/                  # BaseAction, BaseCommandAction, BaseReadAction, BaseProcessAction
│   ├── Models/                   # BaseModel, BaseAuthenticatable, ActivityLog
│   ├── Policies/                 # BasePolicy
│   ├── Livewire/                 # BaseRecordManager
│   ├── Data/                     # BaseData, ActionResponse
│   ├── Entities/                 # BaseEntity
│   ├── Enums/                    # Core enums (AuditCategory, etc.)
│   ├── Events/                   # BaseEvent
│   ├── Exceptions/               # AppException, ModuleException (+ 9 concrete)
│   ├── Contracts/                # LabelEnum, StatusEnum, ColorableEnum, SettingsStore, SendsNotifications
│   ├── Support/                  # SmartLogger, PiiMasker, AppInfo, etc.
│   ├── Http/                     # Core middleware (SecurityHeaders, LogContext)
│   └── Console/                  # System commands
├── Console/                      # App-level console kernel
├── Exceptions/                   # App-level exception handler
├── Http/                         # App-level HTTP kernel
├── Jobs/                         # Queued jobs
├── Providers/                    # AppServiceProvider, EventServiceProvider
└── ... (other modules)

config/                           # 40 config files
docs/                             # Full documentation (see docs/doc-index.md for catalog)
lang/                             # en + id locales
resources/views/{module}/         # Blade views matching app/{Module}/
routes/web/{module}.php           # 17 module route files (no Core routes)
tests/
├── Feature/{Module}/{SubModule}/{Name}Test.php
└── Unit/{Module}/{SubModule}/{Name}Test.php
database/
├── migrations/                   # 43+ migration files
├── factories/                    # Model factories
└── seeders/                      # Database seeders
```

# Dev Commands

```bash
# Setup
composer install && npm install && cp .env.example .env && php artisan key:generate
php artisan setup:install            # Full install wizard (audit → migrate → seed → setup URL)

# Development
composer run dev                     # Serve + Queue + Logs + Vite concurrently
npm run dev                          # Vite only
php artisan serve                    # Dev server only
php artisan queue:work --tries=1     # Queue worker

# Testing
php artisan test --compact --filter=TestName    # Single test
composer run test                    # Full test suite (clear + test)
composer run test:feature            # Feature tests only
composer run test:unit               # Unit tests only
composer run test:coverage           # Coverage (min 80%)
composer run coverage                # pcov-based coverage report

# Quality
vendor/bin/pint --dirty --format agent           # Code style (format changed files)
vendor/bin/pint --test                           # Check style
vendor/bin/phpstan analyse --no-progress         # Static analysis
npm run lint                                     # Prettier check
npm run format                                   # Prettier format
composer run quality                             # lint + analyse + test:feature
composer run quality:full                        # format + analyse:strict + test:coverage

# Artisan
php artisan route:list --method=GET    # Inspect routes (module-split)
php artisan config:show app.name       # Read config
php artisan tinker --execute 'Model::count();'
php artisan system:health              # System health check
php artisan admin:recover              # Super admin recovery (CLI)
php artisan notifications:prune        # Prune old notifications
```

# Critical Rules (DO NOT VIOLATE)

## Super Admin

- Name is ALWAYS `Administrator` (config `setup.defaults.admin_name`)
- Username is ALWAYS `superadmin` (config `setup.defaults.admin_username`)
- `SetupSuperAdminAction::execute()` accepts ONLY `(string $email, string $password)`
- `InitializeSuperAdminAction` must use config defaults, NOT caller-provided values
- `FinalizeSetupAction` must extract only `email` and `password` from `adminData` array

## Coding

- `declare(strict_types=1)` in ALL PHP files except migrations and config
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code
- All user-facing strings use `__()` helper — never hardcode display text
- Foreign keys use `foreignUuid()->constrained('{table}')` with explicit `onDelete()`/`onUpdate()`
- Models use `#[Fillable]` attribute (PHP 8.4), NOT `$fillable` or `$guarded`
- Never pass raw request input to `create()`/`update()` — use explicit `->only()` or `->toArray()`
- No raw SQL (`DB::raw()`, `whereRaw()`, etc.) without parameterized binding
- No `app()->make()` or `resolve()` in application code — use constructor/method injection
- Cache keys must be registered in `config/cache-keys.php` — never inline strings
- Livewire components must NOT call `Model::create/update/delete` directly — use Actions
- Actions must extend the correct base class (Command/Read/Process)
- Command/Process Actions SHOULD accept `BaseData` DTO for 3+ params — never raw `array`
- Command/Process Actions SHOULD return `ActionResponse` for structured feedback
- Livewire components must NOT access Entity methods for WRITE decisions — delegate to Action
- Livewire components MAY access Entity methods for READ-ONLY UI checks (show/hide buttons)
- Entity classes must NOT import Actions, Services, Livewire, or Controllers
- DTOs must NOT import Models, Actions, Entities, or Livewire — only Core BaseData, scalars, enums, Carbon

## Testing

- Every Action MUST have its own test file
- Use `LazilyRefreshDatabase` over `RefreshDatabase`
- Use `assertModelExists()` over `assertDatabaseHas()`
- Never mock Eloquent models — use real database in feature tests
- TDD order: Enum → Entity → Command → Read → Process → Livewire → Policy → Console
- Test structure: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`

# Doc Navigation

All authoritative docs live under `docs/`. Always read relevant docs before making changes.

```mermaid
flowchart LR
    A[doc-index.md] --> B[architecture.md]
    A --> C[conventions.md]
    A --> D[modules/module-index.md]
    A --> E[foundation/*.md]
    A --> F[infrastructure/*.md]
    A --> G[architecture/*-pattern.md]
    B --> G
    D --> H[docs/modules/{module}.md]
    D --> I[docs/modules/{module}-reference.md]
```

| Topic Area                   | Start Here                                                               |
| ---------------------------- | ------------------------------------------------------------------------ |
| Architecture & 4 layers     | `docs/architecture.md`                                                   |
| Action Triad                 | `docs/architecture.md` (§Action Triad)                                   |
| Base Class Mandate           | `docs/architecture.md` (§Base Class Mandate)                             |
| Data Flow & DTO boundaries   | `docs/architecture.md` (§Data Flow)                                      |
| Circular Dependency Prevention | `docs/architecture.md` (§Circular Dependency Prevention)                |
| File structure               | `docs/architecture/modular-pattern.md`                                   |
| Naming conventions           | `docs/conventions.md` (§4)                                               |
| PHP language rules           | `docs/conventions.md` (§2)                                               |
| Models & Entities            | `docs/architecture/model-pattern.md`, `entity-pattern.md`                |
| Enums                        | `docs/architecture/enum-pattern.md`                                      |
| Livewire                     | `docs/architecture/livewire-pattern.md`                                  |
| Policies & RBAC              | `docs/architecture/policy-pattern.md`, `docs/foundation/rbac.md`         |
| Events & Notifications       | `docs/architecture/event-pattern.md`                                     |
| Validation & Exceptions      | `docs/architecture/exception-pattern.md`                                 |
| Logging & SmartLogger        | `docs/architecture/logging-pattern.md`                                   |
| Caching                      | `docs/architecture/cache-pattern.md`, `config/cache-keys.php`            |
| Testing                      | `docs/architecture/testing-pattern.md`, `docs/infrastructure/testing.md` |
| Migrations/Factories/Seeders | `docs/conventions.md` (§7)                                               |
| Deployment                   | `docs/infrastructure/deployment.md`                                      |
| Configuration                | `docs/infrastructure/configuration.md`                                   |
| Database schema              | `docs/infrastructure/database.md`, `docs/foundation/erd.md`              |
| Known issues                 | [GitHub Issues](https://github.com/reasvyn/internara/issues)             |
| Roadmap                      | `docs/roadmap.md`                                                        |
| ADRs                         | `docs/adr/adr-index.md`                                                  |

# Quick Links

These are the most frequently referenced files during development. Open them directly rather than
searching.

## Product & Architecture

- `docs/foundation/product-definition.md` — Product scope, personas, system boundary
- `docs/architecture.md` — 4-layer architecture, Two Kinds of Logic (domain vs infra vs static), Action Triad, data flow, circular dep. prevention
- `docs/conventions.md` — PHP rules, naming, security, testing conventions
- `docs/key-features.md` — Feature inventory across all 19 modules
- `docs/foundation/rbac.md` — Role-based access control, permissions model
- [GitHub Issues](https://github.com/reasvyn/internara/issues) — Bug tracker and feature requests
- `docs/roadmap.md` — Planned features and roadmap

## Module References

- `docs/modules/module-index.md` — Module dependency graph and navigation
- `docs/modules/{module}.md` — Business overview for a specific module
- `docs/modules/{module}-reference.md` — API reference for a specific module

## Architecture Patterns

- `docs/architecture/action-pattern.md` — Action contract details
- `docs/architecture/model-pattern.md` — Eloquent model conventions
- `docs/architecture/entity-pattern.md` — Entity-model separation
- `docs/architecture/livewire-pattern.md` — Livewire component rules
- `docs/architecture/enum-pattern.md` — Enum contracts (LabelEnum, StatusEnum)
- `docs/architecture/policy-pattern.md` — Authorization gates
- `docs/architecture/exception-pattern.md` — Exception hierarchy
- `docs/architecture/logging-pattern.md` — SmartLogger, PII masking
- `docs/architecture/cache-pattern.md` — Cache key registry, invalidation
- `docs/architecture/testing-pattern.md` — Testing patterns
- `docs/architecture/event-pattern.md` — Events and notifications
- `docs/architecture/service-pattern.md` — Services vs Actions vs Support (domain logic vs infra logic vs static utilities)
- `docs/architecture/support-pattern.md` — Support utilities: static-only, no constructor injection

## Infrastructure

- `docs/infrastructure/database.md` — Schema design, UUIDs, engine comparison
- `docs/infrastructure/deployment.md` — Deployment options
- `docs/infrastructure/configuration.md` — Three-tier config system
- `docs/infrastructure/testing.md` — Testing guide
- `docs/infrastructure/backup-recovery.md` — Backup strategies
- `docs/infrastructure/cache.md` — Caching strategy
- `docs/infrastructure/media-library.md` — File uploads and media

## Key Source Files

- `app/Core/Actions/BaseAction.php` — Base action with transaction/event/log
- `app/Core/Actions/BaseCommandAction.php` — Command action with respond/validate/authorize
- `app/Core/Actions/BaseReadAction.php` — Read action contract
- `app/Core/Actions/BaseProcessAction.php` — Process action contract
- `app/Core/Models/BaseModel.php` — Base Eloquent model
- `app/Core/Policies/BasePolicy.php` — Base policy
- `app/Core/Livewire/BaseRecordManager.php` — CRUD table Livewire component
- `app/Core/Data/ActionResponse.php` — Standardized action return envelope
- `app/Core/Support/SmartLogger.php` — Dual-channel logging
- `app/Core/Exceptions/AppException.php` — Base exception (infrastructure)
- `app/Core/Exceptions/ModuleException.php` — Base exception (business rules)

## Key Config Files

- `config/cache-keys.php` — ALL cache keys (never inline)
- `config/permission.php` — RBAC permissions
- `config/setup.php` — Setup wizard defaults and security
- `config/settings.php` — System settings
- `config/module.php` — Module discovery configuration
- `config/pulse.php` — Laravel Pulse observability
- `config/media-library.php` — File upload configuration

## ADRs

- `docs/adr/adr-index.md` — All architecture decision records

# MCP & Tooling

## Boost Tools (preferred over manual alternatives)

Prefer `database-query`, `database-schema`, `get-absolute-url`, `browser-logs` over raw SQL/tinker.
Use `search-docs` with `packages` array before code changes.

## MCP Servers (configured in `opencode.json`)

- `docsgrep` — search documentation (`@anovise/docsgrep`, local npx)
- `laravel-boost` — Laravel Boost MCP (`php artisan boost:mcp`)

## Skills — SDLC Phase Map (in `.agents/skills/`)

Activate the relevant skill when working in that SDLC phase:

| Phase                    | Skills                                                                                                                                           |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **PLANNING**             | `roadmap-planning`                                                                                                                               |
| **ANALYSIS**             | `audit-protocol`, `security-audit`                                                                                                               |
| **DESIGN / REFACTORING** | `code-refactoring`                                                                                                                               |
| **IMPLEMENTATION**       | `feature-building`, `laravel-best-practices`, `livewire-development`, `pulse-development`, `medialibrary-development`, `tailwindcss-development` |
| **TESTING**              | `pest-testing`                                                                                                                                   |
| **MAINTENANCE**          | `sync-docs`                                                                                                                                      |

### boost.json

Enables guidelines mode, MCP, and 6 implementation skills for Boost tooling.

# Quick Reference

## Language

**English only.** Always communicate in English, even when the user writes in Indonesian. Code,
comments, commit messages, and documentation must all be in English.

## PHP Syntax

- Curly braces `{ }` required for ALL control structures (even single-line)
- Constructor property promotion: `public function __construct(protected readonly X $x) {}`
- Explicit return types on ALL methods: `function execute(): void`
- Type hints on ALL parameters: `function find(string $id): ?Model`
- `===` over `==` unless loose comparison is intentional
- `match()` over long `switch()` blocks
- `str_contains()` / `str_starts_with()` / `str_ends_with()` over `strpos() === 0`
- Null-safe `?->` and null coalescing `??` over explicit null checks
- Trailing commas on multiline arrays, function calls, constructor params
- Readonly properties prefer promoted constructor parameters

## Commit Format

```
type(scope): description

- Bullet points for details (optional)
- Reference issues: #123
```

Types: `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`

## Branch Naming

`feat/{kebab-description}`, `fix/{description}`, `refactor/{module}-{scope}`, `docs/{what}`,
`chore/{task}`, `hotfix/{description}`

## Pre-commit Checklist

- [ ] `declare(strict_types=1)` present
- [ ] No debug calls (`dd/dump/ray/var_dump/print_r/die`)
- [ ] All user-facing strings use `__()` helper
- [ ] Action uses correct triad pattern
- [ ] Command/Process Action accepts DTO (for 3+ params) and returns ActionResponse (for structured feedback)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] No N+1 queries — eager loading verified
- [ ] No unescaped `{!! !!}` for user content
- [ ] Tests pass: `php artisan test --compact`
- [ ] Pint clean: `vendor/bin/pint --dirty --format agent`
- [ ] PHPStan passes: `vendor/bin/phpstan analyse --no-progress`
- [ ] Doc metadata updated: Last updated date + one-line Changes description
- [ ] Relevant docs updated (documentation-first approach)

## Documentation Rules

### Metadata (Every `.md` File)

```
> **Last updated:** YYYY-MM-DD
> **Changes:** brief one-line description of changes
```

- **Last updated** is mandatory — reflects the date of the most recent content change, not file creation
- **Changes** is mandatory — must be a single line. Use `sync —` prefix for derivative syncs, or describe the actual change. Files with no history use `initial metadata — no content changes`
- `> **Status:**` must NEVER appear in any doc. If a doc needs context, use factual statements instead
- Metadata block MUST contain ONLY `**Last updated:**` and `**Changes:**`. No other fields.

### Content Rules

| Rule | Correct | Wrong |
|------|---------|-------|
| **Structural** over counts | "Models extend `BaseModel`" | "There are 42 models" |
| **Locational** over listings | "Actions live under `app/{Module}/*/Actions/`" | "Auth module has 10 actions" |
| **Factual** over status | Describe what exists | "Currently in ALPHA stage" |
| **No phase labels** | Describe what works | "Not yet implemented" |
| **No promises** | Document current design only | "Future versions will support X" |
| **No version numbers** in derivative docs | "See composer.json" | "Laravel 13, Livewire 4" in AGENTS.md |

### What to Avoid (Brittle Content)

| Brittle (gets stale) | Resilient alternative |
|---|---|
| "There are 40 models" | "Models extend `BaseModel`" |
| "Auth module has 10 actions" | "Actions live under `app/Auth/*/Actions/`" |
| "Stage: ALPHA / BETA / PRODUCTION" | Describe current state factually |
| "Planned for Q3 2026" | File a GitHub issue, do not document |
| "Version 0.1.0" (in derivative docs) | Keep version only in composer.json |
| Enumeration of features that may grow | Describe the concept without exhaustive list |

### Structure

Every markdown document should follow this section order:

```
+-------------------------------------------+
| YAML frontmatter (optional)               |
+-------------------------------------------+
| # Title — Short Description (subtitle)     |
+-------------------------------------------+
| > **Last updated:** YYYY-MM-DD             |
| > **Changes:** one-line description        |
+-------------------------------------------+
| ## Description                           |
| ...                                       |
+-------------------------------------------+
| Body — topic headings directly            |
| (no "Body/Content" wrapper heading)       |
| ...                                       |
+-------------------------------------------+
| ## Quick References (optional,            |
|     recommended for pattern docs)         |
| ...                                       |
+-------------------------------------------+
```

**Rules:**
- Every doc starts with a `# Title — Short Description` H1 heading with the short description inline on the same line (use `—` em dash or `-` dash after the title)
- Metadata blockquote (`> **Last updated:**`, `> **Changes:**`) goes immediately after the H1 line (separated by a blank line)
- `## Description` is the first body heading — never write "Body/Content" as a heading
- `---` horizontal rules between major sections
- Keep paragraphs short (3-5 sentences max). Use tables for reference data
- Reference other docs by relative path: `[Action Triad](architecture/action-pattern.md)`
- Anchor links for section references: `[see §Data Flow](architecture.md#data-flow)`

### Language

- **Full English** — all documentation, code comments, commit messages, and communication
- No Indonesian in docs. Translation files (`lang/id/`) are the only exception
- Use `__('key')` syntax only in code examples, never in prose documentation

### Two-Tier Module Docs

| Doc | Audience | Content |
|-----|----------|---------|
| `docs/modules/{module}.md` | Architects, devs, stakeholders | Purpose, design principles, boundary |
| `docs/modules/{module}-reference.md` | Developers, reviewers | File paths, classes, schemas, deps |

- Every module must have exactly one conceptual doc and one reference doc
- Conceptual doc must NOT contain implementation details (file paths, schema definitions)
- Reference doc must NOT contain design rationale

### Doc References in AGENTS.md

- AGENTS.md is the thin agentic instruction layer — never duplicate content from `docs/`
- Reference authoritative docs with relative paths like `docs/architecture.md (§Data Flow)`
- The Doc Navigation table and Quick Links are the canonical entry points — add new entries there instead of duplicating descriptions

## Security

- `{{ $var }}` for ALL user-supplied content in Blade (auto-escaped)
- `{!! $var !!}` ONLY for trusted sanitized content, with inline justification comment
- No inline `<script>` — use Alpine.js `x-data` / `@click` / `x-on`
- CSP enforced via `SecurityHeaders` middleware — add external resources to CSP directives
- File uploads go through Spatie MediaLibrary, never `Storage::put()`
- Rate limiting on auth endpoints and setup wizard token validation
