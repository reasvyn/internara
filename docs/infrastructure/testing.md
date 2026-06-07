# Testing

> Last updated: 2026-06-03 Changes: add TDD methodology, update test paths to submodule-based
> structure

## Testing Philosophy

The test suite is organized by module and by test type. Every change to the codebase must be
accompanied by tests that verify the change works correctly and does not break existing behavior.

## Scope Isolation

To maintain strict modularity, high code quality, and predictable testing boundaries, this project
enforces **Scope Isolation** in all test files:

- **One File, One Scope**: Do NOT combine multiple distinct testing scopes into a single test file.
  For example, do not group different console commands (e.g. `system:health` and `system:cleanup`)
  or different actions/components under a single test file.
- **Dedicated Test Files**: Every console command, action, and Livewire component must have its own
  dedicated test file (e.g. `SystemHealthCommandTest.php` and `SystemCleanupCommandTest.php`).
- **Comprehensive Coverage**: A test suite should not be measured by lines of code, but by depth.
  Each dedicated test file must thoroughly cover the target scope from multiple angles:
    - Happy path scenarios.
    - Validation constraints and failure modes.
    - Edge cases and boundary inputs.
    - Error handling (graceful failures, logging).
    - Mocking dependencies/actions and verifying the entire execution chain.

## TDD Approach

This project follows **Test-Driven Development (TDD)** — write the test first, watch it fail, then
write the implementation to make it pass. Tests are not an afterthought; they drive the design.

### Red-Green-Refactor Cycle

Every feature or fix follows the same three-step cycle:

1. **🔴 Red** — Write a failing test that describes the desired behavior. Running the test confirms
   the feature does not exist yet. This step forces you to think about the interface before the
   implementation — what should the method accept, return, and what edge cases exist?
2. **🟢 Green** — Write the minimum implementation to make the test pass. No extra code, no
   premature optimization, no gold-plating. Just enough to turn the test green.
3. **🔵 Refactor** — Clean up the implementation and test. Improve naming, extract helpers,
   optimize, add documentation. The test stays green throughout.

### Test-First Workflow

```bash
# 1. Write a failing test
php artisan make:test --pest CreateInternshipActionTest  # Feature test at tests/Feature/Program/Internship/

# Edit the test file with the expected behavior

# 2. Confirm it fails
php artisan test --compact --filter=CreateInternshipAction

# 3. Write the implementation in app/{Module}/Actions/

# 4. Confirm it passes
php artisan test --compact --filter=CreateInternshipAction

# 5. Refactor and re-run
php artisan test --compact --filter=CreateInternshipAction
```

### Layer-by-Layer TDD

Each layer of the architecture has a natural TDD progression:

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

### TDD by Development Scenario

#### New Entity or Enum

1. Write a unit test that constructs the entity/enum and asserts behavior
2. Confirm test fails (entity does not exist yet)
3. Create the entity/enum class
4. Confirm test passes

#### New Command Action

1. Write a feature test that creates records via factory, calls the Action, and asserts database
   state or return value
2. Confirm test fails (Action does not exist yet)
3. Create the Action class with the `execute()` method
4. Confirm test passes

#### New Livewire Component

1. Write a feature test that renders the component via `Livewire::test()` and asserts initial state
2. Confirm test fails
3. Scaffold the component
4. Add tests for each interaction (create, update, delete, filter, search)
5. Implement each interaction test-first

#### Bug Fix

1. Write a test that reproduces the bug (it fails)
2. Fix the implementation
3. Confirm the test now passes
4. Verify no existing tests broke

### Test Naming Convention

Tests use descriptive `it()` statements that read like specifications:

```php
describe('CreateInternshipAction', function () {  // app/Program/Internship/Actions/CreateInternshipAction.php
    it('creates an internship with active academic year', function () { ... });
    it('rejects creation when academic year is missing', function () { ... });
    it('assigns default status of draft', function () { ... });
    it('logs the creation event', function () { ... });
});
```

The `it()` description should complete the sentence: "it **creates an internship with active
academic year**". This makes test output read as executable documentation.

### TDD and the Action Triad

The three Action types map to distinct TDD approaches:

- **Command Action** → Test that the mutation happened (database row created, status changed, log
  recorded). Use `LazilyRefreshDatabase` + factory + `assertDatabaseHas()`.
- **Read Action** → Test that the correct data is returned given a known state. No database mutation
  expected — assert return values only.
- **Process Action** → Test the orchestration: that each sub-action was called with the correct
  arguments. Use Mockery to mock child Actions and assert they received the right input.

### Running TDD Cycle Efficiently

Run only the tests relevant to your current work:

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

### When TDD Is Optional

TDD is **recommended for all new code**, but not strictly mandatory in these cases:

- **Exploratory/prototype code** — use TDD-light: write a high-level test, then iterate quickly
- **Trivial changes** — renaming, simple config changes, static text without logic
- **Migration-only changes** — schema changes without new business logic

Any code merged to a shared branch must still have tests. TDD is the preferred path, but the gate is
passing tests, not the process that produced them.

## Feature vs Unit Test Distinction

Feature tests test complete workflows from end to end. They hit HTTP routes, submit forms, interact
with Livewire components, and verify database state, redirects, and response content. Feature tests
use the full application boot and a real (in-memory SQLite) database. They answer the question:
"does this workflow produce the correct outcome when used as a real user would?"

Unit tests test isolated pieces of logic in isolation. They test Actions, Entities, and pure utility
classes without the need for a database, HTTP request, or full application boot. Unit tests run fast
because they have no dependencies. They answer the question: "does this specific function return the
correct value given these inputs?"

## When to Use Each

Use a unit test for a pure business rule: an Entity method that determines whether a status
transition is allowed, an Action that computes a score, a Support class that formats data. No
database needed, no HTTP needed.

Use a feature test for a user-visible workflow: registering a user, submitting an assignment,
approving a placement. The test creates any necessary records, performs the action, and verifies the
outcome in the database, response, and session.

## What LazilyRefreshDatabase Does

`LazilyRefreshDatabase` is a testing trait that defers database migration until the first query hits
the database, rather than migrating before every test. This speeds up the test suite dramatically
because tests that do not touch the database — pure logic tests, validation tests, early-return
tests — skip migration entirely. When a test does perform a database operation, the trait migrates
once for that test class, then wraps each test in a transaction that is rolled back after the test
completes.

This is distinct from `RefreshDatabase`, which migrates the database fresh for every test.
LazilyRefreshDatabase achieves the same isolation (each test starts with a clean database) with less
overhead.

## How Entity Testing Works Without a Database

Entities are `final readonly` classes with zero framework dependencies. They do not extend Eloquent,
do not use facades, and do not access the database. Testing them is a matter of constructing an
instance with given values and asserting that its methods return the expected results. For example,
an AccountStatus enum can be tested by verifying that `SUSPENDED->allowsLogin()` returns false, that
status transitions follow the defined state machine, and that terminal statuses cannot transition
further.

This makes Entity tests the fastest and most reliable tests in the suite. They have no setup, no
teardown, no dependencies. They are pure PHP function tests.

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

## Where to Find It

- `tests/Feature/{Module}/{SubModule}/` — feature tests organized by module and submodule
- `tests/Unit/{Module}/{SubModule}/` — unit tests organized by module and submodule
- `tests/Unit/{Module}/Types/` — unit tests for value objects, flat enums, rules
- `tests/TestCase.php` — base test case with `LazilyRefreshDatabase`
- `tests/Pest.php` — Pest global configuration
- `phpunit.xml` — PHPUnit configuration
- `phpunit.coverage.xml` — coverage-specific configuration
- `composer.json` — test scripts in `scripts` section
- `docs/conventions.md` — Section 19 (Testing conventions)
- `docs/infrastructure/infrastructure.md` — tier-based infrastructure design

## Code Coverage

Code coverage requires the **pcov** PHP extension. Configure it via `phpunit.coverage.xml` (separate
from the main `phpunit.xml`).

### Running Coverage

```bash
composer run coverage                          # full app (unit + feature + arch)
composer run coverage -- tests/Unit/Core       # single module (Core)
composer run coverage -- tests/Unit/Settings   # single module (Settings)
composer run coverage -- tests/Unit            # unit tests only

composer run coverage -- --filter=BaseAction   # specific test
```

Arguments after `--` are passed directly to Pest, so any filter, path, or option works. The HTML
report is written to `storage/coverage/html/index.html`.

### Setup

Pcov must be loaded at runtime — it is not enabled by default in `php.ini`:

```bash
php -d extension=pcov.so -d pcov.enabled=1 vendor/bin/pest --coverage
```

The `composer run coverage` script handles this automatically.

---

## Testing the Dual Mentor Fallback Protocol

Testing the **Dual Mentor Fallback & Optionality Protocol** requires verifying both the standard
supervisor-led paths and the teacher-led fallback/bypass paths.

### 1. Attendance & Daily Journal Override Tests

Feature tests for journals and attendance must verify:

- **Auto-escalation flag**: Assert that logbooks or attendance records in the `SUBMITTED` state for
  longer than the bypass window (default: 48 hours) correctly trigger the auto-escalation flag.
- **Teacher override bypass action**:
    - Mock/authenticate as a `Teacher` user and call the verification action (e.g.
      `BypassSupervisorVerificationAction` or a general verification action with a fallback override
      parameter).
    - Assert that the record transitions successfully to `FINALIZED` / `VERIFIED`.
    - Assert that `verified_by_fallback` is set to `true` (or the corresponding fallback verifier
      fields are stamped).
    - Verify that an audit trail log is appended detailing the override.
    - Verify that the record is removed from the corporate supervisor's queue.

### 2. Competency Evaluation & Grading Tests

Tests for end-of-placement grading must cover the three evaluation paths:

- **Standard path**: Verify that the calculated grade correctly combines supervisor score (40%),
  teacher score (20%), and exam score (40%).
- **Proxy path**:
    - Verify that a `Teacher` can submit scores on behalf of the supervisor (proxy toggle = active).
    - Verify that the compiled output/certificate is stamped with the `proxy_scores` metadata tag.
- **Weight redistribution path**:
    - Set up a placement with no supervisor score submitted.
    - Call the final grading action and assert that it correctly redistributes the 40% supervisor
      weight: 20% added to the teacher's evaluation (now 40%) and 20% to the exam (now 60%), using
      the formula: `Grade = (Teacher × 40%) + (Exam × 60%)`
    - Assert that the compiled output/certificate is stamped with the `fallback_weights` metadata
      tag.
