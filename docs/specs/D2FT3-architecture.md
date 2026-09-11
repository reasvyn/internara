# Architecture Design — Module-First 4-Layer Architecture

> **Spec ID:** D2FT3
> **Status:** Full
> **Owner:** Core
> **Depends on:** None

## Description

Defines the architectural foundation of Internara: module colocation, the 4-layer model, the
Action Triad, boundary objects (Entity/DTO/Model/Enum), data-flow contracts, and cross-module
dependency rules. This is the **governing architecture spec** — every other spec and every line of
code operates within this model; its requirements are project-global and may be tightened but never
violated by a feature spec.

The [tech-stack](FB792-tech-stack.md) spec pins dependency versions; the
[core-infra-services](ZT6VS-core-infra-services.md) spec defines runtime services (cache, session,
database, queue, mail, storage). Both are built inside this architecture. The rationale behind the
decisions here is recorded in the [architecture ADRs](../adr/adr-action-based-mvc-architecture.md)
and elaborated in [architecture.md](../architecture.md) and the
[pattern catalog](../guides/arch/index.md).

---

## 1. Problem Statements

### PS-1 — Flat Layering Scatters Features

A conventional flat structure (`app/Models`, `app/Http/Controllers`, `app/Services`) spreads a
single business concept across many unrelated directories. Fixing a bug in "placement" requires
jumping between six top-level directories, and nothing enforces that placement logic stays
together. Features become hard to find, hard to reuse, and easy to break silently.
**→ Requirement:** FR-ARC-001/002 (module colocation), FR-ARC-003 (public surface).

### PS-2 — Unbounded Cross-Module Coupling

Without rules, modules import each other freely: queries reach into another module's models,
mutations are performed by whoever can build a query. This creates tight coupling, hidden
dependencies, and — eventually — circular dependencies that are painful to untangle.
**→ Requirement:** FR-ARC-029/030 (owning-module rule), FR-ARC-031/032 (cycle prevention).

### PS-3 — Scattered Business Logic

Business rules (eligibility, grading, status transitions) end up inline in controllers, Livewire
components, and Model scopes. They cannot be unit-tested in isolation, cannot be reused across
entry points, and drift apart over time.
**→ Requirement:** FR-ARC-015 (rules in Entities), FR-ARC-018/019 (pure Entities), FR-ARC-023
(thin Models).

### PS-4 — Uncontrolled Mutation Paths

Without a single mutation path, writes bypass transactions, audit logging, event dispatch, and
validation. Two call sites can implement the same "approve placement" logic differently, with one
of them forgetting to log the change or clear the cache.
**→ Requirement:** FR-ARC-014 (mutations only through Actions), FR-ARC-024/025 (DTO-in,
Entity-evaluated), FR-ARC-044 (traceable flow).

### PS-5 — Unclear Class Boundaries

Without explicit contracts, Entity classes drift into Model territory (performing queries), Models
drift into business logic, and DTOs are bypassed by passing raw `Request` objects into Actions —
destroying validation boundaries and making every entry point a separate validation surface.
**→ Requirement:** FR-ARC-019/021 (C5/C6 purity), FR-ARC-020/022 (DTO boundary + ownership),
FR-ARC-023 (Model scope).

---

## 2. Goals & Non-Goals

### Goals

- **Module colocation** — every business concept lives as one vertical slice inside a single `app/{Module}` directory. *Why:* features stay findable, independently testable, and safe to change without silent cross-module coupling.
- **Strict 4-layer model** — dependencies flow one way only: Presentation → Business → Data → Framework/Infra. *Why:* upward or sideways imports are what create hidden coupling and circular dependencies.
- **Action Triad** (Command/Read/Process) as the only mutation and query path. *Why:* every mutation follows one traceable, auditable path with transactions, logging, and events.
- **Pure boundary objects** — Entities and DTOs free of framework I/O. *Why:* business rules become unit-testable without a database; each Action gets a single validation surface.
- **Explicit data-flow contracts** at every layer boundary. *Why:* predictable and reviewable; makes the architecture scan-enforceable.
- **Automated enforcement** — architecture rules are checked by scans, not convention alone. *Why:* convention rots; deterministic scans run before every commit.

### Non-Goals

- **Multi-tenant architecture**. *Why:* single-tenant by product definition (QLHDO DD-ARCH-001/005); tenant isolation would add middleware, scoped queries, and per-tenant config with no product need.
- **CQRS with physically separate read/write databases**. *Why:* one small dataset; the Action Triad already separates read intent from write intent at the code level.
- **Event sourcing**. *Why:* an immutable event log with replay is operational weight the PKL domain does not need at MVP.
- **Microservices / message-bus orchestration**. *Why:* one deployable Laravel app at school scale; module boundaries provide local cohesion without the distributed-operations burden.
- **Repository pattern**. *Why:* Eloquent is already the persistence API; the owning-module rule keeps models encapsulated (DD-ARC-006). See [repository-pattern.md](../guides/arch/repository-pattern.md).

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). The table format matches FR/NFR/DD;
fill `Layer` / `Status` only when the UC has a verifiable, code-testable consequence at this spec's
level — otherwise they remain `—`. These three developer workflows are verified by the arch scans,
so `Layer`/`Status` are filled.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-ARC-001 | Developer implements a feature inside a module following the 4-layer stack; every public surface is an Action | P0 | A | Full |
| UC-ARC-002 | Developer implements a cross-module feature via the ranked communication hierarchy without creating a dependency cycle | P0 | A | Full |
| UC-ARC-003 | Developer traces a mutation end-to-end: UI → DTO → Command Action → Entity rule → Model write → Event → ActionResponse | P0 | A | Full |

### 3.1 Developer Workflows

#### UC-ARC-001 — Implement a Feature Inside a Module
A developer picking up a placement ticket starts from a green scan baseline and a module that already exists. Everything new lands under `app/{Module}/` in the directory its layer owns, so a reviewer finds the mutation, the rule, and the component without hunting. The write path becomes a `CommandAction` that accepts a DTO while reads become `ReadAction`s; the business decision itself moves onto the module Entity so the Action stays a thin orchestrator. The Livewire component only invokes the Action and maps the returned `ActionResponse`, never touching a model directly. When `scan_violations.py` and `scan_class_contracts.py` both pass, layer direction and class contracts hold, and the feature is findable, testable, and arch-guarded. The working detail lives in [modular-pattern.md](../guides/arch/modular-pattern.md) and [action-pattern.md](../guides/arch/action-pattern.md).

#### UC-ARC-002 — Implement a Cross-Module Feature
Picture attendance needing a student's enrollment state: the attendance developer never queries the enrollment Model directly. Instead the call goes through enrollment's public `ReadAction`, and when attendance must change enrollment data it invokes enrollment's public `CommandAction`, letting enrollment's own events fire its side effects.

Dependency order comes from `config/module.php`, so a call the registry forbids is caught before it becomes a cycle. When two modules genuinely need each other, the shared concept drops into Core or behind a Core contract rather than surviving as a shortcut. The result carries no circular dependency and no cross-module model reach-in, governed by the [cross-module-communication ADR](../adr/adr-cross-module-communication.md) and FR-ARC-029–034.

#### UC-ARC-003 — Trace a Mutation End-to-End
An auditor chasing a disputed grade approval walks one fixed road. The Livewire component receives a validated DTO, never a raw `Request`, honoring D5 at the door. It calls `CommandAction::execute(DTO)`, and the Action orchestrates the rest: rule evaluation delegated to the Entity, persistence through the injected Model, and an Event dispatched for everything downstream. Listeners then absorb notifications, cache invalidation, and the activity log without bloating the Action. An `ActionResponse` carries success or failure back to the UI, so every mutation stays on one traceable, auditable path described in QLHDO §1.4 and [action-pattern.md](../guides/arch/action-pattern.md). ---

## 4. Functional Requirements

Global defaults every feature spec inherits. A feature spec may tighten but never violate these.

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-001 | All application code lives inside a business module under `app/Modules/{Module}` — never in a flat top-level layer | P0 | A | Full |
| FR-ARC-002 | A module owns its full vertical slice: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`, `Notifications/`, `Policies/`, `Livewire/`, `Http/`, routes, and `lang/` | P0 | A | Full |
| FR-ARC-003 | A module's public surface is its `Actions/`, `Services/`, `Contracts/`, `Events/`, `Entities/`, and `Enums/`; everything else is internal to the module | P0 | A | Full |
| FR-ARC-004 | The Core module (`app/Modules/Core/`) holds shared base classes, cross-cutting contracts, and module-independent infrastructure | P0 | A | Full |
| FR-ARC-005 | Each module is registered in `config/module.php`, auto-discovered from the `app/Modules/` directory listing (deterministic order, no manual registry); the dependency graph is documented in `docs/refs/modules/index.md` · [I1BCV](I1BCV-module-discovery.md) | P0 | A | Full |
| FR-ARC-006 | The architecture defines exactly four layers: 1 — Framework/Infrastructure/Utilities, 2 — Data/Persistent, 3 — Business/Domain Operations, 4 — Presentation/UI | P0 | A | Full |
| FR-ARC-007 | Layer directories map as: 4 → `Livewire/`, `Policies/`, `Http/`, views, routes; 3 → `Actions/`, `Events/`, `Listeners/`, `Notifications/`, `Console/`; 2 → `Models/`, `Entities/`, `Enums/`, `Data/`, `Types/`, database; 1 → `Core/`, `Services/`, `Support/` — full map in §6.2 | P0 | A | Full |
| FR-ARC-008 | Dependencies flow downward only: 4 → 3 → 2 → 1. No upward, sideways-skipping, or Layer-1-into-module-B business imports | P0 | A | Full |
| FR-ARC-009 | Core (Layer 1) depends only on the framework and approved packages — never on business modules | P0 | A | Full |
| FR-ARC-010 | A module importing another module directly must prefer the target's Read/Command Actions over its internals (FR-ARC-034) | P0 | A | Full |
| FR-ARC-011 | Three action base classes: `BaseCommandAction` (state mutation), `BaseReadAction` (query), `BaseProcessAction` (multi-step orchestration) | P0 | A | Full |
| FR-ARC-012 | Every Action exposes exactly one public entry point: `execute()` | P0 | A | Full |
| FR-ARC-013 | Command and Process Actions return `ActionResponse`; Read Actions return a value object, collection, or DTO | P0 | A | Full |
| FR-ARC-014 | Model mutations happen through Command/Process Actions — never in Livewire, Controllers, or Views (C1 invariant) | P0 | A | Full |
| FR-ARC-015 | Business rules live in the module's Entity; Actions orchestrate and stay thin | P0 | A | Full |
| FR-ARC-016 | Command/Process Actions with 3+ parameters accept a DTO (C7 invariant) | P0 | A | Full |
| FR-ARC-017 | Read-only access uses Read Actions; direct Model queries are limited to the owning module | P1 | A | Full |
| FR-ARC-018 | Entities are `final readonly`, constructed via `fromModel()` (or `fromArray()`), and expose value semantics (`equals()`, `with()`) | P0 | U | Full |
| FR-ARC-019 | Entities must not import Models, Data, Actions, or Livewire (C5 invariant) — only value types (scalars, enums, Carbon) | P0 | A | Full |
| FR-ARC-020 | DTOs extend `BaseData`, are `readonly`, and are the boundary object between UI and Business — never raw `Request` (D5 invariant) | P0 | A | Full |
| FR-ARC-021 | DTOs must not import Models or Actions (C6 invariant); they carry validated scalars, enums, and `Carbon` values | P0 | A | Full |
| FR-ARC-022 | A DTO is owned by the Action that consumes it and is defined in the module where that Action lives | P0 | A | Full |
| FR-ARC-023 | Models are persistence-focused: `#[Fillable]` attribute (D4), `HasUuids`, common scopes, and an Entity bridge method; no business rules | P0 | A | Full |
| FR-ARC-024 | UI → Business: validated DTO (or typed scalars), never raw Request (D5) | P0 | A | Full |
| FR-ARC-025 | Business → Data: Model attributes are derived from DTO values | P0 | A | Full |
| FR-ARC-026 | Data → Business: a Model record is used to construct the Entity for rule evaluation | P0 | A | Full |
| FR-ARC-027 | Cross-module side effects are dispatched as Events (see [NUCY3](NUCY3-event-system.md)) and handled by Listeners | P0 | A | Full |
| FR-ARC-028 | Business → UI class imports are forbidden (Blade/Livewire never imported from Actions, Entities, or DTOs) | P0 | A | Full |
| FR-ARC-029 | Cross-module reads call the source module's public `ReadAction` — never query another module's Models directly | P0 | A | Full |
| FR-ARC-030 | Cross-module mutations call the source module's public `CommandAction`; the caller never touches another module's Models | P0 | A | Full |
| FR-ARC-031 | The module registry derives deterministically from `config/module.php` (auto-discovery of `app/Modules/`); a dependency cycle is an architecture violation | P0 | A | Full |
| FR-ARC-032 | When a shared concept would create a cycle, it moves to Core (`app/Modules/Core/`) or a Core contract — never left as a cross-module shortcut | P0 | A | Full |
| FR-ARC-033 | Cross-module visibility is decided at design time; bypassing an Action to reach another module's internals requires a recorded design decision | P0 | A | Full |
| FR-ARC-034 | Cross-module communication follows the ranked hierarchy: 1) Core Contracts (Layer 3 shared interfaces), 2) Module Events (fire-and-forget), 3) Action Delegation (explicit `execute()` call), 4) Direct Import (simplest) — use the lowest coupling that satisfies the need | P0 | A | Full |
| FR-ARC-035 | Core Contracts in `App\Core\Contracts\` are the preferred decoupling for broadly-used abstractions (`LabelEnum`, `StatusEnum`, `SendsNotifications`) | P0 | A | Full |
| FR-ARC-036 | Tier 0 no-regret optimizations are enforced at any scale: composite indexes on FKs and `activity_log`, cache-key registry (`config/cache-keys.php`), eager loading (no N+1), Read Actions to avoid transaction overhead | P0 | A | Full |
| FR-ARC-037 | Tier 1 (shared hosting, ≤500 users) runs on MySQL/MariaDB + file cache + sync queue + database session with zero external services | P1 | A | Full |
| FR-ARC-038 | Tier 2 (VPS, 500–2000 users) and Tier 3 (HA, 2000+) transitions are `.env` swaps with zero code changes (e.g., `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, read replica) | P1 | A | Full |
| FR-ARC-039 | Deferred until measured: Laravel Octane, horizontal auto-scaling, CDN for static assets, database sharding, queue job batching — only adopted when `docs/architecture.md` and Pulse show a bottleneck | P2 | A | Planned |
| FR-ARC-040 | DTO adoption follows Start `array` → Stabilize `Data|array` union → Final `Data` only, with `BaseData::fromArray()` preserving callers during migration | P1 | A | Full |
| FR-ARC-041 | Cache invalidation follows Start `Cache::forget()` inline → Stabilize event+listener → Final `config/cache-keys.php` registry with listener-driven invalidation | P1 | A | Full |
| FR-ARC-042 | Validation rules follow Start Form-Object-only → Stabilize `Entity::rules()` shared → Final centralized in Entities for full DRY | P1 | A | Full |
| FR-ARC-043 | Every module implements at least one Command or Read Action exposing its public surface | P0 | A | Full |
| FR-ARC-044 | Every mutation flow is traceable: Livewire → DTO → Command Action → Entity → Model → Event | P0 | A | Full |

### 4.1 Module Colocation

#### FR-ARC-001 — All code inside modules
A stray `app/Services/PlacementService.php` is how flat-layer rot restarts: one convenience folder becomes ten, and the next placement bug hunts across all of them. Business code therefore lives only inside `app/Modules/{Module}`, with `app/Models/`, `app/Http/Controllers/`, and `app/Services/` treated as absent by convention and by scan.

Shared infrastructure with no single module owner belongs in Core rather than a new top-level folder. The `scan_naming.py` and `scan_module_boundaries.py` pair keeps this green at layer `A`.

#### FR-ARC-002 — Full vertical slice
Opening a module directory should read like opening a dossier: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`, `Notifications/`, `Policies/`, `Livewire/`, `Http/`, routes, and `lang/` each in their place. Multi-submodule modules nest each domain under `Domain/{Domain}/` following [modular-pattern.md](../guides/arch/modular-pattern.md) §1.6, which settles directory depth while this requirement settles the invariant itself. The `scan_naming.py` directory-layout check enforces the slice at layer `A`, so a missing slice is a structural failure rather than a style opinion.

#### FR-ARC-003 — Public surface only
During enrollment week a reporting module once reached straight into placement Models to shave a query, and the next placement refactor broke reporting silently.

Cross-module imports may therefore touch only `Actions/`, `Services/`, `Contracts/`, `Events/`, `Entities/`, and `Enums/`; everything else stays internal. A cross-module call landing on a Model owned elsewhere trips FR-ARC-029 and the `MOD_XMOD_INTERNAL` rule in `scan_module_boundaries.py`, which guards the boundary at layer `A`.

#### FR-ARC-004 — Core owns shared infrastructure
Every `BaseAction`, `BaseEntity`, `BaseData`, and `BaseModel`, plus cross-cutting contracts such as `LabelEnum` and `StatusEnum`, exceptions, middleware, notification channels, and module-independent services, lives in `app/Modules/Core/`. A school that keeps shared grading math in a placement helper eventually forks it into three incompatible copies; Core is the single home that prevents the fork. The `MOD_CORE_IMPORT` rule in `scan_module_boundaries.py` holds the line, and Core reaching into a business module breaks FR-ARC-009 by definition.

#### FR-ARC-005 — Module registry from config
The registry is never hand-edited into drift: `config/module.php` derives deterministically from the `app/Modules/` directory listing, and module dependency order is read from that same registry.

A business module directory sitting on disk without a matching registry entry is an incomplete registration, and the dependency graph documented in `docs/refs/modules/index.md` must mirror what actually ships. The module-boundary scan plus `scan_doc_links.py` over the graph doc close the loop, per [I1BCV](I1BCV-module-discovery.md).

### 4.2 4-Layer Model

#### FR-ARC-006 — Four layers
Four layers means four, not five with a quiet `app/Helpers` tier smuggled in through a service provider. Framework/Infrastructure/Utilities sits at the bottom, Data/Persistent above it, Business/Domain Operations next, Presentation/UI on top, and nothing invents an implicit middle tier such as an ad-hoc middleware service layer. The `scan_violations.py` layer checks carry the invariant, with [architecture.md](../architecture.md) spelling out the shape reviewers apply daily.

#### FR-ARC-007 — Directory → layer map
The abbreviated map in the table points at the canonical §6.2 map: `Livewire/`, `Policies/`, `Http/`, views, and routes on layer 4; `Actions/`, `Events/`, `Listeners/`, `Notifications/`, and `Console/` on layer 3; `Models/`, `Entities/`, `Enums/`, `Data/`, `Types/`, and the database on layer 2; `Core/`, `Services/`, `Support/` plus framework and packages on layer 1.

A file sitting in a directory owned by another layer violates regardless of what its author intended, because placement is the contract. The `scan_naming.py` and `scan_violations.py` pair enforces placement at layer `A`.

#### FR-ARC-008 — Downward dependencies only
Dependencies run 4 → 3 → 2 → 1 and nowhere else. Upward imports from layer 2 into layer 3 or 4, sideways skips from layer 4 straight into layer 2 past the Business layer, and business imports originating in layer 1 all break the flow that keeps coupling visible. `RejectedException` looks like an exception to newcomers but is not one: exceptions and contracts live on layer 1 by design, so every layer importing them still points downward. The direction rules in `scan_violations.py` catch the rest.

#### FR-ARC-009 — Core purity
Core classes never name `App\Modules\{business}`. Core services build on Laravel, PHP, and approved packages only, which is what lets eighteen modules trust Core without auditing it per release.

When Core genuinely needs a business concept, the dependency inverts: the business module implements a Core contract instead, per FR-ARC-035. The `MOD_CORE_IMPORT` check in `scan_module_boundaries.py` proves the purity on every run.

#### FR-ARC-010 — Direct module imports
Direct import is the lightest coupling in the FR-ARC-034 hierarchy, and it stays legal only when it aims at the target module's public surface. A placement Action calling a school ReadAction reads the target's front door, never its internals. The module-boundary scan watches the doorway at layer `A`, turning reach-ins into visible findings.

### 4.3 Action Triad

#### FR-ARC-011 — Three action base classes
`BaseCommandAction` mutates state, `BaseReadAction` answers queries, `BaseProcessAction` sequences multi-step orchestrations, and no fourth shape appears by accident.

The contracts behind them sit in §6.3, with the reasoning recorded in the [action-pattern-over-services ADR](../adr/adr-action-pattern-over-services.md). The `scan_class_contracts.py` check asserts every concrete Action extends its correct base at layer `A`, so a miscategorized Action fails fast instead of drifting into a god service.

#### FR-ARC-012 — One public entry point
Each concrete Action speaks through exactly one public `execute()`; the shared helpers (`validate()`, `authorize()`, `respond()`, `fail()`) stay on the base classes and out of the public API. A reviewer therefore never wonders which method is the real entry point, and a second public method on a concrete Action is a contract break. The `scan_class_contracts.py` check guards the single door at layer `A`.

#### FR-ARC-013 — Return contracts
Commands and Processes answer with `ActionResponse`; Reads answer with a value object, a collection, or a DTO. The distinction matters at midnight during enrollment disputes, when a caller needs to know whether a result carries success semantics or plain data.

A Read Action leaking a raw Eloquent Model across a module boundary breaks the deal, so it maps to an Entity, DTO, or collection first under FR-ARC-017 and FR-ARC-029. The `scan_class_contracts.py` check plus focused unit tests verify both halves.

#### FR-ARC-014 — Mutations only through Actions
Model `create`, `update`, and `delete` never appear in Livewire, Controllers, or Views. A `Placement::create()` hiding in a component is the C1 violation that lets one call site skip transactions, logging, and events while another honors them. The C1 check in `scan_violations.py` finds those strays at layer `A` before they fork behavior.

#### FR-ARC-015 — Business rules in Entities
Eligibility, quota, and transition rules live on the module Entity, reached through `fromModel()`, while the Action orchestrates and stays thin. An approval rule buried in a Livewire method cannot be reused by the console import that needs the same invariant next month.

Entity shape is asserted by the contract scan at layer `A`, with spot unit tests at layer `U` proving the rule itself in isolation.

#### FR-ARC-016 — DTO for 3+ parameters
Once an Action needs three parameters it takes `execute(DTO $data)`; one or two typed scalars may stay direct, but the third argument forces the DTO boundary under C7. The rule kills the long positional-argument lists that silently swap quota and period during enrollment crunch. The C7 check in `scan_violations.py` counts the parameters so reviewers do not have to.

#### FR-ARC-017 — Read Actions for read-only access
Cross-module reads travel through Read Actions; direct Model queries stay inside the module that owns the Model.

Inside one module a trivial lookup may still query its own Model directly, because only the ownership boundary is hard. The module-boundary scan draws that boundary at layer `A`, flagging the reach-in that would otherwise become a hidden dependency.

### 4.4 Boundary Objects

#### FR-ARC-018 — Entity form
Entities are `final readonly` snapshots built through `fromModel()`, with `fromArray()` available for test and seed construction, and they compare and copy by value through `equals()` and `with()`. The full shape lives in [entity-pattern.md](../guides/arch/entity-pattern.md), where the Model-to-Entity bridge keeps column churn in one place. The entity contract in `scan_class_contracts.py` verifies the form at layer `A`, so a mutable or constructor-drifting Entity fails structurally.

#### FR-ARC-019 — Entity purity (C5)
An Entity imports only value types: scalars, enums, Carbon.

Models, Data, Actions, and Livewire stay outside, because the moment an Entity queries or dispatches it stops being unit-testable in milliseconds. The C5 check in `scan_violations.py` polices the import list at layer `A`, catching the convenient `use` that would weld the rule to the framework.

#### FR-ARC-020 — DTO boundary object
UI crosses into Business as a validated `BaseData` DTO that is `readonly`; a raw `Request` never reaches an Action. Without that gate every entry point becomes its own validation surface and the same placement form validates differently on web and import. The D5 check in `scan_violations.py` together with `scan_class_contracts.py` holds the single surface at layer `A`.

#### FR-ARC-021 — DTO purity (C6)
DTOs carry validated scalars, enums, and `Carbon` values, nothing that persists or executes.

Models and Actions have no place in the import list, since a DTO that can query is a validation boundary that can be bypassed from inside. The C6 check in `scan_violations.py` keeps the payload inert at layer `A`.

#### FR-ARC-022 — DTO ownership
A DTO lives in the `Data/` directory of the module whose Action consumes it; ownership follows the consumer, never a neutral shared folder. During an assessment refactor a DTO parked in a common directory silently gained fields for two callers until neither validation surface matched its form. A DTO genuinely shared across two modules earns a place in Core `Data/` only after contract review, where the sharing is deliberate and versioned.

#### FR-ARC-023 — Models are persistence adapters
Models expose `#[Fillable]` under D4, `HasUuids`, shared scopes, and the Entity bridge method, and they stop there.

Business rules migrate to the Entity, leaving the Model a thin adapter that survives schema renames without taking domain logic down with it. The D4 check in `scan_conventions.py` and the model contract in `scan_class_contracts.py` verify the adapter shape.

### 4.5 Data Flow

#### FR-ARC-024 — UI → Business via DTO
A Livewire form that passes `$request->all()` straight into an Action still breaks D5 even when the form validated, because validation at the edge is not a boundary object the Business layer can trust. The Action receives the DTO and nothing else, so the same placement payload validates identically whether it arrives from a component, a console import, or a test. The D5 leg of `scan_violations.py` plus feature tests asserting validation-before-persistence keep the gate closed.

#### FR-ARC-025 — Business → Data
Model attributes derive from DTO values, with bulk fill limited to whitelisted keys behind the D4 `#[Fillable]` attribute and explicit assignment for anything computed.

A computed status stamped through mass assignment is how a crafted payload once flipped a placement state; explicit fields close that hole. The D4 check in `scan_conventions.py` plus review of the assignment path verify the derivation.

#### FR-ARC-026 — Data → Business
Rules never run against raw model state. A Model record crosses into rule evaluation only by constructing its Entity through `fromModel()`, the single construction path defined in [entity-pattern.md](../guides/arch/entity-pattern.md). That bridge is where a column rename ripples exactly once instead of scattering across Actions. Entity unit tests that build exclusively through `fromModel()` prove the path holds.

#### FR-ARC-027 — Cross-module side effects as Events
The originating Action stays focused on its own mutation while notifications, cache invalidation, and logging fan out as Events into Listeners.

Side effects modeled this way stay discoverable and replayable instead of hiding inside a helper call the next developer never finds. The contract lives in [NUCY3](NUCY3-event-system.md), with `scan_violations.py` and review sharing enforcement.

#### FR-ARC-028 — No Business → UI imports
Layer 3 and layer 2 never name layer 4 classes. An Entity importing a Livewire component welds the domain to the presentation lifecycle, and the next UI refactor breaks a rule that should never have known the UI existed. The import rule in `scan_violations.py` draws the line at layer `A`, failing the build on the upward reference.

### 4.6 Cross-Module Dependencies

#### FR-ARC-029 — Cross-module reads via Read Actions
A reporting dashboard that queries another module's Models directly works until the source module renames a scope, then breaks at runtime with no contract to blame.

Cross-module reads call the source module's public `ReadAction`, which owns the query shape and its evolution. The `scan_module_boundaries.py` check flags the direct reach-in at layer `A`, turning the shortcut into a visible finding.

#### FR-ARC-030 — Cross-module mutations via Command Actions
Changing another module's data means calling its public `CommandAction` and letting the owner run its transactions, rules, and events. The caller never touches the other module's Models, so quota checks and audit logging cannot be skipped by a well-meaning shortcut. The module-boundary scan enforces the handoff at layer `A`.

#### FR-ARC-031 — Registry is authoritative
Dependency order is read from `config/module.php`, auto-discovered from `app/Modules/`, and a cycle in that graph is an architecture violation rather than a warning.

Two modules importing each other may compile today and deadlock refactors for months; the registry makes the cycle undeniable. The module-boundary scan reports cycles at layer `A`, so the graph either stays acyclic or fails loudly.

#### FR-ARC-032 — Cycles move to Core
When attendance and scheduling both need the current academic year, neither module should own what both consume. The shared concept moves into `app/Modules/Core/` or behind a Core contract instead of surviving as a cross-module shortcut that cycles. A genuinely cross-cutting lookup such as the active year may live in Core `Support/`, though a Core contract stays the preferred first choice. Review plus the `scan_module_boundaries.py` cycle report confirm the extraction.

#### FR-ARC-033 — Visibility decided at design time
Deliberate reach-ins are not accidents; they are recorded. Bypassing an Action to touch another module's internals is allowed only with a written decision in this spec's DD set, the governing ADR, or an issue-linked note that future reviewers can find.

The scan still flags the reach-in so the exception stays visible, and the review gate checks the paper trail before it passes.

### 4.7 Communication Discipline

#### FR-ARC-034 — Ranked communication hierarchy
Four rungs, lowest coupling that works: Core Contracts for shared interfaces on layer 3, Module Events for fire-and-forget, Action Delegation through an explicit `execute()` call, Direct Import as the simplest. Notifications and cache invalidation fit events because nobody waits for an answer; a caller that needs the result delegates to an Action instead of hoping a listener replies. The ordering comes from the [cross-module-communication ADR](../adr/adr-cross-module-communication.md), which reviewers apply as the default ladder.

#### FR-ARC-035 — Core Contracts for broad abstractions
Widely consumed shapes such as `LabelEnum`, `StatusEnum`, and `SendsNotifications` live as Core Contracts under `App\Core\Contracts\` and are consumed through the contract rather than a concrete module class.

A business module implements the contract; callers depend on the abstraction, so the third consumer of a shape does not drag in the first module's internals. The `scan_class_contracts.py` check verifies the wiring at layer `A`.

### 4.8 Performance & Growth Tiers

#### FR-ARC-036 — Tier 0 no-regret
Tier 0 is the work that never needs a rewrite: composite indexes on foreign keys and `activity_log`, the `config/cache-keys.php` registry, eager loading that kills N+1 before it ships, and Read Actions that keep reporting off transaction overhead. A morning dashboard that fires a thousand queries during attendance rush is a defect here, not a scaling story. Migration review for the indexes, the cache-key presence scan, and N+1 review together hold the tier at layer `A`.

#### FR-ARC-037 — Tier 1 defaults
A small school on $5 shared hosting runs MySQL or MariaDB with file cache, sync queue, and database session, and zero external services.

Nothing in Tier 1 assumes Redis, a worker fleet, or object storage; those arrive later as swaps, not rewrites. The default matrix follows the [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md), proven by a deployment smoke pass on shared hosting with a browser spot check.

#### FR-ARC-038 — Tier 2/3 via config only
Growth from 500 to beyond 2,000 users never forks the code. Cache, queue, and read-replica switches read from env — `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` — with no tier branches in application logic. The doctrine is recorded in the [performance-optimization ADR](../adr/adr-performance-optimization.md). Verification reads the env keys and confirms the `.env` variant documented alongside them.

#### FR-ARC-039 — Deferred until measured
Laravel Octane, horizontal auto-scaling, CDN for static assets, database sharding, and queue job batching wait for evidence.

`docs/architecture.md` and Pulse name the bottleneck first; only then does the optimization graduate from `Planned`. Adopting any of them ahead of demand breaks the no-regret doctrine this tier exists to defend.

### 4.9 Gradual Migration

#### FR-ARC-040 — DTO adoption phases
Legacy callers pass `array` on day one, straddle a `Data|array` union while the migration stabilizes, and land on `Data` only at the end. `BaseData::fromArray()` is what keeps old callers compiling through the middle phase instead of forcing a flag-day rewrite across eighteen modules. The [gradual-migration ADR](../adr/adr-gradual-migration.md) records the three-phase walk and its exit criteria.

#### FR-ARC-041 — Cache invalidation phases
Inline `Cache::forget()` calls start the journey, an event plus listener carries the middle, and the final state resolves every key through the `config/cache-keys.php` registry with listener-driven invalidation.

A renamed key mid-migration would otherwise orphan stale entries across enrollment dashboards. The [gradual-migration ADR](../adr/adr-gradual-migration.md) governs the sequence down to its registry-driven end state.

#### FR-ARC-042 — Validation centralization phases
Validation starts in Form Objects, converges on a shared `Entity::rules()` during stabilization, and finishes centralized in Entities for full DRY. The middle phase is where duplicate placement rules collapse into one home instead of drifting across three forms. The [gradual-migration ADR](../adr/adr-gradual-migration.md) names Entities the final owner of shared rules.

#### FR-ARC-043 — Every module exposes an Action
No module ships as a sealed box. Each one exposes at least one Command or Read Action as its public surface, so cross-module callers always have a front door to knock on.

A module without an Action is undiscoverable by construction. The `scan_class_contracts.py` and module scans confirm the doorway exists at layer `A`.

#### FR-ARC-044 — Mutation flow traceability
Every mutation walks the same road: Livewire into a DTO, into a Command Action, through the Entity, into the Model, out through an Event. The original arch-test guard that once proved this fell to a `pest-plugin-arch` compatibility bug, as the [action-based-mvc ADR](../adr/adr-action-based-mvc-architecture.md) records, so enforcement currently pairs the C1 leg of `scan_violations.py` with review at layer `A` until the guard returns. ---

## 5. Non-Functional Requirements

Project-level NFRs for the architecture. `Target` = `N/A` means the requirement is enforced
architecturally and verified via scans/tests rather than a runtime measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-ARC-001 | Architecture invariants C1–C8 and D1–D6 are enforced by automated scans | N/A | P0 | A | Full |
| NFR-ARC-002 | Class contracts (Action/Entity/DTO/Model/Enum) are enforced by scans | N/A | P0 | A | Full |
| NFR-ARC-003 | New code introduces no top-level directories under `app/` without a spec or recorded decision | N/A | P0 | A | Full |
| NFR-ARC-004 | Actions eager-load relations; N+1 queries are an architecture defect (S3 — Pragmatic Scalability) | N/A | P1 | A | Full |
| NFR-ARC-005 | Authorization is enforced at every layer: Policies guard Presentation; Actions/Entities enforce business authorization via `RejectedException` (C8) | N/A | P0 | A | Full |
| NFR-ARC-006 | Clean-code/DRY: duplicated logic is extracted into shared, named units; modules reuse Core rather than copy (S2 — Sustained Maintainability) | N/A | P1 | A | Full |
| NFR-ARC-007 | Single-tenant deployment matrix: SQLite (dev/test) / MySQL-MariaDB (prod) + file/database cache + sync queue + database session + local disk by default; Redis/S3/Reverb are optional `.env` overrides | N/A | P0 | A | Full |

### 5.1 Architecture Integrity

#### NFR-ARC-001 — Scan-enforced invariants
Convention alone once let a Livewire component mutate a Model during a demo rush, and nobody noticed until the audit log gap surfaced. The C1–C8 and

D1–D6 invariants now run as code: `python3 tools/scan_violations.py` is the CI gate, and the pre-commit baseline in AGENTS.md §4 keeps the arch-guard batch green before anything merges.

#### NFR-ARC-002 — Class-contract scans
A misbased Action or a mutable Entity used to survive review because the shape lived only in a guide. The `python3 tools/scan_class_contracts.py` run asserts Action, Entity, DTO, Model, and Enum contracts structurally, so the pre-commit arch-guard at layer `A` rejects the drift before human eyes ever need to.

#### NFR-ARC-003 — No ad-hoc top-level dirs
The morning someone adds `app/Helpers` for a single date function, the module boundary starts eroding.

New top-level directories under `app/` now require a spec amendment or a recorded DD, with `scan_naming.py` and review sharing the gate. The friction is deliberate: extraction into Core should be a decision, never a shortcut.

#### NFR-ARC-004 — No N+1
A placement dashboard that loads 400 students and then queries mentors row by row turns enrollment morning into a timeout parade. Read Actions eager-load their declared relations, and an N+1 that slips through counts as an architecture defect under S3 Pragmatic Scalability. Review plus targeted feature tests with DB query logging across layers `A` and `F` keep the count at zero.

#### NFR-ARC-005 — Defense in depth
A forged Livewire call once reached an approval Action the component hid but never guarded.

Policies now gatekeep Presentation with 403s while Actions and Entities reject business violations through `RejectedException` under C8, so either layer stops the call alone. The pairing mirrors QLHDO FR-GLB-008 and [T4B26](T4B26-rbac-and-authorization.md), verified through `scan_class_contracts.py` and per-role policy allow/deny unit tests.

#### NFR-ARC-006 — Clean-code DRY
Three modules once carried three copies of the same quota math, and a policy change fixed two of them. Duplicated logic now extracts into shared, named units, with Core as the reuse home rather than copy-paste under S2 Sustained Maintainability. The `scan_violations.py` run plus review catches the second copy before it becomes the third.

#### NFR-ARC-007 — Default-zero-external-services matrix
A school with no budget for Redis runs the same binary as the school with a VPS: SQLite for dev and test, MySQL or MariaDB in prod, file or database cache, sync queue, database session, and local disk by default, with Redis, S3, and Reverb waiting as `.env` overrides. No feature goes dark in any tier. The full matrix lives in the [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md), proven by a fresh deploy smoke on shared hosting.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against. Full per-layer contracts live in the
governing specs ([SE5Q9](SE5Q9-base-classes.md) implements these class contracts).

### 6.1 Module Skeleton

```
app/{Module}/
├── Actions/          # Command/Read/Process actions (Layer 3)
├── Entities/         # final readonly domain objects (Layer 2)
├── Enums/            # LabelEnum/StatusEnum contracts (Layer 2)
├── Data/             # DTOs (BaseData) owned by consuming Actions (Layer 2)
├── Models/           # Eloquent models, #[Fillable], entity bridge (Layer 2)
├── Events/           # domain events (Layer 3)
├── Listeners/        # event handlers (Layer 3)
├── Notifications/    # mail/notification classes (Layer 3)
├── Policies/         # authorization policies (Layer 4)
├── Livewire/         # components (Layer 4)
├── Http/             # controllers/requests if REST is used (Layer 4)
├── Services/         # infrastructure logic (Layer 1)
└── Support/          # module-local helpers (Layer 1)
```

### 6.2 Layer → Directory Map

| Layer | Directories |
| ----- | ----------- |
| 4 — Presentation/UI | `{Module}/Livewire/`, `{Module}/Livewire/Concerns/`, `{Module}/Policies/`, `{Module}/Policies/Concerns/`, `{Module}/Http/Controllers/`, `{Module}/Http/Requests/`, `{Module}/Http/Middleware/`, `resources/views/{module}/`, `routes/web/{module}.php` |
| 3 — Business/Domain Ops | `{Module}/Actions/`, `{Module}/Actions/Concerns/`, `{Module}/Events/`, `{Module}/Listeners/`, `{Module}/Notifications/`, `{Module}/Console/Commands/`, `app/Modules/Core/Channels/` (notification channels), `app/Modules/Core/Channels/Data/` (channel DTOs) |
| 2 — Data/Persistent | `{Module}/Models/`, `{Module}/Models/Concerns/`, `{Module}/Entities/`, `{Module}/Enums/`, `{Module}/Data/`, `Types/`, `app/Modules/Core/Events/BaseEvent.php` (cross-module event base), database |
| 1 — Framework/Infra | `app/Modules/Core/`, `{Module}/Services/`, `{Module}/Support/`, `app/Modules/Core/Contracts/`, `app/Modules/Core/Exceptions/` + `Concerns/`, `app/Modules/Core/Http/Middleware/`, PHP, Laravel, packages |

### 6.3 Base Class Contracts

```php
// Layer 3 — Actions (app/Modules/Core/Actions/)
abstract class BaseAction          { }  // marker + shared concerns: transaction(), log(), dispatchEvent()
abstract class BaseCommandAction extends BaseAction { }  // respond()/validate()/authorize()/fail()
abstract class BaseProcessAction extends BaseAction { }  // step()/trackProgress()/notify()/fail()
abstract class BaseReadAction      { }  // standalone (does NOT extend BaseAction): remember()/forget()/mask()/paginate()

// execute(): exactly one public method, declared in each concrete Action
// (convention + scan-enforced, FR-ARC-012). Command/Process return ActionResponse;
// Read returns value data.

// Layer 2 — Entities (app/Modules/Core/Entities/BaseEntity.php)
abstract readonly class BaseEntity implements JsonSerializable {
    abstract public static function fromModel(Model $model): static;
    public function toArray(): array;        // value snapshot
    public function equals(self $other): bool; // value equality
    public function with(string $property, mixed $value): static; // immutable copy
}

// Layer 2 — DTOs (app/Modules/Core/Data/BaseData.php)
abstract readonly class BaseData implements JsonSerializable {
    public function toArray(): array;        // all properties
    public function only(string ...$keys): array;
    public function except(string ...$keys): array;
    public function merge(array $overrides): static;
}

// Layer 3 — Action result (app/Modules/Core/Data/ActionResponse.php)
final readonly class ActionResponse implements JsonSerializable {
    public static function ok(mixed $data = null, ?string $message = null): self; // success
    public static function created(mixed $data = null, ?string $message = null): self;
    public static function updated(mixed $data = null, ?string $message = null): self;
    public static function deleted(?string $message = null): self;
    public static function error(string $message, array $errors = []): self;      // failure
    public function withRedirect(string $url): self;
    public function failed(): bool;
    public function jsonSerialize(): array;   // {success, data, message, redirect, errors}
}
```

### 6.4 Data Flow

```
 Livewire (L4) ──validated DTO──▶ CommandAction (L3) ──delegates rules──▶ Entity (L2)
      │                                 │                                    │
      │                                 ▼                                    ▼
   ActionResponse ◀──success/fail── Action ──persists via──▶ Model (L2) ──▶ Database
                                        │
                                        ▼
                                    Event (L3) ──▶ Listeners (side effects)
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence; per QLHDO these are recorded decisions, not test rows.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-ARC-001 | Module-first vertical slicing over flat layering | P0 | — | — |
| DD-ARC-002 | Action Triad over a generic service layer | P0 | — | — |
| DD-ARC-003 | Entity/Model split — pure Entities, thin Models | P0 | — | — |
| DD-ARC-004 | DTOs as boundary objects — no raw Request into Actions | P0 | — | — |
| DD-ARC-005 | Events for cross-module side effects | P1 | — | — |
| DD-ARC-006 | No repository pattern | P0 | — | — |
| DD-ARC-007 | Automated architecture enforcement | P0 | — | — |

### 7.1 Structure

#### DD-ARC-001 — Module-First Vertical Slicing over Flat Layering
A placement bug once scattered across `app/Models`, a controller, and two service folders took a day to trace because no directory owned the concept. Organizing by business module puts the whole concept in `app/{Module}`, so the fix, the test, and the review all start in one place that stays findable and independently testable. The price is deliberate extraction: shared infrastructure earns its move into Core under FR-ARC-004 and FR-ARC-032 instead of lingering in ad-hoc shared folders, which flat layering would have permitted silently.

#### DD-ARC-002 — Action Triad over a Generic Service Layer
The generic `PlacementService` with a dozen public methods hid mutations, queries, and orchestration behind one name, and no scan could tell them apart.

Command, Read, and Process base classes make intent structural: mutations carry transactions and audit trails, queries stay lock-free, orchestration sequences named Actions. The triad costs more classes than a single service, a trade gladly paid in a single-tenant system where every mutation under UC-ARC-003 must stay traceable and scan-enforceable.

#### DD-ARC-003 — Entity/Model Split
Eloquent models that enforce their own approval rules weld business invariants to the query builder and the test database. Pure value-object Entities carry the rules and run in millisecond unit tests, while Models remain thin persistence adapters behind D4 and the Entity bridge. Each rule evaluation pays one `fromModel()` call, a small toll for invariants that survive schema renames. The split is argued fully in the [entity-model-separation ADR](../adr/adr-entity-model-separation.md).

#### DD-ARC-004 — DTOs as Boundary Objects
A raw `Request` arriving in an Action once let two entry points validate the same placement payload differently, and the import path wrote rows the web form would have rejected.

Validated `BaseData` DTOs close that fork with one surface per Action under C7 and D5, carrying no framework dependence so tests construct them deterministically. The cost is a DTO per Action signature, replacing ad-hoc arguments under FR-ARC-016 and FR-ARC-021 with something reviewable.

#### DD-ARC-005 — Events for Cross-Module Side Effects
An approval Action that sends mail, flushes cache, and writes logs inline grows a second job every semester until nobody dares touch it. Dispatching side effects as Events into Listeners keeps the originating Action focused while the effects stay discoverable and replayable under [NUCY3](NUCY3-event-system.md). The indirection stings for a single notification, and across module boundaries under FR-ARC-027 that sting buys the decoupling that lets each side evolve alone.

#### DD-ARC-006 — No Repository Pattern
Eloquent already answers the persistence question, so a repository layer would wrap queries in forwarding methods that add review surface without adding safety.

Models stay directly usable by their owning module's Actions, and the owning-module rule under FR-ARC-029 and FR-ARC-030 provides the encapsulation a repository would promise. The full argument against the extra layer lives in [repository-pattern.md](../guides/arch/repository-pattern.md).

#### DD-ARC-007 — Automated Architecture Enforcement
A conventions document that nobody runs rots within a sprint; the `tools/` scans covering C1–C8, D1–D6, contracts, naming, and module boundaries run before commit and fail loudly instead. Deterministic checks beat manual review on speed and memory, which is the Automation-First doctrine applied to architecture itself. The standing cost is scan maintenance as the architecture evolves, a small owned burden next to the coupling it prevents. ---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Module colocation | 100% of `app/` code inside modules/Core | `scan_naming.py` + directory audit |
| Architecture violations | 0 | `python3 tools/scan_violations.py` |
| Class-contract violations | 0 | `python3 tools/scan_class_contracts.py` |
| Module-boundary violations | 0 | `python3 tools/scan_module_boundaries.py` |
| Circular dependencies | 0 | module graph in `config/module.php` + scan |
| Mutations through Actions | 100% (no Livewire model mutation) | C1 scan in `scan_violations.py` |
| Boundary purity (C5/C6) | 0 forbidden imports | `scan_violations.py` |
| Docs ↔ code ↔ specs | aligned | `scan_doc_links.py`, spec audit |

---

## 9. Roadmap

### Prerequisites

None — this is the governing, foundational architecture spec. It must be read (and understood)
before the tech-stack, infrastructure-services, and base-class specs.

### Build Guide

This spec is satisfied continuously: every module, Action, Entity, DTO, and Model built by the
lower-level specs must comply with FR-ARC-001–FR-ARC-044. `docs/architecture.md` and the
`docs/guides/arch/` pattern docs are the living reference for implementation; this spec is the
authoritative contract.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [tech-stack.md](FB792-tech-stack.md) | Pins the dependency versions the architecture builds upon |
| 2 | [core-infra-services.md](ZT6VS-core-infra-services.md) | Defines the runtime services (cache, session, DB, queue, mail, storage) |
| 3 | [base-classes.md](SE5Q9-base-classes.md) | Implements BaseAction/BaseEntity/BaseData/BaseModel contracts (FR-ARC-011–023) |
| 4 | [module-discovery.md](I1BCV-module-discovery.md) | Module registry and dependency order (FR-ARC-005, FR-ARC-031) |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | Arch-test guard removed due to `pest-plugin-arch` compatibility bug; boundary enforcement relies on code review until the guard is restored (per [action-based-mvc ADR](../adr/adr-action-based-mvc-architecture.md)) | Open | Maintainer | — |
| A-1 | We assume `scan_module_boundaries.py` and the other `tools/` scans cover the ground the removed arch tests asserted | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture overview](../architecture.md) — 4-layer model, data-flow diagram, R1–R7 rules
- [Conventions](../conventions.md) — C1–C8 and D1–D6 invariants referenced throughout
- [Pattern catalog](../guides/arch/index.md) — one link per pattern (action, entity, data, model, event, policy, …)
- [Modular pattern](../guides/arch/modular-pattern.md) — module colocation and SRP enforcement
- [ADR: Action-based MVC](../adr/adr-action-based-mvc-architecture.md) — why vertical slicing
- [ADR: Cross-module communication](../adr/adr-cross-module-communication.md) — ranked-hierarchy rationale
- [ADR: Performance optimization](../adr/adr-performance-optimization.md) — growth tiers
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements this spec's rows serve


