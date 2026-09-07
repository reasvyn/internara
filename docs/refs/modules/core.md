# Core — Base Classes, Contracts & Exceptions

## Description

Foundational infrastructure, abstract base classes, contracts, cross-module utilities, concrete
implementations, and architectural mechanisms that every other module depends on.

## Purpose & Boundary

Core provides the non-negotiable foundation for the entire application. It defines the architectural
patterns (Action Triad, Entity separation, exception hierarchy) and enforces them through
code review. Core has **zero dependencies** on any business module — it depends only on
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
AuthorizesOwnership), `Http/Middleware/` (SecurityHeadersMiddleware, LogContextMiddleware),
`Support/` (Color,
CsvHandler, Environment, ModuleManager, PasswordRules, PiiMasker, Spotlight).

**Infrastructure services** (system-level, not domain logic): `Services/SmartLogger` (dual-channel
audit logging with PII masking), `Services/AppInfo` (static metadata), `Services/AppIntegrity`
(author verification), `Services/LangChecker` (missing translation detection),
`Services/ModuleService` (module discovery orchestration; all module config reads go through
`Support/ModuleManager`).

**Shared models**: `Models/ActivityLog` (SmartLogger persistence), `Models/BaseAuthenticatable`
(User model base with manual HasUuids).

**Helper functions**: Core provides `app_info()` for static metadata. The `setting()` and `brand()` helpers
provide runtime settings and branding access.

## Key Concepts

### Separation of Abstract and Concrete

Core provides abstract contracts and base classes alongside concrete implementations. The distinction
prevents framework-level abstractions from being polluted with application-specific defaults. The
concrete layer (Data, Enums, Exceptions, Livewire, Policies/Concerns, Support) contains reusable
classes, while the abstract layer (Contracts, base classes) provides the infrastructure every module
builds on.

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

The three helper functions are split by responsibility:

- `app_info()` — static metadata from config/composer.json
- `setting()` — runtime key-value settings
- `brand()` — dynamic branding values from database with config fallback

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

## Design Principles

- **Zero upward dependencies** — Core depends only on Laravel, Spatie packages, and PHP 8.4. It must never import from any business module. If a business concept feels like it belongs in Core, it almost certainly does not — push it back to its owning module and expose a contract.
- **Abstract and concrete stay separate** — abstract contracts and base classes (the framework) live alongside concrete reusable implementations (DTOs, enums, exceptions, support) but are clearly partitioned. Never bury an abstract contract inside a concrete namespace; the boundary is the architectural invariant.
- **Action Triad is non-negotiable** — mutations are `Command Actions` (transaction + audit + event), reads are plain classes (no transaction, no logging), and multi-step coordination is a `Process Action` extending `BaseAction`. The triad shape is enforced by type, not convention.
- **Exceptions are dual hierarchies** — `AppException` (system failures) and `ModuleException` (business invariants) never mix. Use `AppException` for infrastructure (DB, Redis, filesystem); use `ModuleException` (with `RejectedException`) for business rule violations.
- **Cache keys are registered, never hardcoded** — every cache key string lives in `config/cache-keys.php`. Inline `'cache_key'` strings anywhere in business code is a violation; the registry is the only source of truth.
- **Audit logging is centralized** — `SmartLogger` is the only path for dual-channel audit. Never write directly to `Log` for audit-worthy events; never write directly to `activity_log` from business code — bypass loses PII masking.

## How It Works

*Content to be added — verify against actual implementation.*
