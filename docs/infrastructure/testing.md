# Testing — Spec-Driven Testing Strategy & Infrastructure

> **Last updated:** 2026-08-17 **Changes:** test naming convention — `describe("{SpecID}: Test description...")` +
> `test("{SpecID}-{ReqID}: Test description...")` replaces `it()` / `describe('{SPECID}')`

## Description

Testing strategy, spec traceability, scope isolation, Pest conventions, factory usage, and mocking
boundaries.

## Testing Philosophy

Tests verify the spec — nothing more. A test exists because a requirement in
`docs/specs/{feature}.md` (`FR-*`, `NFR-*`, `UC-*`, or a §6 data contract) demands it. Every test
description prefixes its requirement ID. Coverage is measured in **spec requirements covered**, not
lines of code.

**Write only the tests the spec requires.** The minimalism is the point, not a side effect:
spec-scoped tests run fast (seconds vs. 10+ minutes), consume far fewer resources (~2GB+ RAM for the
full suite), and keep the suite self-explaining — every test carries its requirement ID, so no
archaeology is needed to understand why it exists. Less to write, less to read, less to maintain.

---

## Scope Isolation

To maintain strict modularity, high code quality, and predictable testing boundaries, this project
enforces **Scope Isolation** in all test files:

- **One File, One Scope**: Do not combine multiple distinct testing scopes into a single test file.
  Scope follows the spec: one requirement (or one requirement area) per file.
- **Spec-Aligned Files**: Test files map to spec requirements — e.g., an Action implementing an FR
  gets its own test file.
- **Spec-Scenario Coverage**: Each test file covers exactly the scenarios its requirement names:
    - The happy path scenario the FR/UC defines
    - Each rejection / alternative flow the requirement explicitly lists
    - Never padding: no edge-case matrices, internals, or framework behavior the spec doesn't name

---

## TDD Approach

This project follows **Requirement-First Development**: write the spec requirement, then the test
for it, then the implementation that satisfies it.

### Red-Green-Refactor Cycle

Every requirement follows the same three-step cycle:

1. **Red** — Write a failing test that describes the requirement (named with its `FR-*` / `UC-*` ID).
2. **Green** — Write the minimum implementation to make the test pass.
3. **Refactor** — Clean up the implementation and test. The test stays green throughout.

### Test-First Workflow

```bash
# 1. Read the spec requirement (docs/specs/{feature}.md, e.g., FR-INT1)
# 2. Write a failing test
php artisan make:test --pest CreateInternshipActionTest

# 3. Confirm it fails
php artisan test --compact --filter=CreateInternshipAction

# 4. Write the implementation in app/{Module}/Actions/

# 5. Confirm it passes
php artisan test --compact --filter=CreateInternshipAction

# 6. Refactor and re-run
php artisan test --compact --filter=CreateInternshipAction
```

### Layer-by-Layer Entry Points

| Layer               | Test Type      | Test Only If the Requirement Names It                              |
| ------------------- | -------------- | ------------------------------------------------------------------ |
| **Entity**          | Unit test      | Names a business-rule method the Entity owns                       |
| **Enum**            | Unit test      | Names labels/transition rules/terminal states                      |
| **Command Action**  | Feature test    | Mandates a mutation                                                |
| **Read Action**     | Feature test    | Mandates a query and its returned shape                            |
| **Process Action**  | Feature test    | Mandates orchestration / rollback semantics                        |
| **Livewire**        | Feature test    | Mandates a UI behavior                                             |
| **Policy**          | Unit test       | Mandates an authorization gate per role                            |
| **Console Command** | Feature test    | Mandates a CLI behavior and its exit/output                        |

### TDD and the Action Triad

The three Action types map to distinct approaches:

- **Command Action** → Test the mutation the requirement mandates (row created, status changed).
  Use `LazilyRefreshDatabase` + factory + `assertModelExists()`.
- **Read Action** → Test that the required data shape is returned given known state. No mutation
  expected — assert return values only.
- **Process Action** → Test the orchestration the requirement names. Only mock child Actions when
  the spec mandates rollback semantics.

### Test Naming Convention

Tests use `test()` with the requirement ID prefixed, so traceability is visible in test output:

```php
test("{SpecID}-{ReqID}: Test description...", function () { ... });
test("{SpecID}-{ReqID}: Test description...", function () { ... });
```

The description carries the spec ID and the requirement ID (`{SpecID}-{ReqID}: description`). Group
tests under `describe("{SpecID}: Test description...")` (spec ID plus a short description), and keep
the full `{SpecID}-{ReqID}:` prefix on each `test()` inside:

```php
describe("{SpecID}: Test description...", function () {
    test("{SpecID}-{ReqID}: Test description...", function () { ... });
    test("{SpecID}-{ReqID}: Test description...", function () { ... });
});
```

### Running Tests Efficiently

```bash
# Single test class
php artisan test --compact --filter=CreateInternshipAction

# Single test method
php artisan test --compact --filter='FR-A4: step records success'

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
composer run coverage                                 # full app (all modules)
composer run coverage -- --testsuite=Core             # single module (Core)
composer run coverage -- --testsuite=User             # single module (User)
composer run coverage -- --filter=BaseAction          # specific test
```

Arguments after `--` are passed directly to Pest. The HTML report is written to
`storage/coverage/html/index.html`.

Pcov must be loaded at runtime:

```bash
php -d extension=pcov.so -d pcov.enabled=1 vendor/bin/pest --coverage
```

The `composer run coverage` script handles this automatically.

> **Coverage is a diagnostic, not a mandate.** Use the report to spot **spec gaps** (requirements
> with no test). Never write padding tests to push percentages — tests that exist only for line
> coverage are noise and are rejected. The quality bar is spec-requirement traceability, not
> line-coverage thresholds.

---

## Assertion Conventions

- **Prefer `assertModelExists()` over `assertDatabaseHas()`** — `assertModelExists()` loads the
  actual model, enabling subsequent assertions on model attributes and relationships without
  re-querying. Use `assertDatabaseHas()` only when you need to verify data without loading the model
  (e.g., soft-deleted records).

---

## Where to Find It

- `tests/{Module}/{SubModule}/` — all tests organized by module and submodule (no Unit/Feature split)
- `tests/{Module}/Types/` — tests for value objects, flat enums, rules
- `tests/{Module}/Enums/`, `tests/{Module}/Entities/` — pure logic tests (no database needed)
- `tests/{Module}/Actions/`, `tests/{Module}/Livewire/` — integration tests (use `LazilyRefreshDatabase`)
- `tests/TestCase.php` — base test case with `LazilyRefreshDatabase`
- `tests/Pest.php` — Pest global configuration
- `phpunit.xml` — PHPUnit configuration
- `phpunit.coverage.xml` — coverage-specific configuration
- `composer.json` — test scripts in `scripts` section
- `docs/specs/index.md`, `docs/specs/{feature}.md` — the source of truth for what tests must exist
- `docs/conventions.md` — Section 12 (Testing)
- [Infrastructure](infrastructure.md) — tier-based infrastructure design
