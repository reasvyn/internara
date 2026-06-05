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
- **Exceptions are typed** — use `AppException` for layered framework failures (action/infrastructure/presentation) and `ModuleException` for module invariant violations.
- **Core has zero business module dependencies** — it depends only on Laravel, Spatie packages, and PHP. No business module ever imports Core (Core imports nothing from business modules).

---

## Ideal Core & Infrastructure Design Specification

The ideal Core/Infrastructure design of Internara is divided into 5 architectural mechanisms:

### 1. Unified Transaction and Logging Boundary (`BaseAction`)
Every business mutation uses the Action pattern. To prevent data corruption, `BaseAction` implements a standardized atomic transaction wrapping and logging wrapper:
* **Atomic Transactions**: All mutations automatically wrap database updates inside database transactions. If any segment fails, changes are completely rolled back.
* **Auto-audit Logging**: Every completed mutation dispatches metadata directly to `SmartLogger`, logging user context, changes, and resource targets.

### 2. Dual-channel Structured Logging (`SmartLogger` + `PiiMasker`)
To satisfy security audits while maintaining verbose debug logs:
* **System Channel**: Detailed system errors and stacks for debugging.
* **Audit Channel**: Immutable database records documenting who modified what (e.g., grading changes, role escalation). Lacks sensitive user PII (names, phone numbers, and IDs are programmatically redacted).

### 3. Dynamic Module & Service Discovery (`module:discover`)
Instead of hardcoding route configuration or class listings:
* **Discovery Engine**: The Core module executes a discovery scanning command. This command identifies policies, models, Livewire components, and route directories across the 16 business modules.
* **Dynamic Registration**: Registries are cached automatically inside the Shared `CacheKeys` storage to maintain high boot performance.

### 4. Resilient Double-tree Exception Hierarchy
To isolate system exceptions from client-facing application issues:
* **`AppException` Tree**: Handled presentation, action, or infrastructure failures (e.g., Redis down, duplicate unique keys).
* **`ModuleException` Tree**: Catches strictly business invariant rejections (e.g., state machine bypasses, invalid grading ranges). Enables custom presentation views and clean logs.

### 5. Decoupled Asynchronous Comm (Cross-Module Event Bus)
Modules communicate via events. For example, when an enrollment completes, it fires a `PlacementCompleted` event. Listeners in the `Certification` and `Evaluation` modules handle the follow-up asynchronously. This prevents circular coupling between packages.

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
- **Exceptions/**: `AppException` and `ModuleException` dual hierarchies, plus abstract exceptions (`ActionException`, `InfrastructureException`, `PresentationException`) and concerns (`HasExceptionContext`)
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
