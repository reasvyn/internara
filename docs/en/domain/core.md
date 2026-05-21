# Core Domain

## Purpose

Core is the architectural foundation — every domain depends on it, it depends on no domain. Core provides base classes, contracts, exception hierarchy, logging infrastructure, HTTP middleware, console commands, and shared Livewire concerns used across the entire application.

## Modules

| Layer | Contents |
|---|---|
| **Models** | `BaseModel` (abstract, UUID via HasUuids, non-incrementing, string key type), `ActivityLog` (extends Spatie Activity with query scopes: `forUser()`, `ofAction()`, `forModule()`, `recent()`, `lastDays()`, `groupedByDay()`) |
| **Models/Concerns** | `HasAuditTrail` (trait — auto-logs created/updated/deleted/restored/forceDeleted via SmartLogger, configurable event selection and PII masking) |
| **Entities** | `BaseEntity` (abstract readonly class, `fromModel(Model): static` — the single framework dependency) |
| **Actions** | `BaseAction` (abstract — `execute()`, `transaction()` wrapping, `log()` via SmartLogger, `moduleName()` auto-detection from namespace) |
| **Support** | `SmartLogger` (fluent dual-channel logger — system + activity, PII masking, context enrichment), `PiiMasker` (static masker for passwords, tokens, emails, phones, names, credit cards, SSNs), `HandlesActionErrors` (trait — try-catch-log-rethrow), `Integrity` (final static — `verify()` checks `composer.json` author at boot, attribution protection) |
| **Policies** | `BasePolicy` (abstract, bundles `AuthorizesOwnership` + `AuthorizesRoles` traits) |
| **Policies/Concerns** | `AuthorizesOwnership` (trait — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()`), `AuthorizesRoles` (trait — `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`, `hasAnyOfRoles()`) |
| **Exceptions** | `AppException` (abstract, extends RuntimeException, uses `HasExceptionContext`), `DomainException` (abstract, parallel tree, deliberately NOT a child of AppException), 4 abstract branches + 6 concrete exceptions |
| **Exceptions/Concerns** | `HasExceptionContext` (trait — `withHint()`, `withContext()`, `toCliOutput()`, `isUserFacing()`, `shouldReport()`) |
| **Contracts** | `LabelEnum` (`label(): string`), `StatusEnum` (extends LabelEnum — `canTransitionTo()`, `isTerminal()`, `validTransitions()`), `ColorableEnum` (`color(): string`), `DomainEvent` (`occurredAt(): DateTimeImmutable`), `Filterable`, `Searchable`, `Sortable` |
| **Data** | `Data` (abstract readonly DTO — `toArray()`, `fromArray()`, `from()`), `AuditCheck` (category + status + message keys), `AuditReport` (aggregates checks, `passed()`, `forCategory()`) |
| **Enums** | `AuditCategory` (Requirements, Permissions, Database, Terminal, Recommendations — `isCritical()`), `AuditStatus` (Pass, Fail, Warn — `symbol()`) |
| **States** | `BaseState` (extends Spatie ModelStates\State — `label()`, `isTerminal()`, `toEnum()`) |
| **Http/Controllers** | `BaseController` (abstract marker — no methods yet, available for cross-cutting HTTP concerns) |
| **Http/Requests** | `FormRequest` (extends Laravel's FormRequest — throws `ValidationFailedException` instead of redirect/JSON) |
| **Http/Concerns** | `RespondsWithHttp` (trait — `respondSuccess()` 200, `respondCreated()` 201, `respondError()`, `respondNoContent()` 204, `respondValidationError()` 422) |
| **Http/Middleware** | `SecurityHeaders` (configurable CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy from config), `LogContext` (injects `request_id`, method, URL, IP, `user_id`, `user_role`, `duration_ms` into log context) |
| **Console/Commands** | `HealthCommand` (`system:health` — 14 checks: environment, setup status, PHP version, extensions, recommended extensions, memory, database, migrations pending, storage, disk, queue, cache, app key, storage link, maintenance mode), `CleanupCommand` (`system:cleanup` — prunes expired resets, stale cache tags, failed jobs, activity logs, old log files), `CacheWarmCommand` (`system:cache-warm` — pre-warms settings, brand, config, view, event caches) |
| **Livewire** | `BaseRecordManager` (abstract CRUD base — search, filter, sort, pagination via `WithPagination`, record selection, bulk actions, mass actions) |
| **Livewire/Concerns** | `WithSorting` (trait — safe column whitelist for `orderBy`), `WithRecordSelection` (trait — checkbox state for bulk operations) |
| **Channels** | `CustomDatabaseChannel` (custom notification channel that dispatches in-app notifications via `SendsNotifications` contract, decoupled from any business domain) |
| **Web Routes** | `routes/web/core.php` — `GET /` (home, handled by `Setup\HomeController`), `GET /dashboard` (auth-protected, handled by `User\DashboardController`) |

## Exception Hierarchy

```
RuntimeException
├── AppException (abstract)
│   ├── ActionException (abstract) → operation failures
│   │   ├── ValidationFailedException (422)
│   │   └── ConflictException (409)
│   ├── PresentationException (abstract) → HTTP-layer failures
│   │   ├── NotFoundException (404)
│   │   └── UnauthorizedException (403)
│   └── InfrastructureException (abstract) → external system failures
│       └── RateLimitException (429)
└── DomainException (abstract, parallel tree — NOT under AppException)
    └── RejectedException
```

Design rationale: `DomainException` is intentionally separate from `AppException` so domain catch blocks never accidentally catch framework-layer exceptions. Both use the `HasExceptionContext` trait for consistent API.

## How Base Classes Work Together

- `BaseModel` → all domain models extend it (except User which extends `Authenticatable` directly but uses same UUID conventions).
- `BaseAction` → all domain actions extend it for transaction + logging.
- `BaseEntity` → all business rule objects extend it as `final readonly`.
- `BasePolicy` → all authorization policies extend it for role/ownership checks.
- `BaseRecordManager` → all Livewire CRUD tables extend it for search/sort/filter/paginate.
- `SmartLogger` → primary logger. `Log::withContext()` used only in `LogContext` middleware for request tracing context.
- `BaseController`, `FormRequest`, `RespondsWithHttp` → foundation for HTTP layer.
- `LabelEnum`, `StatusEnum`, `ColorableEnum` → contracts that all domain enums implement.
- `AppException` hierarchy → all exceptions across every domain derive from this tree.

## Requirements

### Purpose (Developer-Facing)

Core has no end-user stories — it provides the architectural foundation every domain builds on. The requirements below describe what the framework guarantees to all consuming domains.

### Key Guarantees

| Guarantee | Description |
|-----------|-------------|
| UUID primary keys | All models (except User) extend `BaseModel` with `HasUuids`, non-incrementing string keys |
| Transaction safety | All business operations run inside `DB::transaction()` via `BaseAction` |
| Dual-channel audit | Every action is logged to both system log and activity log via `SmartLogger` |
| PII masking | Sensitive data (passwords, tokens, emails) is automatically masked in logs |
| Entity purity | Business rules live in `final readonly` entities with zero framework dependencies |
| State machine support | `BaseState` + `StatusEnum` contract for typed lifecycle management |
| Consistent authorization | `BasePolicy` with `AuthorizesRoles` and `AuthorizesOwnership` traits |
| Exception hierarchy | Every exception extends `AppException` or `DomainException` with structured context |
| Console health | `system:health` runs 14 checks; `system:cleanup` prunes stale data; `system:cache-warm` pre-warms caches |
| Security headers | CSP, X-Frame-Options, Referrer-Policy configured via `config/security-headers.php` |
| Request tracing | Every request gets a `request_id` injected into log context |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `BaseModel` (abstract UUID model), `ActivityLog` (audit trail with scopes) |
| **Entity** | `BaseEntity` (abstract `final readonly` with `fromModel()`) |
| **Action** | `BaseAction` (abstract with `transaction()`, `log()`, `moduleName()`) |
| **Policy** | `BasePolicy` (abstract with role/ownership authorization traits) |
| **State** | `BaseState` (Spatie state machine wrapper with `label()`, `isTerminal()`, `toEnum()`) |
| **Enums** | `AuditStatus` — `PASS`, `FAIL`, `WARN`; `AuditCategory` — `Requirements`, `Permissions`, `Database`, `Terminal`, `Recommendations` |
| **Contracts** | `LabelEnum`, `StatusEnum`, `ColorableEnum`, `DomainEvent`, `Filterable`, `Searchable`, `Sortable` |
| **Exceptions** | `AppException` → `ActionException`, `PresentationException`, `InfrastructureException`; `DomainException` → `RejectedException` |
| **Support** | `SmartLogger` (fluent logger), `PiiMasker`, `HandlesActionErrors` (trait), `Integrity` |
| **Livewire** | `BaseRecordManager` (CRUD base with search, filter, sort, pagination, bulk actions) |
| **Middleware** | `SecurityHeaders`, `LogContext` |
| **Channels** | `CustomDatabaseChannel` (notification dispatch via `SendsNotifications`) |
| **Web Routes** | `routes/web/core.php` — `GET /`, `GET /dashboard` |
| **Console** | `system:health`, `system:cleanup`, `system:cache-warm` |

## Dependencies

| Dependency | Reason |
|---|---|
| None | Core is the root of the entire dependency graph. Laravel framework and Spatie packages are its only external dependencies. |

## Important Rules

- Core MUST NOT import any business domain.
- All domain models extend `BaseModel` (except User which extends `Authenticatable`).
- All domain policies extend `BasePolicy`.
- All logging goes through `SmartLogger` — zero `Log::` facade calls.
- Entity subclasses MUST be `final readonly`.
- Security headers configured via `config/security-headers.php`, never hardcoded.
- Every request log entry must include a `request_id` for traceability.
- Console commands registered via `$schedule->command()` in `routes/console.php`.
