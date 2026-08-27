# Testing Pattern Reference — Spec-Driven Testing & Scope Isolation

> **Last updated:** 2026-08-27 **Changes:** rewrite — integrate global standards (TDD, Testing Pyramid, AAA, FIRST, Four Phase Test) with anti-pattern table, Quick References

## Description

> **how to approach** each layer — not exact implementation.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Every spec requirement must have tests.** Tests exist because a requirement in `docs/specs/{feature}.md` (`FR-*`, `NFR-*`, `UC-*`, or a §6 data contract) demands it. A requirement with no test is a **spec gap**; a test with no requirement is **orphan noise**. Coverage is measured in spec requirements covered — never lines of code.

2. **Requirement-first (Spec → Test → Implementation).** Write the spec requirement, then the test for it, then the minimum implementation that makes it pass. The test stays green throughout refactoring.

3. **Traceability — prefix requirement IDs.** Every test description prefixes its requirement ID: `test("{SpecID}-{ReqID}: Test description...")`. Group tests under `describe("{SpecID}: Test description...")`.

4. **Scope isolation — one file, one scope.** Do not combine multiple distinct testing scopes into a single test file. Group the tests of one requirement area in one file. Never split a single requirement's tests across files unless it spans distinct layers.

5. **Layer-by-layer entry points.** Test exactly the layer(s) the requirement touches, nothing more. Enum/Entity/DTO/Policy → unit test. Action/Livewire/Console → feature test.

6. **LazilyRefreshDatabase preferred.** Use `LazilyRefreshDatabase` (not `RefreshDatabase`) for all feature tests. Entity tests NEVER touch the database.

7. **No padding tests.** Do not write tests for behavior no requirement mentions. The minimum tests that prove the spec, then stop.

---

## How to Apply

### 1. TDD (Test-Driven Development)

Red → Green → Refactor. Write the failing test first, make it pass with minimal code, then refactor while keeping tests green. In practice: write the spec requirement, write the test, write the minimum implementation.

**Reference:** [TDD by Kent Beck](https://www.amazon.com/Test-Driven-Development-Kent-Beck/dp/0321146530)

### 2. Testing Pyramid

| Layer | Count | Speed | Examples |
|-------|-------|-------|---------|
| Unit tests | Many | Fast (ms) | Entity, Enum, DTO, Policy |
| Feature tests | Some | Medium (s) | Action, Livewire, Console |
| Integration tests | Few | Slow | API endpoints, third-party |

Internara's testing strategy follows the pyramid: many unit tests for pure logic, some feature tests for Actions and Livewire, few integration tests for cross-module workflows.

### 3. AAA (Arrange-Act-Assert)

Every test follows the three-phase structure:
1. **Arrange** — set up data (factories, mocks, test doubles)
2. **Act** — call the code under test
3. **Assert** — verify the expected outcome

### 4. FIRST Principles

Tests should be:
- **Fast** — run in milliseconds (unit) or seconds (feature)
- **Independent** — no test depends on another
- **Repeatable** — same result every time
- **Self-validating** — automated pass/fail, no manual inspection
- **Timely** — written at the right time (requirement-first)

### 5. Four Phase Test (XUnit Patterns)

| Phase | Purpose | Example |
|-------|---------|---------|
| **Setup** | Create test fixtures | `User::factory()->create()` |
| **Exercise** | Invoke the code under test | `$action->execute($dto)` |
| **Verify** | Assert expected outcomes | `expect($result->success)->toBeTrue()` |
| **Teardown** | Clean up (automatic in Pest) | `LazilyRefreshDatabase` handles this |

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| Test with no requirement ID in description | Prefix with `{SpecID}-{ReqID}:` | No traceability |
| Test testing two unrelated requirements | Split into separate files | Scope isolation violated |
| `RefreshDatabase` when `LazilyRefreshDatabase` works | Use `LazilyRefreshDatabase` | Slower tests unnecessarily |
| Entity test hitting the database | Test as pure function calls | Unit test has side effects |
| Test without Arrange/Act/Assert structure | Follow AAA pattern | Unclear test intent |
| Test with 20+ assertions | Split into focused tests | Too many responsibilities |
| Test for behavior no spec mentions | Remove — spec gap or orphan test | Padding test |
| Feature test for simple getter/setter | Remove — trivial passthrough | Testing what doesn't need testing |
| Test depending on test execution order | Make tests independent | First principle violated |
| Test with `dd()`, `dump()`, or `ray()` | Remove debug calls | Debug in tests |

---

## Quick References

- `docs/conventions.md` §Testing Conventions — test structure, naming, strategies
- `docs/guides/infra/testing.md` — complete testing infrastructure
- `docs/specs/index.md` — spec index with requirement IDs
- [TDD by Kent Beck](https://www.amazon.com/Test-Driven-Development-Kent-Beck/dp/0321146530) — TDD methodology
- [Testing Pyramid](https://martinfowler.com/articles/practical-test-pyramid.html) — test distribution
- [AAA Pattern](https://martinfowler.com/bliki/GivenWhenThen.html) — Arrange-Act-Assert
- [FIRST Principles](https://pragprog.com/the-pragmatic-programmer/20th-edition/tips/) — good test properties
- [XUnit Test Patterns](https://xunitpatterns.com/) — test patterns catalog
- [Laravel Testing](https://laravel.com/docs/testing) — Pest/PHPUnit integration
- [Pest PHP](https://pestphp.com/docs) — Pest testing framework
