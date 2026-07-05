# Testing — Testing Strategy & Infrastructure

> **Last updated:** 2026-06-16 **Changes:** sync — add Assertion Conventions section
> (assertModelExists over assertDatabaseHas)

## Description

Testing strategy, scope isolation, Pest conventions, factory usage, mocking boundaries, and coverage
thresholds.

## Testing Philosophy

The test suite is organized by module and by test type. Every change to the codebase must be
accompanied by tests that verify the change works correctly and does not break existing behavior.

---

## Scope Isolation

To maintain strict modularity, high code quality, and predictable testing boundaries, this project
enforces **Scope Isolation** in all test files:

- **One File, One Scope**: Do not combine multiple distinct testing scopes into a single test file.
  For example, do not group different console commands (e.g., `system:health` and `system:cleanup`)
  or different actions/components under a single test file.
- **Dedicated Test Files**: Every console command, action, and Livewire component must have its own
  dedicated test file (e.g., `SystemHealthCommandTest.php` and `SystemCleanupCommandTest.php`).
- **Comprehensive Coverage**: Each dedicated test file must thoroughly cover the target scope from
  multiple angles:
    - Happy path scenarios
    - Validation constraints and failure modes
    - Edge cases and boundary inputs
    - Error handling (graceful failures, logging)
    - Mocking dependencies/actions and verifying the entire execution chain

---

## TDD Approach

This project follows **Test-Driven Development (TDD)** — write the test first, watch it fail, then
write the implementation to make it pass.

### Red-Green-Refactor Cycle

Every feature or fix follows the same three-step cycle:

1. **Red** — Write a failing test that describes the desired behavior.
2. **Green** — Write the minimum implementation to make the test pass.
3. **Refactor** — Clean up the implementation and test. The test stays green throughout.

### Test-First Workflow

```bash
# 1. Write a failing test
php artisan make:test --pest CreateInternshipActionTest

# 2. Confirm it fails
php artisan test --compact --filter=CreateInternshipAction

# 3. Write the implementation in app/{Module}/Actions/

# 4. Confirm it passes
php artisan test --compact --filter=CreateInternshipAction

# 5. Refactor and re-run
php artisan test --compact --filter=CreateInternshipAction
```

### Layer-by-Layer TDD Entry Points

| Layer               | TDD Entry Point | What You Test First                                    |
| ------------------- | --------------- | ------------------------------------------------------ |
| **Entity**          | Unit test       | Construct with test data, assert business rule methods |
| **Enum**            | Unit test       | Assert `label()`, transition rules, terminal states    |
| **Command Action**  | Feature test    | Factory + execute → assert database state changed      |
| **Read Action**     | Feature test    | Set up data → call method → assert returned structure  |
| **Process Action**  | Feature test    | Complete workflow + partial failure scenarios          |
| **Livewire**        | Feature test    | Render → interact → assert component state / redirect  |
| **Policy**          | Unit test       | Mock user/model → assert boolean gate methods          |
| **Console Command** | Feature test    | Call command → assert exit code / output               |

### TDD and the Action Triad

The three Action types map to distinct TDD approaches:

- **Command Action** → Test that the mutation happened (database row created, status changed, log
  recorded). Use `LazilyRefreshDatabase` + factory + `assertDatabaseHas()`.
- **Read Action** → Test that the correct data is returned given a known state. No database mutation
  expected — assert return values only.
- **Process Action** → Test the orchestration: that each sub-action was called with the correct
  arguments. Use Mockery to mock child Actions and assert they received the right input.

### Test Naming Convention

Tests use descriptive `it()` statements that read like specifications:

```php
describe('CreateInternshipAction', function () {
    it('creates an internship with active academic year', function () { ... });
    it('rejects creation when academic year is missing', function () { ... });
    it('assigns default status of draft', function () { ... });
    it('logs the creation event', function () { ... });
});
```

The `it()` description should complete the sentence: "it **creates an internship with active
academic year**".

### Running Tests Efficiently

```bash
# Single test class
php artisan test --compact --filter=CreateInternshipAction

# Single test method
php artisan test --compact --filter='it creates an internship'

# All tests for a submodule
php artisan test --compact --filter=Internship

# Full suite before committing
php artisan test --compact
```

---

## Feature vs Unit Test Distinction

| Aspect   | Feature Test                                      | Unit Test                                    |
| -------- | ------------------------------------------------- | -------------------------------------------- |
| Scope    | End-to-end workflows                              | Isolated piece of logic                      |
| Database | Yes (in-memory SQLite)                            | No                                           |
| HTTP     | Yes (route hits, form submissions)                | No                                           |
| Question | "Does this workflow produce the correct outcome?" | "Does this function return the right value?" |
| Speed    | Slower (full app boot)                            | Fast (no dependencies)                       |

Use a unit test for a pure business rule — an Entity method, an Action that computes a score, a
Support class that formats data. Use a feature test for a user-visible workflow — registering a
user, submitting an assignment, approving a placement.

---

## LazilyRefreshDatabase

`LazilyRefreshDatabase` is a testing trait that defers database migration until the first query hits
the database, rather than migrating before every test. This speeds up the test suite dramatically
because tests that do not touch the database — pure logic tests, validation tests, early-return
tests — skip migration entirely.

This is distinct from `RefreshDatabase`, which migrates the database fresh for every test.
`LazilyRefreshDatabase` achieves the same isolation (each test starts with a clean database) with
less overhead.

---

## Entity Testing Without a Database

Entities are `final readonly` classes with zero framework dependencies. They do not extend Eloquent,
do not use facades, and do not access the database. Testing them is a matter of constructing an
instance with given values and asserting that its methods return the expected results. This makes
Entity tests the fastest and most reliable tests in the suite.

---

## Running Tests by Tier

```bash
# Development (Tier 1) — full suite
php artisan test

# CI (Tier 2+) — parallel, coverage
php artisan test --parallel
composer run coverage

# Single submodule
php artisan test --filter=Internship

# Single test
php artisan test --filter=testName
```

---

## Code Coverage

Code coverage requires the **pcov** PHP extension. Configure it via `phpunit.coverage.xml`.

```bash
composer run coverage                          # full app (unit + feature + arch)
composer run coverage -- tests/Unit/Core       # single module (Core)
composer run coverage -- tests/Unit            # unit tests only
composer run coverage -- --filter=BaseAction   # specific test
```

Arguments after `--` are passed directly to Pest. The HTML report is written to
`storage/coverage/html/index.html`.

Pcov must be loaded at runtime:

```bash
php -d extension=pcov.so -d pcov.enabled=1 vendor/bin/pest --coverage
```

The `composer run coverage` script handles this automatically.

---

## Where to Find It

- `tests/Feature/{Module}/{SubModule}/` — feature tests organized by module and submodule
- `tests/Unit/{Module}/{SubModule}/` — unit tests organized by module and submodule
- `tests/Unit/{Module}/Types/` — unit tests for value objects, flat enums, rules
- `tests/TestCase.php` — base test case with `LazilyRefreshDatabase`
- `tests/Pest.php` — Pest global configuration
- `phpunit.xml` — PHPUnit configuration
- `phpunit.coverage.xml` — coverage-specific configuration
- `composer.json` — test scripts in `scripts` section
- `docs/conventions.md` — Section 22 (Testing)
- [Infrastructure](infrastructure.md) — tier-based infrastructure design

## Assertion Conventions

- **Prefer `assertModelExists()` over `assertDatabaseHas()`** — `assertModelExists()` loads the
  actual model, enabling subsequent assertions on model attributes and relationships without
  re-querying. Use `assertDatabaseHas()` only when you need to verify data without loading the model
  (e.g., soft-deleted records).
