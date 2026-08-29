# Non-Negotiable Invariants — C1-C8, D1-D6

> **Last updated:** 2026-08-17 **Changes:** rewritten comprehensively — per-invariant intent, enforcement, and detection

These invariants MUST be followed. No exceptions, no "temporary deviations." They are enforced by
`scan_violations.py` (C1-C8, D1-D6) and `scan_conventions.py`. A violation is a workflow violation
even if the feature works.

---

## Architecture Invariants (C1-C8)

### C1 — No `Model::create/update/delete` in Livewire

**Intent:** All mutations happen through Command Actions. Livewire components validate input, catch
exceptions, and delegate writes; they never touch the database directly.

**Why it matters:** Business rules, transactions, logging, and event dispatch are centralized in
Actions (the only sanctioned mutation path). A direct `Model::create()` in a component bypasses
everything — rules can be skipped, no audit log, no transaction, and no event fired downstream.

**How to apply:** In any Livewire component, to mutate state: construct a Command Action (via method
injection), pass a `{Verb}{Entity}Data` DTO to `execute()`, and handle the returned `ActionResponse`.
Methodical: form submits → `Action::execute($data)`.

**Pitfall to avoid:** "Just this one `update()` call" — the scanner flags it, and the missing
transaction/log guarantees are precisely what later production bugs trace to.

**Detection:** `python3 tools/scan_violations.py` · `python3 tools/scan_conventions.py`.

### C2 — No `app()->make()` / `resolve()` — use constructor injection

**Intent:** Dependencies are resolved via constructor injection, never via the service container
directly inside method bodies.

**Why it matters:** `app()->make()` bakes a container dependency into the method, making it
untestable and violating the Action Triad contract (Actions receive collaborators via their
constructor). It also hides dependencies, breaking static analysis of the call graph.

**How to apply:** Constructor-promote dependencies (`protected readonly {Type} $dep`) in the class;
Livewire components use **method injection** (type-hinted method parameters) instead. Actions
(Command/Read/Process) inject collaborators via the constructor.

**Pitfall to avoid:** Using `app(SomeAction::class)->execute()` inside a Process Action "for
laziness" — inject the Action instead so the Process Action's orchestration is testable.

**Detection:** `python3 tools/scan_violations.py` (regex for `app()->make|app\(|resolve\()`).

### C3 — No raw SQL without parameterized binding

**Intent:** All SQL goes through Eloquent's query builder; `DB::raw()` / `whereRaw()` are forbidden
unless the values are passed as parameterized bindings.

**Why it matters:** Concatenating user input into SQL is a SQL-injection channel. Parameterized
bindings keep data and code separate, making injection impossible even when the input is hostile.

**How to apply:** Build queries with Eloquent (`where`, `when`, `orderBy`, etc.). If a raw fragment
is unavoidable, pass values as bindings (`DB::raw('... :param', ['param' => $value])` or
`whereRaw('... ?', [$value])`) and document the exception in the method docblock.

**Pitfall to avoid:** Interpolating a sanitized variable directly into a raw string — sanitization
is not binding; the fraction of developer intent still allows injection if the variable is tainted.

**Detection:** `python3 tools/scan_violations.py` · `scan_security.py` (SQLi).

### C4 — No inline cache keys — register in `config/cache-keys.php`

**Intent:** Every cache key is declared once in `config/cache-keys.php`; code refers to these
registered keys instead of inline string literals.

**Why it matters:** Inline keys are unreviewable, drift silently between caller and flusher, and
break invalidation (a key written as `'user.' . $id` can never be flushed consistently). Centralized
registration gives a single audit surface for key naming and TTL.

**How to apply:** Add the key to `config/cache-keys.php` (see `docs/guides/arch/cache-pattern.md`
§Registration), then use `config('cache-keys.{name}')` (or the documented helper) at the call site.

**Pitfall to avoid:** Reusing a registered name with a different TTL than declared, or building keys
inline inside `Cache::remember()`.

**Detection:** `python3 tools/scan_violations.py` · `scan_conventions.py` (inline cache key regex).

### C5 — Entities must NOT import Actions, Services, Livewire, Controllers

**Intent:** Entities are pure domain value objects — zero I/O, zero dependencies on application
layers. They answer business questions and nothing else.

**Why it matters:** An Entity that imports an Action or Service creates a cycle and drags framework
concerns into the domain. Purity is what makes Entities trivially testable (no DB, no HTTP) and
reusable across modules.

**How to apply:** `final readonly class`, constructor-promoted scalar/enum properties,
`fromModel(Model $model): static`, business questions only (`canX()`, `isX()`, `hasX()`). No `use`
of Actions/Controllers/Livewire; no method performs DB/HTTP/file I/O.

**Pitfall to avoid:** Adding a `static` helper that queries the DB inside an Entity "for convenience".

**Detection:** `python3 tools/scan_violations.py` · `scan_class_contracts.py` (Entity contract).

### C6 — DTOs must NOT import Models, Entities, Actions

**Intent:** DTOs (`BaseData` subclasses) carry data across the UI↔Business boundary using only
scalars, enums, `Carbon`, and nested DTOs.

**Why it matters:** DTOs are the transfer contract; embedding Models/Entities couples the data
layer to the persistence layer and breaks the boundary the whole Action/DTO architecture exists to
enforce. Restrictions mirror C5 but for the data-transfer role.

**How to apply:** `final readonly class {Verb}{Entity}Data extends BaseData` with
scalar/enum/Carbon/nested-DTO properties only. Construct via `{Name}Data::from([...])`.

**Pitfall to avoid:** Passing a `Model` or `Entity` as a DTO property to "avoid boilerplate".

**Detection:** `python3 tools/scan_violations.py` · `scan_class_contracts.py` (Data contract).

### C7 — Command/Process Actions: accept DTO for 3+ params, return ActionResponse

**Intent:** Any Command/Process Action with three or more parameters must accept a single DTO;
structured results (success flag, data, errors, message) come back as `ActionResponse`.

**Why it matters:** Positional parameters of three or more are error-prone and impossible to name at
the call site; a DTO makes arguments self-documenting and reorder-safe. `ActionResponse` gives
Livewire a consistent contract for success/error feedback and flash messages.

**How to apply:** 3+ inputs → define `{Verb}{Entity}Data`; 1-2 typed scalars may stay as explicit
params. Returns are `ActionResponse::ok()/created()/updated()/deleted()/error()`.

**Pitfall to avoid:** Passing a raw associative `array` for 3+ inputs "because it's short" — an
untyped array defeats the contract DTOs exist to provide.

**Detection:** `python3 tools/scan_violations.py` (parametric signature check).

### C8 — Business rules → `RejectedException`, not `RuntimeException`

**Intent:** Domain-level rule violations throw `RejectedException`; infrastructure failures throw
`RuntimeException` (rethrown). The two are never interchangeable.

**Why it matters:** Livewire catches `RejectedException` to show a user-facing flash message
(expected business rejections), while infrastructure failures surface as a generic error. Throwing
`RuntimeException` for a business rule produces the wrong UX (generic error instead of the rejection
message) and conflates recoverable domain logic with system faults.

**How to apply:** Entity business checks that can legitimately reject call `throw new
RejectedException('...')` (with a translated message); infrastructure/DB/HTTP failures signal via
`RuntimeException` at the boundary and are handled by the generic handler.

**Pitfall to avoid:** Using `RuntimeException` for "duplicate record" or "terminal state" checks.

**Detection:** `python3 tools/scan_violations.py` · `scan_conventions.py`.

---

## Coding Invariants (D1-D6)

### D1 — `declare(strict_types=1)` in ALL PHP files except migrations/config

**Intent:** Every PHP file (except migrations and config) opens with `declare(strict_types=1);`
immediately after `<?php`.

**Why it matters:** Strict types prevent silent scalar coercion at function boundaries; without it,
`"5"` and `5` flow interchangeably and subtle bugs appear far from their source.

**How to apply:** `<?php\n\ndeclare(strict_types=1);` is the first statement in classes, tests,
factories, and seeders. Migrations and `config/` files are exempt by convention.

**Pitfall to avoid:** Copy-pasting config snippets into an app class and losing the declare line.

**Detection:** `python3 tools/scan_conventions.py` (strict_types check).

### D2 — No `dd/dump/ray/var_dump/print_r/die` in committed code

**Intent:** Debug calls never exist in committed code.

**Why it matters:** A `dd()` halts a production request at runtime; debug output leaks internals and
breaks API responses. Debugging belongs in tests and the logging pipeline, not in the source tree.

**How to apply:** Remove all debug calls before commit; verify with the scanner. To inspect values
during development, use tests or the `log` facade — never leave a `dd()`.

**Pitfall to avoid:** Leaving a `dump()` "temporarily" behind an `if (config('app.debug'))` guard.

**Detection:** `python3 tools/scan_conventions.py` (debug call regex) · `rg "dd\\(|dump\\(|ray\\("`.

### D3 — All user-facing strings use `__()` — both `lang/en/` and `lang/id/`

**Intent:** Every string rendered to a user goes through `__()`/`trans()`, and the key exists in
both `lang/en/` and `lang/id/`.

**Why it matters:** Internara serves Indonesian schools; hardcoded strings silently ship English-only
(and block the Indonesian locale). Centralized keys keep UI text consistent and auditable.

**How to apply:** `__('{module}.{entity}.{action}_success')` style keys; add the key to
`lang/en/{module}.php` **and** `lang/id/{module}.php`. Status labels via `LabelEnum::label()`.

**Pitfall to avoid:** Hardcoding a flash message in a Livewire component "because it's small".

**Detection:** `python3 tools/scan_conventions.py` · `scan_naming.py` (hardcoded strings).

### D4 — Models use `#[Fillable]` attribute, NOT `$fillable` / `$guarded`

**Intent:** Mass-assignment allow-listing is declared via the `#[Fillable([...])]` PHP attribute
(PHP 8.4), never the legacy `$fillable`/`$guarded` array properties.

**Why it matters:** The attribute form centralizes the allow-list next to casts and keeps the model
body declarative; the legacy array form is a footgun (guarded-by-default invites accidental mass
assignment) and is rejected by the scanners.

**How to apply:** `#[Fillable(['name', 'email', ...])]` on every Model (and `BaseAuthenticatable`
user models).

**Pitfall to avoid:** Copying a stock Laravel model with `protected $fillable = [...]`.

**Detection:** `python3 tools/scan_conventions.py` (Fillable attribute check).

### D5 — Never pass raw request input to `create()`/`update()` — use `->only()` or `->toArray()`

**Intent:** Mutation calls receive explicitly-listed fields or validated DTO data — never
`$request->all()` / `$this->all()`.

**Why it matters:** `->all()` passes every submitted field, so a crafted request can reach
mass-assignable columns outside the intended set. `->only()` or a validated DTO narrows input to the
permitted fields, complementing D4's allow-list.

**How to apply:** `Model::create($data->toArray())` with a DTO, or `->only([...])` with an explicit
field list — never `Model::create($request->all())`.

**Pitfall to avoid:** Trusting front-end rendering to limit fields ("the form doesn't send it").

**Detection:** `python3 tools/scan_violations.py` · `scan_security.py` (mass assignment).

### D6 — Foreign keys use `foreignUuid()->constrained()` with explicit `onDelete()`/`onUpdate()`

**Intent:** Every foreign-key column is declared with `foreignUuid('{fk}')->constrained('{table}')`
plus explicit `->onDelete(...)` / `->onUpdate(...)` behavior.

**Why it matters:** Without explicit referential-action rules the database default silently restricts
deletion; explicit `cascade` vs `restrict`/`nullOnDelete` makes data-integrity intent readable in the
migration and prevents orphaned or unexpectedly-blocked rows.

**How to apply:** In every migration that references another table:
`$table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();` (or
`->restrictOnDelete()`, `->nullOnDelete()` per the domain rule). Both `onDelete` and `onUpdate`
behavior are stated explicitly.

**Pitfall to avoid:** `$table->foreignUuid('user_id');` with no `constrained()`/`onDelete` — the
scanner flags the missing referential actions.

**Detection:** `python3 tools/scan_violations.py` · `scan_security.py` (DB conventions).