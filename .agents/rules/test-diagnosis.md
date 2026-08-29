# Test Diagnosis — Read the Failure Before Re-Running

## Intent

When a test fails, **read the failure before re-running**: parse the output, locate the exact line,
determine whether the fault is the test, the source, or the environment, then fix with the smallest
possible run. Never re-run blindly, and never fix a failing test that was already broken before your
changes.

## Rationale

Test failures are information, not noise. Output contains the test class, the truncated description,
the expected vs actual values, and a file:line — enough to diagnose most failures without a single
re-run. Re-running blindly wastes minutes per trigger and, worse, turns the output into a scroll-past
ritual that hides the actual defect.

Three failure classes must be separated because each has a different remedy:

- **Test defect** — the assertion or setup is wrong (outdated expectation, wrong factory). Fix the
  test.
- **Source defect** — the code is wrong relative to its spec. Fix the code.
- **Environment/pre-existing** — fails on `main` too, or only on CI (DB driver, extension), or only
  in the full suite. It is NOT your change; flag it, don't fix it silently.

## How to Apply

### Diagnosis Protocol

Follow this order to minimize wasted runs:

1. **Read the failure message** — don't re-run blindly:
   - `Failed asserting that...` → assertion mismatch.
   - `Class "X" not found` → autoload/namespace issue.
   - `Call to undefined method` → wrong import or missing method.
   - `SQLSTATE[HY000]` → database/migration issue.

2. **Read the exact line** — open the test file and understand the assertion.

3. **Determine root cause** — is it the test, the source code, or the environment?
   - Test failing on `main` too? → pre-existing issue, not your change.
   - Test passes in isolation but fails in suite? → shared state / ordering issue.
   - Test fails only on CI? → environment-specific (DB driver, extension).

### Reading Failure Output

```
FAILED  Tests\Core\Services\LangCheckerTest > `LangChecker with real…
Expected: :step | Setup
To contain: Internara
at tests/Core/Services/LangCheckerTest.php:86
```

1. **Test class** → `Tests\Core\Services\LangCheckerTest` (namespace reflects `tests/Core/`).
2. **Test description** → `LangChecker with real…` (truncated, check full with `--verbose`).
3. **Expected vs actual** → `Expected: :step | Setup` / `To contain: Internara`.
4. **File:line** → `LangCheckerTest.php:86`.

### Efficient Fix Workflow

```bash
# 1. Run only the failing test (5-60s)
php artisan test --compact --filter={FailingTest}

# 2. After fix, confirm the single test passes
php artisan test --compact --filter={FailingTest}

# 3. Run siblings (same file) to verify no collateral damage
php artisan test --compact --filter={ClassName}

# 4. Only if cross-module impact suspected → run affected module suites or run the full suite
```

### Run-Fix-Repeat Protocol

```bash
# Diagnose: run the exact failing test
php artisan test --compact --filter="LangCheckerTest"

# Fix the issue (edit source or test)

# Verify: run again
php artisan test --compact --filter="LangCheckerTest"

# Verify siblings if needed
php artisan test --compact --filter="LangChecker"
```

### Common Test Failures & Fixes

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| `Class "X" not found` | Wrong namespace in `use` or autoload stale | Run `composer dump-autoload` |
| `Failed asserting that...` | Logic mismatch | Check source logic vs test expectation |
| `Call to undefined method` | Wrong mock or missing import | Check `use` statements |
| `SQLSTATE[HY000]: General error` | Migration missing / DB not fresh | `php artisan migrate:fresh --seed` |
| `The :attribute field is required` | Missing test data | Check factory / DTO defaults |
| Session store not set | Livewire test missing request setup | Use `actingAs()` with Livewire |
| `header may not contain...` | Response content type mismatch | Add `->assertJson()` or explicit header |
| Test times out | Infinite loop / queue not processed | Add `Queue::fake()` or `Bus::fake()` |

### Is It Pre-existing?

```bash
# Stash changes, switch to main, run test
git stash && git checkout main
php artisan test --compact --filter="FailingTest"

# If it fails on main too → pre-existing issue, NOT your change
# Restore your branch
git checkout - && git stash pop
```

### Pre-existing Failure Handling

If a test was already failing before your changes:
- Flag it to the user.
- Do NOT attempt to fix it unless the user asks.
- Document it as a known pre-existing issue.
- Verify your changes didn't introduce NEW failures.

### Orphan Test Handling

If a test (failing or not) maps to no current spec requirement:
- Do NOT fix it to make it pass.
- Flag it as orphan noise — candidate for deletion.
- Remove it only when the user approves the trim (per-module spec-driven pruning).

## Anti-Patterns & Pitfalls

- **Re-run, not read:** executing the failing test before reading the output — the output answers
  most questions for free.
- **Diagnosing in the wrong layer:** a `Class not found` is autoload, not logic; a timeout is a queue
  drain, not an assertion. Fixing in the wrong layer wastes the whole session.
- **"Fixing" a pre-existing failure** to make the suite green — you may be masking a real defect, and
  you've introduced an unrequested behavior change. Flag it and move on (Pre-existing Failure
  Handling).
- **Full-suite re-run loop:** a mid-suite failure triggers the full suite again — run only the failing
  test class (see `rules/verification-strategy.md`).
- **Ignoring sibling damage:** fixing the failing test but not re-running its siblings — collateral
  assertion damage goes undetected.

## Verification / Detection

- The smallest confirming run: failing test → siblings → (if suspected) module suite →
  (only if core infrastructure) full suite.
- `git diff` — confirm the source change is the intended one and the test change is consistent.
- Pre-existing check via `git stash`/`git checkout main` when the failure predates your work.
