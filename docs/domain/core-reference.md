# Core — API Reference
> Last updated: 2026-06-03
> Changes: merge Shared domain into Core — add Livewire components, Blade views (layouts/UI/widgets), ex-Shared support classes, CsvRowResult enum; update total file count and Where to Find It

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 55 files — ✅ 55 Implemented (+ 28 Blade views)

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
| `Core/Enums/AuditCategory.php` | `AuditCategory` | `LabelEnum` | Audit check categories: REQUIREMENTS, PERMISSIONS, DATABASE, TERMINAL, RECOMMENDATIONS. `isCritical()` returns `true` for REQUIREMENTS, PERMISSIONS, DATABASE |
| `Core/Enums/AuditStatus.php` | `AuditStatus` | `LabelEnum` | Audit check pass/fail/warn status. `symbol()` returns '✓', '✗', or '⚠' for CLI output |
| `Core/Enums/CsvRowResult.php` | `CsvRowResult` | `LabelEnum` | CSV import row result: CREATED or SKIPPED |

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

### Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Core/Livewire/ThemeSwitcher.php` | `ThemeSwitcher` | `Component` | Light/dark/system theme toggle with cookie persistence |
| `Core/Livewire/LangSwitcher.php` | `LangSwitcher` | `Component` | Language toggle — delegates to `Locale::set()` for cookie persistence |

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
| `Core/Support/CsvHandler.php` | `CsvHandler` | CSV export (streamed), import with header validation, template download |
| `Core/Support/Environment.php` | `Environment` | Centralized environment detection — `isDebugMode()`, `isDevelopment()`, `isProduction()`, etc. |
| `Core/Support/HasModelStatuses.php` | `HasModelStatuses` | Trait — bridges legacy Spatie HasStatuses with typed StatusEnum. @deprecated |
| `Core/Support/LangChecker.php` | `LangChecker` | @deprecated Implements Translator contract — logs warning on missing translation keys (dev only) |
| `Core/Support/Locale.php` | `Locale` | Locale management — set, current, all, keys, isSupported, metadata. Cookie-based persistence |
| `Core/Support/Theme.php` | `Theme` | Theme/color resolution — defaults, presets, all, cssVariables (cached) |

## Blade Views (`resources/views/core/`)

### Layouts (`x-core::layouts.*`)

| File | Description |
|---|---|
| `core/layouts/base.blade.php` | HTML document skeleton — meta tags, favicon, Vite assets, CSS custom properties for theme, theme init script, flasher render, Livewire event listeners |
| `core/layouts/base/head.blade.php` | `<head>` partial — preconnect hints, meta tags, CSRF token, title, favicon, manifest, Vite assets, head stack |
| `core/layouts/base/footer.blade.php` | Footer partial — credit line with optional full-width mode |
| `core/layouts/app.blade.php` | Authenticated application shell — sidebar drawer, header with breadcrumb context, main content area, footer |
| `core/layouts/guest.blade.php` | Unauthenticated landing shell — brand header with theme/lang toggles, main content, footer |
| `core/layouts/sidebar.blade.php` | Collapsible sidebar drawer — role-based menu groups, brand logo, mobile theme/lang toggles |
| `core/layouts/header.blade.php` | Sticky top header — mobile hamburger, page title, navbar actions (theme/lang/user) |

### Blade UI Components (`x-core::ui.*`)

| File | Description |
|---|---|
| `core/ui/brand.blade.php` | Brand logo with name and optional tagline |
| `core/ui/logo.blade.php` | Brand logo image only |
| `core/ui/credits.blade.php` | Footer credits with app signature |
| `core/ui/theme-switcher.blade.php` | Theme toggle wrapper (light/dark/system) |
| `core/ui/lang-switcher.blade.php` | Language toggle wrapper (EN/ID) |
| `core/ui/navbar-actions.blade.php` | Navbar action items (theme, lang, notifications, user) |
| `core/ui/confirm.blade.php` | Confirmation dialog |
| `core/ui/display-field.blade.php` | Read-only display field with label and optional icon |
| `core/ui/page-header.blade.php` | Page header with title and actions |
| `core/ui/avatar.blade.php` | User avatar with fallback initials |
| `core/ui/credit.blade.php` | Footer credit line |
| `core/ui/markdown-editor.blade.php` | Markdown editor component |
| `core/ui/record-manager.blade.php` | Data table with bulk actions |
| `core/ui/selection-bar.blade.php` | Selection bar for bulk operations |

### Widgets (`x-core::widgets.*`)

| File | Description |
|---|---|
| `core/widgets/stat-card.blade.php` | Displays a numeric statistic with icon and color |
| `core/widgets/profile-summary.blade.php` | User avatar, name, role with optional edit button |
| `core/widgets/quick-link.blade.php` | Navigation link with icon and chevron |
| `core/widgets/action-button.blade.php` | Full-width action button for navigation |
| `core/widgets/empty-state.blade.php` | Empty state placeholder with icon and text |

### Livewire Views

| File | Description |
|---|---|
| `core/theme-switcher.blade.php` | Theme switcher dropdown — light/dark/system options with icon indicators, wire:click delegates to ThemeSwitcher component |
| `core/lang-switcher.blade.php` | Language switcher dropdown — EN/ID options with locale abbreviation, wire:click delegates to LangSwitcher component |

## Dependency Graph

```
                   ┌─────────────────────────────────┐
                   │      22 Business Domains         │
                   │  (Auth, School, Internship, ...)  │
                   └──────────────┬──────────────────┘
                                  │ depends on
                                  ▼
                   ┌─────────────────────────────────────┐
                   │            Core Domain               │
                   │  ┌───────┬──────┬────────┬────────┐  │
                   │  │Contract│Base │Infra   │Cross-  │  │
                   │  │  ts    │Classes│structure│domain  │  │
                   │  │        │      │        │Utilities│  │
                   │  └───────┴──────┴────────┴────────┘  │
                   └──────────────┬──────────────────────┘
                                  │ depends on
                                  ▼
                   ┌─────────────────────────────────┐
                   │   Laravel + Spatie + PHP 8.4     │
                   └─────────────────────────────────┘
```

Core is the root of the entire dependency graph. Nothing depends on it that isn't in the
Laravel framework, Spatie packages, or PHP standard library.

The former **Shared domain** was merged into Core. All cross-domain utilities (CsvHandler,
Environment, Locale, Theme, LangChecker, HasModelStatuses, CsvRowResult) and Blade views
(layouts, UI components, widgets) now live in Core.

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
- `app/Domain/Core/Enums/CsvRowResult.php` — CSV import result enum
- `app/Domain/Core/Livewire/LangSwitcher.php` — language switcher component
- `app/Domain/Core/Livewire/ThemeSwitcher.php` — theme switcher component
- `app/Domain/Core/Support/CsvHandler.php` — CSV handler
- `app/Domain/Core/Support/Environment.php` — environment detection
- `app/Domain/Core/Support/LangChecker.php` — translation key checker (deprecated — use Laravel's built-in trans())
- `app/Domain/Core/Support/Locale.php` — locale management
- `app/Domain/Core/Support/Theme.php` — theme/color resolution
- `app/Domain/Core/Support/HasModelStatuses.php` — legacy Spatie bridge trait
- `resources/views/core/` — Blade views: layouts (7), UI components (14), widgets (5), Livewire views (2)
- `routes/web/core.php` — deleted (was a placeholder; Core owns no routes)

> **Note:** Core owns no HTTP routes. The master `routes/web.php` does not require a Core route file. There are 23 domain route files. Core's Blade views are at `resources/views/core/` and registered as `x-core::*` namespace.
