# Refactoring Safety — Prevent Destructive Changes

Refactoring changes structure without changing behavior. The rules below exist to guarantee that
structural edits never silently alter observable behavior. A refactor that changes behavior is not a
refactor — it is a bug shipping under a refactor label.

---

## Safety Gates — Run Before Any Refactoring

These gates establish a trusted baseline. Skipping any of them means you cannot detect damage
because you cannot prove what "before" looked like.

### Test suite passes (`php artisan test --compact`)

**Intent:** Confirm the current suite is green before you touch anything. A green baseline lets you
attribute later failures to your refactor rather than to a pre-existing fault.

**Why it matters:** Without a green baseline, a red suite after your change is un-diagnosable — you
cannot tell whether you introduced the breakage or inherited it.

**How to apply:** Run the full suite (or the affected module suite at minimum) at the start of the
session. Record which suites were green. If the suite is already red, file/flag the pre-existing
failure (AGENTS.md Pre-existing Defects) and do **not** treat your refactor's red results as clean.

**Pitfall to avoid:** Starting a refactor with a red suite and "hoping" the target area still works.

### `git status` clean (start from clean slate)

**Intent:** Ensure the working tree has only your intended changes, and that `git diff` remains a
reliable signal of what you altered.

**Why it matters:** Uncommitted unrelated changes contaminate every `git diff` you will review during
the refactor, making unintended edits indistinguishable from intended ones (Edit Policy).

**How to apply:** Check `git status`; commit or stash unrelated work before starting. During
refactoring, use `git diff` after every step to prove only the target lines changed.

**Pitfall to avoid:** Refactoring while carrying a half-finished feature in the same working tree.

### No `dd/dump/ray` in codebase

**Intent:** Ensure no debug calls were left behind that could fire during refactored paths.

**Why it matters:** A stray `dd()` in a refactored execution path halts requests at runtime; D2
forbids debug calls in committed code. Refactoring commonly moves code around — a debug call that
was harmless inline becomes a production breaker when relocated.

**How to apply:** Grep the touched files (`rg "dd\\(|dump\\(|ray\\(" {files}`) before and after.
Never introduce debug calls to inspect refactored logic — use tests instead.

**Pitfall to avoid:** Using `dd()` inside a temporary extraction "just to check" and forgetting it.

### If no tests exist for target code, write characterization tests first

**Intent:** Lock in current behavior with tests before restructuring, so equivalence is verifiable.

**Why it matters:** Refactoring is only safe when you can compare before/after behavior. Without
characterization tests, you cannot prove the refactor preserved behavior — you only know it compiles.

**How to apply:** For any class you will restructure that lacks coverage, write tests that assert the
current observable behavior (inputs → outputs, exceptions, side effects), run them green, then
refactor, then re-run. These become permanent regression tests.

**Pitfall to avoid:** Refactoring untested legacy code and confirming only "it still compiles".

---

## During Refactoring

### One concern per commit — do not mix refactor with feature fix

**Intent:** Keep structural changes isolated from behavior changes in version control history.

**Why it matters:** A commit that mixes refactor + feature fix defeats code review ("was this
behavior change intended or introduced?") and makes `git bisect` point at the wrong change. Traceable
history is a refactoring safety net.

**How to apply:** Extract a feature fix into its own commit (or its own branch) before restructuring
the same file. If a behavior bug is discovered mid-refactor, fix it in a separate commit with its own
message.

**Pitfall to avoid:** "While I was in there" bundling — the classic refactoring scope creep.

### After each step: compile/test, no more than 5 minutes without verification

**Intent:** Maintain a short feedback loop so damage is detected immediately, not after ten more
moves.

**Why it matters:** Small frequent verifications localize breakage to the last step. Long silent gaps
accumulate multiple candidate causes, turning a 1-minute fix into a debugging session.

**How to apply:** After every extraction, rename, or move, run the targeted test (`--filter`) or the
affected module suite. Do not batch structural steps before verifying.

**Pitfall to avoid:** Refactoring an entire class, then testing once — the failure could be anywhere.

### Strangler pattern: new code alongside old, verify equivalence, remove old

**Intent:** Replace code incrementally rather than rewriting in place, keeping a working system
throughout.

**Why it matters:** A full rewrite-in-place leaves no fallback if the new structure misbehaves. The
strangler keeps old and new alive in parallel until equivalence is proven, then deletes the old.
This makes refactors reversible step-by-step.

**How to apply:** Introduce the new module/class/action next to the old one; route callers to the new
one; verify behavior matches (ideally via characterization tests); delete the old once unused. Keep
each phase small enough to review.

**Pitfall to avoid:** Deleting the old implementation before proving the new one is equivalent.

### Do NOT change public API signatures in Action/Entity without updating all callers

**Intent:** Preserve the contracts (method signatures, DTO shapes, return types) that other code and
the spec's §6 document.

**Why it matters:** Actions/Entities are orchestration and domain contracts. A signature change ripples
to every caller and to `spec-audit` contract checks; an un-anchored signature change can silently
break Livewire wiring or cross-module imports that are not easily discoverable by grep.

**How to apply:** Gather all callers first (`rg "{ClassName}|{method}" app/ tests/`). Prefer
extracting/new overloads over modifying signatures. If a signature must change, update **all**
callers and the governing spec's §6 contract in the same commit, then run the affected module suite.

**Pitfall to avoid:** Renaming `execute(DTO $data)` to `execute($dto)` and updating only the class
body, leaving callers syntactically valid but semantically altered.

---

## Verification — After Refactoring

### Test suite passes (structural changes did not alter behavior)

**Intent:** The behavior-equivalence proof. Green suite after refactoring is the primary acceptance
criterion.

**Why it matters:** Structure changed, behavior must not have. The suite is the only objective
measure of that equivalence. Verify the full affected module (or full suite for cross-module
refactors).

**How to apply:** Run `vendor/bin/pest --testsuite={Module}` for module-scoped refactors; run the
full suite once for cross-module refactors. Compare pass/fail counts to the safety-gate baseline.

### `vendor/bin/pint --dirty --format agent` — code style clean

**Intent:** Enforce style consistency on the refactored surface.

**Why it matters:** Refactored code must meet the same style bar as new code; stray style drift in a
large move is otherwise easy to miss. Pint also auto-fixes trailing commas and import ordering.

**How to apply:** Run on the dirty files (or `--format agent` to see machine-readable output); fix
any findings before commit.

### `vendor/bin/phpstan analyse --no-progress` — static analysis passes

**Intent:** Catch type-safety and contract regressions the compiler misses.

**Why it matters:** Refactoring often re-types moved parameters or reshapes DTOs; PHPStan (level 8 +
Larastan) detects these at the call sites. Only run the full analysis when the user requests it or on
refactor completion (it is slow); for quick checks use the targeted `--filter` test instead.

### No new `TODO`/`FIXME` without date

**Intent:** Prevent unanchored debt annotations from entering the codebase during a refactor.

**Why it matters:** An undated `TODO` is un-actionable — no one knows when it was created or whether
it is still relevant. Refactoring is precisely when such notes get left behind.

**How to apply:** Format annotations as `TODO(username, YYYY-MM-DD): message` / `FIXME(username,
YYYY-MM-DD): message` per `code-writing` §10 Technical Debt Annotations, and keep them in the moved
code if the debt survives the refactor.

### `declare(strict_types=1)` present in new files

**Intent:** D1 invariant — every new PHP file (except migrations/config) opens with strict types.

**Why it matters:** Extracted files inherit callers; without strict types, coercive type juggling can
silently change behavior at the new boundary.

**How to apply:** Place `declare(strict_types=1);` immediately after `<?php` in every file created by
the refactor; verify with `python3 tools/scan_conventions.py`.

### Imports sorted (Pint handles this automatically)

**Intent:** Keep `use` statements ordered for readability and diff hygiene.

**Why it matters:** Moves and extractions change import lists; unsorted imports create noisy diffs
and violate the file-header order convention in `code-writing` §2.

**How to apply:** Let Pint sort imports; re-run after each structural step so the diff stays clean.

### Dead code cleaned up (unused imports, variables, methods)

**Intent:** A refactor leaves no orphans behind.

**Why it matters:** Extracted code often leaves unused imports, dead branches, or now-unused private
methods in the old location; these are compile-time noise and future confusion.

**How to apply:** After extraction, grep the old file for removed symbols and delete leftovers. Use
`python3 tools/scan_dead_code.py` to catch unregistered observers / orphan events / unused DTOs.

---

## Destructive Patterns to Avoid

These patterns are banned outright — each one has produced real breakage in this codebase's
refactors.

- **Changing the Action base class without updating all method signatures** — a Command→Process
  switch without updating `execute()` params/returns breaks the controller/Livewire wiring and the
  spec §6 contract. Migration to a new base class is a new action, not a silent refactor.
- **Moving Entity methods to Model** — violates Entity purity (C5). Entities own business questions;
  Models are persistence-only. A business rule relocated to the Model ends up on the wrong layer and
  `arch-guard`/`scan_class_contracts.py` flags it.
- **Removing event dispatch without checking for listeners** — an orphaned `dispatchEvent()` may
  leave documented side effects (notifications, projections) permanently silent. Grep for the
  listener class before removing any dispatch.
- **Refactoring and feature fix in the same commit** — see "One concern per commit"; this is the most
  common cause of unreviewable history.
- **Altering behavior that is already tested** — if a test asserts the old behavior and you change
  it "because the refactor needs it", you are changing behavior, not structure. Fix the root cause in
  a separate concern, or write a new spec requirement first (Spec-First Doctrine).
