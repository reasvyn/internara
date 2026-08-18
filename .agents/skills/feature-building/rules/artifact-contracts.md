# Artifact Contracts — Non-Negotiable Component Shapes

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Every component type the build produces has a mandatory shape: Entity, Model, Enum, DTO, and Action
each follow a fixed contract. These contracts are what make the codebase uniform, auditable, and
architecture-conformant. A component that breaks its contract breaks the entire module's ability to
be verified by `arch-guard` scanners and reviewed by other agents.

---

## New Entity — `final readonly` with `fromModel()`

**What it enforces:** Every new Entity class is `final readonly`, exposes a static `fromModel()`
factory bridging from the Eloquent Model, and contains only pure business rules that return `bool`
(or other plain values) — no I/O, no `Action`/`Service` imports.

**Why it matters:** Entities are the business-rule layer. `final readonly` guarantees they are
immutable value objects — no hidden state mutation while evaluating a rule. `fromModel()` is the only
legal way a Model's data becomes an Entity, which keeps the conversion path uniform. Importing
Actions or Services (C5) turns the Entity into a service and breaks the strict downward layer
dependency, making business rules untestable without a database.

**How to apply:**

```php
final readonly class Internship
{
    public function __construct(
        public int $id,
        public string $status,
        public int $quota,
        public int $registeredCount,
    ) {}

    public static function fromModel(InternshipModel $model): self
    {
        return new self(
            id: $model->id,
            status: $model->status,
            quota: $model->quota,
            registeredCount: $model->registered_count,
        );
    }

    public function isFull(): bool
    {
        return $this->registeredCount >= $this->quota;
    }
}
```

**Pitfalls to avoid:**

- Making the Entity mutable (removing `readonly`) "because a rule updates state" — update via a new
  Entity or through the Action, never by mutating the value object.
- Adding `Model::query()`/`DB::` calls inside the Entity — that is I/O and belongs in an Action.
- Importing an Action, Service, or Livewire class into the Entity (C5).

**Verification:** `scan_class_contracts.py` reports the Entity as conformant; the Entity imports only
plain PHP/DTO dependencies.

---

## New Model — `#[Fillable]` + `BaseModel`

**What it enforces:** Every new Model extends `BaseModel` (or `BaseAuthenticatable` for users) and
declares its fillable attributes with the `#[Fillable([...])]` attribute — never the legacy
`$fillable` property.

**Why it matters:** `#[Fillable]` is the project's declared mass-assignment control (D4) and is what
the arch-guard scanner checks. Extending `BaseModel` imports the module's shared behaviors (timestamps,
casts, soft deletes where configured), so a Model that skips it duplicates behavior and diverges from
convention. A Model with no `#[Fillable]` silently allows mass assignment of arbitrary columns, which
is a security regression.

**How to apply:**

```php
use App\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['student_id', 'internship_id', 'status'])]
final class Placement extends BaseModel
{
    protected $table = 'placements';

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }
}
```

**Pitfalls to avoid:**

- Copying `$fillable = [...]` from an old Laravel habit (D4 violation).
- Extending `Model` directly instead of `BaseModel`.
- Putting business logic in the Model — it is persistence-only; delegate to the Entity via an
  `as{Role}(): EntityType` bridge when business rules exist.

**Verification:** `scan_conventions.py` and `scan_class_contracts.py` report the Model as clean.

---

## New Mutation — Command Action, Never `Model::create()` in Livewire

**What it enforces:** Every mutation (create/update/delete/save) goes through a Command Action with a
single public `execute()` method. Livewire components and Blade never call `Model::create/update/delete`
directly (C1).

**Why it matters:** Actions are the only entry point for mutations because they own the transaction,
the audit log, and the domain rules. A Livewire component that mutates a Model bypasses transactions,
logging, and authorization boundaries — the three safety nets the Action triad exists to provide. C1
is enforced by the arch-guard scanner specifically because this is the highest-frequency violation.

**How to apply:**

- Mutation on a UI event → inject a Command Action into the component method and call
  `$action->execute($dto)`.
- Command Action with 3+ parameters → accept a DTO (C7), never a raw positional list.
- Return an `ActionResponse` so the component can branch on `$result->failed()`.

```php
public function save(CreatePlacementAction $action): void
{
    $this->form->validate();
    $result = $action->execute($this->form->toArray());
    // branch on $result->failed(), flash message, redirect
}
```

**Pitfalls to avoid:**

- `Model::create()` in a component "for a tiny field" — C1 has no size threshold.
- `DB::transaction()` in the component — the Action owns transactions.
- Passing a raw `array` of 3+ values to an Action instead of a DTO (C7).

**Verification:** `scan_violations.py` reports no C1 violations; every mutation call site is an
Action `execute()`.

---

## New Query — Read Action for Complex Queries, Model Scopes for Simple Ones

**What it enforces:** Complex queries (aggregations, cross-module data, heavy filtering) are wrapped
in a Read Action; simple single-model lookups may use Model scopes or Eloquent directly in the
component's `render()`.

**Why it matters:** Read Actions give a query a name, a test surface, and a stable return type —
which is what complex or cross-module queries need to be verifiable and reusable. Simple lookups gain
nothing from an Action wrapper; forcing one adds boilerplate without value. The line is drawn at
complexity, not at "it's a query".

**How to apply:**

- Use a Read Action when the query aggregates, joins across modules, or encodes business logic (e.g.,
  "active internships with remaining quota for a student").
- Use a Model scope (or a plain eager-loaded query) in `render()` when it is a simple, single-model
  listing.
- Keep Read Actions `final`, with a single public `execute()`, returning a typed value or
  collection — no side effects, no logging.

**Pitfalls to avoid:**

- Wrapping every trivial listing in a Read Action out of over-engineering.
- Putting a cross-module aggregation inline in a component "to save a file" — that query then has no
  test surface and is duplicated the next time it is needed (DRY).

**Verification:** Every complex/cross-module query is behind a Read Action with a spec-traced test;
every component `render()` contains only simple queries or Read Action calls.

---

## References

| Topic                      | Asset                                        |
| -------------------------- | -------------------------------------------- |
| Entity contracts           | `docs/architecture/entity-pattern.md`        |
| Model contracts            | `docs/architecture/model-pattern.md`         |
| Action Triad               | `docs/architecture/action-pattern.md`        |
| DTO / data contracts       | `docs/architecture/data-pattern.md`          |
| Enum contracts             | `docs/architecture/enum-pattern.md`          |
| Critical invariants C1-C8  | `AGENTS.md` §Critical Invariants             |
