---
name: pest-testing
description: SDLC Phase: TESTING. Test writing, editing, and fixing using Pest — feature tests, unit tests, architecture tests, Livewire component tests.
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

## When to Activate

Use this skill when writing new tests, fixing failing tests, or reviewing test coverage. Covers all
test types: feature, unit, Livewire component, and architecture tests.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Write Tests

- Write unit tests for Entity, Enum, DTO (100% coverage)
- Write feature tests for Action, Livewire, Console Command
- Use LazilyRefreshDatabase, factories, assertModelExists()
- Do not mock Eloquent — use real database
- Test happy path + business rule violations + validation errors
- Output: test files covering happy path, edge cases, business rule violations, and validation
  errors

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of tests written
    - Coverage by layer (Entity/Enum/DTO/Action/Livewire)
    - Test suite status (pass/fail)
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

## Test Priorities (Build Order)

| Priority | What to Test        | Type    | Coverage Target |
| -------- | ------------------- | ------- | --------------- |
| 1        | Enums               | Unit    | 100%            |
| 2        | Entities            | Unit    | 100%            |
| 3        | DTOs                | Unit    | 100%            |
| 4        | Command Actions     | Feature | ≥ 90%           |
| 5        | Read Actions        | Feature | ≥ 80%           |
| 6        | Policies            | Unit    | 100%            |
| 7        | Livewire components | Feature | ≥ 80%           |
| 8        | Console Commands    | Feature | ≥ 80%           |

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
it('creates a resource with valid data', function () {
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
it('rejects invalid state transitions', function () {
    $record = Record::factory()->create(['status' => 'finalized']);

    app(FinalizeAction::class)->execute($record);
})->throws(RejectedException::class);
```

## Verification Checklist

- [ ] Every Action has a test file
- [ ] Happy path and business rule violation tested
- [ ] `LazilyRefreshDatabase` used for feature tests
- [ ] No Eloquent mocking
- [ ] Entity/DTO/Enum tests: 100% method coverage
- [ ] `assertModelExists()` preferred over `assertDatabaseHas()`
- [ ] Tests are isolated — no shared state between tests
- [ ] Full suite passes: `php artisan test --compact`

## References

| Topic                   | Doc                                    |
| ----------------------- | -------------------------------------- |
| Testing patterns (full) | `docs/architecture/testing-pattern.md` |
| Testing infrastructure  | `docs/infrastructure/testing.md`       |
| Pest documentation      | `search-docs` with `pestphp/pest`      |
