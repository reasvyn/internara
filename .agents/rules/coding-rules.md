# Coding Rules — Practical Application Guide

This is NOT a replacement for `docs/conventions.md`. It's a practical guide: *why* the mostly-DR
conventions exist and *what to check* before writing a class. Read `docs/conventions.md` for the
full written spec.

---

## Before Writing Any Class

Answer these five questions before creating anything — most "new" code already exists.

1. **Does this class already exist?** — `find app/ -name '*{Concept}*'`. Duplicating an existing
   Action/Entity/Model violates DRY and creates two sources of truth. Reuse or extract instead.
2. **What base class should it extend?** — Read the *actual* base class declaration in `app/Core/`
   (e.g. `BaseCommandAction` vs `BaseReadAction`) rather than guessing from its name; the base class
   defines the guarantee (transaction/log for Command, none for Read).
3. **Where does it live?** — `app/{Module}/{SubModule}/`. Check for an existing submodule first;
   creating a parallel directory for the same concern fragments the module.
4. **What's the equivalent existing class?** — `find app/{Module} -name '*Action.php' | head -5`.
   A sibling class is the best structural template; copy its contract shape, not a tutorial's.
5. **What do the tests expect?** — `find tests -path '*{Module}*{Concept}*'`. Existing tests encode
   the expected contract; matching them beats reinventing signatures.

**Anti-pattern:** asking none of these and generating a fresh class from memory — the fastest way
to drift from module conventions.

---

## Class Contract Checklist (with rationale)

### Action

- **Extends correct base class (BaseCommandAction / BaseReadAction / BaseProcessAction)** — the
  base class wires transaction/log/event dispatch; see the Triad table in `architecture-rules.md`.
- **Has exactly one public method: `execute()`** — a single entry point keeps mutations governed;
  extra public methods bypass the flow.
- **Returns typed value (ActionResponse, Model, Collection, void, etc.)** — explicit return types
  let PHPStan contract-check the call graph; a missing return type lets `mixed` leak through.
- **3+ params in `execute()` → uses a BaseData DTO (C7)** — positional-array parameters are
  unnameable and reorder-dangerous.
- **Calls `$this->transaction()` for DB writes** — atomic multi-write commits; a bare
  `Model::create()` skips the transaction and the log.
- **Calls `$this->log()` after mutation** — audit record for every change (SmartLogger).
- **Business rules checked via Entity (not inline) (C5)** — the Entity holds the domain rule once,
  where tests hit it directly; inline checks duplicate and drift.
- **Throws `RejectedException` for violations (not RuntimeException) (C8)** — expected rejections
  surface as flash messages, not generic 500s.

**Anti-pattern:** an Action whose body is fifteen nested `if` checks instead of two Entity calls.

### Entity

- **`final readonly class`** — immutability is the Entity contract; no setters, no mutation.
- **Has `fromModel(Model $model): static`** — the single sanctioned bridge from persistence.
- **All properties private, constructor-promoted** — no public mutable state.
- **Methods are business questions only: `canBeDeleted()`, `isActive()`, etc.** — void returners
  that "do work" inside an Entity are side effects; the Entity answers questions.
- **Does NOT import Actions, Services, Livewire, Controllers, HTTP (C5)** — app-layer imports pull
  framework concerns into the pure domain.
- **Does NOT import Model outside `fromModel()`'s parameter type** — the Entity is constructed from
  a snapshot, never from a live query.

**Anti-pattern:** an Entity calling `Model::find()` in a business method — violates pure-domain and
C5 at once.

### DTO

- **`final readonly class extends BaseData`** — the transfer contract is data-only and immutable.
- **Properties are only: string, int, float, bool, enum, Carbon, nested DTO (C6)** — no
  persistence-layer types cross the boundary.
- **Does NOT import Models, Entities, Actions, Livewire (C6)** — keeps the boundary decoupled.

**Anti-pattern:** a DTO carrying a `Collection` of `Model`s to "simplify delivery".

### Model

- **Extends BaseModel (or BaseAuthenticatable for user models)** — shared persistence base with
  `#[Fillable]` support and bridge helpers.
- **Uses `#[Fillable([...])]` attribute (D4)** — never `$fillable`/`$guarded` array properties.
- **Has `protected static function newFactory()`** — deterministic factory for tests.
- **Has entity bridge methods: `asXxxEntity(): XxxEntity`** — the Model exposes persisted state as
  an Entity.
- **NO business logic methods (canX/isX/hasX — those go in Entities)** — a Model answering business
  questions duplicates domain logic on the persistence layer.

**Anti-pattern:** a Model with a `canBeApproved()` method — that rule belongs on the Entity.

### Enum

- **Implements `LabelEnum` (all enums)** — `label()` via `__()` translation keys.
- **Implements `StatusEnum` (state machine enums)** — `validTransitions()` + `isTerminal()`.
- **`validTransitions()` uses exhaustive `match()` on all cases** — PHPStan verifies completeness;
  an incomplete `switch` silently omits a legal transition.
- **Terminal states return empty array from `validTransitions()`** — `isTerminal()` derives from it.

**Anti-pattern:** a hardcoded `label()` returning English text instead of `__()` keys.

---

## Translation Key Patterns

When adding a user-facing string (D3):

- **NEVER hardcode English text** — a hardcoded string renders English-only and blocks `lang/id`.
- **Always use `__('key')`**.
- **Convention: `{module}.{sub_noun}.{descriptive_key}`** — e.g. `setting.messages.saved`.
- **Always add the key to BOTH `lang/en/` and `lang/id/`** — one locale without the key renders the
  other's text or a raw key.
- **Check `lang/{locale}/{module}.php` for existing keys** in the same module before adding a new
  key — reuse over duplicate keys (DRY).

**Anti-pattern:** `__('Successfully saved')` (a sentence as the key) or `__('common.save')` defined
only in `lang/en/`.
