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

Tests exist **only** because a requirement in `docs/specs/{feature}.md` demands it. Every test maps
to at least one requirement ID (`FR-*`, `NFR-*`, `UC-*`, or a §6 data contract). A test that cannot
be traced to a spec requirement is **noise** and must not be written. Coverage is measured in
**spec requirements covered**, never in lines of code.

## When to Activate

Use this skill when:
- Writing tests for a spec requirement (new or changed behavior defined in a spec)
- Fixing failing tests
- Filling a **spec gap** — a requirement in a spec with no corresponding test
- Reviewing whether existing tests still match their specs

## Agent Workflow

Using this skill follows 4 phases (mapped to AGENTS.md 9-step: Construct = Steps 1-5, Execute = 6,
Verify = 7, Report & Commit = 8-9):

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- **Read the spec first** (`docs/specs/{feature}.md`) — list the FR/NFR/UC IDs it defines
- Read relevant docs: module docs, pattern docs, reference docs
- Identify which requirements are already tested and which are gaps
- **Classify the size (S/M/L)** per AGENTS.md Size Triage; if test work spans multiple modules or is
  **L**, inform the user and propose a session plan
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Write Tests

- Write **one test per requirement**, named with the requirement ID (see Spec Traceability)
- Test only the scenarios the spec names: the happy path, and each rejection/validation rule the
  spec explicitly defines
- Use LazilyRefreshDatabase, factories, assertModelExists()
- Do not mock Eloquent — use real database
- **Do not pad** — skip edge-case matrices, implementation internals, and framework behavior that
  no requirement mentions
- Output: test files covering exactly the requirements in scope, each traceable to its ID

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run targeted tests: `php artisan test --compact --filter={TestName}` (module suite for
  module-scoped changes: `vendor/bin/pest --testsuite={ModuleName}`)
- Run arch-guard scripts: `scan_violations.py`, `scan_class_contracts.py`, `scan_security.py`,
  `scan_naming.py`, `scan_conventions.py`, `scan_doc_links.py`
- Verify with git: `git status` + `git diff` — confirm only intended files changed, nothing lost
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind
- **Do not run the full suite unless the user asks** — batch all changes, then verify once

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Spec requirements covered by new tests (with IDs)
    - Spec gaps still open (requirements without tests)
    - Tests removed and why (no spec mapping)
- Feeds into: feature-building (quality gate), sync-docs (test documentation)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                                                                                              |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Upstream**   | `feature-building` (code to test), `code-refactoring` (refactored code), `livewire-development` (components), `medialibrary-development` (uploads) |
| **This skill** | **TESTING** — writes and verifies tests                                                                                                            |
| **Downstream** | `feature-building` (integrated), `sync-docs` (doc updates)                                                                                         |

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

**Test description convention** — prefix with the requirement ID so traceability is visible in test
output:

```php
it('FR-REG1: creates a registration with valid data', function () { ... });
it('FR-PL2: rejects placement when quota is full', function () { ... })->throws(RejectedException::class);
```

Use `describe('{spec}')` or `describe('FR-{area}')` when grouping many requirements of one feature.

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
- Test naming follows `it_{behavior}()` convention
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
it('FR-X1: creates a resource with valid data', function () {
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
it('FR-X2: rejects invalid state transitions', function () {
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
