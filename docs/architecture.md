# Action-based MVC Architecture — 4-Layer Architecture, Data Flow & Dependency Rules

> **Last updated:** 2026-07-21 **Changes:** update route/translation file paths to reflect submodule convention (no module prefix)

## Description

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

## 4-Layer Architecture

### The Four Layers

The system is organized into four layers with strict downward-only dependencies. Each layer depends
only on layers below it — never the reverse.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  Layer 4: PRESENTATION / UI                                                  │
│                                                                             │
│  Livewire components  Blade templates  Controllers  Console commands        │
│  Policies (RBAC / authorization gates)  Routes (17 route files)             │
│  maryUI + DaisyUI + Alpine.js + Tailwind CSS v4                             │
│                                                                             │
│  app/{Module}/**/Livewire/  app/{Module}/**/Policies/  app/{Module}/Http/  │
│  resources/views/{module}/  routes/web/{module}.php (+ {submodule}.php)    │
│  See: docs/architecture/livewire-pattern.md                                 │
│  See: docs/architecture/policy-pattern.md                                   │
└──────────────────────────────────────────────────────────────────────────▲──┘
                                                                           │
┌──────────────────────────────────────────────────────────────────────────┴──┐
│  Layer 3: BUSINESS / DOMAIN OPERATIONS                                       │
│                                                                             │
│  Command Actions (mutations — transaction + log + event)                     │
│  Read Actions (complex queries — no transaction)                            │
│  Process Actions (multi-step orchestration)                                 │
│  Events + Listeners + Notifications                                         │
│  Middleware                                                                  │
│                                                                             │
│  app/{Module}/**/Actions/  →  1 class = 1 use case                         │
│  app/{Module}/**/Events/  app/{Module}/**/Listeners/                        │
│  See: docs/architecture/action-pattern.md                                   │
│  See: docs/architecture/event-pattern.md                                    │
└──────────────────────────────────────────────────────────────────────────▲──┘
                                                                           │
┌──────────────────────────────────────────────────────────────────────────┴──┐
│  Layer 2: DATA / PERSISTENT                                                  │
│                                                                             │
│  Eloquent Models (extend BaseModel, UUID PKs, #[Fillable], HasFactory)     │
│  Entities (final readonly — pure business rules)                            │
│  DTOs (BaseData — immutable boundary objects)                               │
│  Enums (LabelEnum, StatusEnum, ColorableEnum)                               │
│  Database: SQLite (default) / MySQL / MariaDB / PostgreSQL                  │
│  Config: .env, config/*.php, Runtime settings table                         │
│  Files: Spatie Media Library  Cache  Queue  Session                         │
│                                                                             │
│  app/{Module}/**/Models/  app/{Module}/**/Entities/                         │
│  app/{Module}/**/Enums/  app/{Module}/**/Data/                             │
│  app/Core/Data/  app/Core/Exceptions/                                       │
│  database/migrations/  config/  storage/                                    │
│  See: docs/architecture/model-pattern.md                                    │
│  See: docs/architecture/entity-pattern.md                                   │
│  See: docs/architecture/enum-pattern.md                                     │
│  See: docs/architecture/data-pattern.md                                     │
│  See: docs/infrastructure/database.md                                       │
└──────────────────────────────────────────────────────────────────────────▲──┘
                                                                           │
┌──────────────────────────────────────────────────────────────────────────┴──┐
│  Layer 1: FRAMEWORK / INFRASTRUCTURE / UTILITIES                            │
│                                                                             │
│  Core Base Classes: BaseModel, BaseAction, BaseEntity, BasePolicy,          │
│                     BaseRecordManager, BaseData, BaseEvent, etc.            │
│  Core Contracts: LabelEnum, StatusEnum, ColorableEnum                       │
│                  SendsNotifications, SettingsStore                          │
│  Exception Hierarchy: AppException + ModuleException (dual tree)            │
│  Services (infrastructure logic — instance methods, constructor injection)  │
│  Support (static utilities — no side effects, no DI)                       │
│  PHP 8.4 + Laravel + Composer/Spatie packages                              │
│                                                                             │
│  app/Core/{Actions,Models,Policies,Livewire,Data,Events,Exceptions,...}    │
│  app/Core/Services/  app/Core/Support/                                     │
│  app/{Module}/Services/  app/{Module}/Support/                             │
│  See: docs/architecture/action-pattern.md                                   │
│  See: docs/architecture/service-pattern.md                                  │
│  See: docs/architecture/support-pattern.md                                  │
│  See: docs/architecture/exception-pattern.md                                │
└─────────────────────────────────────────────────────────────────────────────┘
```

### How Module Directories Map to Layers

| Layer                       | Directory / Location                                                                                                          |
| --------------------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **4 — Presentation/UI**     | `{SubModule}/Livewire/`, `resources/views/{module}/`, `routes/web/{module}.php` (+ `{submodule}.php`), `{SubModule}/Policies/`, `{SubModule}/Http/` |
| **3 — Business/Domain Ops** | `{SubModule}/Actions/`, `{SubModule}/Events/`, `{SubModule}/Listeners/`, `{SubModule}/Notifications/`, `Console/`             |
| **2 — Data/Persistent**     | `{SubModule}/Models/`, `{SubModule}/Entities/`, `{SubModule}/Enums/`, `{SubModule}/Data/`, `Types/`, database, config         |
| **1 — Framework/Infra**     | `app/Core/`, `{SubModule}/Services/`, `{SubModule}/Support/`, PHP, Laravel, packages                                          |

Cross-submodule files (shared Actions, Http, Console) live at the module root directly under
`app/{Module}/`.

For the complete directory tree and path conventions, see
[Modular Pattern](architecture/modular-pattern.md).

### Layer Dependency Rules

1. **Downward only**: A layer may only depend on layers **below** it.
2. **Core independence**: Core (Layer 1) depends on nothing except Laravel and Spatie packages. No
   business module may be imported by Core.
3. **Sibling imports allowed**: A module at Layer 4 may import another module directly. Prefer
   events when side effects are involved.
4. **Persistence isolation**: Actions (Layer 3) delegate to Models (Layer 2) via injected
   dependencies — never call Eloquent directly.
5. **UI isolation**: Livewire components (Layer 4) must not import other modules' Livewire
   components directly. Use events or redirects.
6. **Entity purity**: Entities (Layer 2) must never import Actions (Layer 3) or Livewire (Layer 4).
7. **DTO ownership**: DTOs (Layer 2) are owned by the consuming Action — defined in the module where
   the Action lives, not in a shared location.

---

## 4-Layer Data Flow

```
┌──────────────────────────────────────────────────────────────────────────┐
│                         4-LAYER DATA FLOW                                 │
│                                                                           │
│  ┌──────────────┐     ┌──────────────────┐     ┌──────────────────┐      │
│  │ PRESENTATION │     │   BUSINESS /     │     │   DATA /         │      │
│  │     / UI     │ ──► │  DOMAIN OPS      │ ──► │  PERSISTENT      │      │
│  │              │     │                  │     │                  │      │
│  │ Livewire     │     │ Actions*         │     │ Models           │      │
│  │ Controllers  │     │ Events           │     │ Entities         │      │
│  │ Console      │     │ Middleware       │     │ DTOs             │      │
│  │ Policies     │     │                  │     │ Enums            │      │
│  │              │     │ * domain logic   │     │ Database         │      │
│  │              │     │                  │     │ Config           │      │
│  └──────┬───────┘     └──────┬───────────┘     └────────┬─────────┘      │
│         │                    │                          │                │
│         │  DTO/FormReq      │      DTO                  │  Model         │
│         │  /LivewireForm    │      (input)              │  Record        │
│         └──────────────────►└──────────────────────────►┘                │
│                                                                           │
│  FRAMEWORK / INFRASTRUCTURE / UTILITIES (Layer 1)                         │
│  ┌──────────────────────────────────────────────────────────────────┐    │
│  │  Core base classes, contracts, exceptions, Services (infra       │    │
│  │  logic), Support (static utils), PHP/Laravel/Spatie packages     │    │
│  └──────────────────────────────────────────────────────────────────┘    │
│                                                                           │
│  BOUNDARY CONTRACTS:                                                       │
│  UI → Business:  DTO (BaseData) or typed scalars — never raw Request     │
│  Business → Domain: Model record for Entity construction                 │
│  Domain → Data:   Model attributes from DTO values                       │
│                                                                           │
│  🚫  NEVER: UI → Model::create() directly                                │
│  🚫  NEVER: Business → UI classes                                        │
│  🚫  NEVER: Entity → Action or Service                                   │
└──────────────────────────────────────────────────────────────────────────┘
```

### Two Kinds of Logic: Domain vs Infrastructure

The project distinguishes **three** class types — Actions (Layer 3), Services (Layer 1), Support
(Layer 1) — but only Actions contain **domain business logic**. This distinction is critical and
prevents the Service scope creep that motivated the Action Triad in the first place.

| Kind of Logic             | What It Is                                                                                                                                        | Example                                                         | Belongs In                                         |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- | -------------------------------------------------- |
| **Domain business logic** | Rules about _your product domain_ — internships, students, grades, enrollments, certificates. These change when **business requirements** change. | "A student must have an active placement before clocking in."   | Actions (Command/Read/Process) + Entities          |
| **Infrastructure logic**  | Rules about _the framework or system_ — environment checks, UI routing, module discovery. These change when **tech stack** changes.               | "Resolve which dashboard route to redirect based on user role." | Services (instance methods, constructor injection) |
| **Static utilities**      | Pure transformations with zero side effects — no DB, no events, no business decisions.                                                            | "Mask email addresses in log output."                           | Support (`public static` methods only)             |

**Quick decision flow:**

```
Does the class contain a business rule about internships, students, grades, etc.?
├─ Yes → Action + Entity (must have transaction, logging, event support)
└─ No → Does it need constructor injection / instance methods?
    ├─ Yes → Service (infrastructure logic only)
    └─ No → Support (static utilities only)
```

For comprehensive guidance on each type, see:

- [Action Pattern](architecture/action-pattern.md) — domain business logic
- [Service Pattern](architecture/service-pattern.md) — infrastructure logic
- [Support Pattern](architecture/support-pattern.md) — static utilities

For the complete data flow reference (mutation flow, read flow, event flow, DTO lifecycle, boundary
crossing tables), see [Data Pattern](architecture/data-pattern.md).

---

## Action Triad

The Action Triad is the single most important architectural decision in Internara. Actions split
into three distinct categories, each with a specific contract. All three live under
`app/{Module}/{SubModule}/Actions/` and follow the single `execute()` method convention.

| Type        | Purpose                                                | Base Class          | Transaction | Logging  | Events      |
| ----------- | ------------------------------------------------------ | ------------------- | ----------- | -------- | ----------- |
| **Command** | All writes — create, update, delete, state transitions | `BaseCommandAction` | Required    | Required | Recommended |
| **Read**    | Complex queries, aggregations, dashboards              | `BaseReadAction`    | Never       | Never    | Never       |
| **Process** | Multi-step orchestration of Command/Read Actions       | `BaseProcessAction` | Required    | Required | Required    |

**Key rules:**

- Every Action has exactly one public method: `execute()`
- Actions are the **only** entry point for mutations — Livewire never calls `Model::create()`
  directly
- Command/Process Actions SHOULD accept a `BaseData` DTO for 3+ params, use typed scalars for 1-2
- Command/Process Actions SHOULD return `ActionResponse` for structured feedback
- Actions MUST delegate business rules to Entities — throw `RejectedException` on violation

For the complete reference (contracts, naming, code examples, ActionResponse, migration paths), see
[Action Pattern](architecture/action-pattern.md).

---

## Circular Dependency Prevention

The architecture prevents circular dependencies through four structural mechanisms:

1. **Strict layer direction** — Dependencies flow one way: UI → Business → Domain → Data
2. **DTOs as boundary objects** — Carry only scalars, enums, Carbon — never Models or Actions
3. **Entity-only business checks** — Entities are `final readonly` with zero framework dependencies
4. **Event-based decoupling** — Cross-module side effects go through events, not direct calls

### Dependency Safety Rules

| #   | Rule                                                                                                                   | Violation Example                                         |
| --- | ---------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| R1  | **DTOs are leaf classes.** Carry only scalars, enums, Carbon. Never Models, Actions, Entities.                         | `CompanyData` with a `Company` property                   |
| R2  | **Entities (Layer 2) must not import Business/UI layers (Layer 3/4).**                                                 | `RegistrationState` importing `ApproveRegistrationAction` |
| R3  | **Layer 4 (UI) must not write to Layer 2 Models directly.** All persistence goes through Command Actions (Layer 3).    | `Company::create(...)` in Livewire                        |
| R4  | **UI may access Entities for READ-ONLY checks only.** WRITE decisions go through Actions.                              | `$reg->asState()->canBeApproved()` before calling Action  |
| R5  | **Actions prefer DTO for complex input.** 3+ params → DTO. 1-2 typed scalars → OK. Never raw `array`.                  | `execute(array $data)` for a 5-param operation            |
| R6  | **Services (Layer 1) must not call Actions (Layer 3).** Services own infrastructure logic (not domain business logic). | Service calling `CreateCompanyAction`                     |
| R7  | **Entities (Layer 2) must not perform I/O.** No DB queries, HTTP, cache, events, facades.                              | Entity calling `Cache::get()`                             |

For detailed circular dependency scenarios, detection, and fixes, see
[Modular Pattern](architecture/modular-pattern.md).

---

## Cross-Module Communication

Cross-module imports are **allowed** — import Models, Actions, or Policies from sibling modules
directly. Four patterns are available:

| Pattern           | When to Use                                                                 |
| ----------------- | --------------------------------------------------------------------------- |
| **Direct import** | Straightforward access with no side effects                                 |
| **Action call**   | Cross-module business operation                                             |
| **Module event**  | Fire-and-forget side effects (notifications, cache invalidation)            |
| **Core contract** | Abstraction used broadly across modules (`LabelEnum`, `SendsNotifications`) |

See [ADR-010](adr/adr-cross-module-communication.md).

---

## Reference Map

| Topic                                                                              | Document                                                                                               |
| ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| **Base class mandate**                                                             | [Coding Conventions](conventions.md) §1                                                                |
| **Complete pattern catalog**                                                       | [Modular Pattern Reference](architecture/modular-pattern.md)                                           |
| **Action contracts & examples**                                                    | [Action Pattern](architecture/action-pattern.md)                                                       |
| **Entity-model separation**                                                        | [Entity Pattern](architecture/entity-pattern.md)                                                       |
| **Model conventions**                                                              | [Model Pattern](architecture/model-pattern.md)                                                         |
| **DTOs & ActionResponse**                                                          | [Data Pattern](architecture/data-pattern.md)                                                           |
| **Enum & state machine**                                                           | [Enum Pattern](architecture/enum-pattern.md)                                                           |
| **Event dispatch & listeners**                                                     | [Event Pattern](architecture/event-pattern.md)                                                         |
| **Livewire component rules**                                                       | [Livewire Pattern](architecture/livewire-pattern.md)                                                   |
| **Policies & RBAC**                                                                | [Policy Pattern](architecture/policy-pattern.md)                                                       |
| **Exception hierarchy**                                                            | [Exception Pattern](architecture/exception-pattern.md)                                                 |
| **Logging & PII masking**                                                          | [Logging Pattern](architecture/logging-pattern.md)                                                     |
| **Caching strategy**                                                               | [Cache Pattern](architecture/cache-pattern.md)                                                         |
| **Service vs Support vs Action** (domain logic vs infra logic vs static utilities) | [Service Pattern](architecture/service-pattern.md), [Support Pattern](architecture/support-pattern.md) |
| **Why no Repository**                                                              | [Repository Pattern](architecture/repository-pattern.md)                                               |
| **Testing patterns**                                                               | [Testing Pattern](architecture/testing-pattern.md)                                                     |
| **Validation strategy**                                                            | [Modular Pattern](architecture/modular-pattern.md) §4                                                  |
| **Module structure & naming**                                                      | [Modular Pattern](architecture/modular-pattern.md)                                                     |
| **22 modules overview**                                                            | [Module Index](modules/index.md)                                                                       |
| **Module invariants**                                                              | [Coding Conventions](conventions.md), AGENTS.md                                                        |
