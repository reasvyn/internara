# Action-based MVC Architecture

> **Last updated:** 2026-06-27
> **Changes:** strip all duplicate content (code examples, detailed contracts, module maps, validation, caching, testing); keep only core layering, data flow, and dependency rules; redirect to pattern-specific docs
>
> Complete architectural foundation of Internara. Covers the 12-layer architecture, 4-layer data
> flow, Action Triad concept, and dependency rules. Every decision here serves three goals:
>
> - **S1 — Secure**: Protect data integrity, enforce authorization, prevent leakage
> - **S2 — Sustain**: Keep the codebase maintainable as it grows across 19 modules
> - **S3 — Scalable**: Design for team expansion and feature accretion without rewrites
>
> **Pattern deep-dives:** For focused documentation on specific domains, see the dedicated pattern
> references listed in each section below.

---

## Philosophy

Internara organizes code by **business module**, not by technical layer. Each business concept —
User, Academics, Program, Assessment — owns its complete vertical slice: persistence, business
rules, UI components, authorization, and HTTP interface.

Flat layering (`app/Models/`, `app/Livewire/`, `app/Actions/`) scatters a single feature across
multiple directories, making it hard to reason about boundaries, impossible to enforce
encapsulation, and expensive to refactor. Module colocation solves this by ensuring everything
related to "Enrollment" lives under `app/Enrollment/`.

The architecture draws inspiration from Domain-Driven Design (strategic design, bounded contexts)
and CQRS (command/query separation) without the operational overhead of separate databases or event
sourcing. Actions replace traditional Service classes to enforce single responsibility by
construction. DTOs act as immutable boundary objects that prevent layer coupling and circular
dependencies.

---

## Layered Architecture

### Two-Dimensional Structure

The system is organized along two axes:

**Vertical axis — 12 infrastructure layers** (bottom to top): Framework, persistence, contracts,
base classes, models, domain rules, business ops, authorization, communication, HTTP, UI, and
business modules at the top. Each layer depends only on layers below it.

**Horizontal axis — 4-layer data flow** (left to right): UI Layer → Business Logic Layer → Domain
Rules Layer → Data Layer. Data crosses each boundary exclusively through typed contracts,
preventing circular dependencies by ensuring no layer ever reaches into a layer above it.

### The 12 Infrastructure Layers

```
Layer 12 ┌─────────────────────────────────────────────────────────────┐
  Business│  19 modules, each a vertical slice through layers 1–11     │
  Modules │  app/{Module}/{SubModule}/{Component}/{ClassName}.php     │
          │  See: docs/modules/module-index.md                        │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 11 ┌─────────────────────────────────────────────────────────────┐
    UI   │  Livewire 4 components  Blade templates                     │
          │  maryUI + DaisyUI + Alpine.js + Tailwind CSS v4            │
          │  resources/views/{module}/                                 │
          │  See: docs/architecture/livewire-pattern.md                │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 10 ┌─────────────────────────────────────────────────────────────┐
   HTTP   │  Controllers  Middleware  Routes (17 route files)          │
          │  SecurityHeaders, LogContext, CheckRole, SetLocale         │
          │  routes/web/{module}.php                                   │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 9 ┌─────────────────────────────────────────────────────────────┐
   Comm.  │  Events + Listeners + Notifications + Console Commands    │
          │  Cross-module communication via events                    │
          │  See: docs/architecture/event-pattern.md                  │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 8 ┌─────────────────────────────────────────────────────────────┐
 Author. │  Policies (28+)  RBAC (5 roles)  Functional roles (2)      │
          │  spatie/laravel-permission  Gate::before bypass           │
          │  See: docs/architecture/policy-pattern.md                 │
          │  See: docs/foundation/rbac.md                             │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 7 ┌─────────────────────────────────────────────────────────────┐
  Bus.   │  Command Actions — mutations (transaction + log)           │
   Ops   │  Read Actions — queries (no transaction)                   │
          │  Process Actions — multi-step orchestration               │
          │  app/{Module}/**/Actions/  →  1 class = 1 use case       │
          │  See: docs/architecture/action-pattern.md                 │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 6 ┌─────────────────────────────────────────────────────────────┐
 Domain  │  Entities (final readonly)  DTOs (BaseData)  Enums         │
  Rules  │  app/{Module}/**/Entities/  app/{Module}/**/Enums/        │
          │  app/Core/Data/  app/Core/Exceptions/                     │
          │  See: docs/architecture/entity-pattern.md                 │
          │  See: docs/architecture/enum-pattern.md                   │
          │  See: docs/architecture/data-pattern.md                   │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 5 ┌─────────────────────────────────────────────────────────────┐
 Module  │  Eloquent Models — extend BaseModel                        │
 Models  │  UUID primary keys (HasUuids)  #[Fillable]  HasFactory    │
          │  Relationships, Scopes, Accessors, Mutators               │
          │  See: docs/architecture/model-pattern.md                  │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 4 ┌─────────────────────────────────────────────────────────────┐
  Core   │  BaseAction  BaseEntity  BasePolicy  BaseRecordManager     │
  Base   │  BaseData  BaseEvent  BaseController  BaseFormRequest      │
  Classes│  app/Core/ — Actions, Entities, Policies, Livewire,        │
          │            Http/Requests, Data, Events                    │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 3 ┌─────────────────────────────────────────────────────────────┐
  Core   │  Contracts: LabelEnum, StatusEnum, ColorableEnum           │
  Con-   │  SendsNotifications, SettingsStore                         │
  tracts │  Exceptions: AppException + ModuleException (dual tree)    │
          │  app/Core/Contracts/  app/Core/Exceptions/                │
          │  See: docs/architecture/exception-pattern.md              │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 2 ┌─────────────────────────────────────────────────────────────┐
 Persist.│  Database: SQLite (default) / MySQL / MariaDB / PostgreSQL │
          │  Config: .env, config/*.php, Runtime settings table       │
          │  Files: Spatie Media Library  Cache  Queue  Session       │
          │  database/migrations/  config/  storage/                   │
          │  See: docs/infrastructure/database.md                     │
          └─────────────────────────────────────────────────────────────┘
                                               ▲ depends on
Layer 1 ┌─────────────────────────────────────────────────────────────┐
  Infra  │  PHP 8.4 + Laravel + Composer packages                     │
          │  Spatie: activitylog, medialibrary, permission, model-status │
          │  Livewire + Tailwind CSS + Alpine.js  Vite                │
          └─────────────────────────────────────────────────────────────┘
```

### Layer Dependency Rules

1. **Downward only**: A layer may only depend on layers **below** it.
2. **Core independence**: Core (layers 3–4) depends on nothing except Laravel and Spatie packages.
   No business module may be imported by Core.
3. **Sibling imports allowed**: A business module at Layer 12 may import another module directly.
   Prefer events when side effects are involved.
4. **Persistence isolation**: Actions delegate to Models via injected dependencies — never call
   Eloquent directly.
5. **UI isolation**: Livewire components must not import other modules' Livewire components
   directly. Use events or redirects.
6. **Entity purity**: Entities (Layer 6) must never import Actions (Layer 7), Livewire (Layer 11),
   or any HTTP layer.
7. **DTO ownership**: DTOs (Layer 6) are owned by the consuming Action — defined in the module
   where the Action lives, not in a shared location.

### How Module Directories Map to Layers

| Layer | Directory within Module |
|-------|------------------------|
| 12 | `app/{Module}/` |
| 11 | `resources/views/{module}/{submodule}/` |
| 10 | `routes/web/{module}.php` |
| 9 | `{SubModule}/Listeners/`, `{SubModule}/Notifications/`, `Console/` |
| 8 | `{SubModule}/Policies/` |
| 7 | `{SubModule}/Actions/` |
| 6 | `{SubModule}/Entities/`, `{SubModule}/Enums/`, `Types/`, `Data/` |
| 5 | `{SubModule}/Models/` |
| 4 | Uses Core base classes: `app/Core/{Actions,Models,Policies,...}` |
| 3 | Uses Core contracts and exceptions |
| 2 | Uses database, config, filesystem |
| 1 | Uses PHP, Laravel, Composer packages |

Cross-submodule files (shared Actions, Http, Console) live at the module root directly under
`app/{Module}/`.

For the complete directory tree and path conventions, see [Modular Pattern](architecture/modular-pattern.md).

---

## The 4-Layer Horizontal Data Flow

The 12 infrastructure layers group into 4 logical layers governing **data flow direction**:

| Horizontal Layer | Infrastructure Layers | Purpose |
|------------------|----------------------|---------|
| **UI Layer** | 12, 11, 10, 9, 8 | Livewire, Controllers, Console, Policies, Events |
| **Business Logic** | 7, 4, 3 | Actions, Services, Support |
| **Domain Rules** | 6, 4, 3 | Entities, Enums, DTOs, Exceptions |
| **Data Layer** | 5, 2, 1 | Models, Database, Config, Infrastructure |

```
┌────────────────────────────────────────────────────────────────────────┐
│                    4-LAYER DATA FLOW                                    │
│                                                                         │
│  ┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌────┐ │
│  │     UI       │     │  BUSINESS    │     │   DOMAIN     │     │DATA│ │
│  │   LAYER      │ ──► │  LOGIC       │ ──► │   RULES      │ ──► │    │ │
│  │              │     │  LAYER       │     │   LAYER      │     │LAY │ │
│  │ Livewire     │     │ Actions      │     │ Entities     │     │    │ │
│  │ Controllers  │     │ Services     │     │ Events       │     │Mod │ │
│  │ Console      │     │ Support      │     │              │     │els │ │
│  └──────┬───────┘     └──────┬───────┘     └──────┬───────┘     └─▲──┘ │
│         │                    │                    │              │     │
│         │  DTO/FormReq      │      DTO           │  Model        │     │
│         │  /LivewireForm    │      (input)       │  Record       │     │
│         └──────────────────►└──────────────────►└───────────────┘     │
│                                                                         │
│  BOUNDARY CONTRACTS:                                                     │
│  UI → Business:  DTO (BaseData) or typed scalars — never raw Request   │
│  Business → Domain: Model record for Entity construction               │
│  Domain → Data:   Model attributes from DTO values                     │
│                                                                         │
│  🚫  NEVER: UI → Model::create() directly                              │
│  🚫  NEVER: Business → UI classes                                      │
│  🚫  NEVER: Entity → Action or Service                                 │
└─────────────────────────────────────────────────────────────────────────┘
```

For the complete data flow reference (mutation flow, read flow, event flow, DTO lifecycle,
boundary crossing tables), see [Data Pattern](architecture/data-pattern.md).

---

## Action Triad

The Action Triad is the single most important architectural decision in Internara. Actions split
into three distinct categories, each with a specific contract. All three live under
`app/{Module}/{SubModule}/Actions/` and follow the single `execute()` method convention.

| Type | Purpose | Base Class | Transaction | Logging | Events |
|------|---------|-----------|-------------|---------|--------|
| **Command** | All writes — create, update, delete, state transitions | `BaseCommandAction` | Required | Required | Recommended |
| **Read** | Complex queries, aggregations, dashboards | `BaseReadAction` | Never | Never | Never |
| **Process** | Multi-step orchestration of Command/Read Actions | `BaseProcessAction` | Required | Required | Required |

**Key rules:**
- Every Action has exactly one public method: `execute()`
- Actions are the **only** entry point for mutations — Livewire never calls `Model::create()` directly
- Command/Process Actions SHOULD accept a `BaseData` DTO for 3+ params, use typed scalars for 1-2
- Command/Process Actions SHOULD return `ActionResponse` for structured feedback
- Actions MUST delegate business rules to Entities — throw `RejectedException` on violation

For the complete reference (contracts, naming, code examples, ActionResponse, migration paths),
see [Action Pattern](architecture/action-pattern.md).

---

## Circular Dependency Prevention

The architecture prevents circular dependencies through four structural mechanisms:

1. **Strict layer direction** — Dependencies flow one way: UI → Business → Domain → Data
2. **DTOs as boundary objects** — Carry only scalars, enums, Carbon — never Models or Actions
3. **Entity-only business checks** — Entities are `final readonly` with zero framework dependencies
4. **Event-based decoupling** — Cross-module side effects go through events, not direct calls

### Seven Rules for Dependency Safety

| # | Rule | Violation Example |
|---|------|-------------------|
| R1 | **DTOs are leaf classes.** Carry only scalars, enums, Carbon. Never Models, Actions, Entities. | `CompanyData` with a `Company` property |
| R2 | **Entities must not import Business/UI layers.** | `RegistrationState` importing `ApproveRegistrationAction` |
| R3 | **Livewire must not write to Models directly.** All persistence goes through Command Actions. | `Company::create(...)` in Livewire |
| R4 | **Livewire may access Entities for READ-ONLY UI checks only.** WRITE decisions go through Actions. | `$reg->asState()->canBeApproved()` before calling Action |
| R5 | **Actions prefer DTO for complex input.** 3+ params → DTO. 1-2 typed scalars → OK. Never raw `array`. | `execute(array $data)` for a 5-param operation |
| R6 | **Services must not call Actions.** Services are infrastructure code. | Service calling `CreateCompanyAction` |
| R7 | **Entities must not perform I/O.** No DB queries, HTTP, cache, events, facades. | Entity calling `Cache::get()` |

For detailed circular dependency scenarios, detection, and fixes, see
[Modular Pattern](architecture/modular-pattern.md).

---

## Cross-Module Communication

Cross-module imports are **allowed** — import Models, Actions, or Policies from sibling modules
directly. Four patterns are available:

| Pattern | When to Use |
|---------|-------------|
| **Direct import** | Straightforward access with no side effects |
| **Action call** | Cross-module business operation |
| **Module event** | Fire-and-forget side effects (notifications, cache invalidation) |
| **Core contract** | Abstraction used broadly across modules (`LabelEnum`, `SendsNotifications`) |

See [ADR-010](adr/adr-cross-module-communication.md).

---

## Reference Map

| Topic | Document |
|-------|----------|
| **Base class mandate** | [Coding Conventions](conventions.md) §1 |
| **Complete pattern catalog** | [Modular Pattern Reference](architecture/modular-pattern.md) |
| **Action contracts & examples** | [Action Pattern](architecture/action-pattern.md) |
| **Entity-model separation** | [Entity Pattern](architecture/entity-pattern.md) |
| **Model conventions** | [Model Pattern](architecture/model-pattern.md) |
| **DTOs & ActionResponse** | [Data Pattern](architecture/data-pattern.md) |
| **Enum & state machine** | [Enum Pattern](architecture/enum-pattern.md) |
| **Event dispatch & listeners** | [Event Pattern](architecture/event-pattern.md) |
| **Livewire component rules** | [Livewire Pattern](architecture/livewire-pattern.md) |
| **Policies & RBAC** | [Policy Pattern](architecture/policy-pattern.md) |
| **Exception hierarchy** | [Exception Pattern](architecture/exception-pattern.md) |
| **Logging & PII masking** | [Logging Pattern](architecture/logging-pattern.md) |
| **Caching strategy** | [Cache Pattern](architecture/cache-pattern.md) |
| **Service vs Support vs Action** | [Service Pattern](architecture/service-pattern.md), [Support Pattern](architecture/support-pattern.md) |
| **Why no Repository** | [Repository Pattern](architecture/repository-pattern.md) |
| **Testing patterns** | [Testing Pattern](architecture/testing-pattern.md) |
| **Validation strategy** | [Modular Pattern](architecture/modular-pattern.md) §4 |
| **Module structure & naming** | [Modular Pattern](architecture/modular-pattern.md) |
| **19 modules overview** | [Module Index](modules/module-index.md) |
| **Module invariants** | [Coding Conventions](conventions.md), AGENTS.md |
