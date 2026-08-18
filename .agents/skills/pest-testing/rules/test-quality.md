# Test Quality — Structure, Database & Noise Control

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

This rule governs the *shape* of a healthy test suite: where tests live, how they touch the
database, what a test is allowed to assert, and which patterns are destructive. It is the quality
gate that keeps the suite spec-traceable, isolated, and fast. It does **not** repeat the spec-driven
doctrine (see `rules/spec-driven-minimalism.md`), the naming rules (see
`rules/test-naming-conventions.md`), the per-layer patterns (see `rules/layer-test-patterns.md`), or
the mocking boundaries (see `rules/mocking-boundaries.md`) — it assumes those and enforces the
structural hygiene around them.

## Rationale

A test suite is a maintenance liability unless it is structurally boring. When a suite ignores these
conventions, the concrete failures are:

- **Wrong location** — tests scattered outside `tests/{Module}/` break the per-module suite runs
  (`vendor/bin/pest --testsuite={ModuleName}`) and the spec-audit mapping, because nobody can tell
  which module and requirement a test belongs to.
- **Unnecessary database use** — pure-logic tests (Entity, DTO, Enum) that spin up the database make
  the suite slower, heavier (~2GB+ RAM), and flakier than it needs to be; every such test is a
  spec-free cost.
- **Brittle assertions** — `assertDatabaseHas()` couples the test to raw column values and table
  shape; `assertModelExists()` verifies the persisted model directly, which stays correct when a
  column is renamed or cast changes.
- **Eloquent mocked** — mocking the ORM tests your mock, not your code; the real failure (a bad
  query, a wrong column) sails through green.
- **Shared state** — a test that depends on rows another test created passes alone and fails in the
  suite, and the order dependence is invisible until someone changes test order.

The default bias is therefore: **real database for anything that touches persistence, fake at the
I/O boundaries, nothing in between.**

## Test File Structure

Every test file follows:

```
tests/{Module}/{SubModule}/{Name}Test.php
```

- File name is `{Name}Test.php` — PascalCase with a `Test` suffix (e.g. `CreateEnrollmentActionTest.php`).
- The `tests/Unit/` and `tests/Feature/` split has been removed. Module-first location is the only
  convention; the module name in the path is what `pest --testsuite={ModuleName}` uses to discover
  and group the suite.
- Tests that need a database use `LazilyRefreshDatabase`; pure-logic tests (no DB) must **not** use
  any refresh trait.

### What "needs a database" means

| Test target | Needs DB? |
|-------------|-----------|
| Command / Read / Process Action | Yes — the Action persists or queries |
| Livewire component | Yes — render and mutations hit models |
| Policy | Yes for the model instance, then pure assertions |
| Entity | No — test business-rule methods directly on a `fromModel()`-built instance or a constructed instance |
| DTO | No — assert the §6 contract shape with plain scalars |
| Enum | No — `label()` / `validTransitions()` are pure |

## Database Conventions

1. **Use `LazilyRefreshDatabase`, never `RefreshDatabase`.** The lazy variant wraps each test in a
   transaction and only runs migrations once, which keeps spec-scoped suites in the seconds range.
   `RefreshDatabase` re-migrates between tests and is a needless slowdown.
2. **Prefer `assertModelExists($model)` over `assertDatabaseHas('table', [...])`.**
   `assertModelExists()` takes the model instance, so it stays robust against column renames and
   stays readable. Use `assertDatabaseHas()` only when a column genuinely has no model counterpart.
3. **Never mock Eloquent.** Arrange with factories, act through the real query builder, and assert
   against the real database. A mocked `Model::find()` cannot verify a scope, a constraint, or a
   relationship — it verifies nothing.
4. **Position `Event::fake()` after factory setup.** Factories fire model events (e.g. created
   listeners). Faking events *before* factory setup silences those real events and can leave
   listeners unregistered for the assertions that follow. Arrange → fake → act.
5. **Isolation.** Every test creates its own fixtures; no test reads rows produced by another test.
   If a test passes alone and fails in the suite, that is a shared-state or ordering defect — fix the
   test, do not reorder to hide it.

## Unit vs Feature Test Boundaries

- **Unit tests** (Entity/DTO/Enum): no database, no HTTP, no framework services. Construct the
  object directly or via `fromModel()`, call the method, assert the result. These are the fastest,
  most stable tests in the suite — keep them that way.
- **Feature tests** (Action/Livewire): `LazilyRefreshDatabase`, factories, `actingAs()` where an
  authenticated actor is part of the requirement. Assert the observable outcome
  (`assertModelExists`, `ActionResponse`, redirect/render).

## Noise Signals

A test that cannot be traced to a requirement is noise. The recurring shapes of noise, and why each
is noise:

| Noise shape | Why it is noise |
|-------------|-----------------|
| Tests of implementation internals the spec never describes | Coupled to private structure; breaks on any refactor; verifies nothing the spec promised |
| Exhaustive validation / edge-case matrices not named in the spec | The spec lists the rejections it cares about; extra cases pad the suite without adding requirement coverage |
| "Comprehensive multi-angle" padding (CSS classes, DOM structure, HTTP status codes) | Framework/UI detail, not requirement behavior |
| Framework behavior tests (UUID generation, pagination internals, config loading) | Testing Laravel, which is already tested upstream |
| Mock-orchestration of child Actions | Unless the spec demands rollback semantics, this verifies the orchestration wiring, not a requirement |
| Tests left behind after their requirement was removed | Orphaned — see `rules/spec-gap-orphan-detection.md` |

## Destructive Patterns

These patterns actively damage the suite and are **never acceptable**:

- **Test-to-test state dependence** — fixtures created in one test consumed by another. The suite
  becomes order-sensitive and flaky.
- **`dd()` / `dump()` / `ray()` / `var_dump()` in test files** — violates D2 (no debug calls); use
  Pest's `->dd()` / `->dump()` test methods instead, which keep the call inside the test harness.
- **`assertDatabaseHas()` when `assertModelExists()` is available** — brittle by default.
- **Silently skipped or conditionally-passing tests** — a test that does not actually exercise its
  assertion is worse than no test: it reports green for a requirement that is untested.

## Quality Gates

Test files are code and must pass the same gates as production code:

- `declare(strict_types=1)` present (D1).
- No debug calls (D2) — use `->dd()` / `->dump()` Pest methods if inspection is needed.
- Naming follows the `test("{SpecID}-{ReqID}: ...")` / `describe("{SpecID}: ...")` convention
  (`rules/test-naming-conventions.md`).
- Arch-guard scanners cover test files — see the `arch-guard` skill for the full rule reference.

## Verification & Detection

Run these to confirm the suite is structurally sound:

```bash
vendor/bin/pest --testsuite={ModuleName}      # Module suite stays green and fast
php artisan test --compact --filter={TestName} # Targeted test passes in isolation
```

Manual detection:

- Grep for `assertDatabaseHas(` — each hit should have a reason it is not `assertModelExists()`.
- Grep for `Mockery::mock(Model` / `shouldReceive` in test files — each is a boundary violation
  (see `rules/mocking-boundaries.md`).
- Grep for `LazilyRefreshDatabase`/`RefreshDatabase` in Entity/DTO/Enum unit tests — a pure-logic
  test should never carry a DB trait.
- Run a test alone, then with its module — any behavior change indicates shared state.

## Related Rules

| Rule | Covers |
|------|--------|
| `rules/spec-driven-minimalism.md` | Why only spec-named scenarios get tests; one test per requirement |
| `rules/test-naming-conventions.md` | `{SpecID}-{ReqID}:` prefixes and `describe()` grouping |
| `rules/layer-test-patterns.md` | Action / Livewire / Entity / DTO / Enum / Policy patterns |
| `rules/mocking-boundaries.md` | Where to fake, and why `shouldReceive()` is a smell |
| `rules/spec-gap-orphan-detection.md` | Coverage as spec compliance; finding gaps and orphans |
