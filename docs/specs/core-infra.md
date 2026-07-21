# Core Infrastructure — Foundation, Dependencies & Contracts

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec covering dependencies, framework,
> database, system requirements, base classes, contracts, middleware, cache, and session

## Description

Complete specification of Internara's core infrastructure layer (Layer 1). Defines minimum system
requirements, third-party dependencies, framework configuration, database support, all base classes
and contracts, middleware stack, cache strategy, and session management. This is the foundation
every module builds upon.

---

## 1. Problem Statements

### PS-1 — Dependency Management

The system relies on 12 production packages and 10 dev packages with specific version constraints.
A broken dependency or version mismatch can cascade across all 22 modules. Package selection must
balance feature needs with maintenance burden and security surface.

### PS-2 — Minimum System Requirements

Schools operate on diverse hosting environments — from shared hosting with PHP 8.4 to VPS with
Redis. The system must clearly define what is required vs recommended, and fail gracefully when
requirements aren't met rather than producing cryptic errors.

### PS-3 — Database Portability

Different schools have different database capabilities. SQLite for zero-config development, MySQL
for shared hosting, PostgreSQL for larger deployments. The system must work across all three without
module-specific SQL, using only portable Eloquent queries and migrations.

### PS-4 — Base Class Consistency

22 modules with 150+ features share a common vocabulary: how to write Actions, Models, Entities,
DTOs, Livewire components, and Policies. Without enforced base classes, each module would reinvent
patterns, creating maintenance nightmares and subtle bugs.

### PS-5 — Middleware Stack Integrity

Security headers, request logging, setup gating, locale resolution, and role checking happen at the
middleware layer. A missing or misordered middleware can silently break security (no CSP headers)
or functionality (wrong locale, setup bypass).

### PS-6 — Cache Coherence

Caching improves performance but introduces staleness risk. Without a centralized key registry and
invalidation strategy, cached data can silently diverge from the database, causing hard-to-debug
inconsistencies across modules.

### PS-7 — Session Security

Sessions hold authentication state, CSRF tokens, wizard progress, and locale preferences. A
compromised session means a compromised account. Session configuration must enforce encryption,
HTTP-only cookies, SameSite protection, and proper lifetime limits.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Define minimum PHP version (8.4) and required extensions with clear error messaging |
| G2  | Enforce Action Triad pattern (Command/Read/Process) via abstract base classes |
| G3  | Support SQLite (default), MySQL 8+, MariaDB 10.6+, PostgreSQL 15+ without module-specific SQL |
| G4  | Provide 5 Livewire base classes covering all UI patterns (table CRUD, modal CRUD, list, form, wizard) |
| G5  | Centralize cache keys in `config/cache-keys.php` with event-driven invalidation |
| G6  | Enforce session security (encrypted, HTTP-only, SameSite, 120min lifetime) |
| G7  | Apply security headers (CSP, HSTS, X-Frame-Options) via middleware on every response |
| G8  | Maintain a dual exception hierarchy (AppException + ModuleException) for precise error handling |
| G9  | Provide UUID primary keys (v7, time-ordered) on all models via `HasUuids` |
| G10 | Support three deployment tiers: shared hosting (file cache, database session), VPS (Redis), HA (Redis cluster) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-tenant database partitioning (single-tenant design) |
| NG2  | Real-time WebSocket infrastructure (out of scope per product definition) |
| NG3  | GraphQL or REST API layer (Livewire-only frontend) |
| NG4  | Custom ORM or query builder (Eloquent is the persistence layer) |
| NG5  | Message queue abstraction beyond Laravel's built-in queue drivers |
| NG6  | Container orchestration (Docker Compose only, no Kubernetes) |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Creates a New Module

**Actor:** Developer
**Preconditions:** Project cloned, `composer install` completed, PHP 8.4+ available
**Flow:**
1. Developer creates module directory under `app/{Module}/`
2. Creates Model extending `BaseModel` (UUID PKs automatic)
3. Creates Entity extending `BaseEntity` (final readonly, `fromModel()`)
4. Creates Command Action extending `BaseCommandAction` (transaction + logging automatic)
5. Creates Livewire component extending `BaseRecordManager` (search, filter, sort, pagination automatic)
6. Creates Policy extending `BasePolicy` (role + ownership checks available)
**Postconditions:** Module follows all architectural conventions, base classes enforce invariants

### UC-2 — System Handles Business Rule Violation

**Actor:** Student
**Preconditions:** Student is logged in, attempting invalid operation
**Flow:**
1. Student submits form via Livewire
2. Livewire calls Command Action
3. Action delegates to Entity for business rule check
4. Entity returns `false` (rule violated)
5. Action throws `RejectedException` with message
6. Livewire catches `RejectedException`, flashes error message
7. Student sees user-friendly error
**Postconditions:** No stack trace exposed, SmartLogger records the attempt, user sees `__()` localized message

### UC-3 — System Redirects Uninstalled Instance to Setup

**Actor:** Any visitor
**Preconditions:** System not yet installed (no `setup.is_installed` setting)
**Flow:**
1. Visitor navigates to any URL
2. `RequireSetupAccessMiddleware` checks `is_installed` (cached)
3. If not installed → redirect to `/setup`
4. If installed → pass through
**Postconditions:** Uninstalled system is unusable until setup completes

### UC-4 — Cache Invalidates on Settings Change

**Actor:** Super Admin
**Preconditions:** System installed, admin changing a setting
**Flow:**
1. Admin updates setting via Settings UI
2. SettingObserver fires on Eloquent model event
3. Observer calls `Cache::forget()` for affected key
4. Next request reads fresh value from database
**Postconditions:** No stale cached values, no full cache flush needed

---

## 4. Functional Requirements

### 4.1 Minimum System Requirements

| ID    | Requirement |
| ----- | ----------- |
| FR-SY1 | PHP >= 8.4.0 is required |
| FR-SY2 | Required extensions: bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip |
| FR-SY3 | Recommended extensions: redis, pcntl, posix |
| FR-SY4 | Composer >= 2.0 is required for dependency management |
| FR-SY5 | Node.js + npm required for frontend build (Vite, Tailwind CSS) |
| FR-SY6 | `storage/` and `bootstrap/cache/` directories must be writable |
| FR-SY7 | `APP_KEY` must be set (32-character base64 string) |

### 4.2 Dependencies

| ID    | Requirement |
| ----- | ----------- |
| FR-D1 | `laravel/framework` ^13.0 — core framework |
| FR-D2 | `livewire/livewire` ^4.0 — reactive UI components |
| FR-D3 | `spatie/laravel-permission` ^8.0 — RBAC (roles + permissions) |
| FR-D4 | `spatie/laravel-activitylog` ^5.0 — audit trail logging |
| FR-D5 | `spatie/laravel-medialibrary` ^11.17 — file upload + image conversions |
| FR-D6 | `spatie/laravel-model-status` ^1.18 — model status tracking |
| FR-D7 | `laravel-lang/lang` ^15.26 — bilingual translations (en/id) |
| FR-D8 | `barryvdh/laravel-dompdf` ^3.1 — PDF generation |
| FR-D9 | `laravel/pulse` * — performance monitoring dashboard |
| FR-D10 | `php-flasher/flasher-laravel` ^2.4 — flash message UI |
| FR-D11 | `robsontenorio/mary` ^2.4 — UI component library (maryUI) |
| FR-D12 | `laravel/tinker` ^3.0 — REPL for debugging |

### 4.3 Database

| ID    | Requirement |
| ----- | ----------- |
| FR-DB1 | SQLite is the default and zero-config database (WAL mode, busy_timeout=5000) |
| FR-DB2 | MySQL 8.0+ supported for shared hosting deployments |
| FR-DB3 | MariaDB 10.6+ supported |
| FR-DB4 | PostgreSQL 15+ supported for larger deployments |
| FR-DB5 | All models use UUID v7 primary keys (time-ordered, via `HasUuids` trait) |
| FR-DB6 | All foreign keys define `onDelete` and `onUpdate` behavior (D6 invariant) |
| FR-DB7 | Migrations organized in 6 sequential layers: Foundation → Auth → Config → Internship Core → Grouping → Evaluation |
| FR-DB8 | 55 tables total: 37 domain + 18 system/package |
| FR-DB9 | Redis used for cache (database 1), session, and queue — separate DB numbers per service |

### 4.4 Core Base Classes — Actions

| ID    | Requirement |
| ----- | ----------- |
| FR-A1 | `BaseAction` — abstract root: transaction wrapper, event dispatch, logging, error handling |
| FR-A2 | `BaseCommandAction` — all mutations: `respond()`, `respondDeleted()`, `respondError()`, `validate()`, `authorize()`, `flash()` |
| FR-A3 | `BaseReadAction` — queries only: `remember()`, `cacheKey()`, `mask()` (PII), `paginate()`, `format()` |
| FR-A4 | `BaseProcessAction` — orchestration: `step()` with success/failure tracking, `trackProgress()`, `notify()`, `logProgress()` |
| FR-A5 | All Actions have exactly one public method: `execute()` |
| FR-A6 | Command/Process Actions wrap DB operations in `$this->transaction()` |
| FR-A7 | Command/Process Actions call `$this->log()` after successful mutation |

### 4.5 Core Base Classes — Data Layer

| ID    | Requirement |
| ----- | ----------- |
| FR-M1 | `BaseModel` — abstract, extends Eloquent, uses `HasUuids` + `HasCommonScopes` traits |
| FR-M2 | `BaseAuthenticatable` — abstract, bridges Laravel Authenticatable with UUID support |
| FR-M3 | `BaseEntity` — abstract, `final readonly`, implements `JsonSerializable`, requires `fromModel()` |
| FR-M4 | `BaseData` — abstract, `final readonly`, implements `JsonSerializable`, `fromArray()` with camelCase/snake_case fallback |
| FR-M5 | `ActionResponse` — final readonly DTO: `ok()`, `created()`, `updated()`, `deleted()`, `error()`, `withRedirect()` |
| FR-M6 | `HasCommonScopes` — `active()`, `inactive()`, `recent()`, `createdAfter()`, `createdBefore()`, `ordered()` |

### 4.6 Core Base Classes — UI Layer

| ID    | Requirement |
| ----- | ----------- |
| FR-L1 | `BaseRecordManager` — table CRUD: search, filter, sort, pagination, bulk actions, selection |
| FR-L2 | `BaseRecordEntry` — modal CRUD: create/edit modal with form, `handleError()` for RejectedException |
| FR-L3 | `BaseRecordList` — read-only list: search, pagination (no create/edit) |
| FR-L4 | `BaseFormView` — full-page form: dirty tracking, `handleSave()` |
| FR-L5 | `BaseWizard` — multi-step wizard: step navigation, validation, completion tracking |
| FR-L6 | `BaseController` — JSON response helpers: `jsonSuccess()`, `jsonCreated()`, `jsonError()`, `jsonPaginated()`, etc. |
| FR-L7 | `BaseFormRequest` — throws `ValidationFailedException` on failed validation |

### 4.7 Contracts

| ID    | Requirement |
| ----- | ----------- |
| FR-C1 | `LabelEnum` — interface requiring `label(): string` on all enums |
| FR-C2 | `StatusEnum` — extends `LabelEnum`, adds `isTerminal()`, `canTransitionTo()`, `validTransitions()` |
| FR-C3 | `ColorableEnum` — interface requiring `color(): string` for badge styling |
| FR-C4 | `SendsNotifications` — interface for notification dispatch: `execute(userId, type, title, ...)` |
| FR-C5 | `SettingsStore` — interface for settings retrieval: `get(key, default)` |

### 4.8 Exception Hierarchy

| ID    | Requirement |
| ----- | ----------- |
| FR-E1 | `AppException` (abstract) — framework-level errors, `statusCode()` abstract, `HasExceptionContext` trait |
| FR-E2 | `ModuleException` (abstract) — business-level errors, `statusCode()` abstract, `HasExceptionContext` trait |
| FR-E3 | `RejectedException` extends `ModuleException` — HTTP 400, business rule violations (C8 invariant) |
| FR-E4 | `ValidationFailedException` extends `ActionException` — HTTP 422, form validation failures |
| FR-E5 | `UnauthorizedException` extends `PresentationException` — HTTP 403, authorization failures |
| FR-E6 | `InfrastructureException` extends `AppException` — HTTP 500, not user-facing |
| FR-E7 | `HasExceptionContext` trait — `hint`, `context`, `toCliOutput()`, `isUserFacing()`, `shouldReport()` |

### 4.9 Middleware

| ID    | Requirement |
| ----- | ----------- |
| FR-MW1 | `SecurityHeaders` — applies CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS |
| FR-MW2 | `LogContext` — adds request_id, method, URL, IP, user_id, user_role, duration_ms to log context |
| FR-MW3 | `RequireSetupAccessMiddleware` — globally applied, redirects to `/setup` when not installed |
| FR-MW4 | `SetLocaleMiddleware` — resolves locale from session, sets `app()->setLocale()` |
| FR-MW5 | `ProtectSetupRouteMiddleware` — validates setup token, rate limits, session versioning |
| FR-MW6 | `CheckRoleMiddleware` — role-based route protection (aliased as `role`) |
| FR-MW7 | `AuthThrottleMiddleware` — login/rate-limit throttling (aliased as `auth.throttle`) |
| FR-MW8 | Middleware execution order: SecurityHeaders → LogContext → RequireSetupAccess → SetLocale → route-specific |

### 4.10 Cache Infrastructure

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

### 4.11 Session Infrastructure

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

### 4.12 Policies

| ID    | Requirement |
| ----- | ----------- |
| FR-P1 | `BasePolicy` — abstract, auto-allows `super_admin` via `before()` method |
| FR-P2 | `AuthorizesRoles` trait — `isAdmin()`, `canManageAnyRole()`, `hasAnyOfRoles()` |
| FR-P3 | `AuthorizesOwnership` trait — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()` |

### 4.13 Support Utilities

| ID    | Requirement |
| ----- | ----------- |
| FR-SUP1 | `SmartLogger` — dual-channel (system + activity) fluent logger with PII masking |
| FR-SUP2 | `PiiMasker` — masks 29+ sensitive keys, partial masks for email/phone/name |
| FR-SUP3 | `PasswordRules` — default password validation: 8+ chars, mixed case, numbers |
| FR-SUP4 | `AppInfo` — reads composer.json metadata with 24h cache |
| FR-SUP5 | `Environment` — environment detection helpers (isProduction, isTesting, etc.) |
| FR-SUP6 | `CsvHandler` — export, import, template download with header validation |
| FR-SUP7 | `Color` — hex/RGB conversion, contrast calculation, DaisyUI shade generation |
| FR-SUP8 | `ModuleDiscoverService` — runtime discovery of Livewire components, policies, Blade namespaces |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | `declare(strict_types=1)` in every PHP file except migrations and config (D1 invariant) |
| NFR-S2 | No debug calls in committed code: dd, dump, ray, var_dump, print_r, die (D2 invariant) |
| NFR-S3 | CSP header must block inline scripts except explicitly whitelisted sources |
| NFR-S4 | Session cookie must be HTTP-only, SameSite=lax, secure in production |
| NFR-S5 | APP_KEY must be 32-byte base64 string; rotation supported via `APP_PREVIOUS_KEYS` |
| NFR-S6 | Redis connections support retry with backoff (max_retries=3, decorrelated jitter) |

### 5.2 Performance

| ID     | Requirement |
| ------ | ----------- |
| NFR-P1 | SQLite WAL mode with 5000ms busy timeout for concurrent reads |
| NFR-P2 | OpCache enabled in production: 256MB memory, 20000 max files, validate_timestamps=0 |
| NFR-P3 | Redis connection pool: persistent connections optional (`REDIS_PERSISTENT`) |
| NFR-P4 | Cache warming reduces first-request latency after deployment |
| NFR-P5 | Application cache (config/route/view/event) reduces bootstrap time by ~60% |

### 5.3 Reliability

| ID     | Requirement |
| ------ | ----------- |
| NFR-R1 | Transaction wrapper retries up to 3 attempts on deadlock (BaseAction) |
| NFR-R2 | Graceful degradation: cache miss returns fresh data, never cached error |
| NFR-R3 | Redis backoff: decorrelated jitter with 100ms base, 1000ms cap |
| NFR-R4 | SQLite foreign keys enforced (`DB_FOREIGN_KEYS=true`) |

### 5.4 Maintainability

| ID     | Requirement |
| ------ | ----------- |
| NFR-M1 | All base classes are abstract — cannot be instantiated directly |
| NFR-M2 | Entities are `final readonly` — no inheritance, no mutation |
| NFR-M3 | DTOs carry only scalars/Enums/Carbon — never Models or Actions (C6 invariant) |
| NFR-M4 | Cache key registry in single file (`config/cache-keys.php`) — discoverable, auditable |
| NFR-M5 | Module discovery at runtime — no manual registration of Livewire/Policies/Views |
| NFR-L1 | All user-facing error messages in base classes must use `__()` translation helper |
| NFR-A1 | Error pages rendered by exception handlers must meet WCAG 2.1 Level AA |

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
  "php-flasher/flasher-laravel": "^2.4",
  "robsontenorio/mary": "^2.4",
  "laravel/tinker": "^3.0"
}
```

### 6.2 Base Class Signatures

```php
// Action Triad
abstract class BaseAction {
    protected function transaction(callable $callback, int $attempts = 3): mixed;
    protected function dispatchEvent(BaseEvent $event): void;
    protected function fail(string $message, array $context = []): never;
    protected function log(string $action, ?Model $subject = null, array $payload = []): void;
}

abstract class BaseCommandAction extends BaseAction {
    protected function respond(mixed $data, ?string $message = null, bool $created = false): ActionResponse;
    protected function respondDeleted(?string $message = null): ActionResponse;
    protected function respondError(string $message, array $errors = []): ActionResponse;
    protected function validate(array $data, array $rules): array;
    protected function authorize(string $ability, mixed $arguments = []): void;
    protected function flash(string $message, string $type = 'success'): void;
}

abstract class BaseReadAction {
    protected function remember(string $key, callable $callback, int $ttl = 300): mixed;
    protected function cacheKey(string $purpose, string ...$qualifiers): string;
    protected function mask(array $data, array $fields = []): array;
    protected function paginate(Builder $query, int $perPage = 15): LengthAwarePaginator;
}

abstract class BaseProcessAction extends BaseAction {
    protected function step(string $name, callable $callback): mixed;
    protected function trackProgress(float $percent, ?string $message = null): void;
    protected function notify(mixed $notifiables, Notification $notification): void;
}

// Data Layer
abstract class BaseModel extends Eloquent\Model {
    // Traits: HasUuids, HasCommonScopes
    // UUID v7 primary keys, $incrementing = false, $keyType = 'string'
}

abstract class BaseEntity implements JsonSerializable {
    abstract public static fromModel(Model $model): static;
    public static fromArray(array $data): static;
    public function toArray(): array;
    public function with(string $property, mixed $value): static;
}

abstract class BaseData implements JsonSerializable {
    public static fromArray(array $data): static;
    public static from(mixed $source): static;
    public function toArray(): array;
    public function only(string ...$keys): array;
    public function merge(array $overrides): static;
}

final readonly class ActionResponse implements JsonSerializable {
    public bool $success;
    public mixed $data;
    public ?string $message;
    public ?string $redirect;
    public array $errors;
    public static ok(mixed $data = null, ?string $message = null): self;
    public static created(mixed $data = null, ?string $message = null): self;
    public static error(string $message, array $errors = []): self;
    public function withRedirect(string $url): self;
}
```

### 6.3 Contracts

```php
interface LabelEnum {
    public function label(): string;
}

interface StatusEnum extends LabelEnum {
    public function isTerminal(): bool;
    public function canTransitionTo(self $target): bool;
    public function validTransitions(): array;
}

interface ColorableEnum {
    public function color(): string;
}

interface SendsNotifications {
    public function execute(string $userId, string $type, string $title, ?string $message = null, ?array $data = null, ?string $link = null): mixed;
}

interface SettingsStore {
    public function get(string $key, mixed $default = null): mixed;
}
```

### 6.4 Exception Hierarchy

```
RuntimeException
├── AppException (abstract)
│   ├── ActionException (abstract, 400)
│   │   └── ValidationFailedException (422)
│   ├── InfrastructureException (abstract, 500)
│   └── PresentationException (abstract, 400)
│       └── UnauthorizedException (403)
└── ModuleException (abstract)
    └── RejectedException (400)
```

### 6.5 Middleware Stack

```php
// bootstrap/app.php — Web middleware stack (execution order)
->withMiddleware(function (Middleware $middleware) {
    $middleware->append([
        SecurityHeaders::class,
        LogContext::class,
        RequireSetupAccessMiddleware::class,
        SetLocaleMiddleware::class,
    ]);
    $middleware->alias([
        'setup.protected' => ProtectSetupRouteMiddleware::class,
        'role' => CheckRoleMiddleware::class,
        'auth.throttle' => AuthThrottleMiddleware::class,
    ]);
})
```

### 6.6 Cache Key Registry

```php
// config/cache-keys.php
[
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
]
```

### 6.7 Session Configuration

```php
// config/session.php
'driver'       => env('SESSION_DRIVER', 'database'),  // database (default) | redis | file
'lifetime'     => 120,       // minutes
'encrypt'      => true,
'http_only'    => true,
'secure'       => env('APP_ENV') === 'production',
'same_site'    => 'lax',
'lottery'      => [2, 100],  // 2% GC chance per request
```

### 6.8 Database Configuration

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

### DD-2 — Dual Exception Hierarchy

**Decision:** Two separate exception trees: `AppException` (framework) and `ModuleException` (business).
**Rationale:** Allows precise catch-block targeting. Framework errors (infrastructure, presentation)
are caught differently from business rule violations. `RejectedException` (the most common business
exception) extends `ModuleException` and always returns HTTP 400.
**Trade-off:** Slightly more complex exception hierarchy, but prevents the "catch everything as
RuntimeException" anti-pattern.

### DD-3 — Centralized Cache Key Registry

**Decision:** All cache keys MUST be declared in `config/cache-keys.php`, never inline.
**Rationale:** Prevents key collisions across modules, makes cache dependencies discoverable,
enables systematic flushing. Without centralized keys, modules would independently invent naming
conventions leading to conflicts.
**Trade-off:** Extra step when adding new cache keys. Mitigated by the clear naming convention
(`{module}.{purpose}[.{qualifier}]`).

### DD-4 — File Cache as Default

**Decision:** Default cache driver is `file`, not Redis.
**Rationale:** Shared hosting deployments cannot install Redis. File cache works without external
services. For Tier 2+ deployments, switching to Redis is a one-line `.env` change.
**Trade-off:** File cache is slower than Redis and doesn't support atomic operations. Acceptable
for single-tenant workloads.

### DD-5 — Database Session as Default

**Decision:** Default session driver is `database`, not `file`.
**Rationale:** Database sessions survive process restarts (important for queue workers), support
multi-process deployments, and the sessions table is auto-created by migration. File sessions
can be lost on deploy.
**Trade-off:** Slightly higher DB load per request. Negligible for single-tenant with <1000
concurrent users.

### DD-6 — UUID v7 Primary Keys

**Decision:** All models use UUID v7 (time-ordered) primary keys via Laravel's `HasUuids` trait.
**Rationale:** Time-ordered UUIDs improve B-tree index performance. UUIDs eliminate sequential ID
exposure (no user can guess `/users/2` → `/users/3`). No migration coordination needed across
environments.
**Trade-off:** 16 bytes per PK vs 4 bytes for auto-increment. Storage overhead is negligible for
<100K rows.

### DD-7 — Middleware Ordering

**Decision:** Security headers first, locale last in the global stack.
**Rationale:** Security headers must be on every response regardless of downstream errors. Locale
resolution depends on session (which needs setup check to have run first). Setup check must
precede locale to redirect uninstalled instances before any business logic.
**Trade-off:** Fixed ordering prevents per-route customization. Route-specific middleware handles
those cases.

### DD-8 — Module Discovery at Runtime

**Decision:** Livewire components, policies, and Blade namespaces are discovered dynamically via
`ModuleDiscoverService`, not manually registered.
**Rationale:** With 22 modules, manual registration in service providers would be error-prone and
a maintenance burden. Runtime scanning adds negligible startup cost (~50ms) and automatically
picks up new modules.
**Trade-off:** Slightly slower boot time. Mitigated by caching discovery results in Redis/file cache.

---

## 8. Success Metrics

### 8.1 System Requirements

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| PHP version check | Always accurate | `php -v` parse result matches FR-SY1 |
| Extension check | 11 required + 3 recommended | `php -m` comparison against FR-SY2/FR-SY3 |
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

### 8.4 Cache

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Key registration | 100% of cache keys in registry | `grep -r "Cache::" app/` → all keys resolve to config |
| Stale data window | < 5 seconds for settings changes | Observer fires on every model event |
| Cache warm time | < 5 seconds | `time php artisan system:cache-warm` |

### 8.5 Session

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Encryption | Always enabled | `SESSION_ENCRYPT=true` in default config |
| Lifetime | 120 minutes | Default config value |
| Fixation prevention | Regenerated on auth change | `session()->regenerate()` in login/logout flow |

### 8.6 Security

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Security headers | Every response has CSP + X-Frame-Options | `curl -I` response check |
| Strict types | 100% of PHP files (except migrations/config) | `python3 scripts/scan_conventions.py` |
| No debug calls | Zero in committed code | `python3 scripts/scan_conventions.py` |

---

## Quick References

- `docs/architecture.md` — 4-layer architecture, Action Triad, dependency rules
- `docs/conventions.md` — Invariants C1-C8, D1-D6, naming, security, testing
- `docs/modules/core.md` — Core module overview
- `docs/modules/core-reference.md` — Core module technical reference
- `docs/architecture/action-pattern.md` — Action Triad contracts and patterns
- `docs/architecture/entity-pattern.md` — Entity contracts and bridge pattern
- `docs/architecture/model-pattern.md` — Model conventions
- `docs/architecture/data-pattern.md` — DTO and ActionResponse contracts
- `docs/architecture/exception-pattern.md` — Dual exception hierarchy
- `docs/architecture/cache-pattern.md` — Cache strategy and key registry
- `docs/infrastructure/database.md` — Schema design, engine comparison
- `docs/infrastructure/cache.md` — Cache driver strategy, invalidation
- `docs/infrastructure/session.md` — Session configuration and security
- `docs/infrastructure/deployment.md` — Three deployment paths
- `config/cache-keys.php` — Centralized cache key registry
- `config/cache.php` — Cache store definitions
- `config/session.php` — Session driver and cookie settings
- `config/database.php` — Database connections and Redis configuration
- `app/Core/` — All base classes, contracts, exceptions, services
- `bootstrap/app.php` — Middleware registration
- `.env.example` — Default configuration values
