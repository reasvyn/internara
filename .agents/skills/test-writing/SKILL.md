---
name: test-writing
description: "SDLC Phase: TESTING. Comprehensive code verification, efficient test execution, and failing test diagnosis — prioritizes lightweight verification over full test suite and spec-traceable, noise-free tests."
upstream:
  - feature-building
  - code-refactoring
  - livewire-development
  - medialibrary-development
downstream:
  - sync-docs
  - pest-testing
---

# Verify & Testing

> **Prerequisite:** Load `context-awareness` for project conventions and critical invariants.

## When to Activate

Use this skill when:
- Verifying code changes before committing (any layer)
- Deciding what verification strategy to use for a given change
- Writing new tests or fixing failing tests
- Auditing tests against their specs (spec gaps, orphan tests)
- Determining whether running the full test suite is necessary

## Core Principles

**1. Verify first, test second.** The full test suite consumes ~2GB+ RAM and takes 10+ minutes.
**Always ask:** can I verify this change without running tests?

**2. Tests verify the spec.** A test exists only because a requirement in the governing spec
`docs/specs/{ID}-{feature}.md` (`FR-*`, `NFR-*`, `UC-*`, or a §6 data contract) demands it (Spec-First
Doctrine: no behavior without a requirement). Tests with no spec mapping are noise — audit them
out, never write them.

**3. Size first.** Classify the change per AGENTS.md Size Triage. If the verification/test work spans
multiple modules or is **L** size, split it into sessions — inform the user and propose a plan
before running anything.

**4. Minimalism — only the tests the spec requires.** Spec-driven testing is about writing the
minimum tests that prove the spec, then stopping. It accelerates development and verification
(spec-scoped tests run in seconds vs. 10+ minutes for the full suite), reduces resource usage
(~2GB+ RAM for the full suite), and reduces cognitive overwhelm — a suite mapping 1:1 to requirement
IDs is self-explaining. When tempted to add "one more test for safety", ask: **which requirement
does it verify?** No requirement → don't write it.

---

## 1. Verification Strategy by Change Type

| Change Type | What to Run | Why Not Full Suite |
|---|---|---|
| **Translation keys** (`lang/*.php`) | `vendor/bin/pint --dirty --test` each file, then `php artisan tinker --execute="echo __('key');"` for both `en` and `id` | No logic change; full suite won't catch missing keys |
| **Config files** (`config/*.php`) | `php artisan config:cache` (dry run) or visual inspection | Config is loaded at boot; full suite irrelevant |
| **Docs / Markdown** | Visual inspection only | Zero runtime impact |
| **Blade templates** | `npm run build` (if using Vite) | Frontend only; no PHP test needed |
| **CSS / JS / NPM** | `npm run build`, check for errors | Purely frontend |
| **Helper / utility function** | Quick tinker test: `php artisan tinker --execute="dump(myFunction('test'));"` | Validate contract before writing test |
| **Single method refactor** | Targeted: `php artisan test --compact --filter={ClassName}` | Isolated change, test only the affected class |
| **Cross-module refactor** | `vendor/bin/pest --testsuite={ModuleName}` (run affected module suites) | Integration risk — module integration tests cover real DB |
| **New Action / Model / Service** | Full suite ONCE after all changes batched | Highest risk — verify nothing broke elsewhere |
| **Composer dependency bump** | Run affected module suites: `vendor/bin/pest --testsuite={ModuleName}` | Lock-only changes rarely break unit tests |
| **NPM dependency bump** | `npm run build` | Frontend only |

### Lightweight Verification Toolkit

```bash
# PHP syntax + style check (0.2s)
vendor/bin/pint --dirty --test --format agent

# Frontend/Blade formatting check (0.5s)
npx prettier --check path/to/file.blade.php

# Translation resolve check (0.5s)
php artisan tinker --execute="echo __('my.key', [], 'en'); echo PHP_EOL; echo __('my.key', [], 'id');"

# Quick class autoload check (0.3s)
php artisan tinker --execute="new MyClass();"

# Quick boot/health check (2s)
php artisan system:health

# Config cache test (1s)
php artisan config:cache

# Vite build check (30s)
npm run build
```

---

## 2. Efficient Test Execution

### Spec-Test Audit Scope

When auditing tests against specs, verify these items:

- Every requirement in the spec (`FR-*` / `NFR-*` / `UC-*` / §6 contracts) has a matching test —
  **spec gaps** to fill
- Every test maps to a requirement ID — **orphan tests** to remove
- Test descriptions carry the requirement ID (e.g., `it('FR-A1: ...')`)
- No padding: no tests for behavior the spec doesn't define
- Feature tests use `LazilyRefreshDatabase` (not `RefreshDatabase`)
- `assertModelExists()` preferred over `assertDatabaseHas()`
- No Eloquent mocking — use factories + real database
- `Event::fake()` positioned AFTER factory setup

### Targeted Test Commands

```bash
# Single test class (fastest)
php artisan test --compact --filter={ClassName}

# Multiple classes batched (use &&)
php artisan test --compact --filter="ActionResponse|BaseFormRequest|LangChecker" \
  && php artisan test --compact --filter="CertificateStatus"

# Run tests for a specific module
vendor/bin/pest --testsuite={ModuleName}   # Run tests for the specified module (replace {ModuleName})
# Run full suite (all modules)
php artisan test --compact                        # All tests
```

### Batch Execution Rule

**NEVER run tests after every individual change.** Follow this order:

1. Make ALL planned changes to ALL files
2. Run `vendor/bin/pint --dirty --test` on every changed PHP file (quick syntax + style check)
3. Verify logic with tinker or artisan commands if possible
4. Run targeted test(s) — only the affected test class(es)
5. Only if changes affect core infrastructure → run full suite

### Test Memory Considerations

- Full suite: ~2GB RAM, 10-15 minutes
- Feature suite: ~1.5GB RAM, 8-12 minutes
- Unit suite: ~500MB RAM, 2-4 minutes
- Single test class: ~200MB RAM, 5-60 seconds

Choose the smallest scope that gives confidence.

---

## 3. Writing Tests Efficiently

### Follow Existing Patterns (Don't Reinvent)

Before writing a test, always read an existing test file of the same type:
- For a **Command Action** test → read another Command Action test
- For a **Livewire component** test → read another Livewire test
- For an **Entity** test → read another Entity test

Copy the structure, imports, and patterns. This avoids:
- Wrong base class usage
- Missing `LazilyRefreshDatabase` or `use RefreshDatabase` decisions
- Inconsistent assertion style

### Spec-Scenario Checklist

Cover exactly the scenarios the spec defines — nothing more:

- [ ] **Happy path** — the primary success scenario each FR/UC mandates
- [ ] **Named rejections** — each `RejectedException` / validation rule the spec explicitly lists
- [ ] **Named alternatives** — alternative flows the spec's use cases describe

Skip unless a requirement explicitly demands it:
- Edge-case matrices (null/empty/boundary matrices not named in the spec)
- HTTP status code assertions (for API-only changes)
- UI rendering details (CSS classes, DOM structure)
- Mock-orchestration of child Actions (unless the spec requires rollback semantics)

### Noise Control

Before writing any test, ask: **"which spec requirement does this verify?"** If the answer is
"none", the test is noise — don't write it, and flag the existing one for removal. If the answer
is "a requirement has no test", that is a **spec gap** — write the test now.

### Test Helper Pattern

When test setup is repetitive (e.g., creating the same model hierarchy), extract into a helper:

```php
// In the test file or a shared helper
function createEnrolledStudent(): User
{
    $school = School::factory()->create();
    $department = Department::factory()->for($school)->create();
    $student = User::factory()->student()->create();
    $student->departments()->attach($department);

    return $student;
}
```

---

## 4. Fixing Failing Tests

### Diagnosis Protocol

When a test fails, follow this order to minimize wasted runs:

1. **Read the failure message** — don't re-run blindly
   - `Failed asserting that...` → assertion mismatch
   - `Class "X" not found` → autoload/namespace issue
   - `Call to undefined method` → wrong import or missing method
   - `SQLSTATE[HY000]` → database/migration issue

2. **Read the exact line** — open the test file and understand the assertion

3. **Determine root cause** — is it the test, the source code, or the environment?
   - Test failing on `main` too? → pre-existing issue, not your change
   - Test passes in isolation but fails in suite? → shared state / ordering issue
   - Test fails only on CI? → environment-specific (DB driver, extension)

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

### Pre-existing Failure Handling

If a test was already failing before your changes:
- Flag it to the user
- Do NOT attempt to fix it unless the user asks
- Document it as a known pre-existing issue
- Verify your changes didn't introduce NEW failures

### Orphan Test Handling

If a test (failing or not) maps to no current spec requirement:
- Do NOT fix it to make it pass
- Flag it as orphan noise — candidate for deletion
- Remove it only when the user approves the trim (per-module spec-driven pruning)

---

## 5. Full Suite Fire Drill

Only run the full suite when:

1. **Before pushing a branch** that modifies `app/` logic
2. **After upgrading** any Composer or NPM dependency
3. **After refactoring** core infrastructure (Base classes, Traits, Contracts)

```bash
# Full quality gate (15-20 min)
php artisan test --compact \
  && vendor/bin/pint --dirty --format agent \
  && vendor/bin/phpstan analyse --no-progress
```

Before that gate, verify with git — `git status` + `git diff` — that only intended files changed and
nothing was dropped (Edit Policy). Run the arch-guard scripts (`scan_violations.py`,
`scan_class_contracts.py`, `scan_security.py`, `scan_naming.py`, `scan_conventions.py`,
`scan_doc_links.py`) before committing.

During full suite run:
- Do NOT interrupt — let it finish
- If it fails, note the failing test(s) and diagnose after completion
- Do NOT fix and re-run the full suite — just run the failing test class

---

## Phase Context

| Role | Skill |
|------|-------|
| **Upstream** | `feature-building` (code to verify), `code-refactoring` (refactored code) |
| **This skill** | **VERIFY & TEST** — verification strategy, efficient test execution, fix diagnosis |
| **Downstream** | `pest-testing` (detailed test writing), `sync-docs` (doc updates after verification) |

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_tests.py` | Run test suite, parse per-module results | `python3 scripts/scan_tests.py` |

Use `--module {Name}` to scope. Output: `scripts/outputs/{timestamp}-tests.json`.

## Quality Gate — arch-guard

Test files must also pass arch-guard checks:
- Test files must have `declare(strict_types=1)` (D1)
- No debug calls in tests (D2) — use `->dd()` or `->dump()` Pest methods instead
- Test naming follows `it_{behavior}()` convention
- See `arch-guard` skill for full rule reference

## References

| Topic | Location |
|-------|----------|
| Feature specs (source of truth for tests) | `docs/specs/index.md`, `docs/specs/{ID}-{feature}.md` |
| Testing patterns | `docs/architecture/testing-pattern.md` |
| Pest testing skill | `.agents/skills/pest-testing/SKILL.md` |
| arch-guard skill | `.agents/skills/arch-guard/SKILL.md` |
| Pre-commit checklist | `AGENTS.md` (end of file) |
| Critical invariants | `AGENTS.md` (§Critical Invariants) |
