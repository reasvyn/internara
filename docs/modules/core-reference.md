# Core — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Core** module.

---

## Overview

Provides foundational infrastructure, base classes, contracts, exception hierarchy, middleware, and request lifecycle utilities that every other module depends on.

### Module Statistics

- **Contracts**: 5 (`LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`, `SettingsStore`)
- **Base Classes**: 9 (`BaseModel`, `BaseAction`, `BaseEntity`, `BasePolicy`, `BaseRecordManager`, `BaseController`, `BaseFormRequest`, `BaseData`, `BaseEvent`)
- **Exceptions**: 12 files (abstract hierarchy + concrete in Shared)
- **Middleware**: 2 (`SecurityHeaders`, `LogContext`)
- **Support**: 12 classes (`SmartLogger`, `LangChecker`, `CacheKeys`, `Color`, `CsvHandler`, `Environment`, `HandlesActionErrors`, `HasModelStatuses`, `Integrity`, `PasswordRules`, `PiiMasker`, `helpers.php`)
- **Models**: 2 (`ActivityLog` — `BaseModel` is abstract)
- **Events**: 1 (`BaseEvent`, abstract)
- **Livewire Components**: 1 (`BaseRecordManager`)
- **Policies**: 1 (`BasePolicy`) + 2 concern traits
- **Data/DTOs**: 1 (`BaseData`) + 2 concrete in Shared
- **Channels**: 1 (`CustomDatabaseChannel`)
- **Console Commands**: 1 (`module:discover`)
- **Views**: 25 (7 layouts, 13 UI, 5 widgets)
- **Tests**: 29 (6 Feature + 23 Unit)
- **Routes**: 0 (health check at `/up` in `bootstrap/app.php`)

---

## Contracts

Located in `app/Core/Contracts/`:

| Contract | Purpose | Implemented By |
| -------- | ------- | -------------- |
| `LabelEnum` | `label(): string` for UI display | All enums across all modules |
| `StatusEnum` | State machine: `canTransitionTo()`, `isTerminal()`, `validTransitions()` | State machine enums |
| `ColorableEnum` | CSS color variant (`color(): string`) | Enums with visual state |
| `SendsNotifications` | Binds notification dispatch to `SendNotificationAction` | Notification infrastructure |
| `SettingsStore` | Key-value store for runtime configuration | Settings `Setting` model |

---

## Base Classes

Located in `app/Core/`:

| Class | Path | Purpose | Mandatory For |
| ----- | ---- | ------- | ------------- |
| `BaseModel` | `Models/BaseModel.php` | UUID PKs, HasFactory, soft-delete, global scopes | All models (User extends `Authenticatable` with manual HasUuids) |
| `BaseAction` | `Actions/BaseAction.php` | Transaction management, activity logging, error handling | All Command & Process Actions |
| `BaseEntity` | `Entities/BaseEntity.php` | `final readonly`, zero framework dependencies, `fromModel()` bridge | All entities |
| `BasePolicy` | `Policies/BasePolicy.php` | Superadmin `before()` bypass, role checks, ownership checks | All policies |
| `BaseRecordManager` | `Livewire/BaseRecordManager.php` | CRUD table: search, sort, filter, paginate, bulk actions, row selection | All CRUD Livewire components |
| `BaseController` | `Http/Controllers/BaseController.php` | Common controller utilities | All HTTP controllers |
| `BaseFormRequest` | `Http/Requests/BaseFormRequest.php` | Validation without redirect, throws `ValidationFailedException` | All form requests |
| `BaseData` | `Data/BaseData.php` | Abstract readonly DTO with `fromArray()`, `toArray()`, `from()` | All DTOs |
| `BaseEvent` | `Events/BaseEvent.php` | `Dispatchable`, `eventName()`, `toPayload()` auto-extracts public properties | All events |

---

## Exception Hierarchy

Base hierarchy in `app/Core/Exceptions/`, concrete implementations in `app/Exceptions/` (Shared):

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

All use `HasExceptionContext` trait (hint, context, CLI format).

---

## Middleware

| Middleware | Path | Purpose |
| ---------- | ---- | ------- |
| `SecurityHeaders` | `Http/Middleware/SecurityHeaders.php` | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| `LogContext` | `Http/Middleware/LogContext.php` | Request tracing: request_id, method, URL, IP, user_id |

---

## Support Classes

| Class | Path | Purpose |
| ----- | ---- | ------- |
| `SmartLogger` | `Core/Support/SmartLogger.php` | Dual-channel logger: system + activity, PII masking |
| `LangChecker` | `Core/Support/LangChecker.php` | Dev helper: warns on missing translation keys |

Cross-referenced from Shared (see [shared-reference.md](shared-reference.md)):
`CacheKeys`, `Color`, `CsvHandler`, `Environment`, `HandlesActionErrors`, `HasModelStatuses`, `Integrity`, `PasswordRules`, `PiiMasker`, `helpers.php`

---

## File Organization

```
app/Core/
├── Actions/
│   └── BaseAction.php
├── Channels/
│   └── CustomDatabaseChannel.php
├── Console/Commands/
│   └── ModuleDiscoverCommand.php
├── Contracts/
│   ├── ColorableEnum.php
│   ├── LabelEnum.php
│   ├── SendsNotifications.php
│   ├── SettingsStore.php
│   └── StatusEnum.php
├── Data/
│   └── BaseData.php
├── Entities/
│   └── BaseEntity.php
├── Events/
│   └── BaseEvent.php
├── Exceptions/
│   ├── Concerns/HasExceptionContext.php
│   ├── ActionException.php
│   ├── AppException.php
│   ├── InfrastructureException.php
│   ├── ModuleException.php
│   └── PresentationException.php
├── Http/
│   ├── Controllers/BaseController.php
│   ├── Middleware/LogContext.php
│   ├── Middleware/SecurityHeaders.php
│   └── Requests/BaseFormRequest.php
├── Livewire/
│   └── BaseRecordManager.php
├── Models/
│   ├── ActivityLog.php
│   └── BaseModel.php
├── Policies/
│   ├── BasePolicy.php
│   └── Concerns/
│       ├── AuthorizesOwnership.php
│       └── AuthorizesRoles.php
└── Support/
    ├── SmartLogger.php
    └── LangChecker.php
```

---

## Architectural Integration

- **Business Logic**: `app/Core/`
- **Routing**: None (health check `/up` in `bootstrap/app.php`)
- **Views**: `resources/views/core/`
- **Testing**: `tests/Feature/Core/`, `tests/Unit/Core/`

*For overview and business context, see [core.md](core.md).*
