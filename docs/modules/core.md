# Core — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Foundational infrastructure, base classes, contracts, and cross-module utilities that every other module depends on.

For complete technical reference including API, models, actions, and components, see [core-reference.md](core-reference.md).

---

## Key Principles

- **Base classes are mandatory** — every Model, Action, Policy, Entity, Controller, FormRequest, Enum, and Livewire CRUD component must extend the corresponding Core base class. No exceptions.
- **Contracts over implementations** — enums implement `LabelEnum`, state machines implement `StatusEnum`. Consistency across all 16 modules.
- **SmartLogger dual-channel** — all logging goes through `SmartLogger`, which simultaneously writes to system and activity channels with automatic PII masking.
- **Exceptions are typed** — use `AppException` for layered framework failures (action/infrastructure/presentation) and `DomainException` for module invariant violations.
- **Core has zero business module dependencies** — it depends only on Laravel, Spatie packages, and PHP. No business module ever imports Core (Core imports nothing from business modules).

---

## Context Boundary

Core is the foundation layer (Layers 3–4 in the 12-layer architecture). Every module depends on it. Core itself depends on nothing except Laravel/Spatie/PHP. It provides:

- **Layer 3 (Contracts)**: Enum contracts, base exception hierarchy, notification contracts
- **Layer 4 (Base Classes)**: BaseModel, BaseAction, BasePolicy, BaseEntity, BaseRecordManager, BaseController, BaseFormRequest, BaseData DTO
- **Cross-module foundation**: SmartLogger, security headers and request tracing middleware, system discovery commands

---

## Module Rules

- All models **must** extend `BaseModel` (or `Authenticatable` for User). UUID primary keys via `HasUuids` trait are enforced at the base level.
- All business mutations **must** go through `BaseAction::execute()`, which wraps in `$this->transaction()` and logs via `$this->log()`. Livewire components must never call `Model::save()` directly.
- All authorization **must** go through `BasePolicy`. Inline `Gate::define()` with closures is forbidden.
- State machine enums **must** implement `StatusEnum` with `canTransitionTo()`, `isTerminal()`, and `validTransitions()`.
- All enums **must** implement `LabelEnum` (provides `label(): string` for UI display).
- Form Requests **must** extend Core's `BaseFormRequest` (throws `ValidationFailedException` instead of redirecting).
- Cache keys **must** be defined as constants in the Shared `CacheKeys` class, not hardcoded as strings.
- Cross-module communication uses four patterns: direct imports, core contracts, module events, and action delegation.

---

## Submodules

Core has no submodules — it provides infrastructure, not business entities. Code is organized by function:

- **Actions/**: `BaseAction`
- **Models/**: `BaseModel`, `ActivityLog` (Spatie)
- **Policies/**: `BasePolicy`
- **Entities/**: `BaseEntity` (final readonly base)
- **Livewire/**: `BaseRecordManager`
- **Http/**: `BaseController`, `BaseFormRequest`, `SecurityHeaders`, `LogContext` middleware
- **Contracts/**: `LabelEnum`, `StatusEnum`, `ColorableEnum`, `SendsNotifications`, `SettingsStore`
- **Exceptions/**: `AppException` and `DomainException` dual hierarchies, plus abstract exceptions (`ActionException`, `InfrastructureException`, `PresentationException`) and concerns (`HasExceptionContext`)
- **Support/**: `SmartLogger`, `LangChecker`
- **Data/**: `BaseData` (abstract readonly DTO base)

---

## Error Handling & Failure Modes

- **Missing base class extension**: PHPStan enforces that every Model/Action/Policy/Entity extends the correct Core base class. CI will fail with a static analysis error.
- **Skipping BaseAction**: If a mutation bypasses `BaseAction` and calls `Model::save()` directly from a Livewire component, it violates the Action Gate rule. Enforced through code review.
- **Bypassing FormRequest**: Using `extends Request` (Laravel's base) instead of Core's `BaseFormRequest` means validation failures will redirect instead of returning JSON. API clients and Livewire subrequests will break.
- **Missing StatusEnum**: State machine enums without `canTransitionTo()` allow invalid transitions silently. The system will not prevent a `REVOKED` certificate from being re-issued.

---

## Quick References

### Actions & Business Logic
- **1** action (`BaseAction`)
- Provides transaction management, activity logging, and error handling for all command/process actions

### Data & Persistence
- **2** models (`BaseModel`, `ActivityLog`)
- BaseModel enforces UUID PKs, HasFactory, and soft-delete support across all module models

### User Interface
- **1** Livewire base component for CRUD tables (`BaseRecordManager`)
- Layouts and base Blade templates in `resources/views/core/`

### Authorization
- **1** authorization policy (`BasePolicy`)
- Provides `before()` gate bypass for superadmin, role checks with `AuthorizesRoles`, ownership checks with `AuthorizesOwnership`

---

For complete technical reference, see [core-reference.md](core-reference.md).
