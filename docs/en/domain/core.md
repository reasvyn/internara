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
| **Support** | `SmartLogger` (fluent dual-channel logger — system + activity, PII masking, context enrichment), `PiiMasker` (static masker for passwords, tokens, emails, phones, names, credit cards, SSNs), `HandlesActionErrors` (trait — try-catch-log-rethrow) |
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
| **Console/Commands** | `HealthCommand` (`system:health` — 12 checks: PHP version, extensions, memory, database, storage, disk, queue, cache, app key, storage link, maintenance mode), `CleanupCommand` (`system:cleanup` — prunes expired resets, stale cache tags, failed jobs, activity logs, old log files), `CacheWarmCommand` (`system:cache-warm` — pre-warms settings, brand, config, view, event caches) |
| **Livewire** | `BaseRecordManager` (abstract CRUD base — search, filter, sort, pagination via `WithPagination`, record selection, bulk actions, mass actions) |
| **Livewire/Concerns** | `WithSorting` (trait — safe column whitelist for `orderBy`), `WithRecordSelection` (trait — checkbox state for bulk operations) |

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
- `SmartLogger` → the only logger used anywhere. Zero direct `Log::` facade calls in the codebase.
- `BaseController`, `FormRequest`, `RespondsWithHttp` → foundation for HTTP layer.
- `LabelEnum`, `StatusEnum`, `ColorableEnum` → contracts that all domain enums implement.
- `AppException` hierarchy → all exceptions across every domain derive from this tree.

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
