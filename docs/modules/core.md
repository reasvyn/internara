# Core

> **Last updated:** 2026-06-10

Foundational infrastructure, abstract base classes, contracts, cross-module utilities, concrete implementations, and architectural mechanisms that every other module depends on.

## Purpose & Boundary

Core provides the non-negotiable foundation for the entire application. It defines the architectural patterns (Action Triad, Entity separation, exception hierarchy) and enforces them through PHPStan rules and code review. Core has **zero dependencies** on any business module — it depends only on Laravel, Spatie packages, and PHP 8.4.

The module is split into two conceptual layers:

- **Infrastructure (abstract):** Base classes, contracts, abstract exceptions, middleware — the framework every module builds on.
- **Concrete layer:** DTOs, enums, concrete exceptions, global UI components, policy concerns, support utilities, helper functions — reusable implementations that any module may import.

Out of scope: domain-specific logic, domain enums, application settings, user-facing features.

## Submodules

Core has no submodules. Code is organized by architectural layer:

- **Actions/BaseAction.php** — Abstract foundation for Command and Process Action types. Provides atomic `$this->transaction()` wrapping, auto-audit logging via SmartLogger, and consistent error handling.
- **Models/BaseModel.php** — Abstract model base enforcing UUID v7 primary keys (via `HasUuids` trait), `HasFactory`, soft deletes, and consistent timestamp behavior. `Authenticatable` variant for the User model. Common scopes (`active`, `inactive`, `recent`, `createdAfter`, `createdBefore`, `ordered`) extracted into shared `HasCommonScopes` trait.
- **Models/Concerns/HasCommonScopes.php** — Shared trait providing 6 common query scopes (`scopeActive`, `scopeInactive`, `scopeRecent`, `scopeCreatedAfter`, `scopeCreatedBefore`, `scopeOrdered`) used by both `BaseModel` and `BaseAuthenticatable`.
- **Models/ActivityLog.php** — Concrete model for SmartLogger's dual-channel audit log persistence.
- **Entities/BaseEntity.php** — `final readonly` base for domain entities. Zero framework dependencies. Entities expose `fromModel()` static factories and `toArray()` for serialization.
- **Policies/BasePolicy.php** — Abstract policy providing `before()` superadmin gate bypass, role checks via `AuthorizesRoles`, and ownership checks via `AuthorizesOwnership`.
- **Policies/Concerns/** — Reusable authorization traits: `AuthorizesRoles`, `AuthorizesOwnership`. Used by all module policies.
- **Livewire/BaseRecordManager.php** — Abstract CRUD table component with built-in sorting, filtering, pagination, bulk actions, and record selection.
- **Livewire/Concerns/** — UI state management traits: `WithSorting` (column sorting), `WithRecordSelection` (checkbox row selection for bulk actions).
- **Http/Controllers/BaseController.php** — Abstract controller providing consistent JSON error responses and request context injection.
- **Http/Requests/BaseFormRequest.php** — Abstract form request that throws `ValidationFailedException` on failure (no redirects), compatible with API and Livewire subrequests.
- **Http/Middleware/** — Request pipeline middleware: `SecurityHeaders` (CSP, X-Frame-Options, etc.), `LogContext` (request tracing).
- **Contracts/** — Interfaces: `LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`, `SettingsStore`.
- **Exceptions/** — Dual hierarchy: `AppException` for infrastructure/presentation/action failures, `ModuleException` for business rule violations. Concrete subclasses: `ConflictException` (409), `NotFoundException` (404), `RateLimitException` (429), `RejectedException` (400), `UnauthorizedException` (403), `ValidationFailedException` (422). All implement `HasExceptionContext`.
- **Events/BaseEvent.php** — Abstract base for event objects with `Dispatchable`, `eventName()`, and `toPayload()`.
- **Data/BaseData.php** — Abstract readonly DTO base for type-safe data transfer objects with `fromArray()`, `toArray()`, `only()`, `except()`, `merge()`. Concrete DTOs: `ActionResponse` (standardized action result), `AuditCheck` (single audit check), `AuditReport` (aggregated audit results).
- **Enums/** — System-wide enums: `CsvRowResult` (row import status), `AuditCategory` (health categories), `AuditStatus` (pass/fail/warn). All implement `LabelEnum`.
- **Support/SmartLogger.php** — Fluent dual-channel logger writing to system (debug) and activity (immutable audit) channels with automatic PII masking, event dispatching, and translation resolution.
- **Support/LangChecker.php** — Dev helper that warns on missing translation keys via SmartLogger.
- **Support/AppInfo.php** — Static application metadata from `composer.json` with config fallback (name, version, author, license, gitUrl). Powers `app_info()` global helper.
- **Support/AppIntegrity.php** — Composer author verification, enforcing that the author name must be "Reas Vyn".
- **Support/** — Concrete utilities: `Color` (hex manipulation, luminance, contrast, shade computation), `CsvHandler` (CSV parsing/generation with safe file handle management), `Environment` (system environment detection), `HandlesActionErrors` (generic try-catch-log-rethrow trait), `PasswordRules` (password policy presets), `PiiMasker` (PII redaction for emails, phones, names, IPs, user agents).
- **helpers.php** — Global helper function: `app_info()` for static metadata access.

The helpers `setting()` and `brand()` are defined in the Settings module at `app/Settings/Support/helpers.php`.

## Key Concepts

### Separation of Abstract and Concrete

Core provides abstract contracts and base classes alongside concrete implementations under the same `app/Core/` namespace. The distinction prevents framework-level abstractions from being polluted with application-specific defaults. `Data/`, `Enums/`, `Exceptions/`, `Livewire/`, `Policies/Concerns/`, and `Support/` contain concrete classes, while `Contracts/`, `Actions/BaseAction.php`, `Models/BaseModel.php`, `Entities/BaseEntity.php`, etc. contain abstract infrastructure.

### Action Triad

All business logic follows the Triad pattern:
- **Command Action** (extends `BaseAction`): Mutations wrapped in database transactions with automatic SmartLogger audit. Returns `ActionResponse`.
- **Read Action** (plain class, no BaseAction): Queries only, no transactions or audit logging.
- **Process Action** (extends `BaseAction`): Orchestrates multiple Command Actions in a single transaction, coordinating cross-submodule workflows.

### Dual Exception Hierarchy

- **AppException** tree: `InfrastructureException` → `ActionException` → `PresentationException`. Used for system-level failures (Redis down, DB constraint violations).
- **ModuleException** tree: Per-module exceptions extending `ModuleException`. Used for business invariant violations (invalid state transitions, capacity exceeded).

### Centralized Cache Registry

Cache keys are defined in `config/cache-keys.php` — the single source of truth for all cache key strings. Every module must register its cache keys here rather than hardcoding them. This prevents key collisions and enables centralized cache management.

### Global Helpers

The three helper functions are split across two files:
- `app_info()` in `app/Core/Support/helpers.php` — static metadata from config/composer.json
- `setting()` in `app/Settings/Support/helpers.php` — runtime key-value settings
- `brand()` in `app/Settings/Support/helpers.php` — dynamic branding values from database with config fallback

### Cross-Module Communication

Four patterns, in order of preference:
1. **Direct import** — For shared entities, enums, and contracts.
2. **Core contracts** — Interfaces defined in Core implemented by any module.
3. **Module events** — Decoupled async communication via event bus (e.g., `EnrollmentCompleted` triggers Certification).
4. **Action delegation** — One module's Action calls another module's Action directly (acceptable for tight coupling within the same bounded context).

### Dynamic Discovery

The `module:discover` command scans all business modules and registers policies, Livewire components, route directories, and cache keys dynamically. Results are cached in `config('cache-keys.module_*')` for boot performance.

## Dependencies

- Laravel 13 framework
- `spatie/laravel-permission` — Role-based access control integration
- PHP 8.4

## Used By

Every module in the application.