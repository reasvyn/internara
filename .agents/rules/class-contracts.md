# Class Contract Checks — Actions, Entities, DTOs, Models, Enums & Support Classes

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Every component type in Internara has a strict structural contract that `scan_class_contracts.py`
enforces. A class that "sort of" matches its contract fails the scan and — worse — silently changes
the guarantees its consumers rely on (transactions, logging, purity, immutability). This rule
documents each contract, why it exists, and what a violation breaks.

---

## Action Contracts

| Type | Base Class | Required Methods | Forbidden |
|------|-----------|------------------|-----------|
| **Command** | `BaseCommandAction` | `execute()` (single public), `transaction()`, `log()` | `handle()`, multiple public methods |
| **Read** | `BaseReadAction` | `execute()` (single public) | `handle()`, transactions, event dispatch |
| **Process** | `BaseProcessAction` | `execute()` (single public), `transaction()`, `log()` | `handle()`, multiple public methods |

**Intent:** The base class *is* the contract — it wires `transaction()`, `log()`, and
`dispatchEvent()` guarantees. Extending the wrong base silently adds or drops those guarantees.

**Why it matters:** A mutation implemented as a Read Action loses its transaction boundary and audit
trap. A Read Action that calls `log()` is either a misclassified Command or an unguarded side effect.
`handle()` is a Livewire/queue naming nobody expects in an Action — the scanner treats it as an
alternate (bypass) entry point.

**How to apply:**

- **Command** — all mutations: wrap writes in `$this->transaction()`, call `$this->log()` after, use
  `$this->dispatchEvent()` (only if a listener exists), return `ActionResponse`.
- **Read** — complex queries only: no `$this->transaction()`, no `$this->log()`, no event dispatch;
  may cache with `Cache::remember()`.
- **Process** — multi-step orchestration: compose injected Actions, wrap in `$this->transaction()`,
  call `$this->log()`, **never** touch the DB directly (delegate to injected Actions).

**Action file checks (also enforced):**

- File header order: `declare(strict_types=1)` → namespace → use → class → constructor → `execute()`
- Constructor: `final public function __construct(private readonly DependencyService $service)`
- No `Model::create/update/delete` anywhere (that is a Command's job)
- No `app()->make()` (C2) — constructor injection only
- No `DB::raw()` without bindings (C3)
- Command/Process: DTO for 3+ params (C7), returns `ActionResponse`
- Events: `$this->dispatchEvent()`, never `$event::dispatch()` (guarantees post-commit dispatch)

**Anti-patterns to avoid:** `handle()` instead of `execute()`; a Read Action that writes; a Process
Action inlining another Action's body "to avoid an extra class"; `$event::dispatch()` in an Action.

---

## Entity Contracts

| Property | Rule |
|----------|------|
| Class | `final readonly class` |
| Properties | `private readonly` scalars/enums/Carbon/ValueObjects only |
| Methods | Business question methods, `fromModel()`, `toArray()`, `toJson()` |
| Forbidden imports | Actions, Services, Livewire, Controllers, Repository interfaces |
| Forbidden I/O | Database, HTTP, cache, filesystem, logging, event dispatch |
| Forbidden construction | Static factory methods on Entity itself (use Actions) |

**Intent:** Entities are pure, immutable domain snapshots. They answer business questions (`canX()`,
`isX()`, `hasX()`) deterministically from a Model snapshot, with zero I/O.

**Why it matters:** Immutability makes entities trivially testable and the domain logic
reproducible. Any I/O inside an Entity breaks determinism (same inputs may produce different answers)
and violates C5. Static factory methods on the Entity bypass the Action boundary and let Livewire
construct domain objects directly.

**How to apply:** `StudentEntity::fromModel($student)` is the only sanctioned entry from
persistence (plus the Model's `as{Role}Entity()` bridge). Business questions return `bool`; no void
methods that "do work".

**Anti-patterns to avoid:** importing `App\Models\{Model}` inline; lazy-loading attributes inside the
Entity; a `__toString()` that hits a relationship; a static `create()` on the Entity.

---

## DTO / Data Contracts

| Property | Rule |
|----------|------|
| Class | `final readonly class` extending `BaseData` |
| Properties | `private readonly` — scalars, enums, Carbon only |
| Constructor | Single `public function __construct()` |
| Invariants | Enforced via `__construct` + private validation helpers |
| Forbidden imports | Models, Entities, Actions, Repositories |

**Intent:** DTOs carry data across the UI↔Business boundary. They are the transfer envelope, not a
second access path to the database.

**Why it matters:** Extending `BaseData` provides `from([...])` construction and validation wiring;
a standalone `final readonly class` that does not extend `BaseData` fails too. Importing Models/
Entities/Actions couples the layers the DTO exists to separate (C6).

**How to apply:** Properties are `string`/`int`/`float`/`bool`/`enum`/`Carbon` or a nested DTO.
Invariants are asserted inside `__construct` through private validation helpers so an invalid DTO can
never be constructed.

**Anti-patterns to avoid:** a `Collection` of Models inside a DTO to "hand tabular data" (use a typed
DTO list); extending a raw class instead of `BaseData`; public mutable properties.

---

## Model Contracts

| Property | Rule |
|----------|------|
| Fillable | `#[Fillable]` attribute (PHP 8.4) — NOT `$fillable`/`$guarded` |
| Business methods | None — delegate to Entity via `entity()` bridge |
| Entity bridge | `public function entity(): {ModuleName}Entity` |
| Forbidden | `update()`, `delete()`, `forceDelete()` calls in Model methods |
| Relationships | `return $this->hasMany(...)` (not string-based) |

**Intent:** Models are **persistence only**. They hold no business logic — that lives in Entities —
and expose state to the domain via `as{Role}Entity()` bridges.

**Why it matters:** A Model with business getters (`canX()`, `isX()`) duplicates domain logic on the
persistence layer, making the Entity contract meaningless and the domain rules ambiguous.
`$this->update()` / `$this->delete()` inside a model method is a silent mutation path that skips
Actions and their transaction/log edge — that is a C1-adjacent violation at the Model layer.

**How to apply:** `#[Fillable([...])]`; relationships as real method calls (`return $this->hasMany(...)`),
never string-based relationship magic; entity bridges per role (`asTeacherEntity()`, `asStudentEntity()`).

**Anti-patterns to avoid:** copying a stock Laravel model with implicit relationships and business
getters; `$fillable`/`$guarded` properties; `update()`/`delete()` self-calls in model methods.

---

## Enum Contracts

| Type | Base | Required | Forbidden |
|------|------|----------|-----------|
| **LabelEnum** | `LabelEnum` | `label()` method, backed string | Mutable state, I/O |
| **StatusEnum** | `StatusEnum` | `validTransitions()`, `allowedTransitions()`, `label()`, `color()`, `icon()` | Mutable state, I/O |
| **IntBacked** | `IntBackedEnum` | Integer backing values | — |
| **StringBacked** | `StringBackedEnum` | String backing values | — |

**Intent:** Enums are stable, translated state vocabulary. `LabelEnum` provides `label()`; `StatusEnum`
adds a state machine (`validTransitions()`).

**Why it matters:** The `label()` implementation must use `__()` (D3) so translations work; the
state machine must be exhaustive (`match()` on all cases) so PHPStan catches a missed transition. A
mutable enum or one with I/O breaks the pure-value contract.

**How to apply:** Backed `string`/`int` enums with `UPPER_SNAKE` case names and `snake_case` backing
values; terminal states return `[]` from `validTransitions()`; `isTerminal()` derives from it.

**Anti-patterns to avoid:** hardcoding `label()` output instead of `__('{module}.enums.{name}.{value}')`;
defining `validTransitions()` with a non-exhaustive `match()`.

---

## Event Contracts

| Property | Rule |
|----------|------|
| Dispatch | `$this->dispatchEvent()` in Actions (auto-flushed after TX commit) |
| Forbidden | `$event::dispatch()` in Actions |
| Listeners | Must implement `ShouldHandleEventsAfterCommit` |
| Properties | `private readonly` with business meaning in name |

**Intent:** Events carry domain facts; Actions dispatch them through the base-class helper so they
fire **after the transaction commits**.

**Why it matters:** `$event::dispatch()` inside a transaction fires before commit — a listener reading
committed state reads stale (or absent) rows. `ShouldHandleEventsAfterCommit` formalizes the same
guarantee for queued listeners.

**How to apply:** `$this->dispatchEvent(new {Entity}Created($entity))` only when a listener exists
(check `config/event.php`); Events are `final readonly` with `private readonly` promoted properties
named for business meaning (`$invoiceId`, not `$id`).

**Anti-patterns to avoid:** `$event::dispatch()` in an Action; dispatching to no listener (dead work
and implied intent); mutable event properties.

---

## Policy Contracts

| Property | Rule |
|----------|------|
| Authorization | `$this->authorize()` in Policy methods (NOT `$this->policy()->authorize()`) |
| Ability methods | `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete` |
| Forbidden | Business logic in Policies — only authorization |

**Intent:** Policies are pure authorization gates; they answer "can this role do this?" and nothing
else.

**Why it matters:** Business logic inside a Policy duplicates the Entity/domain layer and creates two
sources of truth for "is this allowed". The correct authorization call is `$this->authorize()` inside
the ability methods — calling the policy through `policy()` from inside a Policy is a self-referential
anti-pattern.

**How to apply:** Implement the seven standard ability methods with role/ownership checks only; render
the decision in the controller/Livewire via `$this->authorize('update', $model)`.

**Anti-patterns to avoid:** calling `$this->policy()->authorize()` inside a Policy; embedding
domain rules (deadlines, eligibility) in an ability method.

---

## Livewire Contracts

| Property | Rule |
|----------|------|
| Business logic | None — delegate to Actions |
| Model mutations | Use Command Actions — NEVER `Model::create/update/delete` |
| State management | `#[Computed]`, `#[Url]`, `#[Locked]` attributes |
| Security | `#[Authorize]` on sensitive methods |
| File uploads | Use Actions with Spatie MediaLibrary |
| Component structure | Properties → Lifecycle Hooks → Computed → Render → Actions → Private Helpers |

**Intent:** Livewire components are thin view models: render, validate, call Actions, catch
`RejectedException`, navigate.

**Why it matters:** A component that mutates Models directly is a C1 violation (see
`invariant-enforcement.md`); a component with heavy business logic can't be tested without a browser
and re-implements domain rules in the wrong layer. The fixed structure keeps components reviewable
and the mutation path single.

**How to apply:** Any save = `app({CommandAction}::class)->execute($dto)`; catch `RejectedException`
and render its message as a flash; use `#[Authorize]` before sensitive mutations.

**Anti-patterns to avoid:** `Model::save()` in a `save()` method; `DB::transaction()` in a component;
swallowing `Throwable` before `RejectedException`.

---

## Service Contracts

| Property | Rule |
|----------|------|
| Registration | `bind()`, `scoped()`, or `singleton()` in Providers |
| Constructor injection | `public function __construct(...)` |
| Forbidden | Facade static calls (prefer injected instances) |
| State | Stateless — no mutable properties |

**Intent:** Services are stateless infrastructure/DOMAIN helpers resolved by the container.

**Why it matters:** Static facade calls in a service make it coupled to globals and untestable;
mutable state on a singleton service leaks across requests. Stateless services are safely
resolvable as singletons/scoped without cross-request contamination.

**How to apply:** Container-registered classes with injected dependencies; pure functions of their
inputs.

**Anti-patterns to avoid:** `DB::...` facade calls inside Services when the dependency could be
injected; caching state on `$this`.

---

## Verification

```bash
python3 tools/scan_class_contracts.py        # Action/Entity/DTO/Model/Enum contracts
python3 tools/scan_violations.py             # C1-C8, D1-D6 cross-cutting
python3 tools/scan_conventions.py            # strict_types, Fillable, debug calls
```

**Interpretation guidance:** contract findings are **HIGH** when they break the guarantees consumers
rely on (wrong base class, model business methods, missing `#[Fillable]`) and **MEDIUM** for
cosmetic structural drift. A class that matches a *different* contract than its directory implies is
always HIGH — it is misplaced and mislabeled.