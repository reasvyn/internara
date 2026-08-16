---
name: pest-testing
description: "SDLC Phase: TESTING. Spec-driven test writing, editing, and fixing using Pest — every test traces to a spec requirement (FR/NFR/UC ID); no orphan tests."
upstream:
  - test-writing
  - code-writing
  - feature-building
  - code-refactoring
  - livewire-development
  - medialibrary-development
downstream:
  - feature-building
  - sync-docs
---

# Pest Testing

> **Prerequisite:** Load `context-awareness` for testing conventions.

## Core Doctrine — Tests Verify the Spec

Tests exist **only** because a requirement in `docs/specs/{ID}-{feature}.md` demands it. Every test maps
to at least one requirement ID (`FR-*`, `NFR-*`, `UC-*`, or a §6 data contract). A test that cannot
be traced to a spec requirement is **noise** and must not be written. Coverage is measured in
**spec requirements covered**, never in lines of code.

**Write only the tests the spec requires, then stop** (see `docs/architecture/testing-pattern.md`
§1.0 for the rationale). Minimalism is deliberate:

- **Speed** — spec-scoped tests run in seconds vs. 10+ minutes for the full suite; faster feedback,
  faster cycles.
- **Resource use** — the full suite consumes ~2GB+ RAM; every padded test wastes RAM, disk, and CI
  time for zero verification value.
- **Cognitive load** — a suite mapping 1:1 to requirement IDs is self-explaining; no "why does this
  test exist?" archaeology. Less to write, less to read, less to maintain.

Minimal is not thin — every requirement the spec names (happy path + each named rejection) still
gets its test. It just gets **exactly** that, never more.

## When to Activate

Use this skill when:
- Writing tests for a spec requirement (new or changed behavior defined in a spec)
- Fixing failing tests
- Filling a **spec gap** — a requirement in a spec with no corresponding test
- Reviewing whether existing tests still match their specs

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (read the spec's FR/NFR/UC IDs first), **Size Triage** (S/M/L session splitting),
verification strategy, and commit format. This skill adds Pest-specific guidance — spec
traceability, test structure, mocking boundaries, and quality gates — nothing else.

### Execute — Write Tests

- Write **one test per requirement**, named with the requirement ID (see Spec Traceability)
- Test only the scenarios the spec names: the happy path, and each rejection/validation rule the
  spec explicitly defines
- Use `LazilyRefreshDatabase`, factories, `assertModelExists()`
- Do not mock Eloquent — use real database
- **Do not pad** — skip edge-case matrices, implementation internals, and framework behavior that
  no requirement mentions

### Verify — Quality Gates

- Targeted tests: `php artisan test --compact --filter={TestName}`; module suite:
  `vendor/bin/pest --testsuite={ModuleName}`
- **Do not run the full suite unless the user asks** — batch all changes, then verify once
- Report: requirements covered (with IDs), spec gaps still open, tests removed and why

## Test Structure

```
tests/{Module}/{SubModule}/{Name}Test.php
```

All tests live under `tests/{Module}/` — the old `tests/Unit/` and `tests/Feature/` split has been removed.
Tests that need a database use `LazilyRefreshDatabase`; pure logic tests do not.

## Spec Traceability

The spec is the source of truth. Tests are written per requirement, not per class:

| Spec artifact | Test mapping |
| ------------- | ------------ |
| `FR-*` Functional Requirement | Feature test verifying the behavior it mandates |
| `NFR-*` Non-Functional Requirement | Test only when the NFR is testable at code level (e.g., auth/security rules); skip metrics like "load time" |
| `UC-*` Use Case | Feature test for the end-to-end flow (happy path + named alternatives) |
| §6 Data contract | Unit test of the DTO/Enum/Entity shape only if the spec defines it |
| Nothing in any spec | **Do not write a test** |

**Test description convention** — prefix with the full `{SPECID}-{REQ}:` so traceability is
visible in test output:

```php
test('SE5Q9-FR-A4: step() records success and returns the step result', function () { ... });
test('SE5Q9-FR-A4: step() records failure and rethrows the exception', function () { ... })->throws(RejectedException::class);
```

Use `describe('{SPECID}')` to carry the spec ID once, then `test('{REQ}: ...')` per requirement.

**When a spec changes, its tests change.** Requirement removed → remove its tests. Requirement
rewritten → rewrite the test to match. A test left behind with no current requirement is orphaned
noise — delete it.

## Key Conventions

### Database

- Use `LazilyRefreshDatabase` trait (not `RefreshDatabase`)
- Use `assertModelExists()` over `assertDatabaseHas()`
- Never mock Eloquent models — use factories + real database

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_tests.py` | Run test suite, parse per-module results | `python3 scripts/scan_tests.py` |

Use `--module {Name}` to run tests for a single module. Output:
`scripts/outputs/{timestamp}-tests.json`.

## Quality Gate — arch-guard

Test files must also pass arch-guard checks:
- Test files must have `declare(strict_types=1)` (D1)
- No debug calls in tests (D2) — use `->dd()` or `->dump()` Pest methods instead
- Test naming follows `test("{SPECID}-{REQ}: ...")` convention
- See `arch-guard` skill for full rule reference

### Mocking

| Boundary      | Approach                         |
| ------------- | -------------------------------- |
| External HTTP | `Http::fake()`                   |
| File system   | `Storage::fake()`                |
| Queue         | `Queue::fake()`                  |
| Notifications | `Notification::fake()`           |
| Events        | `Event::fake([Specific::class])` |
| Cache         | `Cache::fake()`                  |
| Auth          | `actingAs($user)`                |

If you're using `shouldReceive()`, reconsider — prefer `fake()` methods.

### Action Test Pattern

```php
test('SE5Q9-FR-A1: a failing callback rolls back the transaction', function () {
    // Arrange
    $data = CreateResourceData::from([...]);

    // Act
    $result = app(CreateResourceAction::class)->execute($data);

    // Assert
    expect($result)->toBeInstanceOf(ActionResponse::class);
    expect($result->success)->toBeTrue();
    assertModelExists($result->data);
});
```

```php
test('SE5Q9-FR-A1: nested transactions run without double-wrapping', function () {
    $record = Record::factory()->create(['status' => 'finalized']);

    app(FinalizeAction::class)->execute($record);
})->throws(RejectedException::class);
```

## Verification Checklist

- [ ] Read the spec; each new test maps to a requirement ID (`FR-*` / `NFR-*` / `UC-*`)
- [ ] Only spec-defined scenarios tested — no padding, no exhaustive edge-case matrices
- [ ] No test written for behavior that no requirement mentions
- [ ] `LazilyRefreshDatabase` used for feature tests
- [ ] No Eloquent mocking
- [ ] `assertModelExists()` preferred over `assertDatabaseHas()`
- [ ] Tests are isolated — no shared state between tests
- [ ] Targeted tests pass (`php artisan test --compact --filter={TestName}`)

## References

| Topic                   | Doc                                    |
| ----------------------- | -------------------------------------- |
| Testing patterns (full) | `docs/architecture/testing-pattern.md` |
| Testing infrastructure  | `docs/infrastructure/testing.md`       |
| Pest documentation      | `search-docs` with `pestphp/pest`      |
