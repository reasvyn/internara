# Architecture Rules — Layer Boundaries & Action Triad

Do NOT use this as an authoritative spec. Read `docs/architecture.md` for the full architecture and
`app/Core/Actions/` files for actual contracts. This file explains *why* each boundary check matters,
so verification is grounded, not mechanical, and the arch-guard scans stay comprehensible.

---

## Layer Boundary Checks

The 4-layer model is strictly downward — each layer depends only on layers below it. The checks
below verify no layer crosses its boundary.

### UI Layer (Livewire/Blade/Controller) — Layer 4

**What it may do:** Render, validate input, call Read/Command Actions, catch `RejectedException`,
navigate.

**Checks and why:**

- **Does NOT call `Model::create/update/delete/save` directly (C1)** — direct model access in the
  UI bypasses Command Actions, so business rules, transactions, audit logging, and event dispatch
  are skipped. The UI's only mutation path is `{CommandAction}::execute(DTO)`.
- **Does NOT call `DB::transaction()` / `DB::beginTransaction()`** — transaction management belongs
  to Actions (`$this->transaction()`); the UI holding a transaction open across render paths leaks
  locks and commits the wrong unit of work.
- **Does NOT use `app()->make()`, `resolve()`, or `new Action()` (C2)** — direct container or
  constructor resolution in the UI hides dependencies and breaks testability. Livewire uses **method
  injection** (type-hinted params); the container wires them per request.
- **Injects Actions via method parameters (not constructor)** — Livewire components re-render
  between lifecycle hooks; constructor-injected services become stale across renders, while method
  injection keeps them current and explicit at the call site.
- **Catches `RejectedException` from Action calls (C8)** — a business rejection is an expected
  outcome, not an error; catching and rendering the flash is the UI's job. Not catching it surfaces
  a 500 instead of the rejection message.
- **Passes DTO or typed scalars to Actions (never raw array for 3+ params) (C7)** — positional
  arrays of 3+ are unnameable and reorder-dangerous; a `{Verb}{Entity}Data` DTO names the inputs.

**Anti-patterns to avoid:** `Model::save()` in a component's `save()` method; `app()->make(X::class)`
to skip injection; swallowing `Throwable` before `RejectedException`.

### Business Layer (Actions) — Layer 3

**What it may do:** Enforce business flow, orchestrate DB writes in transactions, log, dispatch
events.

**Checks and why:**

- **Extends the correct base class (Command/Read/Process)** — the base class defines the guarantees:
  Command = transaction + log, Read = no mutation, Process = orchestration with transaction + log.
  A wrong base silently adds or drops those guarantees.
- **Has exactly one public method: `execute()`** — Actions have one entry point; extra public
  methods become de-facto alternate entry points that bypass the intended flow. See
  `code-writing/rules/class-contracts.md`.
- **Command/Process: calls `$this->transaction()` for DB writes** — multi-write mutations commit
  atomically; a write outside the transaction risks partial state on failure.
- **Command/Process: calls `$this->log()` after mutation** — every mutation is audited via
  SmartLogger; skipping it leaves unrecorded changes.
- **Command/Process: calls `$this->dispatchEvent()` only if a listener exists** — dispatching to no
  listener is dead work and implied intent; check the listener first.
- **Uses `RejectedException` for business rule violations (C8)** — domain rejections propagate to
  the UI as flash messages, not generic 500s.
- **Does NOT catch `RejectedException` inside the Action (let it propagate)** — the Action does not
  decide the rejection's UX; the UI does. Catching inside hides the outcome from the caller.

**Anti-patterns to avoid:** DB writes in a Read Action; orchestrating without `$this->transaction()`;
catching `RejectedException` in the Action "to normalize" it.

### Data Layer (Models/Entities/DTOs) — Layer 2

**What it may do:** Persist (Models), hold pure domain state + business questions (Entities), carry
data across boundaries (DTOs).

**Checks and why:**

- **Entity is `final readonly`, has `fromModel(Model): static`** — immutability and a single bridge
  from persistence keep entities deterministic and trivially testable.
- **Entity does NOT import Actions, Services, Livewire, Controllers (C5)** — importing app-layer
  classes drags framework concerns into the domain and creates cycles; entities answer business
  questions with zero I/O.
- **DTO is `final readonly`, only carries scalars/enums/Carbon (C6)** — the transfer boundary is
  data-only; models/entities/actions in a DTO couple the layers the DTO exists to separate.
- **DTO does NOT import Models, Entities, Actions (C6)** — same boundary; see
  `code-writing/rules/class-contracts.md`.
- **Model uses `#[Fillable]` attribute, not `$fillable` property (D4)** — declarative attribute
  allow-list keeps mass assignment safe and replaceable.

**Anti-patterns to avoid:** `Model::query()` inside an Entity; a DTO carrying a `Model` property;
`$fillable = [...]` legacy property.

---

## Action Triad Quick Check

| Found this | Must extend | Must have | Must NOT have |
|-----------|-------------|-----------|----------------|
| Creates/updates/deletes data | `BaseCommandAction` | `$this->transaction()` + `$this->log()` | Any query-only logic |
| Complex query only | `BaseReadAction` | `Cache::remember()` for read cache | `$this->transaction()` or `$this->log()` |
| Orchestrates multiple steps | `BaseProcessAction` | `$this->transaction()` + `$this->log()` | Direct DB queries (delegate to other Actions) |

**Read this row-wise:** the "Must have" column is what the base class *provides the wiring for*;
the "Must NOT have" column is what would violate the boundary. A Read Action with a log call is
either a misclassified Command or an unguarded side effect.

---

## Data Flow Verification

Every mutation flows through this canonical path:

```
Livewire → validates → Action::execute(DTO)
                         ├── Entity::fromModel(model) → business check
                         ├── Model::create/update(values from DTO)
                         ├── $this->log()
                         ├── $this->dispatchEvent() [queued]
                         └── transaction commits → events fire
```

If any step is skipped, verify there's a valid reason documented in the code. Skipping `fromModel`
means the business check didn't run; skipping `dispatchEvent` means a documented side effect is
silently absent. Both are drift worth auditing (`spec-audit` traces these).
