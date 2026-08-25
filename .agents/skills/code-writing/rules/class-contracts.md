# Class Contract Checklists — Action, Entity, DTO, Model, Enum

> **Last updated:** 2026-08-17 **Changes:** rewritten comprehensively — per-contract structural rules, rationale, and detection

Each component type has a strict structural contract. The contracts below are enforced by
`scan_class_contracts.py` and mirror `docs/guides/arch/{pattern}-pattern.md`. Follow them exactly; a
component that "sort of" matches the contract fails the scan and confuses the layer it belongs to.

---

## Command Action

### Structure

```php
class Create{Entity}Action extends BaseCommandAction
{
    public function __construct(
        // Constructor-injected dependencies (readonly promoted)
    ) {}

    public function execute(Create{Entity}Data $data): ActionResponse
    {
        // 1. Business rules via Entity (throw RejectedException on violation)
        // 2. $this->transaction(fn () => ...)
        // 3. Model::create() inside transaction
        // 4. $this->log() after mutation
        // 5. $this->dispatchEvent() if listener exists
        // 6. Return ActionResponse
    }
}
```

### Rules

- **Extends `BaseCommandAction`** — the base class provides `transaction()`, `log()`, and
  `dispatchEvent()`. Using `BaseReadAction` or `BaseProcessAction` changes those guarantees (Read
  has no transaction/log; Process is for orchestration).
- **Single public method: `execute()`** — Actions have exactly one entry point. No public helpers,
  no public `__invoke` elsewhere.
- **Accepts DTO for 3+ params** (C7) — typed scalars are acceptable for 1-2 params only.
- **Returns `ActionResponse`** — success flag, data, errors, and message for Livewire to render.
- **Wraps DB writes in `$this->transaction()`** — multiple writes commit atomically; a failure rolls
  back all of them.
- **Calls `$this->log()` after mutation** — a SmartLogger audit entry exists for the change.
- **Business rules delegated to Entity** — the Entity (not the Action body) answers `canX()`
  questions; the Action reads the result and throws `RejectedException` on violation.
- **Throws `RejectedException` (not `RuntimeException`)** (C8) — domain rejections flow to the UI as
  flash messages.
- **Events dispatched via `$this->dispatchEvent()`** (not `$event::dispatch()`) — guarantees
  dispatch fires after commit, which is required for listeners that read committed state.

**Pitfall:** Wrapping a single `Model::create()` in a raw `DB::transaction()` inside the Action
instead of `$this->transaction()` — you lose the base class's consistent logging/dispatch wiring.

---

## Read Action

### Structure

```php
class Read{Entity}Action extends BaseReadAction
{
    public function execute(): {ReturnType}
    {
        // Complex query logic
        // May use Cache::remember()
        // NEVER mutates database state
    }
}
```

### Rules

- **Extends `BaseReadAction`** — provides cache integration and no transaction mapping.
- **Single public method: `execute()`**.
- **NO `$this->transaction()` or `$this->log()`** — Read Actions never mutate and never audit.
- **NO database mutations** — `create/update/delete/insert` anywhere in a Read Action is wrong; it
  should be a Command Action (C1 flow).
- **Returns typed objects or collections (never raw arrays)** — strong return types (`Collection`,
  typed DTO/Entity lists) so callers and PHPStan can contract-check the result.

**Pitfall:** A Read Action that performs `Model::save()` "for a minor side effect" — split the
mutation into the Command Action and keep the read pure.

---

## Process Action

### Structure

```php
class Process{Entity}Action extends BaseProcessAction
{
    public function __construct(
        // Injected Command/Read Actions
    ) {}

    public function execute(): ActionResponse
    {
        // 1. Compose other Actions via injected dependencies
        // 2. $this->transaction(fn () => ...)
        // 3. $this->log() after orchestration
        // 4. $this->dispatchEvent() if listener exists
    }
}
```

### Rules

- **Extends `BaseProcessAction`** — composes Command/Read Actions into a multi-step flow.
- **Composes other Actions via constructor injection** (C2) — never `app()->make()` inside.
- **NO direct DB queries** — delegate to the injected Actions; a Process Action that touches the DB
  directly duplicates Command logic and skips its transaction/log wiring.
- **Wraps orchestration in `$this->transaction()`** — the whole multi-action flow commits atomically.
- **Calls `$this->log()` after completion** — the orchestration is logged as one auditable unit.

**Pitfall:** Inlining another Action's body into the Process Action to "avoid an extra class" — that
recreates the monolith pattern the triad exists to prevent.

---

## Entity

### Structure

```php
final readonly class {Entity}
{
    public function __construct(
        // private properties from model attributes
    ) {}

    public static function fromModel(Model $model): static
    {
        // Bridge from Model to Entity
    }

    public function canBeDeleted(): bool
    {
        // Business question method
    }
}
```

### Rules

- **`final readonly class`** — immutability is non-negotiable; entities never mutate.
- **`fromModel(Model $model): static`** — the only sanctioned entry point from the persistence layer
  (plus the Model's `as{Role}Entity()` bridge).
- **All properties private, constructor-promoted** — no public state to mutate.
- **Methods are business questions only** (`canX()`, `isX()`, `hasX()`) — void returners that do
  work are suspect.
- **NO imports** of Actions, Services, Livewire, Controllers, or HTTP (C5).
- **NO I/O** — no DB calls, no HTTP calls, no file operations (pure domain).

**Pitfall:** Adding a `public function __tostring()` or helper that reads from a Model property
lazily — entities construct fully from a Model snapshot; no lazy loading.

---

## DTO (BaseData)

### Structure

```php
final readonly class {Verb}{Entity}Data extends BaseData
{
    public function __construct(
        // scalar, enum, Carbon, or nested DTO properties only
    ) {}
}
```

### Rules

- **`final readonly class`**.
- **Extends `BaseData`** — provides `from([...])` construction and validation wiring.
- **Properties are only** `string`, `int`, `float`, `bool`, `enum`, `Carbon`, or a nested DTO (C6).
- **NO imports** of Models, Entities, Actions, Livewire — the transfer boundary stays clean.

**Pitfall:** Putting a `Collection` of Models into a DTO to deliver tabular data — use a typed DTO
list or collection of DTOs instead.

---

## Model

### Structure

```php
class {Entity} extends BaseModel
{
    #[Fillable([...])]
    protected function casts(): array
    {
        // ...
    }

    public function as{Role}Entity(): {Entity}
    {
        // Bridge to Entity
    }
}
```

### Rules

- **Extends `BaseModel`** (or `BaseAuthenticatable` for user models) — gives the shared Eloquent
  base, `#[Fillable]` support, and entity bridges.
- **Uses `#[Fillable([...])]` attribute** (D4), NOT `$fillable`/`$guarded`.
- **Has `protected static function newFactory()`** — so tests can factory the model deterministically.
- **Has entity bridge methods** `as{Role}Entity(): {Entity}` — the Model exposes its persisted state
  as a pure Entity.
- **NO business logic methods** (`canX()`, `isX()`, `hasX()` — those live in Entities). A Model that
  answers business questions duplicates domain logic on the persistence layer (C5-adjacent).

**Pitfall:** Copying a stock Laravel model with implicit relationships and business getters — keep
Models persistence-only.

---

## Enum

### Structure

```php
enum {Name}: string implements LabelEnum, StatusEnum
{
    case STATE_A = 'state_a';
    case STATE_B = 'state_b';

    public function label(): string
    {
        return __('{module}.enums.{name}.{value}');
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::STATE_A => [self::STATE_B],
            self::STATE_B => [],  // terminal
        };
    }

    public function isTerminal(): bool
    {
        return $this->validTransitions() === [];
    }
}
```

### Rules

- **`string`-backed enum** — backing values are stable `snake_case` strings stored in the DB.
- **Implements `LabelEnum`** (all enums) — provides `label()` and the `__()` translation contract.
- **Implements `StatusEnum`** (lifecycle enums) — provides `validTransitions()` and `isTerminal()`.
- **`UPPER_SNAKE` case names, `snake_case` backing values.**
- **`label()` returns translated string via `__()`** (D3).
- **`validTransitions()` uses an exhaustive `match()`** on all cases — PHPStan verifies no case is
  missed.
- **Terminal states return `[]`** — `isTerminal()` derives from that.

**Pitfall:** Hardcoding `label()` output instead of `__()` keys, or defining `validTransitions()`
that omits a case (incomplete `match()`).

---

## Verification Commands

```bash
python3 scripts/scan_class_contracts.py        # Action/Entity/DTO/Model/Enum contracts
python3 scripts/scan_violations.py             # C1-C8, D1-D6
python3 scripts/scan_conventions.py            # strict_types, Fillable, debug, hardcoded strings
```