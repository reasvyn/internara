# Core

> **Last updated:** 2026-06-08

Foundational infrastructure, abstract base classes, contracts, cross-module utilities, concrete implementations, and architectural mechanisms that every other module depends on.

## Purpose & Boundary

Core provides the non-negotiable foundation for the entire application. It defines the architectural patterns (Action Triad, Entity separation, exception hierarchy) and enforces them through PHPStan rules and code review. Core has **zero dependencies** on any business module — it depends only on Laravel, Spatie packages, and PHP 8.4.

The module is split into two conceptual layers:

- **Infrastructure (abstract):** Base classes, contracts, abstract exceptions, middleware — the framework every module builds on.
- **Shared (concrete):** DTOs, enums, concrete exceptions, global UI components, policy concerns, support utilities, helper functions — reusable implementations that any module may import.

Out of scope: domain-specific logic, domain enums, application settings, user-facing features.

## Submodules

Core has no submodules. Code is organized by architectural layer:

- **Actions/BaseAction.php** — Abstract foundation for Command and Process Action types. Provides atomic `$this->transaction()` wrapping, auto-audit logging via SmartLogger, and consistent error handling.
- **Models/BaseModel.php** — Abstract model base enforcing UUID v7 primary keys (via `HasUuids` trait), `HasFactory`, soft deletes, and consistent timestamp behavior. `Authenticatable` variant for the User model.
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
- **Data/BaseData.php** — Abstract readonly DTO base for type-safe data transfer objects. Concrete DTOs: `AuditCheck`, `AuditReport`.
- **Enums/** — System-wide enums: `CsvRowResult` (row import status), `AuditCategory` (health categories), `AuditStatus` (pass/fail/warn). All implement `LabelEnum`.
- **Support/SmartLogger.php** — Fluent dual-channel logger writing to system (debug) and activity (immutable audit) channels with automatic PII masking.
- **Support/LangChecker.php** — Dev helper that warns on missing translation keys.
- **Support/** — Concrete utilities: `CacheKeys` (centralized cache key registry), `Color` (hex manipulation), `CsvHandler` (CSV parsing/generation), `Environment` (system environment detection), `HandlesActionErrors` (generic try-catch-log-rethrow), `HasModelStatuses` (status enum integration), `Integrity` (composer/security assessment), `PasswordRules` (password policy presets), `PiiMasker` (PII redaction).
- **helpers.php** — Global helper functions: `setting()`, `brand()`, `app_info()`.

## Key Concepts

### Separation of Abstract and Concrete

Core provides abstract contracts and base classes. Shared provides concrete implementations. The distinction prevents framework-level abstractions from being polluted with application-specific defaults. All shared components live under `app/Core/` but are conceptually separated: `Data/`, `Enums/`, `Exceptions/`, `Livewire/`, `Policies/Concerns/`, and `Support/` contain concrete classes, while `Contracts/`, `Actions/BaseAction.php`, `Models/BaseModel.php`, `Entities/BaseEntity.php`, etc. contain abstract infrastructure.

### Action Triad

All business logic follows the Triad pattern:
- **Command Action** (extends `BaseAction`): Mutations wrapped in database transactions with automatic SmartLogger audit. Returns `ActionResponse`.
- **Read Action** (plain class, no BaseAction): Queries only, no transactions or audit logging.
- **Process Action** (extends `BaseAction`): Orchestrates multiple Command Actions in a single transaction, coordinating cross-submodule workflows.

### Dual Exception Hierarchy

- **AppException** tree: `InfrastructureException` → `ActionException` → `PresentationException`. Used for system-level failures (Redis down, DB constraint violations).
- **ModuleException** tree: Per-module exceptions extending `ModuleException`. Used for business invariant violations (invalid state transitions, capacity exceeded).

### Centralized Cache Registry

`CacheKeys` is the single source of truth for all cache key strings. Every module must register its cache keys here rather than hardcoding them. This prevents key collisions and enables centralized cache management.

### Global Helpers

The three helper functions (`setting()`, `brand()`, `app_info()`) are the primary way any code — Blade templates, Livewire components, Actions — accesses runtime configuration. They resolve through the Settings fallback chain (override → cache → config → default).

### Cross-Module Communication

Four patterns, in order of preference:
1. **Direct import** — For shared entities, enums, and contracts.
2. **Core contracts** — Interfaces defined in Core implemented by any module.
3. **Module events** — Decoupled async communication via event bus (e.g., `EnrollmentCompleted` triggers Certification).
4. **Action delegation** — One module's Action calls another module's Action directly (acceptable for tight coupling within the same bounded context).

### Dynamic Discovery

The `module:discover` command scans all business modules and registers policies, Livewire components, route directories, and cache keys dynamically. Results are cached in `CacheKeys` for boot performance.

## Dependencies

- Laravel 13 framework
- `spatie/laravel-permission` — Role-based access control integration
- PHP 8.4

## Used By

Every module in the application.
