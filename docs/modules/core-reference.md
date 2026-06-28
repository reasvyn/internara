# Core — Technical Reference

> **Last updated:** 2026-06-24
> **Changes:** sync — fix ModuleDiscoverService method names (discoverModules → discoverLivewireComponents)

## Description
Detailed structural and implementation reference for the **Core** module, including both abstract
infrastructure and concrete shared components.

---


## Overview

Provides foundational infrastructure, base classes, contracts, exception hierarchy, middleware,
request lifecycle utilities, and cross-module concrete implementations that every other module
depends on.

### Module Statistics

- **Services**: 1 (`ModuleDiscoverService`)

- **Contracts**: 5 (`LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`,
  `SettingsStore`)
- **Base Classes**: 10 (`BaseModel`, `BaseAuthenticatable`, `BaseAction`, `BaseEntity`,
  `BasePolicy`, `BaseRecordManager`, `BaseController`, `BaseFormRequest`, `BaseData`, `BaseEvent`) +
  3 concern traits (`HasCommonScopes`, `WithSorting`, `WithRecordSelection`)
- **Concrete DTOs**: 3 (`ActionResponse`, `AuditCheck`, `AuditReport`)
- **Concrete Enums**: 3 (`CsvRowResult`, `AuditCategory`, `AuditStatus`)
- **Concrete Exceptions**: 6 (`ConflictException`, `NotFoundException`, `RateLimitException`,
  `RejectedException`, `UnauthorizedException`, `ValidationFailedException`)
- **Middleware**: 2 (`SecurityHeaders`, `LogContext`)
- **Support Classes**: 11 (`SmartLogger`, `LangChecker`, `AppInfo`, `AppIntegrity`, `Color`,
  `CsvHandler`, `Environment`, `PasswordRules`, `PiiMasker`, `Spotlight`, `helpers.php`)
- **Action Traits**: 1 (`HandlesActionErrors`)
- **Command Action Base**: 1 (`BaseCommandAction`)
- **Read Action Base**: 1 (`BaseReadAction`)
- **Process Action Base**: 1 (`BaseProcessAction`)
- **Models**: 2 concrete (`ActivityLog`, `BaseAuthenticatable`) + 1 abstract (`BaseModel`)
- **Events**: 1 (`BaseEvent`, abstract)
- **Livewire Components**: 1 (`BaseRecordManager`) + 2 concerns (`WithSorting`,
  `WithRecordSelection`)
- **Policies**: 1 (`BasePolicy`) + 2 concern traits (`AuthorizesRoles`, `AuthorizesOwnership`)
- **Data/DTOs**: 1 abstract (`BaseData`) + 3 concrete
- **Channels**: 1 (`CustomDatabaseChannel`)
- **Console Commands**: 1 (`module:discover`)
- **Global Helpers**: 1 (`app_info()` in `helpers.php`)
- **Config Files**: 1 (`config/cache-keys.php` — centralized cache key registry)
- **Tests**: across Unit and Feature suites
- **Routes**: 0 (health check at `/up` in `bootstrap/app.php`)

---

## Services

Located in `app/Core/Services/`:

| Service                 | Purpose                                                                               | Public Methods                              |
| ----------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------- |
| `ModuleDiscoverService` | Scan module directories to auto-register Livewire components, Gate policies, and Blade view namespaces | `discoverLivewireComponents()`, `discoverPolicies()`, `registerBladeNamespaces()` |

---

## Contracts

Located in `app/Core/Contracts/`:

| Contract             | Purpose                                                                  | Implemented By               |
| -------------------- | ------------------------------------------------------------------------ | ---------------------------- |
| `LabelEnum`          | `label(): string` for UI display                                         | All enums across all modules |
| `StatusEnum`         | State machine: `canTransitionTo()`, `isTerminal()`, `validTransitions()` | State machine enums          |
| `ColorableEnum`      | CSS color variant (`color(): string`)                                    | Enums with visual state      |
| `SendsNotifications` | Binds notification dispatch to `SendNotificationAction`                  | Notification infrastructure  |
| `SettingsStore`      | Key-value store for runtime configuration                                | Settings `Setting` model     |

---

## Base Classes

Located in `app/Core/`:

| Class               | Path                                  | Purpose                                                                      | Mandatory For                                                    |
| ------------------- | ------------------------------------- | ---------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| `BaseModel`         | `Models/BaseModel.php`                | UUID PKs, HasFactory, soft-delete, global scopes                             | All models (User extends `Authenticatable` with manual HasUuids) |
| `BaseAction`        | `Actions/BaseAction.php`              | Transaction management, activity logging, error handling                     | All Command & Process Actions                                    |
| `BaseCommandAction` | `Actions/BaseCommandAction.php`       | Command action contract: wraps mutations in transaction + logging            | Command Actions                                                  |
| `BaseReadAction`    | `Actions/BaseReadAction.php`          | Read action contract: query-only, no transaction or logging                  | Read Actions                                                     |
| `BaseProcessAction` | `Actions/BaseProcessAction.php`       | Process action contract: multi-step orchestration with transaction + logging | Process Actions                                                  |
| `BaseEntity`        | `Entities/BaseEntity.php`             | `final readonly`, zero framework dependencies, `fromModel()` bridge          | All entities                                                     |
| `BasePolicy`        | `Policies/BasePolicy.php`             | Superadmin `before()` bypass, role checks, ownership checks                  | All policies                                                     |
| `BaseRecordManager` | `Livewire/BaseRecordManager.php`      | CRUD table: search, sort, filter, paginate, bulk actions, row selection      | All CRUD Livewire components                                     |
| `BaseController`    | `Http/Controllers/BaseController.php` | Common controller utilities                                                  | All HTTP controllers                                             |
| `BaseFormRequest`   | `Http/Requests/BaseFormRequest.php`   | Validation without redirect, throws `ValidationFailedException`              | All form requests                                                |
| `BaseData`          | `Data/BaseData.php`                   | Abstract readonly DTO with `fromArray()`, `toArray()`, `from()`              | All DTOs                                                         |
| `BaseEvent`         | `Events/BaseEvent.php`                | `Dispatchable`, `eventName()`, `toPayload()` auto-extracts public properties | All events                                                       |

---

## Data & DTOs

Located in `app/Core/Data/`:

| Class            | Extends    | Purpose                                                                                                                    |
| ---------------- | ---------- | -------------------------------------------------------------------------------------------------------------------------- |
| `BaseData`       | —          | Abstract readonly DTO base                                                                                                 |
| `ActionResponse` | —          | Standardized action result: `ok()`, `created()`, `updated()`, `deleted()`, `error()`, `withRedirect()`, JSON serialization |
| `AuditCheck`     | `BaseData` | Single health audit check (status, category, label, message)                                                               |
| `AuditReport`    | `BaseData` | Collection of `AuditCheck` entries with pass/fail aggregates                                                               |

---

## Enums

Located in `app/Core/Enums/`. All implement `LabelEnum`:

| Enum            | Purpose                                                                   |
| --------------- | ------------------------------------------------------------------------- |
| `CsvRowResult`  | Row import status: SUCCESS, ERROR, SKIPPED                                |
| `AuditCategory` | System health categories: DATABASE, SYSTEM, ENVIRONMENT, SECURITY, HEALTH |
| `AuditStatus`   | Audit check results: PASS, FAIL, WARN                                     |

---

## Exception Hierarchy

Full hierarchy in `app/Core/Exceptions/`. All use `HasExceptionContext` trait (hint, context, CLI
format):

```
AppException (abstract, extends RuntimeException)
├── ActionException (abstract) — business operation failed
│   ├── ConflictException (409) — duplicate resource
│   └── ValidationFailedException (422) — input validation
├── InfrastructureException (abstract) — external system failure
│   └── RateLimitException (429)
└── PresentationException (abstract) — HTTP-layer failure
    ├── NotFoundException (404)
    └── UnauthorizedException (403)

ModuleException (abstract, extends RuntimeException)
└── RejectedException (400) — domain invariant violation
```

---

## Middleware

| Middleware        | Path                                  | Purpose                                                   |
| ----------------- | ------------------------------------- | --------------------------------------------------------- |
| `SecurityHeaders` | `Http/Middleware/SecurityHeaders.php` | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| `LogContext`      | `Http/Middleware/LogContext.php`      | Request tracing: request_id, method, URL, IP, user_id     |

---

## Livewire Components & Concerns

| Component / Trait     | Path                                        | Purpose                                                       |
| --------------------- | ------------------------------------------- | ------------------------------------------------------------- |
| `BaseRecordManager`   | `Livewire/BaseRecordManager.php`            | Abstract CRUD table with search, sort, paginate, bulk actions |
| `WithSorting`         | `Livewire/Concerns/WithSorting.php`         | Column sorting state management                               |
| `WithRecordSelection` | `Livewire/Concerns/WithRecordSelection.php` | Checkbox row selection for bulk actions                       |

---

## Policies

| Trait                 | Path                                        | Purpose                                        |
| --------------------- | ------------------------------------------- | ---------------------------------------------- |
| `AuthorizesRoles`     | `Policies/Concerns/AuthorizesRoles.php`     | Quick role-based authorization by role string  |
| `AuthorizesOwnership` | `Policies/Concerns/AuthorizesOwnership.php` | Ownership check comparing primary/foreign keys |

---

## Support Classes

| Class                 | Path                                       | Purpose                                                                          |
| --------------------- | ------------------------------------------ | -------------------------------------------------------------------------------- |
| `SmartLogger`         | `Support/SmartLogger.php`                  | Dual-channel logger: system + activity, PII masking                              |
| `LangChecker`         | `Support/LangChecker.php`                  | Dev helper: warns on missing translation keys                                    |
| `AppInfo`             | `Support/AppInfo.php`                      | Static metadata from composer.json + config                                      |
| `AppIntegrity`        | `Support/AppIntegrity.php`                 | Author verification (Reas Vyn). Duplicate `Integrity` class removed in refactor. |
| `Color`               | `Support/Color.php`                        | Hex-to-RGB, HSL conversion, color manipulation                                   |
| `CsvHandler`          | `Support/CsvHandler.php`                   | CSV parsing, heading validation, export generation                               |
| `Environment`         | `Support/Environment.php`                  | Environment detection (staging, production, dev)                                 |
| `HandlesActionErrors` | `Actions/Concerns/HandlesActionErrors.php` | Generic try-catch-log-rethrow for actions                                        |
| `PasswordRules`       | `Support/PasswordRules.php`                | Common password strength validation rules                                        |
| `PiiMasker`           | `Support/PiiMasker.php`                    | Regex-based PII redaction (IDs, phone numbers)                                   |
| `Spotlight`           | `Support/Spotlight.php`                    | Debug/development helper utilities                                               |
| `helpers.php`         | `Support/helpers.php`                      | `app_info()` helper function                                                     |

The helpers `setting()` and `brand()` are defined in `app/Settings/Support/helpers.php`.

---

## Routes

No dedicated route file. Health check endpoint `/up` is defined in `bootstrap/app.php`. See
[Routes](../infrastructure/routes.md) for the routing architecture.

## Views

Views are located in `resources/views/core/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/{Feature,Unit}/Core/`. See [Testing](../infrastructure/testing.md) for
the testing conventions.

## Factories

None - Core provides base classes only.

## Migrations

| Migration                   | Table          |
| --------------------------- | -------------- |
| `create_activity_log_table` | `activity_log` |
| `create_cache_table`        | `cache`        |
| `create_jobs_table`         | `jobs`         |
| `create_failed_jobs_table`  | `failed_jobs`  |
| `create_job_batches_table`  | `job_batches`  |
| `create_media_table`        | `media`        |
| `create_pulse_tables`       | `pulse_*`      |

---

## Architectural Integration

- **Business Logic**: `app/Core/`
- **Routing**: None (health check `/up` in `bootstrap/app.php`)
- **Views**: `resources/views/core/`
- **Testing**: `tests/Feature/Core/`, `tests/Unit/Core/`
- **Cache Config**: `config/cache-keys.php`

_For overview and business context, see [core.md](core.md)._
