# Core Foundation — Base Classes, Contracts, Exceptions, Cache & Session

> **Last updated:** 2026-07-23 **Changes:** refactor — cross-ref exception hierarchy, SmartLogger,
> and middleware to dedicated specs (logging-and-error-handling, middleware-pipeline,
> security-headers)

## Description

Specification of Internara's core foundation — the architectural base every module builds
upon. Defines the Action Triad base classes, Entity/DTO/Model contracts, Livewire base classes,
exception hierarchy, middleware stack, cache infrastructure, session management, policies, and
support utilities.

---

## 1. Problem Statements

### PS-1 — Base Class Consistency

18 modules with 150+ features share a common vocabulary: how to write Actions, Models, Entities,
DTOs, Livewire components, and Policies. Without enforced base classes, each module would reinvent
patterns, creating maintenance nightmares and subtle bugs.

### PS-2 — Middleware Stack Integrity

Security headers, request logging, setup gating, locale resolution, and role checking happen at the
middleware layer. A missing or misordered middleware can silently break security (no CSP headers)
or functionality (wrong locale, setup bypass).

### PS-3 — Cache Coherence

Caching improves performance but introduces staleness risk. Without a centralized key registry and
invalidation strategy, cached data can silently diverge from the database, causing hard-to-debug
inconsistencies across modules.

### PS-4 — Session Security

Sessions hold authentication state, CSRF tokens, wizard progress, and locale preferences. A
compromised session means a compromised account. Session configuration must enforce encryption,
HTTP-only cookies, SameSite protection, and proper lifetime limits.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Enforce Action Triad pattern (Command/Read/Process) via abstract base classes |
| G2  | Provide 5 Livewire base classes covering all UI patterns (table CRUD, modal CRUD, list, form, wizard) |
| G3  | Centralize cache keys in `config/cache-keys.php` with event-driven invalidation |
| G4  | Enforce session security (encrypted, HTTP-only, SameSite, 120min lifetime) |
| G5  | Apply security headers (CSP, HSTS, X-Frame-Options) via middleware on every response |
| G6  | Maintain a dual exception hierarchy (AppException + ModuleException) for precise error handling |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time WebSocket infrastructure (out of scope per product definition) |
| NG2  | GraphQL or REST API layer (Livewire-only frontend) |
| NG3  | Message queue abstraction beyond Laravel's built-in queue drivers |

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

### 4.1 Core Base Classes — Actions

| ID    | Requirement |
| ----- | ----------- |
| FR-A1 | `BaseAction` — abstract root: transaction wrapper, event dispatch, logging, error handling |
| FR-A2 | `BaseCommandAction` — all mutations: `respond()`, `respondDeleted()`, `respondError()`, `validate()`, `authorize()`, `flash()` |
| FR-A3 | `BaseReadAction` — queries only: `remember()`, `cacheKey()`, `mask()` (PII), `paginate()`, `format()` |
| FR-A4 | `BaseProcessAction` — orchestration: `step()` with success/failure tracking, `trackProgress()`, `notify()`, `logProgress()` |
| FR-A5 | All Actions have exactly one public method: `execute()` |
| FR-A6 | Command/Process Actions wrap DB operations in `$this->transaction()` |
| FR-A7 | Command/Process Actions call `$this->log()` after successful mutation |

### 4.2 Core Base Classes — Data Layer

| ID    | Requirement |
| ----- | ----------- |
| FR-M1 | `BaseModel` — abstract, extends Eloquent, uses `HasUuids` + `HasCommonScopes` traits |
| FR-M2 | `BaseAuthenticatable` — abstract, bridges Laravel Authenticatable with UUID support |
| FR-M3 | `BaseEntity` — abstract, `final readonly`, implements `JsonSerializable`, requires `fromModel()` |
| FR-M4 | `BaseData` — abstract, `final readonly`, implements `JsonSerializable`, `fromArray()` with camelCase/snake_case fallback |
| FR-M5 | `ActionResponse` — final readonly DTO: `ok()`, `created()`, `updated()`, `deleted()`, `error()`, `withRedirect()` |
| FR-M6 | `HasCommonScopes` — `active()`, `inactive()`, `recent()`, `createdAfter()`, `createdBefore()`, `ordered()` |

### 4.3 Core Base Classes — UI Layer

| ID    | Requirement |
| ----- | ----------- |
| FR-L1 | `BaseRecordManager` — table CRUD: search, filter, sort, pagination, bulk actions, selection |
| FR-L2 | `BaseRecordEntry` — modal CRUD: create/edit modal with form, `handleError()` for RejectedException |
| FR-L3 | `BaseRecordList` — read-only list: search, pagination (no create/edit) |
| FR-L4 | `BaseFormView` — full-page form: dirty tracking, `handleSave()` |
| FR-L5 | `BaseWizard` — multi-step wizard: `steps()` (abstract, returns key array), `nextStep()` (validates + advances), `prevStep()`, `goToStep()` (with access check), `isStepAccessible()` (all prior steps completed), `progressPercent()`, `currentStepKey()`, `handleStepError()` (catches `RejectedException`), state persistence hooks |
| FR-L6 | `BaseController` — JSON response helpers: `jsonSuccess()`, `jsonCreated()`, `jsonError()`, `jsonPaginated()`, etc. |
| FR-L7 | `BaseFormRequest` — throws `ValidationFailedException` on failed validation |

### 4.4 Contracts

| ID    | Requirement |
| ----- | ----------- |
| FR-C1 | `LabelEnum` — interface requiring `label(): string` on all enums |
| FR-C2 | `StatusEnum` — extends `LabelEnum`, adds `isTerminal()`, `canTransitionTo()`, `validTransitions()` |
| FR-C3 | `ColorableEnum` — interface requiring `color(): string` for badge styling |
| FR-C4 | `SendsNotifications` — interface for notification dispatch: `execute(userId, type, title, ...)` |
| FR-C5 | `SettingsStore` — interface for settings retrieval: `get(key, default)` |

### 4.5 Exception Hierarchy

> **Canonical source:** [logging-and-error-handling.md](logging-and-error-handling.md) §4.5

The dual exception hierarchy (`AppException` + `ModuleException`) is fully specified in the
logging-and-error-handling spec. This section provides a brief overview for reference.

| ID    | Requirement |
| ----- | ----------- |
| FR-E1 | `AppException` (abstract) — framework-level errors, `statusCode()` abstract, `HasExceptionContext` trait |
| FR-E2 | `ModuleException` (abstract) — business-level errors, `statusCode()` abstract, `HasExceptionContext` trait |
| FR-E3 | `RejectedException` extends `ModuleException` — HTTP 400, business rule violations (C8 invariant) |
| FR-E4 | `ValidationFailedException` extends `ActionException` — HTTP 422, form validation failures |
| FR-E5 | `UnauthorizedException` extends `PresentationException` — HTTP 403, authorization failures |
| FR-E6 | `InfrastructureException` extends `AppException` — HTTP 500, not user-facing |
| FR-E7 | `HasExceptionContext` trait — `hint`, `context`, `toCliOutput()`, `isUserFacing()`, `shouldReport()` |

For full hierarchy, error handling in Actions, `HandlesActionErrors` trait, and exception
rendering — see [logging-and-error-handling.md](logging-and-error-handling.md).

### 4.6 Middleware

> **Canonical sources:** [middleware-pipeline.md](middleware-pipeline.md) (execution order,
> registration), [security-headers.md](security-headers.md) (CSP, HSTS details)

| ID    | Requirement |
| ----- | ----------- |
| FR-MW1 | `SecurityHeaders` — applies CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS. Full spec: [security-headers.md](security-headers.md) |
| FR-MW2 | `LogContext` — adds request_id, method, URL, IP, user_id, user_role, duration_ms. Full spec: [logging-and-error-handling.md](logging-and-error-handling.md) §4.6 |
| FR-MW3 | `RequireSetupAccessMiddleware` — globally applied, redirects to `/setup` when not installed |
| FR-MW4 | `SetLocaleMiddleware` — resolves locale from session, sets `app()->setLocale()` |
| FR-MW5 | `ProtectSetupRouteMiddleware` — validates setup token, rate limits, session versioning |
| FR-MW6 | `CheckRoleMiddleware` — role-based route protection. Full spec: [rbac-and-authorization.md](rbac-and-authorization.md) |
| FR-MW7 | `AuthThrottleMiddleware` — login/rate-limit throttling. Full spec: [authentication.md](authentication.md) |
| FR-MW8 | Middleware execution order: SecurityHeaders → LogContext → RequireSetupAccess → SetLocale → route-specific. Full spec: [middleware-pipeline.md](middleware-pipeline.md) |

### 4.7 Cache Infrastructure

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

### 4.8 Session Infrastructure

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

### 4.9 Policies

| ID    | Requirement |
| ----- | ----------- |
| FR-P1 | `BasePolicy` — abstract, auto-allows `super_admin` via `before()` method |
| FR-P2 | `AuthorizesRoles` trait — `isAdmin()`, `canManageAnyRole()`, `hasAnyOfRoles()` |
| FR-P3 | `AuthorizesOwnership` trait — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()` |

### 4.10 Support Utilities

> **SmartLogger canonical source:** [logging-and-error-handling.md](logging-and-error-handling.md)

| ID    | Requirement |
| ----- | ----------- |
| FR-SUP1 | `SmartLogger` — dual-channel (system + activity) fluent logger with PII masking. Full spec: [logging-and-error-handling.md](logging-and-error-handling.md) |
| FR-SUP2 | `PiiMasker` — masks 29+ sensitive keys, partial masks for email/phone/name. Full spec: [logging-and-error-handling.md](logging-and-error-handling.md) §4.3 |
| FR-SUP3 | `PasswordRules` — default password validation: 8+ chars, mixed case, numbers |
| FR-SUP4 | `AppInfo` — reads composer.json metadata with 24h cache |
| FR-SUP5 | `Environment` — environment detection helpers (isProduction, isTesting, etc.) |
| FR-SUP6 | `CsvHandler` — export, import, template download with header validation. Full spec: [csv-import-export.md](csv-import-export.md) |
| FR-SUP7 | `Color` — hex/RGB conversion, contrast calculation, DaisyUI shade generation |
| FR-SUP8 | `ModuleDiscoverService` — runtime discovery of Livewire components, policies, Blade namespaces. Full spec: [module-discovery.md](module-discovery.md) |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | CSP header must block inline scripts except explicitly whitelisted sources |
| NFR-S2 | Session cookie must be HTTP-only, SameSite=lax, secure in production |
| NFR-S3 | Redis connections support retry with backoff (max_retries=3, decorrelated jitter) |

### 5.2 Performance

| ID     | Requirement |
| ------ | ----------- |
| NFR-P1 | Redis connection pool: persistent connections optional (`REDIS_PERSISTENT`) |
| NFR-P2 | Cache warming reduces first-request latency after deployment |
| NFR-P3 | Application cache (config/route/view/event) reduces bootstrap time by ~60% |

### 5.3 Reliability

| ID     | Requirement |
| ------ | ----------- |
| NFR-R1 | Transaction wrapper retries up to 3 attempts on deadlock (BaseAction) |
| NFR-R2 | Graceful degradation: cache miss returns fresh data, never cached error |
| NFR-R3 | Redis backoff: decorrelated jitter with 100ms base, 1000ms cap |

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

### 6.1 Base Class Signatures

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

### 6.2 Contracts

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

### 6.3 Exception Hierarchy

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

### 6.4 Middleware Stack

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

### 6.5 Cache Key Registry

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

### 6.6 Session Configuration

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

---

## 7. Design Decisions

### DD-1 — Dual Exception Hierarchy

**Decision:** Two separate exception trees: `AppException` (framework) and `ModuleException` (business).
**Rationale:** Allows precise catch-block targeting. Framework errors (infrastructure, presentation)
are caught differently from business rule violations. `RejectedException` (the most common business
exception) extends `ModuleException` and always returns HTTP 400.
**Trade-off:** Slightly more complex exception hierarchy, but prevents the "catch everything as
RuntimeException" anti-pattern.
**Full specification:** [logging-and-error-handling.md](logging-and-error-handling.md) §4.5, §7.1

### DD-2 — Centralized Cache Key Registry

**Decision:** All cache keys MUST be declared in `config/cache-keys.php`, never inline.
**Rationale:** Prevents key collisions across modules, makes cache dependencies discoverable,
enables systematic flushing. Without centralized keys, modules would independently invent naming
conventions leading to conflicts.
**Trade-off:** Extra step when adding new cache keys. Mitigated by the clear naming convention
(`{module}.{purpose}[.{qualifier}]`).

### DD-3 — File Cache as Default

**Decision:** Default cache driver is `file`, not Redis.
**Rationale:** Shared hosting deployments cannot install Redis. File cache works without external
services. For Tier 2+ deployments, switching to Redis is a one-line `.env` change.
**Trade-off:** File cache is slower than Redis and doesn't support atomic operations. Acceptable
for single-tenant workloads.

### DD-4 — Database Session as Default

**Decision:** Default session driver is `database`, not `file`.
**Rationale:** Database sessions survive process restarts (important for queue workers), support
multi-process deployments, and the sessions table is auto-created by migration. File sessions
can be lost on deploy.
**Trade-off:** Slightly higher DB load per request. Negligible for single-tenant with <1000
concurrent users.

### DD-5 — Middleware Ordering

**Decision:** Security headers first, locale last in the global stack.
**Rationale:** Security headers must be on every response regardless of downstream errors. Locale
resolution depends on session (which needs setup check to have run first). Setup check must
precede locale to redirect uninstalled instances before any business logic.
**Trade-off:** Fixed ordering prevents per-route customization. Route-specific middleware handles
those cases.

### DD-6 — Module Discovery at Runtime

**Decision:** Livewire components, policies, and Blade namespaces are discovered dynamically via
`ModuleDiscoverService`, not manually registered.
**Rationale:** With 22 modules, manual registration in service providers would be error-prone and
a maintenance burden. Runtime scanning adds negligible startup cost (~50ms) and automatically
picks up new modules.
**Trade-off:** Slightly slower boot time. Mitigated by caching discovery results in Redis/file cache.

---

## 8. Success Metrics

### 8.1 Cache

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Key registration | 100% of cache keys in registry | `grep -r "Cache::" app/` → all keys resolve to config |
| Stale data window | < 5 seconds for settings changes | Observer fires on every model event |
| Cache warm time | < 5 seconds | `time php artisan system:cache-warm` |

### 8.2 Session

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Encryption | Always enabled | `SESSION_ENCRYPT=true` in default config |
| Lifetime | 120 minutes | Default config value |
| Fixation prevention | Regenerated on auth change | `session()->regenerate()` in login/logout flow |

### 8.3 Security

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
- `config/cache-keys.php` — Centralized cache key registry
- `config/cache.php` — Cache store definitions
- `config/session.php` — Session driver and cookie settings
- `app/Core/` — All base classes, contracts, exceptions, services
- `bootstrap/app.php` — Middleware registration
- **Related specs:** [system-requirements.md](system-requirements.md) — Dependencies, platform & database
- **Related specs:** [logging-and-error-handling.md](logging-and-error-handling.md) — Exception hierarchy, SmartLogger, error handling
- **Related specs:** [middleware-pipeline.md](middleware-pipeline.md) — Middleware execution order and registration
- **Related specs:** [security-headers.md](security-headers.md) — CSP, HSTS, security header details
- **Related specs:** [rbac-and-authorization.md](rbac-and-authorization.md) — Policies, roles, authorization
- **Related specs:** [event-system.md](event-system.md) — Event dispatch and listener infrastructure
- **Related specs:** [module-discovery.md](module-discovery.md) — Module discovery and caching
- **Related specs:** [csv-import-export.md](csv-import-export.md) — CsvHandler utility
