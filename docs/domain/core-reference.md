# Core — API Reference
> Last updated: 2026-06-01
> Changes: removed BaseState, DomainEvent; removed registerCommands() refs; updated CacheKeys count

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 48 files — ✅ 48 Implemented

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Actions/BaseAction.php` | `BaseAction` | — | Abstract base for Command and Process Actions with `transaction()`, `log()`, `HandlesActionErrors` |

## Channels

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Channels/CustomDatabaseChannel.php` | `CustomDatabaseChannel` | — | Custom notification channel using the `SendsNotifications` contract |

## Console Commands

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Console/Commands/CacheWarmCommand.php` | `CacheWarmCommand` | `Command` | Pre-warms settings, brand, config, view, and event caches |
| `Core/Console/Commands/CleanupCommand.php` | `CleanupCommand` | `Command` | Prunes expired resets, stale cache, failed jobs, old logs |
| `Core/Console/Commands/DomainDiscoverCommand.php` | `DomainDiscoverCommand` | `Command` | Re-discovers and registers domain Livewire components, policies, and Blade namespaces |
| `Core/Console/Commands/HealthCommand.php` | `HealthCommand` | `Command` | 15-point system health check (PHP, extensions, DB, storage, queue, cache) |

## Contracts

| File | Class/Interface | Description |
|---|---|---|
| `Core/Contracts/ColorableEnum.php` | `ColorableEnum` | Interface for enums that provide CSS color values for UI badges |
| `Core/Contracts/LabelEnum.php` | `LabelEnum` | Interface for enums that provide human-readable labels |
| `Core/Contracts/SendsNotifications.php` | `SendsNotifications` | Interface for notification-sending services |
| `Core/Contracts/StatusEnum.php` | `StatusEnum` | Interface for state-machine enums with lifecycle transitions |

## Data (DTOs)

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Data/AuditCheck.php` | `AuditCheck` | `Data` | Immutable DTO for a single audit check result (category, status, message key) |
| `Core/Data/AuditReport.php` | `AuditReport` | `Data` | Immutable DTO aggregating multiple `AuditCheck` results |
| `Core/Data/Data.php` | `Data` | — | Abstract base for immutable readonly DTOs with `toArray()`, `fromArray()`, `from()` |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Entities/BaseEntity.php` | `BaseEntity` | — | Abstract `final readonly` base with `fromModel(Model): static` bridge |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Core/Enums/AuditCategory.php` | `AuditCategory` | `LabelEnum` | Audit check categories: REQUIREMENTS, PERMISSIONS, DATABASE, TERMINAL, RECOMMENDATIONS |
| `Core/Enums/AuditStatus.php` | `AuditStatus` | `LabelEnum` | Audit check pass/fail/warn status |

## Exceptions

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Exceptions/AppException.php` | `AppException` | `RuntimeException` | Abstract root for framework-layer exceptions |
| `Core/Exceptions/ActionException.php` | `ActionException` | `AppException` | Abstract base for operation-level failures |
| `Core/Exceptions/ConflictException.php` | `ConflictException` | `ActionException` | Duplicate or conflicting state |
| `Core/Exceptions/DomainException.php` | `DomainException` | `RuntimeException` | Abstract root for domain rule violations (parallel tree) |
| `Core/Exceptions/InfrastructureException.php` | `InfrastructureException` | `AppException` | Abstract base for external system failures |
| `Core/Exceptions/NotFoundException.php` | `NotFoundException` | `PresentationException` | Resource not found (404) |
| `Core/Exceptions/PresentationException.php` | `PresentationException` | `AppException` | Abstract base for HTTP-layer failures |
| `Core/Exceptions/RateLimitException.php` | `RateLimitException` | `InfrastructureException` | Rate limit exceeded (429) |
| `Core/Exceptions/RejectedException.php` | `RejectedException` | `DomainException` | Domain invariant violated (e.g., invalid state transition) |
| `Core/Exceptions/UnauthorizedException.php` | `UnauthorizedException` | `PresentationException` | Authorization failure (403) |
| `Core/Exceptions/ValidationFailedException.php` | `ValidationFailedException` | `ActionException` | Input validation failure (422) |

### Exception Traits

| File | Trait | Description |
|---|---|---|
| `Core/Exceptions/Concerns/HasExceptionContext.php` | `HasExceptionContext` | Provides `withHint()`, `withContext()`, `toCliOutput()` to both exception trees |

## Controllers

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Http/Controllers/BaseController.php` | `BaseController` | — | Abstract marker base controller |

## Middleware

| File | Class | Description |
|---|---|---|
| `Core/Http/Middleware/LogContext.php` | `LogContext` | Injects request_id, method, URL, IP, user_id, duration into log context |
| `Core/Http/Middleware/SecurityHeaders.php` | `SecurityHeaders` | Adds CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy headers |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Http/Requests/FormRequest.php` | `FormRequest` | `LaravelFormRequest` | Throws `ValidationFailedException` instead of redirect on validation failure |

## Livewire

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Livewire/BaseRecordManager.php` | `BaseRecordManager` | `Component` | Abstract CRUD base with search, filter, sort, pagination, bulk/mass actions |

### Livewire Concerns

| File | Trait | Description |
|---|---|---|
| `Core/Livewire/Concerns/WithRecordSelection.php` | `WithRecordSelection` | Provides `selectedIds`, `clearSelection()`, `selectAll()`, `selected_count` |
| `Core/Livewire/Concerns/WithSorting.php` | `WithSorting` | Provides `sortBy` with whitelist-protected `applySorting()` |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Models/ActivityLog.php` | `ActivityLog` | `Activity` (Spatie) | Extended activity log with `forUser()`, `ofAction()`, `forModule()`, `recent()`, `lastDays()`, `getGroupedByDay()` |
| `Core/Models/BaseModel.php` | `BaseModel` | `Model` | Abstract base with UUID primary key (`HasUuids`), non-incrementing, string key type |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Policies/BasePolicy.php` | `BasePolicy` | — | Abstract base bundling `AuthorizesRoles` and `AuthorizesOwnership` traits |

### Policy Concerns

| File | Trait | Description |
|---|---|---|
| `Core/Policies/Concerns/AuthorizesOwnership.php` | `AuthorizesOwnership` | `isOwner()`, `isOwnerOrAdmin()`, `isRelatedThrough()` |
| `Core/Policies/Concerns/AuthorizesRoles.php` | `AuthorizesRoles` | `isAdmin()`, `isTeacher()`, `isStudent()`, `isSupervisor()`, `hasAnyOfRoles()` |

## Support

| File | Class | Description |
|---|---|---|
| `Core/Support/CacheKeys.php` | `CacheKeys` | Central registry of all application cache keys as typed constants with invalidation docs |
| `Core/Support/HandlesActionErrors.php` | `HandlesActionErrors` | Trait providing `withErrorHandling()` — try-catch-log-rethrow for Actions |
| `Core/Support/Integrity.php` | `Integrity` | Runtime composer.json author verification (exit in production, warning in dev) |
| `Core/Support/PasswordRules.php` | `PasswordRules` | Shared password validation rules — `default()` and `defaultAsArray()` |
| `Core/Support/PiiMasker.php` | `PiiMasker` | PII masking for passwords, tokens, emails, phones, names, IPs, user agents |
| `Core/Support/SmartLogger.php` | `SmartLogger` | Fluent dual-channel logger (system + activity) with PII masking and 3 routing modes |

## Dependency Graph

```
                  ┌─────────────────────────────────┐
                  │      All Business Domains        │
                  │  (Auth, School, Internship, ...)  │
                  └──────────────┬──────────────────┘
                                 │ depends on
                                 ▼
                  ┌─────────────────────────────────┐
                  │            Core Domain           │
                  │  ┌───────┬──────┬────────┬────┐  │
                  │  │Contract│Base │Infra   │Frame│  │
                  │  │  ts    │Classes│structure│work│  │
                  │  └───────┴──────┴────────┴────┘  │
                  └──────────────┬──────────────────┘
                                 │ depends on
                                 ▼
                  ┌─────────────────────────────────┐
                  │   Laravel + Spatie + PHP 8.4     │
                  └─────────────────────────────────┘
```

Core is the root of the entire dependency graph. Nothing depends on it that isn't in the
Laravel framework, Spatie packages, or PHP standard library.

## Where to Find It

- `app/Domain/Core/Actions/BaseAction.php` — abstract action base
- `app/Domain/Core/Models/BaseModel.php` — abstract model base with UUID
- `app/Domain/Core/Entities/BaseEntity.php` — abstract entity base
- `app/Domain/Core/Policies/BasePolicy.php` — abstract policy base
- `app/Domain/Core/Livewire/BaseRecordManager.php` — abstract CRUD Livewire base
- `app/Domain/Core/Support/SmartLogger.php` — dual-channel logger
- `app/Domain/Core/Support/CacheKeys.php` — cache key registry
- `app/Domain/Core/Support/PiiMasker.php` — PII masking
- `app/Domain/Core/Support/PasswordRules.php` — password validation rules
- `app/Domain/Core/Exceptions/` — exception hierarchy
- `app/Domain/Core/Contracts/` — core interfaces
- `app/Domain/Core/Http/Middleware/` — global middleware
- `app/Domain/Core/Console/Commands/` — system CLI commands
- `resources/views/core/` — does not exist (Core is infrastructure and has no Blade views)
- `routes/web/core.php` — deleted (was a placeholder; Core owns no routes)

> **Note:** Core has no routes and no views. The master `routes/web.php` does not require a Core route file. There are 23 domain route files (not 24).
