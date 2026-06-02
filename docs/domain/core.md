# Core Domain — Context Boundaries & Rules

> Last updated: 2026-06-02

## Table of Contents

1. [Purpose &amp; Identity](#purpose--identity)
2. [Context Boundary](#context-boundary)
3. [Subsystems](#subsystems)
    - 3.1 Base Classes (Layer 4)
    - 3.2 Contracts (Layer 3)
    - 3.3 Exception Hierarchy
    - 3.4 Logging &amp; Observability
    - 3.5 Caching
    - 3.6 Middleware
    - 3.7 Console Commands
    - 3.8 Data Transfer Objects
    - 3.9 Enums
    - 3.10 Events
4. [Dependency Rules](#dependency-rules)
5. [Usage Patterns by Domain Layer](#usage-patterns-by-domain-layer)
6. [What Belongs in Core](#what-belongs-in-core)
7. [What Does NOT Belong in Core](#what-does-not-belong-in-core)
8. [Extension Guide](#extension-guide)
9. [Current Usage Statistics](#current-usage-statistics)
10. [Known Gaps &amp; Future Direction](#known-gaps--future-direction)

---

## Purpose & Identity

Core is the **root of the dependency graph**. Every other business domain depends on Core; Core
depends on nothing except Laravel, Spatie packages, and PHP 8.4. It provides the shared
infrastructure, contracts, and base classes that make the 24-domain architecture work without every
domain reinventing the same patterns.

Core has three responsibilities:

1. **Provide base classes** — `BaseModel`, `BaseAction`, `BaseEntity`, `BasePolicy`,
   `BaseRecordManager`, `BaseController`, `Data`, `FormRequest`. These
   establish a consistent structure across all 465+ files in the application.

2. **Define shared contracts** — `LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`.
   These are interfaces that any domain can implement, consumed through Laravel's service container.

3. **Provide cross-cutting infrastructure** — logging (`SmartLogger`), PII masking (`PiiMasker`),
   caching (`CacheKeys`), validation (`PasswordRules`), error handling (`HandlesActionErrors`),
   exception hierarchy (`AppException`/`DomainException`), middleware (`LogContext`,
   `SecurityHeaders`), console commands (`system:health`, `system:cleanup`, `system:cache-warm`,
   `domain:discover`), notification channels (`CustomDatabaseChannel`), and DTOs (`Data`,
   `AuditCheck`, `AuditReport`).

---

## Context Boundary

Core is the **platform layer**. It has zero knowledge of any business domain — it does not import,
reference, or couple to Auth, School, Internship, or any other domain. This is the single most
important invariant of the entire architecture.

```
┌─────────────────────────────────────────────────┐
│              24 Business Domains                 │
│  (Auth, School, Internship, Registration, ...)   │
│         All import from Core                     │
└──────────────────┬──────────────────────────────┘
                   │  depends on
                   ▼
┌─────────────────────────────────────────────────┐
│               Core Domain                        │
│  Base classes  │  Contracts  │  Infrastructure   │
│  Exceptions    │  Logging    │  Caching          │
│  Middleware    │  Commands   │  DTOs             │
└──────────────────┬──────────────────────────────┘
                   │  depends on
                   ▼
┌─────────────────────────────────────────────────┐
│   Laravel 13  │  Spatie Packages  │  PHP 8.4      │
└─────────────────────────────────────────────────┘
```

### Boundary Rules

| Direction         | Rule                                                                     | Rationale                                                    |
| ----------------- | ------------------------------------------------------------------------ | ------------------------------------------------------------ |
| Business → Core   | ✅ Allowed. Every domain imports Core                                    | Core provides foundational classes                           |
| Core → Business   | ❌ **Forbidden.** Core must never import anything from a business domain | Would create circular dependency and violate layer isolation |
| Core → Laravel    | ✅ Allowed                                                               | Core depends on the framework                                |
| Core → Spatie     | ✅ Allowed                                                               | Activitylog, Permission, etc. are infrastructure             |
| Core → PHP stdlib | ✅ Allowed                                                               | PHP built-in functions and classes                           |

---

## Subsystems

### 3.1 Base Classes (Layer 4)

Core provides 9 base classes that every domain layer must (or may) extend.

#### Model Layer — `BaseModel`

**File:** `app/Domain/Core/Models/BaseModel.php`

**Purpose:** Every Eloquent model (except User) extends `BaseModel` for UUID primary key consistency
across all 50+ models and 75+ tables.

**What it provides:**

- `HasUuids` trait — automatically generates UUIDs for new records
- `getIncrementing(): false` — disables auto-increment
- `getKeyType(): 'string'` — tells Laravel the PK is a string

**Exception:** The `User` model in `Auth` domain extends `Illuminate\Foundation\Auth\User` directly
(required by Laravel's authentication system). It must manually apply `HasUuids` and override
`getIncrementing()`/`getKeyType()`.

**Usage count:** 49 models across all domains.

#### Action Layer — `BaseAction`

**File:** `app/Domain/Core/Actions/BaseAction.php`

**Purpose:** Base class for Command Actions (mutations) and Process Actions (orchestration). Read
Actions should NOT extend `BaseAction` — they should be plain invocable classes.

**What it provides:**

- `transaction(callable): mixed` — wraps logic in `DB::transaction()`
- `log(string $action, ?Model $subject, ?array $payload): void` — dual-channel logger via
  `SmartLogger::info()` with auto-PII-masking and auto-detected module name
- `HandlesActionErrors` trait — `withErrorHandling(callable, string $context): mixed` for
  try-catch-log-rethrow

**Contracts for subclasses:**

| Aspect                | Command Action         | Process Action          |
| --------------------- | ---------------------- | ----------------------- |
| Transaction           | ✅ Required            | ✅ Required             |
| Logging               | ✅ Required            | ✅ Required             |
| Event dispatch        | ✅ Recommended         | ✅ Required             |
| Compose other Actions | ❌                     | ✅ Required             |
| Naming pattern        | `{Verb}{Entity}Action` | `{Verb}{Entity}Process` |

**Usage count:** 174 files extend `BaseAction` — the single most-used Core class.

#### Entity Layer — `BaseEntity`

**File:** `app/Domain/Core/Entities/BaseEntity.php`

**Purpose:** Base class for immutable domain business rule objects. Entities are `readonly` value
objects that encapsulate business logic without persistence concerns.

**What it provides:**

- `abstract public static function fromModel(Model $model): static` — bridge from Eloquent to entity
- `readonly` keyword — immutability enforced at the class level
- `final` keyword on concrete subclasses — no inheritance chains

**Framework dependency policy (revised 2026-06-01):** Entities MAY use framework classes (Eloquent,
Carbon) where practical. The original "zero framework dependencies" mandate was relaxed to
prioritize development speed over architectural purity. However, entities should still prefer
constructor injection over framework access.

**Usage count:** 28 entities across 13 domains.

#### Policy Layer — `BasePolicy`

**File:** `app/Domain/Core/Policies/BasePolicy.php`

**Purpose:** Base class for all 34+ authorization policies. Provides ready-made role and ownership
checks.

**What it provides:**

- `AuthorizesRoles` trait — `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`,
  `isAdminOrTeacher()`, `hasAnyOfRoles()`
- `AuthorizesOwnership` trait — `isOwner()`, `isOwnerOrAdmin()` , `isRelatedThrough()`

**Usage count:** 34 policies across all domains.

#### Livewire CRUD Layer — `BaseRecordManager`

**File:** `app/Domain/Core/Livewire/BaseRecordManager.php`

**Purpose:** Base class for list/table Livewire components with search, filter, sort, pagination,
and bulk actions.

**What it provides:**

- `WithPagination` — Livewire's built-in pagination
- `WithRecordSelection` — `$selectedIds`, `clearSelection()`, `selectAll()`, `selected_count`
- `WithSorting` — `$sortBy`, whitelist-protected `applySorting()`
- `$search` — triggers `resetPage()` on update
- `$perPage` — configurable page size
- `$filters` — associative filter array
- `resetFilters()` — clears all filters
- `rows(): LengthAwarePaginator` — applies search, filters, sorting, eager loading, then paginates
- `performBulkAction()` — iterates over `$selectedIds` with optional transaction
- `performMassAction()` — applies callback to the full filtered query
- Abstract methods: `headers()`, `query()`

**Usage count:** 25 Livewire components extend `BaseRecordManager`.

#### Controller Layer — `BaseController`

**File:** `app/Domain/Core/Http/Controllers/BaseController.php`

**Purpose:** Minimal abstract marker base for the rare HTTP controllers (most UI is Livewire).

**What it provides:** Nothing except the marker class. It is an empty abstract class that signals
"this is a controller."

**Usage count:** 5 controllers extend `BaseController`.

#### Form Request Layer — `FormRequest`

**File:** `app/Domain/Core/Http/Requests/FormRequest.php`

**Purpose:** Custom form request base that throws `ValidationFailedException` instead of Laravel's
default redirect on validation failure.

**What it provides:**

- Overrides `failedValidation()` to throw `ValidationFailedException` with error context
- Extends `Illuminate\Foundation\Http\FormRequest` — compatible with all Laravel validation features

**Usage count:** 11 form requests use this base.

#### DTO Base — `Data`

**File:** `app/Domain/Core/Data/Data.php`

**Purpose:** Abstract base for immutable readonly DTOs.

**What it provides:**

- `toArray(): array` — extracts all public properties recursively
- `fromArray(array $data): static` — named constructor that maps array keys to constructor
  parameters (supports both camelCase and snake_case)
- `from(mixed $source): static` — dispatcher that calls `fromArray()` for arrays

**Usage count:** 0 direct imports of `Data` itself; 2 subclasses (`AuditCheck`, `AuditReport`) used
for setup auditing.

---

### 3.2 Contracts (Layer 3)

Core defines 4 interfaces that any domain can implement and consume. These are the only
architectural abstraction boundary that crosses domains without coupling them.

#### `LabelEnum`

**File:** `app/Domain/Core/Contracts/LabelEnum.php`

**Purpose:** Every string-backed enum must implement this. Provides a `label(): string` method for
human-readable display values.

**Usage:** 31 enums implement `LabelEnum`.

```php
enum InternshipStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return __("internship.status.{$this->value}");
    }
}
```

#### `StatusEnum`

**File:** `app/Domain/Core/Contracts/StatusEnum.php`

**Purpose:** For enums that represent state machine statuses. Extends `LabelEnum` (so implementors
must provide both).

**Additional methods (expected from implementors):**

- `canTransitionTo(self $target): bool` — whether transition to another state is valid
- `validTransitions(): array` — list of valid target states
- `isTerminal(): bool` — whether this is a final (non-transitionable) state

**Usage:** 17 enums implement `StatusEnum`.

#### `ColorableEnum`

**File:** `app/Domain/Core/Contracts/ColorableEnum.php`

**Purpose:** For enums that provide CSS color values for UI badge rendering.

**Usage:** 1 enum implements `ColorableEnum`.

#### `SendsNotifications`

**File:** `app/Domain/Core/Contracts/SendsNotifications.php`

**Purpose:** Abstraction for notification dispatch. Bound to `SendNotificationAction` in
`DomainServiceProvider::register()`.

**Usage:** 2 imports (the binding in `DomainServiceProvider` and consumption in
`CustomDatabaseChannel`).

---

### 3.3 Exception Hierarchy

Core defines a two-root exception hierarchy with 11 exception classes and 1 shared trait.

**Framework tree (`AppException` → `RuntimeException`):**

```
AppException (abstract)
├── ActionException (abstract) — operation-level failures
│   ├── ConflictException — duplicate/conflicting state
│   └── ValidationFailedException — input validation failure
├── InfrastructureException (abstract) — external system failures
│   └── RateLimitException — rate limit exceeded
└── PresentationException (abstract) — HTTP-layer failures
    ├── NotFoundException — resource not found (404)
    └── UnauthorizedException — authorization failure (403)
```

**Domain tree (parallel, NOT child of AppException):**

```
DomainException (abstract, extends RuntimeException)
└── RejectedException — domain invariant violated
```

**Why two roots?** So that a controller can `catch (DomainException $e)` for user-facing domain
errors without accidentally catching infrastructure failures like `ValidationFailedException`. Each
tree communicates a fundamentally different kind of failure.

**Shared trait:** `HasExceptionContext` provides `withHint()`, `withContext()`, `toCliOutput()` to
both trees.

**Exception API:**

All exceptions inherit two behavioral methods via `HasExceptionContext`:

| Method | Return | Default | Overridden In | Purpose |
|---|---|---|---|---|
| `isUserFacing(): bool` | Whether message is safe to expose to users | `true` | `InfrastructureException` → `false` | Prevents internal error details in production UI |
| `shouldReport(): bool` | Whether exception is written to logs | `true` | (none) | Suppress logging for expected/handled failures |

These are checked in `bootstrap/app.php` by the `AppException` renderer: non-user-facing exceptions
show a generic "An unexpected error occurred." message instead of the actual exception text.

**Usage statistics:**

| Exception            | Business Domain Usage | Notes                                                                   |
| -------------------- | --------------------- | ----------------------------------------------------------------------- |
| `RejectedException`  | 48 imports            | The most-used exception — the standard "business rule violation" signal |
| `AppException`       | 1 import              | Rarely caught directly (usually caught by HTTP error handlers)          |
| `DomainException`    | 1 import              | Same pattern                                                            |
| Other exceptions (8) | 0 imports             | 4 registered in `bootstrap/app.php`; 4 completely unreferenced           |

**Observation:** `RejectedException` carries the entire exception usage weight in business domains.
Other exception classes are used primarily in HTTP error handling infrastructure. This is by design
— most domain code needs only a "rejected" signal. Of the 8 unused exceptions, 4 are referenced
only in `bootstrap/app.php` (`ValidationFailedException`, `RateLimitException`, `NotFoundException`,
`UnauthorizedException`), and 4 have zero references anywhere (`ActionException`,
`ConflictException`, `InfrastructureException`, `PresentationException`) — abstract hierarchy
placeholders preserved for architectural completeness.

---

### 3.4 Logging & Observability

Core provides a complete logging subsystem with three components.

#### SmartLogger

**File:** `app/Domain/Core/Support/SmartLogger.php`

**Purpose:** The ONLY logging entry point in the entire application. No direct `Log::` facade calls
are allowed in business logic.

**API:**

```php
SmartLogger::success('User registered')->for($user)->save();
SmartLogger::info('Profile updated')->for($user)->about($profile)->save();
SmartLogger::warning('Disk space low')->systemOnly()->save();
SmartLogger::error('Payment failed', ['txn' => 'abc'])
    ->activityOnly()
    ->save();
```

**Three routing modes:**

| Mode               | System Log (laravel.log) | Activity Log (activity_log DB) | When to Use                  |
| ------------------ | ------------------------ | ------------------------------ | ---------------------------- |
| `both()` (default) | ✅                       | ✅                             | Command Action mutations     |
| `systemOnly()`     | ✅                       | ❌                             | Technical operations, errors |
| `activityOnly()`   | ❌                       | ✅                             | Audit-only events            |

**Usage:** 40+ files across domains import and use `SmartLogger`. It is the backbone of all observability.

#### PiiMasker

**File:** `app/Domain/Core/Support/PiiMasker.php`

**Purpose:** Automatic PII redaction for log payloads. Invoked when `SmartLogger::withPiiMasking()`
is called (which is the default in `BaseAction::log()`).

**Key features:**

- Fully masked keys (value → `***`): password, token, secret, authorization, credit_card, ssn,
  national_id (matched by substring)
- Partially masked keys: email (jo**\*@example.com), phone (\*\*\*\***7890), name (J. Doe)
- IP masking: first 2 octets preserved (192.168.**_._**)
- User-Agent truncation: first 50 chars only
- Recursive array traversal

**Usage:** Only imported directly by `SmartLogger` (it is not used independently).

#### Integrity

**File:** `app/Domain/Core/Support/Integrity.php`

**Purpose:** Runtime verification of `composer.json` authorship. Ensures the `authors[0].name`
matches the canonical author (`Reas Vyn`). In `local`/`testing` environments, a mismatch logs a
warning. In production, a mismatch throws `RuntimeException`.

**Usage:** Called during application bootstrap by the Setup domain's environment audit. Not directly
imported by business domain code.

#### HandlesActionErrors

**File:** `app/Domain/Core/Support/HandlesActionErrors.php`

**Purpose:** Try-catch-log-rethrow pattern for Actions. Wraps a callback so that known exceptions
(`RuntimeException`, `AppException`, `DomainException`, `ValidationException`,
`AuthorizationException`, `ModelNotFoundException`, `NotFoundHttpException`) are re-thrown
directly without logging. Unknown `\Throwable` errors are logged to the system log via
`SmartLogger` and rethrown as `RuntimeException` with context preservation.

**Usage:** Used by `BaseAction` via trait. 1 direct import outside Core.

#### ActivityLog Model

**File:** `app/Domain/Core/Models/ActivityLog.php`

**Purpose:** Extends Spatie's `Activity` model with domain-specific query scopes for operational
audit queries.

**Scopes:** `forUser()`, `whereSubject()`, `ofAction()`, `inLog()`, `recent()`, `lastDays()`,
`forModule()`.

**Additional methods:** `getGroupedByDay()` — daily activity counts for dashboards.

**Usage:** 3 direct imports (typically for admin audit log views).

#### LogContext Middleware

**File:** `app/Domain/Core/Http/Middleware/LogContext.php`

**Purpose:** Injects request-level context into every log entry. Registered globally in
`bootstrap/app.php`.

**Context added:** `request_id` (UUID), method, URL, IP, user_id, user_role, duration_ms, status.

---

### 3.5 Caching

#### CacheKeys

**File:** `app/Domain/Core/Support/CacheKeys.php`

**Purpose:** Central registry for ALL cache keys used across the application. Every cache key must
be defined here as a typed string constant.

**Naming convention:** `{domain}.{purpose}[.{qualifier}]`

**TTL legend:** short (<5 min), medium (5 min–1h), long (1h–24h), static (until flush), forever.

**Current keys (17):**

| Constant                | Key                          | TTL     | Invalidated By                                    |
| ----------------------- | ---------------------------- | ------- | ------------------------------------------------- |
| `SETUP_INSTALLED`       | `setup.is_installed`         | forever | FinalizeSetupAction, GenerateSetupTokenAction     |
| `ADMIN_DASHBOARD_STATS` | `admin.dashboard.stats`      | medium  | User/Department/Internship CRUD actions            |
| `THEME_CSS_VARIABLES`   | `theme.css_variables`        | long    | Settings update (color change)                    |
| `NOTIFICATION_UNREAD`   | `notification.unread:`       | medium  | MarkAsRead/MarkAllAsRead/SendNotification actions |
| `CORE_INTEGRITY`        | `core.integrity_verified`    | forever | composer.json changes (manual flush)              |
| `CORE_APP_NAME`         | `core.app_name`              | forever | composer.json changes (manual flush)              |
| `APPINFO_METADATA`      | `appinfo.metadata`           | forever | composer.json changes                             |
| `DOMAIN_LIVEWIRE`       | `domain.discovered_livewire` | static  | Structural changes (add/remove Livewire components)|
| `DOMAIN_POLICIES`       | `domain.discovered_policies` | static  | Structural changes (add/remove policies)          |
| `DOMAIN_VIEWS`          | `domain.discovered_views`    | static  | Structural changes (add/remove view directories)  |
| `AUTH_LOGIN_FAILURES`   | `auth.login-failures:`       | medium  | Successful login (LoginAction::clearFailedAttempts)|
| `HEALTH_CHECK`          | `health_check`               | short   | Each health check run                             |
| `RECOVER_ADMIN_ATTEMPTS`| `recover_admin_attempts_`    | medium  | Successful recovery (RecoverSuperAdminAction)     |
| `SETTINGS_ALL`          | `settings.all`               | forever | Settings::set(), Settings::forget()               |
| `SETTINGS_GROUP`        | `settings.group.`            | forever | Settings::set(), Settings::forget()               |
| `SETTINGS_KEYS`         | `settings.keys`              | forever | Settings::set(), Settings::forget()               |
| `SETTINGS_KEY`          | `settings.`                  | forever | Settings::set(), Settings::forget()               |

**Usage:** 15 files across domains reference `CacheKeys`.

---

### 3.6 Middleware

Two middleware classes live in Core because they are global HTTP infrastructure that all domains
depend on.

#### LogContext

**Purpose:** Enriches every log entry with request context. Registered globally.

#### SecurityHeaders

**File:** `app/Domain/Core/Http/Middleware/SecurityHeaders.php`

**Purpose:** Applies security headers (CSP, X-Frame-Options, X-Content-Type-Options,
Referrer-Policy, Permissions-Policy) to every response.

**CSP handling:** Automatically injects Vite dev server URL when `public/hot` exists (development
mode).

---

### 3.7 Console Commands

Core owns 4 system-level Artisan commands that operate across all domains.

| Command             | Class                   | Purpose                                                                                  |
| ------------------- | ----------------------- | ---------------------------------------------------------------------------------------- |
| `system:health`     | `HealthCommand`         | 15-point system health check (PHP, DB, cache, storage, queue, disk space, etc.)          |
| `system:cleanup`    | `CleanupCommand`        | Prunes password resets, stale cache, failed jobs, old logs, activity log, orphaned media |
| `system:cache-warm` | `CacheWarmCommand`      | Pre-warms config, views, events, settings, and brand caches                              |
| `domain:discover`   | `DomainDiscoverCommand` | Rediscover and register domain Livewire components, policies, and Blade namespaces       |

All commands are registered directly in `DomainServiceProvider::boot()` via artisan command
auto-discovery.

---

### 3.8 Data Transfer Objects

Core provides 3 DTO classes.

| Class         | Extends | Purpose                                                                          |
| ------------- | ------- | -------------------------------------------------------------------------------- |
| `Data`        | —       | Abstract base for all DTOs with `toArray()`, `fromArray()`, `from()`             |
| `AuditCheck`  | `Data`  | Single audit check result (category, status, message key)                        |
| `AuditReport` | `Data`  | Aggregation of multiple `AuditCheck` results with `passed()` and `forCategory()` |

`AuditCheck` and `AuditReport` are used by the Setup domain's environment audit workflow.

---

### 3.9 Enums

Core defines 2 enums used during system setup and auditing.

| Enum            | Implements  | Cases                                                          | Purpose                                 |
| --------------- | ----------- | -------------------------------------------------------------- | --------------------------------------- |
| `AuditCategory` | `LabelEnum` | REQUIREMENTS, PERMISSIONS, DATABASE, TERMINAL, RECOMMENDATIONS | Categories for environment audit checks |
| `AuditStatus`   | `LabelEnum` | PASS, FAIL, WARN                                               | Results of individual audit checks      |

`AuditCategory::isCritical()` returns `true` for REQUIREMENTS, PERMISSIONS, and DATABASE — used by
the setup wizard to determine if setup can proceed. The remaining cases — `TERMINAL` (CLI
availability checks) and `RECOMMENDATIONS` (non-blocking optimizations) — return `false`, meaning
they do not block setup.

---

### 3.10 Events

Events are defined per domain. Core provides no base event class — each domain defines `final readonly`
event classes with the `Dispatchable` trait. This avoids coupling domain events to a shared hierarchy
and keeps event definitions self-contained.

**Convention:** Events live in `app/Domain/{Domain}/Events/` and use the naming `{Entity}{PastTenseAction}`
(e.g., `InternshipCreated`, `ReportApproved`).

**Registration:** Event-to-listener bindings are registered in `DomainServiceProvider::boot()`.

---

### Routes & Views

Core owns **no HTTP routes** and **no Blade views**. The `routes/web/core.php` file was removed
(was a placeholder after routes moved to respective business domains, e.g., password confirmation
→ `routes/web/auth.php`, dashboard → `routes/web/user.php`). Core is infrastructure-only; every
other domain defines its own routes and views.

---

## Dependency Rules

These rules are **absolute invariants**:

### Rule 1: Core Must Never Import Business Domains

```php
// ❌ FORBIDDEN — Core importing from a business domain
use App\Domain\School\Models\AcademicYear;

// ❌ FORBIDDEN — even indirectly through dynamic references
$class = 'App\\Domain\\School\\Models\\AcademicYear';
```

This rule exists because Core is the root of the dependency graph. If Core imports a business
domain, the dependency arrow points upward, breaking the layered architecture. Every class in
Laravel would have to load before Core can be tested, and domain changes could break infrastructure
code.

### Rule 2: All Business Domains May Import Core

```php
// ✅ Allowed — any domain importing Core
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Models\BaseModel;
use App\Domain\Core\Support\SmartLogger;
```

This is bidirectional for all 24 domains. Every domain depends on Core for base classes, contracts,
logging, exceptions, and infrastructure.

### Rule 3: Core Depends Only on Framework + Spatie + PHP

```php
// ✅ Allowed
use Illuminate\Database\Eloquent\Model; // Laravel
use Spatie\Activitylog\Models\Activity; // Spatie
use RuntimeException; // PHP
```

No other package dependencies should be introduced to Core without clear cross-domain need.

### Rule 4: Contracts Bind in DomainServiceProvider, Not in Core

The `DomainServiceProvider` (in `app/Providers/`) is responsible for binding Core contracts to
domain implementations:

```php
// DomainServiceProvider::register()
$this->app->bind(SendsNotifications::class, SendNotificationAction::class);
```

Core never references `SendNotificationAction` directly. The binding happens outside Core.

---

## Usage Patterns by Domain Layer

Here is how each architectural layer should interact with Core:

| Domain Layer       | Core Class to Use                               | How to Use                                               |
| ------------------ | ----------------------------------------------- | -------------------------------------------------------- |
| Models             | `BaseModel`                                     | `class MyModel extends BaseModel`                        |
| Models (User only) | `HasUuids` trait                                | Apply trait manually + override key methods              |
| Actions (Command)  | `BaseAction`                                    | `class MyAction extends BaseAction`                      |
| Actions (Read)     | (none)                                          | Plain class with constructor injection                   |
| Actions (Process)  | `BaseAction`                                    | `class MyProcess extends BaseAction`                     |
| Entities           | `BaseEntity`                                    | `final readonly class MyEntity extends BaseEntity`       |
| State Entities     | `BaseEntity`                                     | State-machine helpers defined per entity |
| Policies           | `BasePolicy`                                    | `class MyPolicy extends BasePolicy`                      |
| Livewire CRUD      | `BaseRecordManager`                             | `class MyManager extends BaseRecordManager`              |
| Livewire (simple)  | `Component` (Livewire's)                        | Plain Livewire component                                 |
| Controllers        | `BaseController`                                | `class MyController extends BaseController`              |
| Form Requests      | `FormRequest` (Core's)                          | `class MyRequest extends FormRequest`                    |
| Enums              | `LabelEnum`                                     | `enum MyEnum: string implements LabelEnum`               |
| Status Enums       | `LabelEnum + StatusEnum`                        | `enum MyStatus: string implements LabelEnum, StatusEnum` |
| Exceptions         | `RejectedException`                             | `throw new RejectedException('message')`                 |
| Logging            | `SmartLogger`                                   | `SmartLogger::info(...)->save();`                        |
| Caching            | `CacheKeys`                                     | `CacheKeys::MY_KEY`                                      |
| Notifications      | `SendsNotifications` or `CustomDatabaseChannel` | Implicit via notification `toCustomDatabase()`           |

---

## What Belongs in Core

These criteria determine whether new code should be added to Core:

1. **Used by 3+ domains** — If a utility, base class, contract, or infrastructure component is
   needed by 3 or more business domains, it belongs in Core. Examples: `BaseAction` (174 usages),
   `SmartLogger` (40 usages), `BaseModel` (49 usages).

2. **Cross-cutting infrastructure** — Global middleware, shared console commands, notification
   channels, exception hierarchy. These affect every request or every domain. Examples:
   `LogContext`, `SecurityHeaders`, `HealthCommand`, `AppException`.

3. **Architectural enforcement** — Base classes and contracts that establish structural consistency
   across domains. Examples: `BaseEntity` (ensures `final readonly`), `BaseModel` (ensures UUIDs),
   `BasePolicy` (ensures role checks).

4. **Purely technical with no business logic** — Things that solve technical problems without
   referencing any business concept. Examples: `PiiMasker` (string manipulation), `PasswordRules`
   (validation rules), `HandlesActionErrors` (error handling pattern).

---

## What Does NOT Belong in Core

These are things that may seem like infrastructure but should NOT be in Core:

1. **Single-domain utilities** — If only one domain uses it, put it in that domain's `Support/`
   directory. Example: CSV handling belongs in `Shared`, not Core.

2. **Business-specific base classes** — If a base class is specific to one domain's behavior
   pattern, define it in that domain. Example: A `BaseAttendanceAction` would belong in
   `Attendance`, not Core.

3. **Third-party package wrappers** — If a wrapper exists only to abstract a specific third-party
   package used by a single domain, it belongs in that domain. Example: A PDF rendering wrapper
   belongs in `Document`.

4. **Configuration files** — `config/*.php` files are not part of Core even if they configure Core
   features. They belong in `config/` at the application root.

5. **Database migrations** — Migrations live in `database/migrations/`. Even if they create tables
   for Core features (like activity_log indexes), they are not part of the Core domain.

6. **UI components** — Blade components, Alpine.js snippets, and Tailwind utilities do not belong in
   Core. They belong in `Shared` (for cross-domain UI) or in individual domains.

7. **Package discovery and service providers** — `DomainServiceProvider` is at `app/Providers/`, not
   inside Core. It orchestrates cross-domain registration.

8. **Business domain enums, entities, actions, models, policies** — Anything with business meaning
   (role types, statuses specific to a workflow, domain-specific validation) belongs in the
   respective domain, not in Core.

---

## Extension Guide

### Adding a New Base Class

1. Create the class in `app/Domain/Core/{Category}/`
2. Ensure it has zero references to any business domain
3. Add it to the reference table in `docs/domain/core-reference.md`
4. Add usage guidance in `docs/conventions.md` (Section 1 — Base Classes)
5. Audit usage after 3 months to verify adoption

### Adding a New Contract

1. Create the interface in `app/Domain/Core/Contracts/`
2. Define the minimum methods needed (prefer single-method interfaces)
3. Register bindings in `DomainServiceProvider::register()` (not in Core)
4. Document the contract in `docs/domain/core-reference.md`

### Adding a New Exception

1. Choose the correct hierarchy: `AppException` branch for framework/infrastructure,
   `DomainException` branch for business rules
2. Use the `HasExceptionContext` trait
3. Add the class in `app/Domain/Core/Exceptions/`
4. Register in the exception handler in `bootstrap/app.php` if it needs special HTTP rendering

### Adding a New Console Command

1. Create the command in `app/Domain/Core/Console/Commands/`
2. Register it in `DomainServiceProvider::boot()`
3. Prefix the command name with `system:` to distinguish from domain commands

### Adding a New Support Utility

1. Ensure it's used by 3+ domains (or clearly cross-cutting)
2. Create the class in `app/Domain/Core/Support/`
3. Ensure it has no business domain imports
4. Write unit tests in `tests/Unit/Core/`

---

## Current Usage Statistics

As of 2026-06-02, across all business domains (excluding `Core/` itself):

| Rank | Core Class              | Unique Files | Category      |
| ---- | ----------------------- | ------------ | ------------- |
| 1    | `BaseAction`            | 173          | Actions       |
| 2    | `BaseModel`             | 49           | Models        |
| 3    | `RejectedException`     | 116          | Exceptions    |
| 4    | `SmartLogger`           | 37           | Logging       |
| 5    | `BasePolicy`            | 35           | Authorization |
| 6    | `LabelEnum`             | 29           | Contracts     |
| 7    | `BaseEntity`            | 27           | Entities      |
| 8    | `BaseRecordManager`     | 25           | Livewire      |
| 9    | `CustomDatabaseChannel` | 20           | Notifications |
| 10   | `StatusEnum`            | 16           | Contracts     |
| 11   | `CacheKeys`             | 15           | Caching       |
| 12   | `FormRequest`           | 11           | HTTP          |
| 13   | `ActivityLog`           | 9            | Models        |
| 14   | `PasswordRules`         | 6            | Support       |
| 15   | `BaseController`        | 5            | HTTP          |
| 16   | `AuditStatus`           | 4            | Enums         |
| 17   | `AuditCategory`         | 4            | Enums         |
| 18   | `AuditReport`           | 3            | DTOs          |
| 19   | `SendsNotifications`    | 2            | Contracts     |
| 20   | `ColorableEnum`         | 1            | Contracts     |
| 21   | Others (6 classes)      | 1 each       | Various       |

**Notes:**
- Usage counts are unique files importing or referencing the class (not total references).
- `SmartLogger` has 37 unique importers in business domains + 6 in Core itself = 43 total.
- `RejectedException` (116 files) and `SmartLogger` (37 files) are the most-used infrastructure
  classes — they carry the entire logging and domain-error signaling load.
- `PiiMasker` is not directly imported by any business domain — it's used only through
  `SmartLogger::withPiiMasking()`.

**Zero-adoption exception classes:**

- 8 exception classes have 0 business domain imports — 4 registered in `bootstrap/app.php`
  (`ValidationFailedException`, `RateLimitException`, `NotFoundException`, `UnauthorizedException`),
  4 completely unreferenced (`ActionException`, `ConflictException`, `InfrastructureException`,
  `PresentationException`). These are abstract hierarchy placeholders preserved for architectural
  completeness and ready for adoption when needed.

---

## Known Gaps & Future Direction

### Gap 1: Exception Classes Underutilized

**Issue:** Of 11 exception classes, only `RejectedException` is widely used (48 imports). Six
classes have zero business domain usage and are only referenced in HTTP error rendering.

**Impact:** None. The hierarchy is complete and ready to use. Low usage reflects the fact that most
domain code signals failure via `RejectedException` rather than framework-specific exceptions.

**Recommendation:** No action needed. The hierarchy is correct. Encourage `ConflictException` and
`ValidationFailedException` adoption when appropriate.

### Gap 2: CacheKeys Needs Expansion

**Issue:** Only 14 cache keys are registered. As domains add more caching, `CacheKeys` should grow
to include every cached value.

**Impact:** Low. New keys can be added as needed.

**Recommendation:** Enforce in code review that every new `Cache::remember()` or `Cache::put()` call
uses a `CacheKeys` constant.
