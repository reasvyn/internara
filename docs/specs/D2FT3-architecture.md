# Architecture Design — Module-First 4-Layer Architecture

> **Spec ID:** D2FT3

## Description

Defines the architectural foundation of Internara: module colocation, the 4-layer model, the
Action Triad, boundary objects (Entity/DTO/Model/Enum), data-flow contracts, and cross-module
dependency rules. This is the **governing architecture spec** — every other spec and every line
of code operates within this model.

The [tech-stack](FB792-tech-stack.md) spec pins dependency versions; the
[core-infra-services](ZT6VS-core-infra-services.md) spec defines runtime services (cache, session,
database, queue, mail, storage). Both are built inside this architecture.

---

## 1. Problem Statements

### PS-1 — Flat Layering Scatters Features

A conventional flat structure (`app/Models`, `app/Http/Controllers`, `app/Services`) spreads a
single business concept across many unrelated directories. Fixing a bug in "placement" requires
jumping between six top-level directories, and nothing enforces that placement logic stays
together. Features become hard to find, hard to reuse, and easy to break silently.

### PS-2 — Unbounded Cross-Module Coupling

Without rules, modules import each other freely: queries reach into another module's models,
mutations are performed by whoever can build a query. This creates tight coupling, hidden
dependencies, and — eventually — circular dependencies that are painful to untangle.

### PS-3 — Scattered Business Logic

Business rules (eligibility, grading, status transitions) end up inline in controllers,
Livewire components, and Model scopes. They cannot be unit-tested in isolation, cannot be reused
across entry points, and drift apart over time.

### PS-4 — Uncontrolled Mutation Paths

Without a single mutation path, writes bypass transactions, audit logging, event dispatch, and
validation. Two call sites can implement the same "approve placement" logic differently, with
one of them forgetting to log the change or clear the cache.

### PS-5 — Unclear Class Boundaries

Without explicit contracts, Entity classes drift into Model territory (performing queries),
Models drift into business logic, and DTOs are bypassed by passing raw `Request` objects into
Actions — destroying validation boundaries and making every entry point a separate validation
surface.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Module colocation — every business concept lives as a vertical slice inside one `app/{Module}` directory |
| G2  | A strict 4-layer model with one-way dependency direction: Presentation → Business → Data → Framework/Infra |
| G3  | The Action Triad (Command/Read/Process) as the only mutation and query path |
| G4  | Pure boundary objects: Entities and DTOs free of framework I/O |
| G5  | Explicit data-flow contracts at every layer boundary |
| G6  | Architecture enforced by automated scans, not by convention alone |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multi-tenant architecture (single-tenant by product definition — S3 doctrine) |
| NG2  | CQRS with physically separate read/write databases |
| NG3  | Event sourcing |
| NG4  | Microservices / message-bus orchestration (single deployable Laravel app) |
| NG5  | Repository pattern (Entity + Model directly bridge; see [entity-pattern.md](../guides/arch/entity-pattern.md)) |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Implements a Feature Inside a Module

**Actor:** Developer
**Preconditions:** Module exists, architecture scans are green
**Flow:**
1. Developer adds a new feature; all code goes into `app/{Module}/` under the correct layer directories
2. Mutation logic is a new `CommandAction` accepting a DTO; queries are `ReadAction`s
3. Business rules are delegated to the module's Entity; the Action stays thin
4. Livewire calls the Action and maps the `ActionResponse`; no model mutations in the component
5. `scan_violations.py` passes — layer direction, contracts, and boundaries hold
**Postconditions:** Feature is findable, testable, and arch-guarded

### UC-2 — Developer Implements a Cross-Module Feature

**Actor:** Developer
**Preconditions:** Two modules exist, dependency order in `config/module.php` allows the call
**Flow:**
1. Module B needs data from module A: it calls A's public `ReadAction` — never A's models directly
2. Module B needs to change A's data: it calls A's public `CommandAction`; side effects in A fire
   through A's events
3. If the call would create a cycle, the shared concept moves to Core or a contract in Core
**Postconditions:** No circular dependency, no cross-module model reach-in

### UC-3 — Developer Traces a Mutation End-to-End

**Actor:** Developer (debugging/auditing)
**Preconditions:** Application running
**Flow:**
1. Livewire component receives the validated DTO (never raw Request)
2. Component invokes `CommandAction::execute(DTO)`
3. Action orchestrates: delegates rules to Entity, persists via injected Model, dispatches Event
4. Listeners handle side effects (notifications, cache invalidation, activity log)
5. `ActionResponse` returns success/failure to the UI
**Postconditions:** Every mutation follows one traceable, auditable path

---

## 4. Functional Requirements

### Module Colocation

| ID      | Requirement |
| ------- | ----------- |
| FR-ARC1 | All application code MUST live inside a business module under `app/{Module}` — never in a flat top-level layer |
| FR-ARC2 | A module owns its full vertical slice: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`, `Notifications/`, `Policies/`, `Livewire/`, `Http/`, routes, and `lang/` |
| FR-ARC3 | A module's public surface is its `Actions/`, `Services/`, `Contracts/`, `Events/`, `Entities/`, and `Enums/`; everything else is internal to the module |
| FR-ARC4 | The Core module (`app/Core/`) holds shared base classes, cross-cutting contracts, and module-independent infrastructure |
| FR-ARC5 | Each module is registered in `config/module.php` with its dependency order; `docs/refs/modules/index.md` documents the graph |

### 4-Layer Model

| ID      | Requirement |
| ------- | ----------- |
| FR-ARC6 | The architecture defines exactly four layers: 1 — Framework/Infrastructure/Utilities, 2 — Data/Persistent, 3 — Business/Domain Operations, 4 — Presentation/UI |
| FR-ARC7 | Layer directories map as follows: 4 → `{Module}/Livewire/`, `{Module}/Policies/`, `{Module}/Http/`, `resources/views/`, `routes/`; 3 → `{Module}/Actions/`, `{Module}/Events/`, `{Module}/Listeners/`, `{Module}/Notifications/`, `Console/`; 2 → `{Module}/Models/`, `{Module}/Entities/`, `{Module}/Enums/`, `{Module}/Data/`, `Types/`, database; 1 → `app/Core/`, `{Module}/Services/`, `{Module}/Support/` |
| FR-ARC8 | Dependencies flow downward only: 4 → 3 → 2 → 1. No upward, sideways-skipping, or layer-1-into-module-B business imports |
| FR-ARC9 | Core (Layer 1) MUST depend on nothing except the framework and approved packages — never on business modules |
| FR-ARC10 | A module at Layer 4 MAY import another module directly; prefer calling the other module's Read/Command Actions over its internals (FR-ARC14) |

### Action Triad

| ID      | Requirement |
| ------- | ----------- |
| FR-ARC11 | Three action base classes: `BaseCommandAction` (state mutation), `BaseReadAction` (query), `BaseProcessAction` (multi-step orchestration) |
| FR-ARC12 | Every Action exposes exactly one public entry point: `execute()` |
| FR-ARC13 | Command and Process Actions return `ActionResponse`; Read Actions return a value object, collection, or DTO |
| FR-ARC14 | Model mutations MUST happen through Command/Process Actions — never in Livewire, Controllers, or Views (C1 invariant) |
| FR-ARC15 | Business rules MUST live in the module's Entity; Actions orchestrate and stay thin |
| FR-ARC16 | Command/Process Actions with 3+ parameters MUST accept a DTO (C7 invariant) |
| FR-ARC17 | Read-only data access SHOULD use Read Actions; direct Model queries are limited to the owning module |

### Boundary Objects

| ID      | Requirement |
| ------- | ----------- |
| FR-ARC18 | Entities are `final readonly`, constructed via `fromModel()` (or `fromArray()`), and expose value semantics (`equals()`, `with()`) |
| FR-ARC19 | Entities MUST NOT import Models, Data, Actions, or Livewire (C5 invariant) — only value types (scalars, enums, Carbon) |
| FR-ARC20 | DTOs extend `BaseData`, are `readonly`, and are the boundary object between UI and Business — never raw `Request` (D5 invariant) |
| FR-ARC21 | DTOs MUST NOT import Models or Actions (C6 invariant); they carry validated scalars, enums, and `Carbon` values |
| FR-ARC22 | A DTO is owned by the Action that consumes it and is defined in the module where that Action lives |
| FR-ARC23 | Models are persistence-focused: `#[Fillable]` attribute (D4), `HasUuids`, common scopes, and an Entity bridge method; no business rules |

### Data Flow

| ID      | Requirement |
| ------- | ----------- |
| FR-ARC24 | UI → Business: validated DTO (or typed scalars), never raw Request (D5) |
| FR-ARC25 | Business → Data: Model attributes derived from DTO values |
| FR-ARC26 | Data → Business: a Model record is used to construct the Entity for rule evaluation |
| FR-ARC27 | Cross-module side effects are dispatched as Events (see [event-system](NUCY3-event-system.md)) and handled by Listeners |
| FR-ARC28 | Business → UI class imports are forbidden (Blade/Livewire never imported from Actions, Entities, or DTOs) |

### Cross-Module Dependencies

| ID      | Requirement |
| ------- | ----------- |
| FR-ARC29 | Cross-module reads MUST call the source module's public `ReadAction` — never query another module's Models directly |
| FR-ARC30 | Cross-module mutations MUST call the source module's public `CommandAction`; the caller never touches another module's Models |
| FR-ARC31 | Module dependencies MUST follow the order in `config/module.php`; a cycle is an architecture violation |
| FR-ARC32 | When a shared concept would create a cycle, it moves to Core (`app/Core/`) or a Core contract — never left as a cross-module shortcut |
| FR-ARC33 | Cross-module visibility is decided at design time; bypassing an Action to reach another module's internals requires a recorded design decision |

### Communication Discipline

| ID | Requirement |
|----|-------------|
| FR-ARC34 | Cross-module communication MUST follow the ranked hierarchy: 1) Core Contracts (Layer 3 shared interfaces), 2) Module Events (fire-and-forget), 3) Action Delegation (explicit `execute()` call), 4) Direct Import (simplest) — use the lowest coupling that satisfies the need |
| FR-ARC35 | Core Contracts in `App\Core\Contracts\` are the preferred decoupling for broadly-used abstractions (`LabelEnum`, `StatusEnum`, `SendsNotifications`) |

### Performance & Growth Tiers

| ID | Requirement |
|----|-------------|
| FR-ARC36 | Tier 0 no-regret optimizations MUST be enforced at any scale: composite indexes on FKs and `activity_log`, cache-key registry (`config/cache-keys.php`), eager-loading (no N+1), and Read Actions to avoid transaction overhead |
| FR-ARC37 | Tier 1 (Shared, ≤500 users) MUST run on MySQL/MariaDB + file cache + sync queue + database session with zero external services |
| FR-ARC38 | Tier 2 (VPS, 500–2000 users) and Tier 3 (HA, 2000+ users) transitions MUST be `.env` swaps with zero code changes (e.g., `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, read replica) |
| FR-ARC39 | The following are explicitly deferred until measured need: Laravel Octane, horizontal auto-scaling, CDN for static assets, database sharding, queue job batching — `docs/architecture.md` and Pulse must show a bottleneck before adoption |

### Gradual Migration

| ID | Requirement |
|----|-------------|
| FR-ARC40 | DTO adoption MUST follow Start `array` → Stabilize `Data\|array` union → Final `Data` only, with `BaseData::fromArray()` preserving callers during migration |
| FR-ARC41 | Cache invalidation MUST follow Start `Cache::forget()` inline → Stabilize event+listener → Final `config/cache-keys.php` registry with listener-driven invalidation |
| FR-ARC42 | Validation rules MUST follow Start Form-Object-only → Stabilize `Entity::rules()` shared → Final centralized in Entities for full DRY |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-A1 | Architecture invariants C1–C8 and D1–D6 are enforced by automated scans (`tools/scan_violations.py`) |
| NFR-A2 | Class contracts (Action/Entity/DTO/Model/Enum) are enforced by `tools/scan_class_contracts.py` |
| NFR-A3 | New code must not introduce top-level directories under `app/` without a spec or recorded decision |
| NFR-A4 | Actions eager-load relations; N+1 queries are an architecture defect (S3 — Scalable) |
| NFR-A5 | Authorization is enforced at every layer: Policies guard Presentation, Actions and Entities enforce business authorization with `RejectedException` (C8) |
| NFR-A6 | Clean-Code/DRY: duplicated logic must be extracted into shared, named units; modules reuse Core rather than copy (S2 — Sustain) |
| NFR-A7 | Single-tenant deployment matrix MUST be SQLite (dev/test) / MySQL-MariaDB (prod) + file/database cache + sync queue + database session + local disk with zero external services by default; Redis/S3/Reverb are optional `.env` overrides — no centralized auth, billing, or tenant isolation |

---

## 6. API / Data Contracts

### Module Skeleton

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

### Layer → Directory Map

| Layer | Directories |
| ----- | ----------- |
| 4 — Presentation/UI | `{Module}/Livewire/`, `{Module}/Policies/`, `{Module}/Http/`, `resources/views/{module}/`, `routes/web/{module}.php` |
| 3 — Business/Domain Ops | `{Module}/Actions/`, `{Module}/Events/`, `{Module}/Listeners/`, `{Module}/Notifications/`, `Console/` |
| 2 — Data/Persistent | `{Module}/Models/`, `{Module}/Entities/`, `{Module}/Enums/`, `{Module}/Data/`, `Types/`, database |
| 1 — Framework/Infra | `app/Core/`, `{Module}/Services/`, `{Module}/Support/`, PHP, Laravel, packages |

### Base Class Contracts

```php
// Layer 3 — Actions (app/Core/Actions/)
abstract class BaseAction          { }  // marker + shared concerns: transaction(), log(), dispatchEvent()
abstract class BaseCommandAction extends BaseAction { }  // respond()/validate()/authorize()/fail()
abstract class BaseProcessAction extends BaseAction { }  // step()/trackProgress()/notify()/fail()
abstract class BaseReadAction      { }  // standalone (does NOT extend BaseAction): remember()/forget()/mask()/paginate()

// execute(): exactly one public method, declared in each concrete Action
// (convention + scan-enforced, FR-ARC12). Command/Process return ActionResponse;
// Read returns value data.

// Layer 2 — Entities (app/Core/Entities/BaseEntity.php)
abstract readonly class BaseEntity implements JsonSerializable {
    abstract public static function fromModel(Model $model): static;
    public function toArray(): array;        // value snapshot
    public function equals(self $other): bool; // value equality
    public function with(string $property, mixed $value): static; // immutable copy
}

// Layer 2 — DTOs (app/Core/Data/BaseData.php)
abstract readonly class BaseData implements JsonSerializable {
    public function toArray(): array;        // all properties
    public function only(string ...$keys): array;
    public function except(string ...$keys): array;
    public function merge(array $overrides): static;
}

// Layer 3 — Action result (app/Core/Data/ActionResponse.php)
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

### Data Flow

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

### DD-1 — Module-First Vertical Slicing over Flat Layering

**Decision:** Organize code by business module (`app/{Module}`), each with its own vertical slice.
**Rationale:** A business concept is one directory — findable, independently testable, and safe to
change. Flat layering spreads concepts and allows silent coupling.
**Trade-off:** Shared infrastructure must be deliberately extracted into Core instead of "shared
folders"; managed by FR-ARC4/FR-ARC32.

### DD-2 — Action Triad over a Generic Service Layer

**Decision:** Command/Read/Process base classes are the only business entry points.
**Rationale:** A typed triad makes intent explicit (mutation vs. query vs. orchestration), enables
scan-enforcement, and gives every mutation a single traceable path (UC-3).
**Trade-off:** More classes than a generic `Service` — accepted for auditable single-tenant
systems.

### DD-3 — Entity/Model Split

**Decision:** Entities are pure value objects; Models handle persistence.
**Rationale:** Business rules become unit-testable without a database and survive schema changes;
Models stay thin persistence adapters (D4, Entity bridge).
**Trade-off:** A model record must be bridged to an entity before rules run — the cost is a
`fromModel()` call.

### DD-4 — DTOs as Boundary Objects

**Decision:** UI → Business passes validated `BaseData` DTOs, never raw Request.
**Rationale:** One validation surface per action (C7, D5), no framework dependence in business
code, deterministic test construction.
**Trade-off:** A DTO per action signature — accepted by FR-ARC16/FR-ARC21.

### DD-5 — Events for Cross-Module Side Effects

**Decision:** Side effects (notifications, cache invalidation, logging) are dispatched as Events
and handled by Listeners (see [event-system](NUCY3-event-system.md)).
**Rationale:** The originating Action stays focused; modules decouple; side effects are
discoverable and replayable.
**Trade-off:** Extra indirection versus a direct call — worth it across module boundaries
(FR-ARC27).

### DD-6 — No Repository Pattern

**Decision:** Models are used directly by the owning module's Actions; no repository abstraction.
**Rationale:** Eloquent already provides the persistence API; a repository would add indirection
without benefit in a single-tenant app.
**Trade-off:** The owning-module rule (FR-ARC29/FR-ARC30) is what keeps models encapsulated, not
a repository layer.

### DD-7 — Automated Architecture Enforcement

**Decision:** Architecture rules are enforced by `tools/` scans (C1–C8, D1–D6, contracts,
naming, security) that run before commit.
**Rationale:** Convention alone rots; deterministic scans are faster and more reliable than manual
review (Automation-First doctrine).
**Trade-off:** Scans must be maintained as the architecture evolves — a small, owned cost.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Module colocation | 100% of `app/` code inside modules/Core | `scan_naming.py` + directory audit |
| Architecture violations | 0 | `python3 tools/scan_violations.py` |
| Class-contract violations | 0 | `python3 tools/scan_class_contracts.py` |
| Circular dependencies | 0 | module graph in `config/module.php` + scan |
| Mutations through Actions | 100% (no Livewire model mutation) | C1 scan |
| Boundary purity (C5/C6) | 0 forbidden imports | `scan_violations.py` |
| Docs ↔ code ↔ specs | aligned | `scan_doc_links.py`, spec audit |

---

## 9. Roadmap

### Prerequisites

None — this is the governing, foundational architecture spec. It must be read (and understood)
before the tech stack, infra services, and base-class specs.

### Build Guide

This spec is satisfied continuously: every module, Action, Entity, DTO, and Model built by the
lower-level specs must comply with FR-ARC1–FR-ARC33. `docs/architecture.md` and the
`docs/guides/arch/` pattern docs are the living reference for implementation; this spec is the
authoritative contract.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [tech-stack.md](FB792-tech-stack.md) | Pins the dependency versions the architecture builds upon |
| 2 | [core-infra-services.md](ZT6VS-core-infra-services.md) | Defines the runtime services (cache, session, DB, queue, mail, storage) |
| 3 | [base-classes.md](SE5Q9-base-classes.md) | Implements BaseAction/BaseEntity/BaseData/BaseModel contracts (FR-ARC11–FR-ARC23) |
| 4 | [module-discovery.md](I1BCV-module-discovery.md) | Module registry and dependency order (FR-ARC5, FR-ARC31) |

---

## Quick References

- `docs/architecture.md` — 4-layer model, data flow, dependency rules (living reference)
- `docs/guides/arch/modular-pattern.md` — module boundaries and colocation
- `docs/guides/arch/action-pattern.md` — Action Triad contracts (C1, C7)
- `docs/guides/arch/entity-pattern.md` — Entity contract (C5)
- `docs/guides/arch/data-pattern.md` — DTO/BaseData contract (C6)
- `docs/guides/arch/model-pattern.md` — Model contract (D4)
- `docs/guides/arch/enum-pattern.md` — Enum contracts
- `docs/guides/arch/event-pattern.md` — Event dispatch & listeners
- `docs/guides/arch/exception-pattern.md` — RejectedException (C8)
- `docs/conventions.md` — Invariants C1–C8, D1–D6
- `config/module.php` — module dependency order
- `tools/scan_violations.py`, `tools/scan_class_contracts.py` — enforcement scans
- **Related specs:** [tech-stack.md](FB792-tech-stack.md) — dependency versions; [core-infra-services.md](ZT6VS-core-infra-services.md) — runtime services; [base-classes.md](SE5Q9-base-classes.md) — base class contracts; [event-system.md](NUCY3-event-system.md) — events
