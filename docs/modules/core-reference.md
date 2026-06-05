# Core — Technical Reference

> Last updated: 2026-06-04
> Changes: Moved system:* commands to SysAdmin (only module:discover remains in Core); updated stat from 4→1 commands

Detailed structural and implementation reference for the **Core** module.

---

## Overview

Provides foundational infrastructure, base classes, contracts, and application-wide utilities that every other module depends on.

### Module Statistics
- **Contracts**: 5 (LabelEnum, StatusEnum, ColorableEnum, SendsNotifications, SettingsStore)
- **Base Classes**: 8 (BaseModel, BaseAction, BaseEntity, BasePolicy, BaseRecordManager, BaseController, BaseFormRequest, BaseData)
- **Exceptions**: 12 files (dual hierarchy: AppException tree + DomainException tree, plus HasExceptionContext trait)
- **Middleware**: 2 in Core (SecurityHeaders, LogContext) — RequireSetupAccess is in Academics, SetLocale is in SysAdmin
- **Support Classes**: 13 (SmartLogger, PiiMasker, CacheKeys, Environment, CsvHandler, Color, Theme, Locale, LangChecker, HandlesActionErrors, PasswordRules, Integrity, HasModelStatuses)
- **Models**: 2 (ActivityLog, BaseModel is abstract)
- **Enums**: 3 (CsvRowResult, AuditCategory, AuditStatus)
- **Livewire Components**: 3 + 2 concerns (BaseRecordManager, LangSwitcher, ThemeSwitcher, WithSorting, WithRecordSelection)
- **Policies**: 1 BasePolicy + 2 concern traits (AuthorizesRoles, AuthorizesOwnership)
- **Data/DTOs**: 3 (BaseData, AuditCheck, AuditReport)
- **Channels**: 1 (CustomDatabaseChannel)
- **Console Commands**: 1 (module:discover) — system:* commands moved to SysAdmin
- **Views**: 29 (7 layouts, 15 UI components, 5 widgets, 2 top-level)
- **Tests**: 14 (9 Feature + 5 Unit)
- **Submodules**: 0 (Core has no submodules — it provides infrastructure)
- **Routes**: 0 (Core has no dedicated route file)

---

## Contracts (Layer 3)

Located in `app/Core/Contracts/`:

| Contract | Purpose | Implemented By |
|---|---|---|
| `LabelEnum` | Provides `label(): string` for UI display | All enums across all modules |
| `StatusEnum` | State machine: `canTransitionTo()`, `isTerminal()`, `validTransitions()` | State machine enums (e.g., ProgramStatus, AccountStatus) |
| `ColorableEnum` | Provides CSS color variant (`color(): string`) | Enums with visual state representation |
| `SendsNotifications` | Binds notification dispatch to `SendNotificationAction` | Notification-sending infrastructure |

---

## Exceptions (Layer 3)

Located in `app/Core/Exceptions/`. Dual hierarchy, both using `HasExceptionContext` trait:

### AppException Tree

```
AppException (abstract, extends RuntimeException)
├── ActionException (abstract) — business operation failed
│   ├── ConflictException — duplicate or conflicting state
│   └── ValidationFailedException — input validation failure
├── InfrastructureException (abstract) — external system failure
│   └── RateLimitException — rate limit exceeded
└── PresentationException (abstract) — HTTP-layer failure
    ├── NotFoundException — resource not found
    └── UnauthorizedException — access denied
```

### DomainException Tree (separate, isolated)

```
DomainException (abstract, extends RuntimeException)
└── RejectedException — module invariant violated (e.g., invalid state transition)
```

### Usage Guide

| Scenario | Exception | Hierarchy |
|---|---|---|
| Input validation failed | `ValidationFailedException` | AppException |
| Duplicate record | `ConflictException` | AppException |
| Permission denied | `UnauthorizedException` | AppException |
| Resource not found | `NotFoundException` | AppException |
| External API timeout | `InfrastructureException` or `RateLimitException` | AppException |
| Invalid state transition | `RejectedException` | DomainException |
| Module invariant violated | `RejectedException` | DomainException |

---

## Base Classes (Layer 4)

Located in `app/Core/`:

| Class | File | Purpose | Mandatory For |
|---|---|---|---|
| `BaseModel` | `Models/BaseModel.php` | UUID PKs, HasFactory, soft-delete support, global scopes | All models (except User which extends `Authenticatable`) |
| `BaseAction` | `Actions/BaseAction.php` | Transaction management, activity logging, `HandlesActionErrors` | All Command and Process Actions |
| `BaseEntity` | `Entities/BaseEntity.php` | `final readonly` business rules, zero framework dependencies | All entities |
| `BasePolicy` | `Policies/BasePolicy.php` | Superadmin `before()` bypass, role checks (`AuthorizesRoles`), ownership checks (`AuthorizesOwnership`) | All policies |
| `BaseRecordManager` | `Livewire/BaseRecordManager.php` | CRUD table: search, sort, filter, paginate, bulk actions, row selection | All CRUD list Livewire components |
| `BaseController` | `Http/Controllers/BaseController.php` | Common controller utilities | All HTTP controllers |
| `BaseFormRequest` | `Http/Requests/BaseFormRequest.php` | Validation without redirect (throws `ValidationFailedException`) | All form requests |
| `BaseData` | `Data/BaseData.php` | Abstract readonly DTO with `fromArray()` and `toArray()` | All data transfer objects |

---

## Middleware

Core provides 2 middleware in `app/Core/Http/Middleware/`:

| Middleware | File | Purpose |
|---|---|---|
| `SecurityHeaders` | `Middleware/SecurityHeaders.php` | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| `LogContext` | `Middleware/LogContext.php` | Request tracing: request_id, method, URL, IP, user_id |

Additional middleware used globally (located in other modules):
- `RequireSetupAccessMiddleware` — `app/Academics/Http/Middleware/RequireSetupAccessMiddleware.php` — redirects to /setup when not installed
- `SetLocaleMiddleware` — `app/SysAdmin/Setting/Http/Middleware/SetLocaleMiddleware.php` — language preference from session/database

---

## Actions

| File | Class | Extends | Purpose |
|---|---|---|---|
| `Actions/BaseAction.php` | `BaseAction` | (abstract) | Base for all Command and Process Actions: transaction, log, error handling |

---

## Models

| File | Class | Extends | Purpose |
|---|---|---|---|
| `Models/BaseModel.php` | `BaseModel` | `Model` (abstract) | Base for all module models — UUID PKs, HasFactory, soft-delete |
| `Models/ActivityLog.php` | `ActivityLog` | `BaseModel` | Spatie Activity Log model with query scopes |

---

## Livewire Components

| File | Component | Extends | Purpose |
|---|---|---|---|
| `Livewire/BaseRecordManager.php` | `BaseRecordManager` | `Component` | Base CRUD table: search, sort, filter, paginate, bulk |
| `Livewire/LangSwitcher.php` | `LangSwitcher` | `Component` | Bilingual language toggle |
| `Livewire/ThemeSwitcher.php` | `ThemeSwitcher` | `Component` | Light/dark/system theme toggle |

---

## Support Classes

Located in `app/Core/Support/` (13 files):

| Class | File | Purpose |
|---|---|---|
| `SmartLogger` | `Support/SmartLogger.php` | Fluent dual-channel logger: system + activity, with PII masking |
| `PiiMasker` | `Support/PiiMasker.php` | Masks PII (email, phone, ID numbers) in log output |
| `CacheKeys` | `Support/CacheKeys.php` | Centralized cache key constants — all cache keys defined here |
| `Environment` | `Support/Environment.php` | Environment detection (debug, development, production) |
| `CsvHandler` | `Support/CsvHandler.php` | CSV export, import, and template download with header validation |
| `Theme` | `Support/Theme.php` | Color resolution from settings into CSS custom properties |
| `Color` | `Support/Color.php` | Color utility (hex/rgb, luminance, contrast, lighten/darken) |
| `Locale` | `Support/Locale.php` | Locale management (en/id) |
| `LangChecker` | `Support/LangChecker.php` | Dev helper: logs warnings for missing translation keys |
| `HandlesActionErrors` | `Support/HandlesActionErrors.php` | Trait: try-catch-log-rethrow for action error handling |
| `PasswordRules` | `Support/PasswordRules.php` | Password validation rule presets |
| `Integrity` | `Support/Integrity.php` | Composer.json attribution verification |
| `HasModelStatuses` | `Support/HasModelStatuses.php` | **(Deprecated)** Migration to plain StatusEnum columns |

---

## Authorization Policies

| File | Policy | Purpose |
|---|---|---|
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
│       └── DomainDiscoverCommand.php      ← module:discover
├── Contracts/
│   ├── ColorableEnum.php                  ← CSS color variant contract
│   ├── LabelEnum.php                      ← Enum label contract
│   ├── SendsNotifications.php             ← Notification dispatch contract
│   ├── SettingsStore.php                  ← Settings key-value store contract
│   └── StatusEnum.php                     ← State machine contract
├── Data/
│   ├── AuditCheck.php                     ← Single audit check result (DTO)
│   ├── AuditReport.php                    ← Collection of audit checks (DTO)
│   └── BaseData.php                       ← Abstract readonly DTO base
├── Entities/
│   └── BaseEntity.php                     ← Final readonly business rules base
├── Enums/
│   ├── AuditCategory.php                  ← System audit categories
│   ├── AuditStatus.php                    ← PASS/FAIL/WARN audit statuses
│   └── CsvRowResult.php                   ← CSV import row outcomes
├── Exceptions/
│   ├── Concerns/
│   │   └── HasExceptionContext.php        ← Hint, context, CLI format trait
│   ├── ActionException.php                ← Business operation failures (abstract)
│   ├── AppException.php                   ← Abstract root for layered exceptions
│   ├── ConflictException.php              ← Duplicate/conflicting state
│   ├── DomainException.php                ← Abstract root for module invariants
│   ├── InfrastructureException.php        ← External system failures (abstract)
│   ├── NotFoundException.php              ← Resource not found
│   ├── PresentationException.php          ← HTTP-layer failures (abstract)
│   ├── RateLimitException.php             ← Rate limit exceeded
│   ├── RejectedException.php              ← Module invariant violation
│   ├── UnauthorizedException.php          ← Access denied
│   └── ValidationFailedException.php      ← Input validation failures
├── Http/
│   ├── Controllers/
│   │   └── BaseController.php             ← Abstract base controller
│   ├── Middleware/
│   │   ├── LogContext.php                 ← Request tracing
│   │   └── SecurityHeaders.php            ← CSP, X-Frame-Options
│   └── Requests/
│       └── BaseFormRequest.php            ← Validation throws ValidationFailedException
├── Livewire/
│   ├── Concerns/
│   │   ├── WithRecordSelection.php        ← Row selection for bulk actions
│   │   └── WithSorting.php                ← Sortable column headers
│   ├── BaseRecordManager.php              ← CRUD table base component
│   ├── LangSwitcher.php                   ← Bilingual toggle
│   └── ThemeSwitcher.php                  ← Light/dark/system theme toggle
├── Models/
│   ├── ActivityLog.php                    ← Spatie activity log model (extends BaseModel)
│   └── BaseModel.php                      ← Abstract base: UUID PKs, HasFactory
├── Policies/
│   ├── Concerns/
│   │   ├── AuthorizesOwnership.php        ← Ownership check trait
│   │   └── AuthorizesRoles.php            ← Role check trait
│   └── BasePolicy.php                     ← Abstract base: roles + ownership
└── Support/
    ├── CacheKeys.php                      ← Centralized cache key constants
    ├── Color.php                          ← Color utility (hex/rgb, contrast)
    ├── CsvHandler.php                     ← CSV export/import/template
    ├── Environment.php                    ← Environment detection
    ├── HandlesActionErrors.php            ← Try-catch-log-rethrow trait
    ├── HasModelStatuses.php               ← (Deprecated) Status enum migration
    ├── Integrity.php                      ← Composer.json attribution verification
    ├── LangChecker.php                    ← Translation key checker
    ├── Locale.php                         ← Locale management
    ├── PasswordRules.php                  ← Password validation rule presets
    ├── PiiMasker.php                      ← PII masking for logs
    ├── SmartLogger.php                    ← Fluent dual-channel logger
    └── Theme.php                          ← Theme CSS variable resolution
```

---

*For overview and business context, see [core.md](core.md)*
