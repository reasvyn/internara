# Refactoring — Verification Checklist

This checklist is the final gate before declaring a refactor complete. Each check exists because a
refactor can look structurally clean while still breaking behavior, violating an invariant, or
leaving dead code. Run every check; a single unchecked box means the refactor is not done.

---

## Behavior preserved

**Check:** Tests pass before and after refactoring.

**Why:** Behavior equivalence is the entire point of a refactor. The test suite is the only objective
measure; `git diff` proves structure changed but not that behavior survived. A green before/after
comparison is the acceptance criterion. Compare against the full affected module suite, or the full
suite for cross-module refactors.

**Pitfall:** Treating "it compiles" as "it works". Compilation proves types, not behavior.

---

## Action contract preserved

**Check:** Action uses the correct base class, has a single `execute()`, accepts a DTO for 3+
params (C7), and returns `ActionResponse` where the spec's §6 requires it.

**Why:** Actions are the mutation entry points (C1). If the refactor changed the base class
(Command/Read/Process), the transaction/log/event guarantees changed too — silently. Callers and the
spec's contract must both match the refactored signature.

**Pitfall:** Extracting logic into a helper that performs DB writes outside the Action's
`$this->transaction()` — the refactor appears clean but breaks the write-consistency contract.

---

## Entity purity preserved

**Check:** Entity is `final readonly`, zero I/O, has `fromModel()`, and the Model exposes the bridge
(as{Role}Entity()).

**Why:** Entities own business questions only; any I/O (DB, HTTP, files) violates C5 and breaks the
testability guarantees `scan_class_contracts.py` enforces. A refactor that moves query logic into an
Entity is an architecture violation, not an equivalent structure.

**Pitfall:** Adding a `static` factory that calls `Model::find()` inside the Entity to "simplify the
caller".

---

## Model boundary preserved

**Check:** Model has no business methods, uses `#[Fillable]` (D4), and only offers entity bridges.

**Why:** Models are persistence-only. Business methods on Models duplicate Entity logic and confuse
the layer; D4 bans the legacy `$fillable` array form. A refactor must not push `canX()/isX()` logic
onto the Model.

**Pitfall:** Moving a business rule "closer to the data" during a refactor and silently violating
C5/D4.

---

## DTO boundary preserved

**Check:** DTO is `final readonly`, holds only scalars/enums/Carbon (C6), and extends `BaseData`.

**Why:** DTOs are the UI↔Business contract. If the refactor widened a DTO to carry Models/Entities,
it now crosses the BaseData boundary and violates C6 — decoupling erodes exactly where the refactor
was supposed to improve it.

**Pitfall:** Adding an extra `Model` property to a DTO "temporarily" during extraction.

---

## No debug calls introduced

**Check:** No `dd/dump/ray/var_dump/print_r/die` in the touched files (D2).

**Why:** Refactors relocate code; relocating a debug call turns harmless debugging into a runtime
halt on a production path. D2 is absolute.

**Pitfall:** Leaving a `dump()` used during before/after comparison.

---

## strict types present

**Check:** `declare(strict_types=1)` in every new file created by the refactor (D1, except
migrations/config).

**Why:** New files become new boundaries; without strict types, coercion can alter behavior at the
junction. The refactor should not degrade the codebase's strictness.

---

## Tooling gates green

**Check:** Pint clean (`vendor/bin/pint --dirty --format agent`); PHPStan passes for the touched
surface (full `vendor/bin/phpstan analyse --no-progress` on demand/refactor completion).

**Why:** Style and static analysis catch the drift that reviewing-by-eye misses: unsorted imports,
untyped arrays flowing through new signatures, unused symbols left behind.

---

**Ordering:** Run the behavior gate first (tests), then the contract gates (Action/Entity/Model/DTO),
then hygiene (debug, strict types, tooling). A red behavior gate invalidates the rest — fix behavior
before re-running the others.
