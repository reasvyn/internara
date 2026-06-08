# Core

> **Last updated:** 2026-06-08

Foundational infrastructure, abstract base classes, contracts, cross-module utilities, and architectural mechanisms that every other module depends on.

## Purpose & Boundary

Core provides the non-negotiable foundation for the entire application. It defines the architectural patterns (Action Triad, Entity separation, exception hierarchy) and enforces them through PHPStan rules and code review. Core has **zero dependencies** on any business module — it depends only on Laravel, Spatie packages, and PHP 8.4.

Out of scope: concrete business logic, domain enums, application settings, user-facing features.

## Submodules

Core has no submodules. Code is organized by architectural layer:

- **Actions/BaseAction.php** — Abstract foundation for Command and Process Action types. Provides atomic `$this->transaction()` wrapping, auto-audit logging via SmartLogger, and consistent error handling.
- **Models/BaseModel.php** — Abstract model base enforcing UUID v7 primary keys (via `HasUuids` trait), `HasFactory`, soft deletes, and consistent timestamp behavior. `Authenticatable` variant for the User model.
- **Entities/BaseEntity.php** — `final readonly` base for domain entities. Framework dependencies allowed. Entities expose `fromModel()` static factories and `toArray()` for serialization.
- **Policies/BasePolicy.php** — Abstract policy providing `before()` superadmin gate bypass, role checks via `AuthorizesRoles`, and ownership checks via `AuthorizesOwnership`.
- **Livewire/BaseRecordManager.php** — Abstract CRUD table component with built-in sorting, filtering, pagination, bulk actions, and record selection.
- **Http/Controllers/BaseController.php** — Abstract controller providing consistent JSON error responses and request context injection.
- **Http/Requests/BaseFormRequest.php** — Abstract form request that throws `ValidationFailedException` on failure (no redirects), compatible with API and Livewire subrequests.
- **Contracts/** — `LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`, `SettingsStore`.
- **Exceptions/** — Dual hierarchy: `AppException` for infrastructure/presentation/action failures, `ModuleException` for business rule violations. Both implement `HasExceptionContext`.
- **Support/SmartLogger.php** — Fluent dual-channel logger writing to system (debug) and activity (immutable audit) channels with automatic PII masking via `PiiMasker`.
- **Data/BaseData.php** — Abstract readonly DTO base for type-safe data transfer objects.

## Key Concepts

### Action Triad

All business logic follows the Triad pattern:
- **Command Action** (extends `BaseAction`): Mutations wrapped in database transactions with automatic SmartLogger audit. Returns `ActionResponse`.
- **Read Action** (plain class, no BaseAction): Queries only, no transactions or audit logging.
- **Process Action** (extends `BaseAction`): Orchestrates multiple Command Actions in a single transaction, coordinating cross-submodule workflows.

### Dual Exception Hierarchy

- **AppException** tree: `InfrastructureException` → `ActionException` → `PresentationException`. Used for system-level failures (Redis down, DB constraint violations).
- **ModuleException** tree: Per-module exceptions extending `ModuleException`. Used for business invariant violations (invalid state transitions, capacity exceeded).

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
