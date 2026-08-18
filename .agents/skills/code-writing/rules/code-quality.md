# Code Quality — File, Class, Method & Style Checklist

> **Last updated:** 2026-08-17 **Changes:** rewritten comprehensively — per-check intent, application, and avoidance

This checklist is the pre-commit hygiene gate for PHP code. It overlaps `invariants.md`,
`class-contracts.md`, and `naming-conventions.md` but cross-cuts them by file position (file →
class → method) — run it top-to-bottom on every touched file.

---

## File-Level Checks

### `declare(strict_types=1)` present (except migrations/config)

**Intent:** Strict typing at the file boundary (D1).

**How to apply:** It is the first statement after `<?php`. Migrations and `config/` files are the
only exemptions.

**Pitfall:** A class that silently omitted it accepts `"5"` where `int` is declared.

### Namespace matches directory location

**Intent:** `App\{Module}\{SubModule}\{Type}` mirrors the on-disk path so autoloading, PHPStan, and
review agree.

**How to apply:** `namespace App\Assessment\Rubric\Actions;` in
`app/Assessment/Rubric/Actions/Foo.php`.

**Pitfall:** Copying a class from another module and keeping the old namespace.

### Use statements sorted alphabetically

**Intent:** Stable, diff-friendly imports.

**How to apply:** Let Pint sort them (`vendor/bin/pint`); review the order if Pint is not run.

### No unused imports

**Intent:** Clean namespace; unused `use` clauses are dead weight and hide missing references.

**Pitfall:** Imports left behind after an extraction — common after refactors.

### No debug calls (`dd`, `dump`, `ray`, `var_dump`, `print_r`, `die`)

**Intent:** D2 — debug output never ships.

**Pitfall:** `if (config('app.debug')) { dump(...); }` is still a debug call.

---

## Class-Level Checks

### Extends correct base class

**Intent:** The class inherits the architecture guarantees (Command/Read/Process) for its role.

**How to apply:** Mutations → `BaseCommandAction`; complex reads → `BaseReadAction`; orchestration →
`BaseProcessAction`; Models → `BaseModel`/`BaseAuthenticatable`.

**Pitfall:** `extends BaseReadAction` for a class that mutates — the base class provides no
transaction/log, so the mutation is unguarded.

### Constructor uses `protected readonly` promotion for injected dependencies

**Intent:** Injected dependencies are immutable, visible in the signature, and (for Actions) matched
by the DI container.

**How to apply:** `public function __construct(protected readonly SomeService $service) {}`.

**Pitfall:** Mutable public properties or assignment-style constructor bodies that reset defaults.

### No empty zero-parameter constructors (unless private factory method)

**Intent:** A constructor that takes nothing and does nothing is noise; if a class must hide its
constructor (e.g., factories), make it `private`.

### Single `execute()` method on Actions

**Intent:** Actions expose exactly one public entry point; everything else is private helpers.

**Pitfall:** A second public method (`validate()`, `helper()`) that callers discover and depend on —
it becomes a second de-facto entry point.

---

## Method-Level Checks

### Explicit return types on every method

**Intent:** Contract-checkable call graphs (POSIX: PHPStan relies on declared returns).

**Pitfall:** Omitting a return type lets arrays/`mixed` flow silently through the call chain.

### Type hints on all parameters

**Intent:** No implicit `mixed` inputs; the DTO/target type is documented by the signature.

### Curly braces on all control structures (even single-line)

**Intent:** Prevents dangling-`else` and follow-on-statement bugs in single-line bodies.

**Pitfall:** `if ($x) $a();` grows a line later and silently widens the branch.

### `match()` over `switch()` when returning from expression

**Intent:** `match()` returns values, is exhaustive, and has strict comparison — `switch()` does
none of these.

### Null-safe operator `?->` and null-coalescing `??` over explicit null checks

**Intent:** Readable null handling without nested ternary chains.

### Trailing commas on multiline arrays, function calls, constructor params

**Intent:** Diff-friendly structures — adding one argument touches one line, not two.

### `str_contains()` / `str_starts_with()` / `str_ends_with()` over `strpos() === 0`

**Intent:** Name the intent; `strpos() === 0` errors on the `false`-vs-`0` distinction.

---

## Security Checks

- No `{!! $var !!}` for unsanitized user content (see `security.md`, XSS).
- No `DB::raw()` without parameterized binding (C3).
- No `$request->all()` — use `->only()` or `->toArray()` (D5).
- `#[Fillable]` attribute on all Models (D4).
- `@csrf` or Livewire on all forms (CSRF).

Each is expanded (intent, application, detection) in `rules/security.md`.

---

## Performance Checks

- No N+1 — `->with()` for eager loading (see `performance.md`).
- `exists()` over `count() > 0`.
- `pluck()` over `get()->pluck()`.
- `chunk()` / `lazy()` for 1000+ row queries.
- Cache keys registered in `config/cache-keys.php` (C4).

Each is expanded (intent, application, detection) in `rules/performance.md`.

---

## Architecture Checks

- No `Model::create/update/delete` in Livewire (C1).
- No `app()->make()` / `resolve()` — constructor injection (C2).
- Business rules via Entity methods, not inline in Actions (C5).
- `RejectedException` for business rules, not `RuntimeException` (C8).
- Events via `$this->dispatchEvent()`, not `$event::dispatch()` (event pattern).
- DTO for 3+ params, ActionResponse for structured returns (C7).

Each is expanded (intent, application, detection) in `rules/invariants.md`.

---

## Destructive Patterns

These are explicit `[red X]` failures — reject the code on sight:

- `dd()` / `dump()` / `ray()` left in committed code (D2)
- `$fillable` / `$guarded` property instead of `#[Fillable]` attribute (D4)
- `Model::create()` called directly from a Livewire component (C1)
- `app()->make()` or `resolve()` for dependency resolution (C2)
- `RuntimeException` thrown for business rule violations (C8)
- Hardcoded English strings not using `__()` (D3)
- Missing `declare(strict_types=1)` (D1)
- Inline cache key strings not in `config/cache-keys.php` (C4)