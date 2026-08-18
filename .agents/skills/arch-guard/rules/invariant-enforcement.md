# Invariant Enforcement — C1-C8 & D1-D6 Critical Rules

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

The C1-C8 and D1-D6 invariants are the non-negotiable contract of the Internara architecture. They
are the highest-priority checks in the rule reference hierarchy — everything else (architecture
patterns, conventions, security, performance) is subordinate. This rule explains each invariant, why
it exists, how it is detected, and what failure occurs when a codebase violates it.

---

## Priority Hierarchy

Rules are checked in this fixed priority order — when a finding is ambiguous or a codebase has
multiple problems, the higher-priority rule wins and is reported first:

1. **AGENTS.md Critical Invariants** (C1-C8, D1-D6) — non-negotiable
2. **Architecture patterns** (`docs/architecture/*.md`) — structural contracts
3. **Coding conventions** (`docs/conventions.md`) — style and naming
4. **Security rules** — OWASP, Laravel security best practices
5. **Performance rules** — N+1, query optimization, eager loading

**Why this order exists:** invariants are architecture-breaking and must be surfaced to the developer
before cosmetic nits. A C1 violation buried under a low-severity naming finding will be fixed last —
exactly when it should be fixed first. If a finding violates both an invariant and a convention,
report the invariant.

---

## C-Invariants (Architecture)

These protect the layered architecture and the Action Triad. Each has a precise detection rule used
by `scan_violations.py`.

| ID | Rule | Detection |
|----|------|-----------|
| **C1** | No `Model::create/update/delete/forceDelete/trash` in Livewire | Livewire PHP files: `Model::create(`, `Model::update(`, `Model::delete(`, `Model::forceDelete(`, `->delete(` on Model instances in Livewire methods |
| **C2** | No `app()->make()` / `app()->makeWith()` / `resolve()` / `app()->bind()` | All PHP: `app()->make(`, `app()->makeWith(`, `resolve(`, `app()->bind(`, `app()->singleton(` (in non-Providers) |
| **C3** | No `DB::raw()` / `whereRaw()` / `selectRaw()` without parameterized binding | All PHP: `DB::raw(`, `->whereRaw(`, `->selectRaw(`, `->havingRaw(`, `->orderByRaw(` |
| **C4** | No inline cache keys — register in `config/cache-keys.php` | `Cache::remember(`, `Cache::get(`, `Cache::put(` with string literals not from config |
| **C5** | Entities must NOT import Actions, Services, Livewire, Controllers | `app/{Module}/Entities/` files: forbidden `use` statements |
| **C6** | DTOs must NOT import Models, Entities, Actions | `app/{Module}/Data/` files: forbidden `use` statements |
| **C7** | Command/Process Actions: DTO for 3+ params, return ActionResponse | Command/Process actions with execute() params > 2 not using BaseData |
| **C8** | Business rules → `RejectedException`, not `RuntimeException` | `throw new RuntimeException(` in Action/Entity methods |

### C1 — No Model mutations in Livewire

**Intent:** The UI layer must never persist directly. All mutations flow through Command Actions.

**Why it exists:** Direct `Model::create()`/`update()`/`delete()` in a Livewire component bypasses
Command Actions, so business rules (Entity checks), transactions, audit logging, and event dispatch
are all skipped. The mutation becomes an unlogged, unvalidated write — a data-integrity and
auditability hole.

**How to apply:** Any mutation the component needs is expressed as
`app({CommandAction}::class)->execute($dto)` where the Action owns the Model call.

**Failure mode if ignored:** A school administrator updates an enrollment that violates an Entity
business rule; the component writes it anyway, the audit log shows nothing, and downstream report
generation crashes on the invalid state.

### C2 — No service locator

**Intent:** Dependencies are resolved by the container through **constructor/method injection**,
never pulled out of the container at call time.

**Why it exists:** `app()->make()` / `resolve()` hides the dependency from the signature, defeats
testability (you cannot substitute a mock without container rebinding), and makes the dependency
graph invisible.

**How to apply:** Livewire uses method injection; Actions use promoted constructor injection. The
only legitimate `app()->bind()` / `app()->singleton()` usage is inside `Providers/`.

**Failure mode if ignored:** A component that calls `app()->make(LoginAction::class)` cannot be
unit-tested against a fake Action; refactors that rename the Action fail at runtime instead of at the
call site.

### C3 — No raw SQL without bindings

**Intent:** Every query is parameterized.

**Why it exists:** Interpolating user input into raw SQL is the canonical SQL injection vector.
`DB::raw()` with a string literal is allowed only where a literal contains no user data — but any raw
clause that receives user-controlled values must use parameter bindings.

**How to apply:** Prefer the query builder / Eloquent. When a raw clause is unavoidable, pass
bindings (e.g., `->whereRaw('... IN (?)', [$ids])`).

**Failure mode if ignored:** A search field concatenated into `->whereRaw(...)` becomes exploitable;
CVE-level data exposure.

### C4 — No inline cache keys

**Intent:** Every cache key is declared centrally in `config/cache-keys.php`.

**Why it exists:** Inline string keys cannot be enumerated, collide across modules, and silently
diverge on rename. Central registration makes invalidation auditable and keys greppable.

**How to apply:** `Cache::remember(config('cache-keys.module.key'), ...)` — never
`Cache::remember('module-key-'. $id, ...)` with a literal not sourced from config.

**Failure mode if ignored:** Two modules use `'user_list'` with different shapes; one invalidation
wipes the other's cache; stale data served to users with no way to find the collision.

### C5 — Entities must stay pure

**Intent:** Entities answer business questions with zero I/O; they import nothing from the app layers.

**Why it exists:** An Entity importing Actions/Services/Livewire/Controllers drags framework concerns
into the domain, creates import cycles, and makes the entity's behavior depend on non-deterministic
side effects.

**How to apply:** `use` statements in `app/{Module}/Entities/` are restricted to domain types (Enums,
other Entities, DTOs, Carbon, ValueObjects).

**Failure mode if ignored:** An Entity that calls a Service inside `isEligible()` returns different
answers depending on service state, breaking the deterministic snapshot contract that `final
readonly` entities promise.

### C6 — DTOs carry data only

**Intent:** DTOs transfer scalars/enums/Carbon across the layer boundary; they import nothing.

**Why it exists:** A DTO importing Models/Entities/Actions couples the layers the DTO exists to
separate and lets business objects leak through the transfer channel.

**How to apply:** `use` statements in `app/{Module}/Data/` are restricted to scalars, enums, Carbon,
and nested DTOs.

**Failure mode if ignored:** A DTO carrying a `Model` property becomes a second (mutable, loaded) path
into the database, defeating the whole point of the transfer boundary.

### C7 — DTO for 3+ params, ActionResponse returned

**Intent:** Command/Process Action `execute()` accepts a DTO when it needs 3+ parameters and returns
`ActionResponse` for structured feedback.

**Why it exists:** Positional args beyond 2 are unnameable and reorder-dangerous; a DTO names the
inputs and makes the call site self-documenting. Returning `ActionResponse` standardizes success
flags, data, errors, and messages for the UI.

**How to apply:** Up to 2 typed scalars are acceptable; 3+ require a `BaseData` DTO.

**Failure mode if ignored:** `execute($a, $b, $c, $d)` call sites hard-code ordering; a caller swaps
two params and the mutation writes wrong data with no compile error.

### C8 — Business rules throw `RejectedException`

**Intent:** Business-rule violations surface as domain rejections, not generic errors.

**Why it exists:** `RuntimeException` for a business rule produces a 500 in the UI; `RejectedException`
is caught by the Livewire layer and rendered as a flash message, because rejection is an expected
outcome, not a failure.

**How to apply:** In Actions and Entities, `throw new RejectedException('...')` for any rule that
blocks the requested operation.

**Failure mode if ignored:** A student submits a logbook entry after the deadline and instead of a
friendly "entry period closed" message, the UI shows a 500 error page.

---

## D-Invariants (Coding)

These protect code quality and consistency. They are simpler than the C-invariants but equally
non-negotiable.

| ID | Rule | Detection |
|----|------|-----------|
| **D1** | `declare(strict_types=1)` in ALL PHP (except migrations, configs) | PHP files without `declare(strict_types=1)` |
| **D2** | No `dd/dump/ray/var_dump/print_r/die` in committed code | Any PHP/Blade: `dd(`, `dump(`, `ray(`, `var_dump(`, `print_r(`, `die(`, `exit(` |
| **D3** | All user-facing strings use `__()` | Blade/Livewire: hardcoded strings in UI, missing `__()` wrapper |
| **D4** | Models use `#[Fillable]` attribute (NOT `$fillable`/`$guarded`) | Model files: `$fillable =`, `$guarded =`, missing `#[Fillable]` |
| **D5** | Never pass raw request input to `create()`/`update()` | `->create($this->validate(...))` without `->only()`/`->toArray()` |
| **D6** | Foreign keys use `foreignUuid()->constrained()->onDelete()->onUpdate()` | Migrations: missing `onDelete`/`onUpdate` on foreign keys |

### D1 — Strict types

**Intent:** All PHP files declare strict types (except migrations and config).

**Why it exists:** Without `declare(strict_types=1)`, PHP coerces scalar arguments silently; strict
types turn type mismatch bugs into immediate errors at the call site.

**Failure mode if ignored:** A `User::find(id)` receives `"42"` as a string and Eloquent silently
casts; later a `(string)$id` comparison yields a subtle identity bug.

### D2 — No debug calls

**Intent:** Committed code contains no `dd`, `dump`, `ray`, `var_dump`, `print_r`, `die`, `exit`.

**Why it exists:** Debug calls leak internal state to output, break HTTP responses, and block
production traffic. They are the single most common accidental corruption of a working endpoint.

**Failure mode if ignored:** A `dd($data)` left in a login action abort mid-request, and the 
compliant endpoint digests the dumped state instead of a JSON response.

### D3 — Localization

**Intent:** Every user-facing string goes through `__()` (both `lang/en/` and `lang/id/`).

**Why it exists:** PKL management is used by Indonesian and English-speaking users; hardcoded strings
cannot be translated and force a code change for any copy fix.

**Failure mode if ignored:** A component hardcodes "Kembali" in Blade; international users see
Indonesian text and the maintainer must redeploy just to fix a label.

### D4 — `#[Fillable]` attribute

**Intent:** Models declare mass-assignment allow-lists via the PHP 8.4 `#[Fillable([...])]` attribute,
never the legacy `$fillable`/`$guarded` properties.

**Why it exists:** The attribute is declarative, auditable, and replaceable; `$fillable`/`$guarded`
are side-effect-prone legacy properties the scanner flags as drift.

**Failure mode if ignored:** A new model copied from a stock Laravel tutorial uses `$fillable = [...]`
and silently leaks a `role` column into mass assignment.

### D5 — No raw request into create/update

**Intent:** create/update receives validated, allow-listed input — never raw request arrays.

**Why it exists:** `$request->all()` (or a raw `$this->validate(...)` array) may contain keys the
developer did not intend, opening mass-assignment holes even with `#[Fillable]`.

**How to apply:** Pass `->only([...])` / `->toArray()` slices, or construct a DTO from validated
fields.

**Failure mode if ignored:** A form includes an unexpected `is_admin` field; `create($request->all())`
sets it and grants privileges the user never earned.

### D6 — FK constraints

**Intent:** Every foreign key migration specifies `onDelete()` and `onUpdate()` constraints.

**Why it exists:** Without them, deletes/re-keys either cascade destructively (deleting a company
wipes its placement records) or laravel's default leaves orphaned rows. Explicit behavior is
deterministic and reviewed at migration time.

**Failure mode if ignored:** A company delete cascades into enrollments unexpectedly, silently
destroying student placement history.

---

## Verification

```bash
python3 scripts/scan_violations.py            # C1-C8, D1-D6 (plus security & performance)
python3 scripts/scan_conventions.py           # strict_types (D1), Fillable (D4), debug calls (D2), hardcoded strings (D3)
python3 scripts/scan_violations.py --module {Name}   # scope to a single module
```

**Interpretation guidance:** a C-invariant finding is always **HIGH** or **CRITICAL** severity —
never downgrade one to LOW because the fix looks small. D-invariants are **HIGH** when they are
active security/mass-assignment risks (D2, D3, D5) and otherwise HIGH by the convention contract.
See `output-and-integration.md` for severity mapping and report structure.