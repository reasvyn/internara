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

**Actor:** Developer
**Preconditions:** Module exists; architecture scans green.
**Flow:**
1. All code goes under `app/{Module}/` in the correct layer directories
2. Mutation logic is a new `CommandAction` accepting a DTO; queries are `ReadAction`s
3. Business rules are delegated to the module's Entity; the Action stays thin
4. Livewire calls the Action and maps the `ActionResponse`; no model mutations in the component
5. `scan_violations.py` + `scan_class_contracts.py` pass — layer direction, contracts, and boundaries hold
**Postconditions:** The feature is findable, testable, and arch-guarded.
**Governing guidance:** [modular-pattern.md](../guides/arch/modular-pattern.md), [action-pattern.md](../guides/arch/action-pattern.md).

#### UC-ARC-002 — Implement a Cross-Module Feature

**Actor:** Developer
**Preconditions:** Two modules exist; dependency order in `config/module.php` allows the call.
**Flow:**
1. Module B needs data from module A → call A's public `ReadAction`, never A's Models directly
2. Module B must change A's data → call A's public `CommandAction`; A's side effects fire through A's events
3. A call that would create a cycle pushes the shared concept into Core or a Core contract
**Postconditions:** No circular dependency; no cross-module model reach-in.
**Governing guidance:** [cross-module-communication ADR](../adr/adr-cross-module-communication.md), FR-ARC-029–034.

#### UC-ARC-003 — Trace a Mutation End-to-End

**Actor:** Developer (debugging/auditing)
**Preconditions:** Application running.
**Flow:**
1. Livewire receives the validated DTO (never raw `Request` — D5)
2. Component invokes `CommandAction::execute(DTO)`
3. The Action orchestrates: delegates rules to the Entity, persists via the injected Model, dispatches the Event
4. Listeners handle side effects (notifications, cache invalidation, activity log)
5. `ActionResponse` returns success/failure to the UI
**Postconditions:** Every mutation follows one traceable, auditable path.
**Governing guidance:** QLHDO §1.4 mutation path, [action-pattern.md](../guides/arch/action-pattern.md).

---

## 4. Functional Requirements

Global defaults every feature spec inherits. A feature spec may tighten but never violate these.

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

### 4.1 Module Colocation

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-001 | All application code lives inside a business module under `app/Modules/{Module}` — never in a flat top-level layer | P0 | A | Full |
| FR-ARC-002 | A module owns its full vertical slice: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`, `Notifications/`, `Policies/`, `Livewire/`, `Http/`, routes, and `lang/` | P0 | A | Full |
| FR-ARC-003 | A module's public surface is its `Actions/`, `Services/`, `Contracts/`, `Events/`, `Entities/`, and `Enums/`; everything else is internal to the module | P0 | A | Full |
| FR-ARC-004 | The Core module (`app/Modules/Core/`) holds shared base classes, cross-cutting contracts, and module-independent infrastructure | P0 | A | Full |
| FR-ARC-005 | Each module is registered in `config/module.php`, auto-discovered from the `app/Modules/` directory listing (deterministic order, no manual registry); the dependency graph is documented in `docs/refs/modules/index.md` · [I1BCV](I1BCV-module-discovery.md) | P0 | A | Full |

#### FR-ARC-001 — All code inside modules

- No business code under `app/` top-level directories — `app/Models/`, `app/Http/Controllers/`, `app/Services/` must not appear.
- **Edge case:** shared infrastructure with no single module owner goes to Core, not to a flat folder.
- **Verification:** `scan_naming.py` + `scan_module_boundaries.py` clean (layer `A`).

#### FR-ARC-002 — Full vertical slice

- Directory inventory per module as listed in the table; multi-submodule modules place each domain under `Domain/{Domain}/` per [modular-pattern.md](../guides/arch/modular-pattern.md) §1.6 — this spec fixes the invariant, not the directory depth.
- **Verification:** `scan_naming.py` directory layout (layer `A`).

#### FR-ARC-003 — Public surface only

- Cross-module imports may touch only the public surface; everything else is internal.
- **Edge case:** a cross-module call that reaches a Model is a violation unless that module owns the Model (FR-ARC-029).
- **Verification:** `scan_module_boundaries.py` `MOD_XMOD_INTERNAL` rule (layer `A`).

#### FR-ARC-004 — Core owns shared infrastructure

- Base classes (`BaseAction`, `BaseEntity`, `BaseData`, `BaseModel`), contracts (`LabelEnum`, `StatusEnum` …), exceptions, middleware, notification channels, and module-independent services live in Core.
- **Verification:** `scan_module_boundaries.py` `MOD_CORE_IMPORT` rule; Core must not import business modules (FR-ARC-009).

#### FR-ARC-005 — Module registry from config

- `config/module.php` is derived deterministically from the `app/Modules/` directory listing; dependency order between modules is read from the same registry.
- **Edge case:** a business module directory without a registry entry is an incomplete registration — the graph doc must match the on-disk modules.
- **Verification:** module-boundary scan + `scan_doc_links.py` on the graph doc.

### 4.2 4-Layer Model

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-006 | The architecture defines exactly four layers: 1 — Framework/Infrastructure/Utilities, 2 — Data/Persistent, 3 — Business/Domain Operations, 4 — Presentation/UI | P0 | A | Full |
| FR-ARC-007 | Layer directories map as: 4 → `Livewire/`, `Policies/`, `Http/`, views, routes; 3 → `Actions/`, `Events/`, `Listeners/`, `Notifications/`, `Console/`; 2 → `Models/`, `Entities/`, `Enums/`, `Data/`, `Types/`, database; 1 → `Core/`, `Services/`, `Support/` — full map in §6.2 | P0 | A | Full |
| FR-ARC-008 | Dependencies flow downward only: 4 → 3 → 2 → 1. No upward, sideways-skipping, or Layer-1-into-module-B business imports | P0 | A | Full |
| FR-ARC-009 | Core (Layer 1) depends only on the framework and approved packages — never on business modules | P0 | A | Full |
| FR-ARC-010 | A module importing another module directly must prefer the target's Read/Command Actions over its internals (FR-ARC-034) | P0 | A | Full |

#### FR-ARC-006 — Four layers

- The 4-layer model is the invariant; nothing may introduce a fifth implicit layer (e.g., an ad-hoc middleware service tier or a "helpers" namespace).
- **Verification:** `scan_violations.py` layer checks; [architecture.md](../architecture.md) elaborates.

#### FR-ARC-007 — Directory → layer map

- The abbreviated map in the table is canonicalized in §6.2; a file placed in a directory that belongs to another layer is a violation regardless of intent.
- **Verification:** `scan_naming.py` + `scan_violations.py` (layer `A`).

#### FR-ARC-008 — Downward dependencies only

- Upward imports (Layer 2 importing Layer 3/4), sideways skips (Layer 4 into Layer 2 without the Business layer), and business imports from Layer 1 are forbidden.
- **Edge case:** `RejectedException` is a Core (Layer 1) exception imported by all layers — exceptions and contracts live on Layer 1, not Layer 3.
- **Verification:** `scan_violations.py` direction rules.

#### FR-ARC-009 — Core purity

- Core classes must not reference `App\Modules\{business}`. Core services depend on Laravel, PHP, and approved packages only.
- **Edge case:** when Core needs a business concept, invert the dependency — the business module implements a Core contract (FR-ARC-035).
- **Verification:** `scan_module_boundaries.py` `MOD_CORE_IMPORT`.

#### FR-ARC-010 — Direct module imports

- Direct import is allowed as the lowest coupling (FR-ARC-034) but must target only the target module's public surface.
- **Verification:** `scan_module_boundaries.py` (layer `A`).

### 4.3 Action Triad

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-011 | Three action base classes: `BaseCommandAction` (state mutation), `BaseReadAction` (query), `BaseProcessAction` (multi-step orchestration) | P0 | A | Full |
| FR-ARC-012 | Every Action exposes exactly one public entry point: `execute()` | P0 | A | Full |
| FR-ARC-013 | Command and Process Actions return `ActionResponse`; Read Actions return a value object, collection, or DTO | P0 | A | Full |
| FR-ARC-014 | Model mutations happen through Command/Process Actions — never in Livewire, Controllers, or Views (C1 invariant) | P0 | A | Full |
| FR-ARC-015 | Business rules live in the module's Entity; Actions orchestrate and stay thin | P0 | A | Full |
| FR-ARC-016 | Command/Process Actions with 3+ parameters accept a DTO (C7 invariant) | P0 | A | Full |
| FR-ARC-017 | Read-only access uses Read Actions; direct Model queries are limited to the owning module | P1 | A | Full |

#### FR-ARC-011 — Three action base classes

- Contracts in §6.3; rationale in [action-pattern-over-services ADR](../adr/adr-action-pattern-over-services.md).
- **Verification:** `scan_class_contracts.py` asserts every Action extends the correct base (layer `A`).

#### FR-ARC-012 — One public entry point

- Each concrete Action exposes exactly one public `execute()`; helpers (`validate()`, `authorize()`, `respond()`, `fail()`) live on the base classes and stay out of the public API.
- **Verification:** `scan_class_contracts.py` (layer `A`).

#### FR-ARC-013 — Return contracts

- Command/Process → `ActionResponse`; Read → value object, collection, or DTO.
- **Edge case:** a Read Action must not return a raw Eloquent Model across module boundaries — map to an Entity/DTO or collection first (FR-ARC-017/029).
- **Verification:** `scan_class_contracts.py` + targeted unit tests.

#### FR-ARC-014 — Mutations only through Actions

- Model `create`/`update`/`delete` must not appear in Livewire, Controllers, or Views (C1 invariant).
- **Verification:** `scan_violations.py` C1 check (layer `A`).

#### FR-ARC-015 — Business rules in Entities

- Actions orchestrate and stay thin; rules live on the module's Entity bridged from the Model via `fromModel()`.
- **Verification:** code review + entity unit tests; contract scan asserts entity shape (layer `A` + spot `U`).

#### FR-ARC-016 — DTO for 3+ parameters

- `execute(DTO $data)` when the Action needs 3+ parameters (C7); one or two typed scalars may remain direct arguments.
- **Verification:** `scan_violations.py` C7 check.

#### FR-ARC-017 — Read Actions for read-only access

- Cross-module reads must go through Read Actions; direct Model queries stay inside the owning module.
- **Edge case:** trivial same-module lookups may query the Model directly — only the ownership boundary is hard.
- **Verification:** `scan_module_boundaries.py` (layer `A`).

### 4.4 Boundary Objects

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-018 | Entities are `final readonly`, constructed via `fromModel()` (or `fromArray()`), and expose value semantics (`equals()`, `with()`) | P0 | U | Full |
| FR-ARC-019 | Entities must not import Models, Data, Actions, or Livewire (C5 invariant) — only value types (scalars, enums, Carbon) | P0 | A | Full |
| FR-ARC-020 | DTOs extend `BaseData`, are `readonly`, and are the boundary object between UI and Business — never raw `Request` (D5 invariant) | P0 | A | Full |
| FR-ARC-021 | DTOs must not import Models or Actions (C6 invariant); they carry validated scalars, enums, and `Carbon` values | P0 | A | Full |
| FR-ARC-022 | A DTO is owned by the Action that consumes it and is defined in the module where that Action lives | P0 | A | Full |
| FR-ARC-023 | Models are persistence-focused: `#[Fillable]` attribute (D4), `HasUuids`, common scopes, and an Entity bridge method; no business rules | P0 | A | Full |

#### FR-ARC-018 — Entity form

- Per [entity-pattern.md](../guides/arch/entity-pattern.md); `fromModel()` is the Model→Entity bridge.
- **Verification:** `scan_class_contracts.py` entity contract (layer `A`).

#### FR-ARC-019 — Entity purity (C5)

- Entities import only value types — no Models, Data, Actions, Livewire.
- **Verification:** `scan_violations.py` C5 check (layer `A`).

#### FR-ARC-020 — DTO boundary object

- UI → Business crosses as a validated `BaseData` DTO; raw `Request` never reaches an Action (D5).
- **Verification:** `scan_violations.py` D5 + `scan_class_contracts.py` (layer `A`).

#### FR-ARC-021 — DTO purity (C6)

- DTOs carry validated scalars, enums, `Carbon`; no Models or Actions.
- **Verification:** `scan_violations.py` C6 check (layer `A`).

#### FR-ARC-022 — DTO ownership

- A DTO lives in the consuming Action's module (`Data/`); ownership follows the Action, never a neutral folder.
- **Edge case:** a DTO genuinely shared across two modules belongs in Core `Data/` with contract review.

#### FR-ARC-023 — Models are persistence adapters

- `#[Fillable]` (D4), `HasUuids`, scopes, Entity bridge — business rules stay out of Models.
- **Verification:** `scan_conventions.py` D4 + `scan_class_contracts.py` model contract.

### 4.5 Data Flow

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-024 | UI → Business: validated DTO (or typed scalars), never raw Request (D5) | P0 | A | Full |
| FR-ARC-025 | Business → Data: Model attributes are derived from DTO values | P0 | A | Full |
| FR-ARC-026 | Data → Business: a Model record is used to construct the Entity for rule evaluation | P0 | A | Full |
| FR-ARC-027 | Cross-module side effects are dispatched as Events (see [NUCY3](NUCY3-event-system.md)) and handled by Listeners | P0 | A | Full |
| FR-ARC-028 | Business → UI class imports are forbidden (Blade/Livewire never imported from Actions, Entities, or DTOs) | P0 | A | Full |

#### FR-ARC-024 — UI → Business via DTO

- **Edge case:** validated-through-a-form `$request->all()` into an Action is still a D5 violation; the Action receives the DTO only.
- **Verification:** `scan_violations.py` D5; feature tests assert validation before persistence.

#### FR-ARC-025 — Business → Data

- Bulk fill only whitelisted keys (D4 `#[Fillable]`); explicit field assignment for computed attributes.
- **Verification:** `scan_conventions.py` D4 + review.

#### FR-ARC-026 — Data → Business

- Rules run on the Entity, never on raw model state; `fromModel()` is the only construction path (per [entity-pattern.md](../guides/arch/entity-pattern.md)).
- **Verification:** entity unit tests construct via `fromModel()`.

#### FR-ARC-027 — Cross-module side effects as Events

- The originating Action stays focused; side effects are discoverable and replayable via Listeners.
- **Verification:** `scan_violations.py` + review; contract in [NUCY3](NUCY3-event-system.md).

#### FR-ARC-028 — No Business → UI imports

- Layer 3/2 must not know Layer 4 classes.
- **Verification:** `scan_violations.py` import rule (layer `A`).

### 4.6 Cross-Module Dependencies

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-029 | Cross-module reads call the source module's public `ReadAction` — never query another module's Models directly | P0 | A | Full |
| FR-ARC-030 | Cross-module mutations call the source module's public `CommandAction`; the caller never touches another module's Models | P0 | A | Full |
| FR-ARC-031 | The module registry derives deterministically from `config/module.php` (auto-discovery of `app/Modules/`); a dependency cycle is an architecture violation | P0 | A | Full |
| FR-ARC-032 | When a shared concept would create a cycle, it moves to Core (`app/Modules/Core/`) or a Core contract — never left as a cross-module shortcut | P0 | A | Full |
| FR-ARC-033 | Cross-module visibility is decided at design time; bypassing an Action to reach another module's internals requires a recorded design decision | P0 | A | Full |

#### FR-ARC-029 — Cross-module reads via Read Actions

- **Verification:** `scan_module_boundaries.py` flags direct Model reach-in (layer `A`).

#### FR-ARC-030 — Cross-module mutations via Command Actions

- **Verification:** `scan_module_boundaries.py` (layer `A`).

#### FR-ARC-031 — Registry is authoritative

- Dependency order comes from `config/module.php`; a cycle in that graph fails the module-boundary scan.
- **Verification:** module-boundary scan reports cycles (layer `A`).

#### FR-ARC-032 — Cycles move to Core

- **Edge case:** a genuinely cross-cutting lookup (e.g., current academic year) may live in Core `Support/` rather than in either module — prefer a Core contract first.
- **Verification:** review + `scan_module_boundaries.py` cycle report.

#### FR-ARC-033 — Visibility decided at design time

- A deliberate reach-in is allowed only with a recorded decision (this spec's DD, the governing ADR, or an issue-linked note).
- **Verification:** review gate; the scan flags the reach-in so the decision is visible.

### 4.7 Communication Discipline

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-034 | Cross-module communication follows the ranked hierarchy: 1) Core Contracts (Layer 3 shared interfaces), 2) Module Events (fire-and-forget), 3) Action Delegation (explicit `execute()` call), 4) Direct Import (simplest) — use the lowest coupling that satisfies the need | P0 | A | Full |
| FR-ARC-035 | Core Contracts in `App\Core\Contracts\` are the preferred decoupling for broadly-used abstractions (`LabelEnum`, `StatusEnum`, `SendsNotifications`) | P0 | A | Full |

#### FR-ARC-034 — Ranked communication hierarchy

- **Edge case:** fire-and-forget (events) suits notifications and cache invalidation; when the caller needs the result, delegate to an Action instead.
- **Governance:** [cross-module-communication ADR](../adr/adr-cross-module-communication.md).

#### FR-ARC-035 — Core Contracts for broad abstractions

- Implemented by business modules; consumed via the contract, not the concrete module class.
- **Verification:** `scan_class_contracts.py` (layer `A`).

### 4.8 Performance & Growth Tiers

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-036 | Tier 0 no-regret optimizations are enforced at any scale: composite indexes on FKs and `activity_log`, cache-key registry (`config/cache-keys.php`), eager loading (no N+1), Read Actions to avoid transaction overhead | P0 | A | Full |
| FR-ARC-037 | Tier 1 (shared hosting, ≤500 users) runs on MySQL/MariaDB + file cache + sync queue + database session with zero external services | P1 | A | Full |
| FR-ARC-038 | Tier 2 (VPS, 500–2000 users) and Tier 3 (HA, 2000+) transitions are `.env` swaps with zero code changes (e.g., `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, read replica) | P1 | A | Full |
| FR-ARC-039 | Deferred until measured: Laravel Octane, horizontal auto-scaling, CDN for static assets, database sharding, queue job batching — only adopted when `docs/architecture.md` and Pulse show a bottleneck | P2 | A | Planned |

#### FR-ARC-036 — Tier 0 no-regret

- Always-on costs that never need a rewrite: indexes, cache-key registry, eager loading, Read Actions.
- **Verification:** migration review for composite indexes; `config/cache-keys.php` presence scan; N+1 review (layer `A`).

#### FR-ARC-037 — Tier 1 defaults

- Zero external services by default; per [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md).
- **Verification:** deployment smoke on shared hosting (browser spot).

#### FR-ARC-038 — Tier 2/3 via config only

- Cache, queue, and read-replica switches read from env; no code branches on tier.
- **Governance:** [performance-optimization ADR](../adr/adr-performance-optimization.md).
- **Verification:** config reads env keys; `.env` variant documented.

#### FR-ARC-039 — Deferred until measured

- Adopting any of these ahead of demand violates the no-regret doctrine; status is `Planned` by decision.

### 4.9 Gradual Migration

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ARC-040 | DTO adoption follows Start `array` → Stabilize `Data|array` union → Final `Data` only, with `BaseData::fromArray()` preserving callers during migration | P1 | A | Full |
| FR-ARC-041 | Cache invalidation follows Start `Cache::forget()` inline → Stabilize event+listener → Final `config/cache-keys.php` registry with listener-driven invalidation | P1 | A | Full |
| FR-ARC-042 | Validation rules follow Start Form-Object-only → Stabilize `Entity::rules()` shared → Final centralized in Entities for full DRY | P1 | A | Full |
| FR-ARC-043 | Every module implements at least one Command or Read Action exposing its public surface | P0 | A | Full |
| FR-ARC-044 | Every mutation flow is traceable: Livewire → DTO → Command Action → Entity → Model → Event | P0 | A | Full |

#### FR-ARC-040 — DTO adoption phases

- **Governance:** [gradual-migration ADR](../adr/adr-gradual-migration.md); `fromArray()` keeps legacy callers compiling during stabilization.

#### FR-ARC-041 — Cache invalidation phases

- **Governance:** [gradual-migration ADR](../adr/adr-gradual-migration.md); final state is registry-driven invalidation via listeners.

#### FR-ARC-042 — Validation centralization phases

- **Governance:** [gradual-migration ADR](../adr/adr-gradual-migration.md); entities are the final DRY home for shared rules.

#### FR-ARC-043 — Every module exposes an Action

- No module may be a black box without a public entry point.
- **Verification:** `scan_class_contracts.py` / module scan (layer `A`).

#### FR-ARC-044 — Mutation flow traceability

- Enforcement relies on code review until arch tests are restored — the original arch guard was removed due to a `pest-plugin-arch` compatibility bug (per [action-based-mvc ADR](../adr/adr-action-based-mvc-architecture.md)).
- **Verification:** `scan_violations.py` C1 + review (layer `A`).

---

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

- CI gate is `python3 tools/scan_violations.py` for C1–C8 / D1–D6. **Verification:** pre-commit baseline (AGENTS.md §4) runs the arch-guard batch green.

#### NFR-ARC-002 — Class-contract scans

- `python3 tools/scan_class_contracts.py` asserts Action/Entity/DTO/Model/Enum contracts. **Verification:** pre-commit arch-guard (layer `A`).

#### NFR-ARC-003 — No ad-hoc top-level dirs

- New `app/` top-level directories require a spec amendment or a recorded DD. **Verification:** `scan_naming.py` + review.

#### NFR-ARC-004 — No N+1

- Read Actions eager-load declared relations; N+1 defects are blocking. **Verification:** review + targeted feature tests with DB query logging (layer `A`/`F`).

#### NFR-ARC-005 — Defense in depth

- Policy gatekeeping (403) plus Action/Entity business-rule rejection. Cross-ref: QLHDO FR-GLB-008, [T4B26](T4B26-rbac-and-authorization.md). **Verification:** `scan_class_contracts.py` + policy unit tests.

#### NFR-ARC-006 — Clean-code DRY

- Shared, named units before copy-paste; modules reuse Core. **Verification:** `scan_violations.py` + review.

#### NFR-ARC-007 — Default-zero-external-services matrix

- Full matrix in [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md); no feature is disabled in any tier. **Verification:** fresh deploy smoke on shared hosting.

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

**Decision:** Organize code by business module (`app/{Module}`), each with its own vertical slice.
**Rationale:** A business concept is one directory — findable, independently testable, and safe to
change. Flat layering spreads concepts and allows silent coupling.
**Trade-off:** Shared infrastructure must be deliberately extracted into Core instead of "shared
folders" (FR-ARC-004, FR-ARC-032).

#### DD-ARC-002 — Action Triad over a Generic Service Layer

**Decision:** Command/Read/Process base classes are the only business entry points.
**Rationale:** A typed triad makes intent explicit (mutation vs. query vs. orchestration), enables
scan-enforcement, and gives every mutation a single traceable path (UC-ARC-003).
**Trade-off:** More classes than a generic `Service` — accepted for an auditable single-tenant system.

#### DD-ARC-003 — Entity/Model Split

**Decision:** Entities are pure value objects; Models handle persistence.
**Rationale:** Business rules become unit-testable without a database and survive schema changes;
Models stay thin persistence adapters (D4, Entity bridge).
**Trade-off:** A model record must be bridged to an Entity before rules run — the cost of one
`fromModel()` call. (ADR: [entity-model-separation](../adr/adr-entity-model-separation.md)).

#### DD-ARC-004 — DTOs as Boundary Objects

**Decision:** UI → Business passes validated `BaseData` DTOs, never raw Request.
**Rationale:** One validation surface per Action (C7, D5), no framework dependence in business code,
deterministic test construction.
**Trade-off:** A DTO per Action signature replaces ad-hoc arguments (FR-ARC-016, FR-ARC-021).

#### DD-ARC-005 — Events for Cross-Module Side Effects

**Decision:** Side effects (notifications, cache invalidation, logging) are dispatched as Events and
handled by Listeners (see [NUCY3](NUCY3-event-system.md)).
**Rationale:** The originating Action stays focused; modules decouple; side effects are discoverable
and replayable.
**Trade-off:** Extra indirection versus a direct call — worth it across module boundaries
(FR-ARC-027).

#### DD-ARC-006 — No Repository Pattern

**Decision:** Models are used directly by the owning module's Actions; no repository abstraction.
**Rationale:** Eloquent already provides the persistence API; a repository adds indirection without
benefit in a single-tenant app.
**Trade-off:** The owning-module rule (FR-ARC-029/030) is what keeps models encapsulated, not a
repository layer. See [repository-pattern.md](../guides/arch/repository-pattern.md).

#### DD-ARC-007 — Automated Architecture Enforcement

**Decision:** Architecture rules are enforced by `tools/` scans (C1–C8, D1–D6, contracts, naming,
module boundaries) that run before commit.
**Rationale:** Convention alone rots; deterministic scans are faster and more reliable than manual
review (Automation-First doctrine).
**Trade-off:** Scans must be maintained as the architecture evolves — a small, owned cost.

---

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