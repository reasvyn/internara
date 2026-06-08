# Shared — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for **Shared** (cross-module) components.

---

## Overview

Contains concrete classes, common DTOs, global enums, concrete exceptions, static utilities, and UI components shared across all business modules. These were migrated out of `App\Core` to keep the Core namespace abstract-only.

### Module Statistics

- **Data/DTOs**: 2 (`AuditCheck`, `AuditReport`)
- **Enums**: 3 (`CsvRowResult`, `AuditCategory`, `AuditStatus`)
- **Exceptions**: 6 (`ConflictException`, `NotFoundException`, `RateLimitException`, `RejectedException`, `UnauthorizedException`, `ValidationFailedException`)
- **Livewire Components**: 2 (`LangSwitcher`, `ThemeSwitcher`)
- **Livewire Concerns**: 2 (`WithSorting`, `WithRecordSelection`)
- **Policy Concerns**: 2 (`AuthorizesRoles`, `AuthorizesOwnership`)
- **Support Classes**: 9 + `helpers.php`
- **Views**: 2 (`resources/views/livewire/lang-switcher.blade.php`, `resources/views/livewire/theme-switcher.blade.php`)
- **Tests**: 25 (Feature + Unit)

---

## Data & DTOs

Located in `app/Data/`:

| Class | Extends | Purpose |
| ----- | ------- | ------- |
| `AuditCheck` | `BaseData` | Single health audit check (status, category, label, message) |
| `AuditReport` | `BaseData` | Collection of `AuditCheck` entries with pass/fail aggregates |

---

## Enums

Located in `app/Enums/`:

| Enum | Implements | Purpose |
| ---- | ---------- | ------- |
| `CsvRowResult` | `LabelEnum` | Row import status: SUCCESS, ERROR, SKIPPED |
| `AuditCategory` | `LabelEnum` | System health categories: DATABASE, SYSTEM, ENVIRONMENT, SECURITY, HEALTH |
| `AuditStatus` | `LabelEnum` | Audit check results: PASS, FAIL, WARN |

---

## Exceptions

Located in `app/Exceptions/`. Each extends the abstract Core exception and uses `HasExceptionContext`:

| Exception | Extends | HTTP | Purpose |
| --------- | ------- | ---- | ------- |
| `ConflictException` | `ActionException` | 409 | Duplicate resource / conflicting operation |
| `NotFoundException` | `PresentationException` | 404 | Resource not found |
| `RateLimitException` | `InfrastructureException` | 429 | Rate limit exceeded |
| `RejectedException` | `ModuleException` | 400 | Domain invariant violation |
| `UnauthorizedException` | `PresentationException` | 403 | Not authorized |
| `ValidationFailedException` | `ActionException` | 422 | Input validation failed |

---

## Livewire Components

Located in `app/Livewire/`:

| Component | Description |
| --------- | ----------- |
| `LangSwitcher` | Bilingual (EN/ID) selector, updates session locale |
| `ThemeSwitcher` | Dark mode toggle (system, light, dark) |

### Concerns

| Trait | Location | Purpose |
| ----- | -------- | ------- |
| `WithSorting` | `app/Livewire/Concerns/WithSorting.php` | Column sorting state management |
| `WithRecordSelection` | `app/Livewire/Concerns/WithRecordSelection.php` | Checkbox row selection for bulk actions |

---

## Policy Concerns

Located in `app/Policies/Concerns/`:

| Trait | Purpose |
| ----- | ------- |
| `AuthorizesRoles` | Quick role-based authorization by role string |
| `AuthorizesOwnership` | Ownership check comparing primary/foreign keys |

---

## Support Classes

Located in `app/Support/`:

| Class | Purpose |
| ----- | ------- |
| `CacheKeys` | Central registry of all cache key constants |
| `Color` | Hex-to-RGB, HSL conversion, color manipulation |
| `CsvHandler` | CSV parsing, heading validation, export generation |
| `Environment` | Environment detection (staging, production, dev) |
| `HandlesActionErrors` | Generic try-catch-log-rethrow for actions |
| `HasModelStatuses` | Historical status column utilities |
| `Integrity` | Composer config and security assessment |
| `PasswordRules` | Common password strength validation rules |
| `PiiMasker` | Regex-based PII redaction (IDs, phone numbers) |
| `helpers.php` | `setting()`, `brand()`, `app_info()` helper functions |

---

## File Organization

```
app/
├── Data/
│   ├── AuditCheck.php
│   └── AuditReport.php
├── Enums/
│   ├── AuditCategory.php
│   ├── AuditStatus.php
│   └── CsvRowResult.php
├── Exceptions/
│   ├── ConflictException.php
│   ├── NotFoundException.php
│   ├── RateLimitException.php
│   ├── RejectedException.php
│   ├── UnauthorizedException.php
│   └── ValidationFailedException.php
├── Livewire/
│   ├── Concerns/
│   │   ├── WithRecordSelection.php
│   │   └── WithSorting.php
│   ├── LangSwitcher.php
│   └── ThemeSwitcher.php
├── Policies/
│   └── Concerns/
│       ├── AuthorizesOwnership.php
│       └── AuthorizesRoles.php
└── Support/
    ├── CacheKeys.php
    ├── Color.php
    ├── CsvHandler.php
    ├── Environment.php
    ├── HandlesActionErrors.php
    ├── HasModelStatuses.php
    ├── Integrity.php
    ├── PasswordRules.php
    ├── PiiMasker.php
    └── helpers.php
```

---

## Architectural Integration

- **Business Logic**: `app/Data/`, `app/Enums/`, `app/Exceptions/`, `app/Livewire/`, `app/Policies/`, `app/Support/`
- **Views**: `resources/views/livewire/`
- **Testing**: `tests/Feature/Data/`, `tests/Unit/Enums/`, `tests/Unit/Support/`, `tests/Unit/Livewire/`, `tests/Unit/Policies/`

*For overview and business context, see [shared.md](shared.md).*
