# Shared — Technical Reference

> Last updated: 2026-06-05
> Changes: Created shared module reference containing detailed class mappings, namespaces, and namespaces migration out of Core

Detailed structural and implementation reference for the **Shared** (cross-module) components.

---

## Overview

Contains concrete classes, common DTOs, global enums, static utilities, and UI components shared across multiple business modules.

### Shared Statistics
- **Data/DTOs**: 2 (`AuditCheck`, `AuditReport`)
- **Enums**: 3 (`CsvRowResult`, `AuditCategory`, `AuditStatus`)
- **Exceptions**: 6 (`ConflictException`, `NotFoundException`, `RateLimitException`, `RejectedException`, `UnauthorizedException`, `ValidationFailedException`)
- **Livewire Components**: 2 + 2 concerns (`LangSwitcher`, `ThemeSwitcher`, `WithSorting`, `WithRecordSelection`)
- **Policies Concerns**: 2 (`AuthorizesRoles`, `AuthorizesOwnership`)
- **Support Classes**: 11 (`CacheKeys`, `Color`, `CsvHandler`, `Environment`, `HandlesActionErrors`, `HasModelStatuses`, `Locale`, `PasswordRules`, `PiiMasker`, `Theme`, `Integrity`)
- **Views**: 2 (`resources/views/livewire/lang-switcher.blade.php`, `resources/views/livewire/theme-switcher.blade.php`)
- **Tests**: 11 unit/feature tests under `tests/{Feature,Unit}/{Component}/`

---

## Data & DTOs

Located in `app/Data/`:

| Class | Extends | Purpose |
|---|---|---|
| `AuditCheck` | `App\Core\Data\BaseData` | Represents a single check in the system health audit (stores status, category, label, and message). |
| `AuditReport` | `App\Core\Data\BaseData` | Represents a collection of `AuditCheck` entries, calculating pass/fail aggregates. |

---

## Enums

Located in `app/Enums/`:

| Enum | Implements | Purpose |
|---|---|---|
| `CsvRowResult` | `App\Core\Contracts\LabelEnum` | Status of individual row importing: SUCCESS, ERROR, SKIPPED. |
| `AuditCategory` | `App\Core\Contracts\LabelEnum` | System health categories: DATABASE, SYSTEM, ENVIRONMENT, SECURITY, HEALTH. |
| `AuditStatus` | `App\Core\Contracts\LabelEnum` | Result status for audit checks: PASS, FAIL, WARN. |

---

## Exceptions

Located in `app/Exceptions/`. These extend the abstract exceptions defined in `app/Core/Exceptions/` and use `HasExceptionContext`:

| Exception | Extends | HTTP Status Code | Purpose |
|---|---|---|---|
| `ConflictException` | `App\Core\Exceptions\ActionException` | 409 | Duplicate resource or conflicting operation (e.g. active placement collision). |
| `NotFoundException` | `App\Core\Exceptions\PresentationException` | 404 | Requested resource does not exist. |
| `RateLimitException` | `App\Core\Exceptions\InfrastructureException` | 429 | Rate limit limit exceeded. |
| `RejectedException` | `App\Core\Exceptions\DomainException` | 400 (or action error) | Domain invariant violation (e.g. invalid state machine transition). |
| `UnauthorizedException` | `App\Core\Exceptions\PresentationException` | 403 | User is not authorized to perform the action. |
| `ValidationFailedException` | `App\Core\Exceptions\ActionException` | 422 | Input validation failed. |

---

## Livewire Components

Located in `app/Livewire/`:

### UI Components
- **`LangSwitcher.php`** — Bilingual (English/Indonesian) selector that updates session locale state.
- **`ThemeSwitcher.php`** — Dark mode toggle supporting system, light, and dark presets.

### Concerns (Traits)
Located in `app/Livewire/Concerns/`:
- **`WithSorting.php`** — Helper trait for managing column sorting state (sort field and direction) in data tables.
- **`WithRecordSelection.php`** — Helper trait for managing checkboxes and row selections in bulk action tables.

---

## Policies Concerns

Located in `app/Policies/Concerns/`:

- **`AuthorizesRoles.php`** — Trait allowing policies to quickly authorize access based on user role strings.
- **`AuthorizesOwnership.php`** — Trait checking if the user owns a resource model (compares primary/foreign keys).

---

## Support Classes

Located in `app/Support/`:

| Class | Purpose |
|---|---|
| `CacheKeys` | Central registry of all cache keys constants (e.g. `SETUP_INSTALLED`, `THEME_CSS_VARIABLES`). |
| `Color` | Hex-to-RGB, HSL conversion, and color manipulation library for the dynamic theme engine. |
| `CsvHandler` | Stateless CSV file parsing, heading validation, and export response generation. |
| `Environment` | Helper detecting if the system is running in staging, production, or developer environment. |
| `HandlesActionErrors` | Trait providing generic `try-catch-log-rethrow` for command action operations. |
| `HasModelStatuses` | Utility managing statuses for historical columns. |
| `Locale` | Helper managing localization codes, browser-detecting locale, and fallback settings. |
| `PasswordRules` | Common password strength validation options. |
| `PiiMasker` | RegEx-based utility to redact sensitive student personal info from log outputs. |
| `Theme` | Resolves database values to dynamic CSS properties for the custom brand colors. |
| `Integrity` | Helper assessing composer config and security. |

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
    ├── Locale.php
    ├── PasswordRules.php
    ├── PiiMasker.php
    └── Theme.php
```

---

## Namespace Migration Details

All files under `App\Data`, `App\Enums`, `App\Exceptions`, `App\Livewire`, `App\Policies`, and `App\Support` were successfully migrated out of the `App\Core` namespace.

For example, imports such as:
- `App\Core\Support\CacheKeys` -> migrated to `App\Support\CacheKeys`
- `App\Core\Exceptions\NotFoundException` -> migrated to `App\Exceptions\NotFoundException`
- `App\Core\Enums\CsvRowResult` -> migrated to `App\Enums\CsvRowResult`
- `App\Core\Livewire\LangSwitcher` -> migrated to `App\Livewire\LangSwitcher`

This ensures that the `App\Core` namespace contains strictly only abstract base classes, system contracts, and foundation middleware/commands.
