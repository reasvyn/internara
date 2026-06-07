# Core — Technical Reference

> Last updated: 2026-06-07 Changes: Refactored Core base classes and SmartLogger, updated test
> and view counts

Detailed structural and implementation reference for the **Core** module.

---

## Overview

Provides foundational infrastructure, base classes, contracts, and request lifecycle utilities that
every other module depends on.

### Module Statistics

- **Contracts**: 5 (LabelEnum, StatusEnum, ColorableEnum, SendsNotifications, SettingsStore)
- **Base Classes**: 9 (BaseModel, BaseAction, BaseEntity, BasePolicy, BaseRecordManager,
  BaseController, BaseFormRequest, BaseData, BaseEvent)
- **Exceptions**: 6 files (base hierarchy: AppException tree + ModuleException tree, plus abstract
  layers and HasExceptionContext trait)
- **Middleware**: 2 (SecurityHeaders, LogContext) — RequireSetupAccess is in Academics, SetLocale is
  in SysAdmin
- **Support Classes**: 2 (SmartLogger, LangChecker)
- **Models**: 2 (ActivityLog, BaseModel is abstract)
- **Enums**: 0 (all system-wide concrete enums moved to Shared)
- **Events**: 1 (BaseEvent abstract base, concrete events in module submodules)
- **Livewire Components**: 1 (BaseRecordManager CRUD base component)
- **Policies**: 1 BasePolicy (concern traits moved to Shared)
- **Data/DTOs**: 1 (BaseData DTO base, concrete DTOs moved to Shared)
- **Channels**: 1 (CustomDatabaseChannel)
- **Console Commands**: 1 (module:discover)
- **Views**: 25 (7 layouts, 13 UI, 5 widgets)
- **Tests**: 29 (6 Feature + 23 Unit)
- **Submodules**: 0 (Core has no submodules — it provides infrastructure)
- **Routes**: 0 (Core has no dedicated route file)

---

## Core Infrastructure Implementation Mechanics

### 1. The Transaction & Logging Engine (`BaseAction.php`)

Every Command and Process Action extends `BaseAction`. When `execute()` is called, it triggers the
inherited `transaction()` and `log()` logic:

- **`transaction(Closure $callback)`**: Uses Laravel's DB transaction manager. If any query throws a
  Throwable, the transaction catches it, rolls back all database modifications, processes it through
  `HandlesActionErrors`, and rethrows it as an `AppException` or `ModuleException`.
- **`log(string $event, Model $model, array $context = [])`**: Relies on `SmartLogger` to record
  mutations into Spatie's activity log tables. The context stores metadata like before/after states,
  omitting masked PII attributes.

### 2. Dual-channel Output Stream (`SmartLogger.php`)

`SmartLogger` wraps Laravel's native logger to write structured, JSON-formatted output to two
separate pipelines:

- **Application Channel (`storage/logs/laravel.log`)**: Detailed traces, stack traces, and database
  query executions for local troubleshooting.
- **Audit Channel (`activity_log` DB table)**: Immutable entries documenting administrative action
  records (e.g. grading updates, placement reassignments). Before logging, `PiiMasker` uses regular
  expressions to redact personal IDs and contact numbers.

### 3. Dynamic Scan Engine (`ModuleDiscoverCommand.php`)

Instead of manual registry mappings, `module:discover` automates service discovery:

- **File Scanner**: Traverses `app/` using regex directory matching to discover controllers,
  Livewire component classes, route files, and policy registrations.
- **Boot Registries**: Dynamic mappings are compiled into single array payloads and written directly
  to Shared Cache storage using `CacheKeys::MODULE_LIVEWIRE` and `CacheKeys::MODULE_POLICIES` to
  avoid runtime directory traversal costs.

---

## Contracts (Layer 3)

Located in `app/Core/Contracts/`:

| Contract             | Purpose                                                                  | Implemented By                                           |
| -------------------- | ------------------------------------------------------------------------ | -------------------------------------------------------- |
| `LabelEnum`          | Provides `label(): string` for UI display                                | All enums across all modules                             |
| `StatusEnum`         | State machine: `canTransitionTo()`, `isTerminal()`, `validTransitions()` | State machine enums (e.g., ProgramStatus, AccountStatus) |
| `ColorableEnum`      | Provides CSS color variant (`color(): string`)                           | Enums with visual state representation                   |
| `SendsNotifications` | Binds notification dispatch to `SendNotificationAction`                  | Notification-sending infrastructure                      |

---

## Exceptions (Layer 3)

Located in `app/Core/Exceptions/`. Base hierarchy using `HasExceptionContext` trait:

### AppException Tree

```
AppException (abstract, extends RuntimeException)
├── ActionException (abstract) — business operation failed
├── InfrastructureException (abstract) — external system failure
└── PresentationException (abstract) — HTTP-layer failure
```

### ModuleException Tree (separate, isolated)

```
ModuleException (abstract, extends RuntimeException)
```

---

## Base Classes (Layer 4)

Located in `app/Core/`:

| Class               | File                                  | Purpose                                                                                                           | Mandatory For                                            |
| ------------------- | ------------------------------------- | ----------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `BaseModel`         | `Models/BaseModel.php`                | UUID PKs, HasFactory, soft-delete support, global scopes                                                          | All models (except User which extends `Authenticatable`) |
| `BaseAction`        | `Actions/BaseAction.php`              | Transaction management, activity logging, `HandlesActionErrors`                                                   | All Command and Process Actions                          |
| `BaseEntity`        | `Entities/BaseEntity.php`             | `final readonly` business rules, zero framework dependencies                                                      | All entities                                             |
| `BasePolicy`        | `Policies/BasePolicy.php`             | Superadmin `before()` bypass, role checks, ownership checks                                                       | All policies                                             |
| `BaseRecordManager` | `Livewire/BaseRecordManager.php`      | CRUD table: search, sort, filter, paginate, bulk actions, row selection                                           | All CRUD list Livewire components                        |
| `BaseController`    | `Http/Controllers/BaseController.php` | Common controller utilities                                                                                       | All HTTP controllers                                     |
| `BaseFormRequest`   | `Http/Requests/BaseFormRequest.php`   | Validation without redirect (throws `ValidationFailedException`)                                                  | All form requests                                        |
| `BaseData`          | `Data/BaseData.php`                   | Abstract readonly DTO with `fromArray()` and `toArray()`                                                          | All data transfer objects                                |
| `BaseEvent`         | `Events/BaseEvent.php`                | Abstract base event with `Dispatchable`, `eventName()`, `toPayload()` auto-extracts public properties for logging | All module events                                        |

---

## Middleware

Core provides 2 middleware in `app/Core/Http/Middleware/`:

| Middleware        | File                             | Purpose                                                   |
| ----------------- | -------------------------------- | --------------------------------------------------------- |
| `SecurityHeaders` | `Middleware/SecurityHeaders.php` | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| `LogContext`      | `Middleware/LogContext.php`      | Request tracing: request_id, method, URL, IP, user_id     |

---

## Actions

| File                     | Class        | Extends    | Purpose                                                                    |
| ------------------------ | ------------ | ---------- | -------------------------------------------------------------------------- |
| `Actions/BaseAction.php` | `BaseAction` | (abstract) | Base for all Command and Process Actions: transaction, log, error handling |

---

## Events

| File                   | Class       | Extends    | Purpose                                                                                                     |
| ---------------------- | ----------- | ---------- | ----------------------------------------------------------------------------------------------------------- |
| `Events/BaseEvent.php` | `BaseEvent` | (abstract) | Base for all module events — Dispatchable, eventName() for log integration, toPayload() for logging payload |

---

## Models

| File                     | Class         | Extends            | Purpose                                                        |
| ------------------------ | ------------- | ------------------ | -------------------------------------------------------------- |
| `Models/BaseModel.php`   | `BaseModel`   | `Model` (abstract) | Base for all module models — UUID PKs, HasFactory, soft-delete |
| `Models/ActivityLog.php` | `ActivityLog` | `BaseModel`        | Spatie Activity Log model with query scopes                    |

---

## Livewire Components

| File                             | Component           | Extends     | Purpose                                               |
| -------------------------------- | ------------------- | ----------- | ----------------------------------------------------- |
| `Livewire/BaseRecordManager.php` | `BaseRecordManager` | `Component` | Base CRUD table: search, sort, filter, paginate, bulk |

---

## Support Classes

Located in `app/Core/Support/`:

| Class         | File                      | Purpose                                                         |
| ------------- | ------------------------- | --------------------------------------------------------------- |
| `SmartLogger` | `Support/SmartLogger.php` | Fluent dual-channel logger: system + activity, with PII masking |
| `LangChecker` | `Support/LangChecker.php` | Dev helper: logs warnings for missing translation keys          |

---

## Authorization Policies

| File                      | Policy       | Purpose                                                                  |
| ------------------------- | ------------ | ------------------------------------------------------------------------ |
| `Policies/BasePolicy.php` | `BasePolicy` | Base for all policies — superadmin bypass, role checks, ownership checks |

---

## File Organization

```
app/Core/
├── Actions/
│   └── BaseAction.php                     ← Abstract base for all actions
├── Channels/
│   └── CustomDatabaseChannel.php          ← Custom DB notification channel
├── Console/
│   └── Commands/
│       └── ModuleDiscoverCommand.php      ← module:discover
├── Contracts/
│   ├── ColorableEnum.php                  ← CSS color variant contract
│   ├── LabelEnum.php                      ← Enum label contract
│   ├── SendsNotifications.php             ← Notification dispatch contract
│   ├── SettingsStore.php                  ← Settings key-value store contract
│   └── StatusEnum.php                     ← State machine contract
├── Data/
│   └── BaseData.php                       ← Abstract readonly DTO base
├── Entities/
│   └── BaseEntity.php                     ← Final readonly business rules base
├── Events/
│   └── BaseEvent.php                      ← Abstract base event with Dispatchable, eventName(), toPayload()
├── Exceptions/
│   ├── Concerns/
│   │   └── HasExceptionContext.php        ← Hint, context, CLI format trait
│   ├── ActionException.php                ← Business operation failures (abstract)
│   ├── AppException.php                   ← Abstract root for layered exceptions
│   ├── ModuleException.php                ← Abstract root for module invariants
│   ├── InfrastructureException.php        ← External system failures (abstract)
│   └── PresentationException.php          ← HTTP-layer failures (abstract)
├── Http/
│   ├── Controllers/
│   │   └── BaseController.php             ← Abstract base controller
│   ├── Middleware/
│   │   ├── LogContext.php                 ← Request tracing
│   │   └── SecurityHeaders.php            ← CSP, X-Frame-Options
│   └── Requests/
│       └── BaseFormRequest.php            ← Validation base form request
├── Livewire/
│   └── BaseRecordManager.php              ← CRUD table base component
├── Models/
│   ├── ActivityLog.php                    ← Spatie activity log model (extends BaseModel)
│   └── BaseModel.php                      ← Abstract base: UUID PKs, HasFactory
└── Policies/
    └── BasePolicy.php                     ← Abstract base: roles + ownership
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: None (infrastructure foundation)
- **Business Logic (`app/`)**: Located in
  [app/Core/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Core/)
- **Routing (`routes/`)**: None (uses system health check path `/up` defined in `bootstrap/app.php`)
- **Views (`views/`)**: Blade templates and layouts are in
  [resources/views/core/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/core/)
- **Testing (`tests/`)**: Feature `tests/Feature/Core/`, Unit `tests/Unit/Core/`

_For overview and business context, see [core.md](core.md)_
