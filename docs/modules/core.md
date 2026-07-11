# Core — Base Classes, Contracts & Exceptions

> **Last updated:** 2026-07-11 **Changes:** sync — replace class-by-class listing with conceptual overview

## Description

Foundational infrastructure, abstract base classes, contracts, cross-module utilities, concrete
implementations, and architectural mechanisms that every other module depends on.

## Purpose & Boundary

Core provides the non-negotiable foundation for the entire application. It defines the architectural
patterns (Action Triad, Entity separation, exception hierarchy) and enforces them through PHPStan
rules and code review. Core has **zero dependencies** on any business module — it depends only on
Laravel, Spatie packages, and PHP 8.4.

The module is split into two conceptual layers:

- **Infrastructure (abstract):** Base classes, contracts, abstract exceptions, middleware — the
  framework every module builds on.
- **Concrete layer:** DTOs, enums, concrete exceptions, global UI components, policy concerns,
  support utilities, helper functions — reusable implementations that any module may import.

Out of scope: domain-specific logic, domain enums, application settings, user-facing features.

## Submodules

Core has no submodules. Code is organized by architectural layer:

**Abstract infrastructure** (contracts, base classes, interfaces): `Actions/BaseAction.php`,
`Models/BaseModel.php`, `Entities/BaseEntity.php`, `Policies/BasePolicy.php`,
`Events/BaseEvent.php`, `Data/BaseData.php`, `Http/Controllers/BaseController.php`,
`Http/Requests/BaseFormRequest.php`, `Contracts/` (LabelEnum, StatusEnum, ColorableEnum,
SendsNotifications, SettingsStore).

**Concrete implementations** (reusable across all modules): `Data/ActionResponse`,
`Data/AuditCheck`, `Data/AuditReport`, `Enums/` (CsvRowResult, AuditCategory, AuditStatus),
`Exceptions/` (full dual hierarchy with HasExceptionContext), `Livewire/BaseRecordManager` and
concerns (WithSorting, WithRecordSelection), `Policies/Concerns/` (AuthorizesRoles,
AuthorizesOwnership), `Http/Middleware/` (SecurityHeaders, LogContext), `Support/` (Color,
CsvHandler, Environment, PasswordRules, PiiMasker, Spotlight).

**Infrastructure services** (system-level, not domain logic): `Services/SmartLogger` (dual-channel
audit logging with PII masking), `Services/AppInfo` (static metadata), `Services/AppIntegrity`
(author verification), `Services/LangChecker` (missing translation detection),
`Services/ModuleDiscoverService` (dynamic module registration).

**Shared models**: `Models/ActivityLog` (SmartLogger persistence), `Models/BaseAuthenticatable`
(User model base with manual HasUuids).

**Helper functions**: `app/Core/Support/helpers.php` provides `app_info()`. The `setting()` and
`brand()` helpers live in `app/Settings/Support/helpers.php`.

## Key Concepts

### Separation of Abstract and Concrete

Core provides abstract contracts and base classes alongside concrete implementations under the same
`app/Core/` namespace. The distinction prevents framework-level abstractions from being polluted
with application-specific defaults. `Data/`, `Enums/`, `Exceptions/`, `Livewire/`,
`Policies/Concerns/`, and `Support/` contain concrete classes, while `Contracts/`,
`Actions/BaseAction.php`, `Models/BaseModel.php`, `Entities/BaseEntity.php`, etc. contain abstract
infrastructure.

### Action Triad

All business logic follows the Triad pattern:

- **Command Action** (extends `BaseAction`): Mutations wrapped in database transactions with
  automatic SmartLogger audit. Returns `ActionResponse`.
- **Read Action** (plain class, no BaseAction): Queries only, no transactions or audit logging.
- **Process Action** (extends `BaseAction`): Orchestrates multiple Command Actions in a single
  transaction, coordinating cross-submodule workflows.

### Dual Exception Hierarchy

- **AppException** tree: `InfrastructureException` → `ActionException` → `PresentationException`.
  Used for system-level failures (Redis down, DB constraint violations).
- **ModuleException** tree: Per-module exceptions extending `ModuleException`. Used for business
  invariant violations (invalid state transitions, capacity exceeded).

### Centralized Cache Registry

Cache keys are defined in `config/cache-keys.php` — the single source of truth for all cache key
strings. Every module must register its cache keys here rather than hardcoding them. This prevents
key collisions and enables centralized cache management.

### Global Helpers

The three helper functions are split across two files:

- `app_info()` in `app/Core/Support/helpers.php` — static metadata from config/composer.json
- `setting()` in `app/Settings/Support/helpers.php` — runtime key-value settings
- `brand()` in `app/Settings/Support/helpers.php` — dynamic branding values from database with
  config fallback

### Cross-Module Communication

Four patterns, in order of preference:

1. **Direct import** — For shared entities, enums, and contracts.
2. **Core contracts** — Interfaces defined in Core implemented by any module.
3. **Module events** — Decoupled async communication via event bus (e.g., `EnrollmentCompleted`
   triggers Certification).
4. **Action delegation** — One module's Action calls another module's Action directly (acceptable
   for tight coupling within the same bounded context).

### Dynamic Discovery

The `module:discover` command scans all business modules and registers policies, Livewire
components, route directories, and cache keys dynamically. Results are cached in
`config('cache-keys.module_*')` for boot performance.

## Dependencies

- Laravel 13 framework
- `spatie/laravel-permission` — Role-based access control integration
- PHP 8.4

## Used By

Every module in the application.
