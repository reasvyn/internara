# Testing Pattern Reference — Spec-Driven Testing & Scope Isolation

> **Last updated:** 2026-08-10 **Changes:** spec-driven doctrine — tests trace to spec requirements
> (FR/NFR/UC IDs); coverage measured in requirements, not lines; minimalism rationale (speed, resource
> use, cognitive load)

## Description

> **how to approach** each layer — not exact implementation.

---

## 1. Testing Philosophy

### 1.0 Why Minimalism Matters

**Spec-driven testing writes only the tests the spec requires — nothing more.** This is deliberate,
not lazy:

- **Speed:** fewer tests → faster suite runs, faster feedback, faster development cycles. The full
  suite costs ~2GB+ RAM and 10+ minutes; a spec-scoped test runs in seconds.
- **Resource use:** every padded test consumes RAM, disk, and CI time for zero verification value.
  Minimal suites keep CI cheap and local runs snappy.
- **Cognitive load:** a test file that maps 1:1 to requirement IDs is self-explaining. Every test
  answers "which requirement does this verify?"; there is no "why does this test exist?" archaeology.
  Less to write, less to read, less to maintain.

The doctrine is **write the minimum tests that prove the spec**, then stop.

### 1.1 Every Spec Requirement Must Have Tests

Tests exist because a requirement in `docs/specs/{feature}.md` (`FR-*`, `NFR-*`, `UC-*`, or a §6
data contract) demands it. A requirement with no test is a **spec gap**; a test with no requirement
is **orphan noise**. Coverage is measured in spec requirements covered — never lines of code. Do not
write padding tests for behavior no requirement mentions.

### 1.2 Requirement-First (Spec → Test → Implementation)

Write the spec requirement, then the test for it, then the minimum implementation that makes it
pass. The test stays green throughout refactoring. When a requirement changes, its test changes;
when a requirement is removed, its test is removed.

### 1.3 Traceability

Every test description prefixes its requirement ID:

```php
it('FR-A1: allows a supervisor to approve attendance', function () { ... });
```

Use `describe('{spec}')` or `describe('FR-{area}')` to group requirements of one feature.

### 1.4 Layer-by-Layer Entry Points

The layer you test is whatever layer implements the requirement. Layer order below is a guide, not a
mandate: **test exactly the layer(s) the requirement touches, nothing more.**

| Layer               | Test Type      | Test Only If the Requirement...                                        |
| ------------------- | -------------- | ---------------------------------------------------------------------- |
| **Enum**            | Unit test      | Names labels, transitions, or terminal states                           |
| **Entity**          | Unit test      | Names a business rule the Entity owns                                   |
| **DTO (BaseData)**  | Unit test      | Defines a data contract shape in §6                                     |
| **Command Action**  | Feature test   | Mandates a mutation and its result                                      |
| **Read Action**     | Feature test   | Mandates a query and its returned shape                                 |
| **Process Action**  | Feature test   | Mandates multi-step orchestration or rollback semantics                 |
| **Livewire**        | Feature test   | Mandates a UI behavior (render/submit/authz)                            |
| **Policy**          | Unit test      | Mandates an authorization gate per role                                 |
| **Console Command** | Feature test   | Mandates a CLI behavior and its exit/output                             |

---

## 2. Test Structure & File Organization

### 2.1 Module-First Structure

Tests mirror the source structure exactly. Every file in `app/` has a corresponding test file in
`tests/`:

```
tests/
├── {Module}/{SubModule}/{Name}Test.php   → Integration tests (Actions, Livewire)
├── {Module}/{SubModule}/{Name}Test.php   → Isolated tests (Entities, Enums)
└── {Module}/{Component}/{Name}Test.php   → Shared component tests
```

Both unit and feature tests live flat under `tests/{Module}/` (no `Feature/`/`Unit/` split).

### 2.2 Three Test Tiers

| Tier        | Directory                 | Database | Speed      | Tests What                                              |
| ----------- | ------------------------- | -------- | ---------- | ------------------------------------------------------- |
| **Unit**    | `tests/{Module}/`    | Never    | Fast (ms)  | Entities, Enums, DTOs, Policies, Support, Contracts     |
| **Feature** | `tests/{Module}/` | Always   | Medium (s) | Actions, Livewire, Console Commands, Middleware, Events |
| **Arch**    | `tests/Arch/`             | Never    | Fast (ms)  | Structural rules via `arch()` expectations              |

### 2.3 Value Object Tests

Value objects, flat enums, and small validation rules that belong to a module but are too small for
their own submodule go under `tests/{Module}/Types/`.

---

## 3. Scope Isolation

### 3.1 One File, One Scope — CRITICAL

Do **not** combine multiple distinct testing scopes into a single test file. Scope follows the spec:
group the tests of one requirement (or one requirement area) in one file. Cover exactly the
scenarios the spec names — happy path plus each rejection/alternative the requirement lists.

### 3.2 File Organization by Spec Scope

- A spec's requirements map to test files by subject (e.g., one Action implementing an FR gets its
  own file)
- A spec with many requirements may group related ones under `describe('{spec}')`
- Never split a single requirement's tests across files unless it spans distinct layers

---

## 4. Test Naming Conventions

### 4.1 File Naming

Files use PascalCase with `Test.php` suffix.

### 4.2 Test Descriptions

Use `it()` with the requirement ID prefix. The description completes the sentence: "it **FR-A1:
allows a supervisor to approve attendance**".

### 4.3 Grouping with `describe()`

Use `describe()` to group related requirements by spec or by requirement area
(e.g., `describe('FR-ATT')`).

### 4.4 Simple `test()` for Flat Structure

For simple tests without grouping, use `test()`.

---

## 5. Database Handling

### 5.1 `LazilyRefreshDatabase` (Preferred)

Defers database migration until the first query hits the database. Tests that do not touch the
database skip migration entirely. Use for all feature tests.

### 5.2 `RefreshDatabase` (When Needed)

Use only when `LazilyRefreshDatabase` causes issues with specific test scenarios.

### 5.3 In-Memory SQLite

The test suite runs against an in-memory SQLite database.

### 5.4 Entity Tests — NO Database

Entities are `final readonly` classes with zero framework dependencies. They never touch the
database. Testing them is pure function calls.

---

## 6. Layer-Specific Testing Strategies

### 6.1 Entity Tests (Unit)

Direct instantiation without DB. Assert business rule methods (e.g., canBeDeleted, isLocked). Test
factory methods (fromModel), equality (equals), and immutability (with). Enforce `final readonly`
structure.

### 6.2 Enum Tests (Unit)

Assert label strings for every case. Test transition rules (canTransitionTo), terminal state
detection (isTerminal), and valid transitions. Group by behavior (transitions, terminal states).

### 6.3 Data / DTO Tests (Unit)

Constructor and property assertions. Test fromArray hydration, toArray serialization, key conversion
(snake_case to camelCase). Test only/except filtering, merge immutability, and polymorphic from()
sources.

### 6.4 Command Action Tests (Feature)

Resolve from container, construct a DTO, execute the Action with it. Assert the returned
`ActionResponse` is successful and wraps the expected data. Assert database changes via
`assertModelExists()`. Assert validation exceptions and domain rule exceptions
(`RejectedException`). Verify `ActionResponse::failed()` returns `true` on error responses.

### 6.5 Read Action Tests (Feature)

Set up data, call the reader, assert return structure and types. No database mutation expected.

### 6.6 Process Action Tests (Feature)

Mock child Actions to verify orchestration. Test full workflow and partial failure / rollback
scenarios.

### 6.7 Livewire Tests (Feature)

Use `Livewire::test()` entry point. Assert properties with `assertSet()`, views with
`assertViewIs()`, dispatched events with `assertDispatched()`. Test invalid input graceful fallback.

### 6.8 Policy Tests (Unit)

Direct gate method assertions. Use anonymous classes as simple mocks. No database needed — pure
boolean gate logic.

### 6.9 Console Command Tests (Feature)

Use `$this->artisan()` with `assertExitCode()`, `expectsOutputToContain()`, and
`expectsConfirmation()`. Use `partialMock()` for services.

### 6.10 Middleware Tests (Feature)

Register test routes in `beforeEach()`, then `$this->get()` and assert headers.

### 6.11 Event / Listener Tests (Feature)

Fake specific events after factory setup. Direct listener instantiation when testing handlers in
isolation. Assert cache or side-effect changes.

### 6.12 Support / Utility Tests (Unit)

Group by method name with `describe()`. Use anonymous classes with trait usage. Fake `MessageLogged`
for logger assertions.

---

## 7. Assertion Preferences

- `assertModelExists($model)` over `assertDatabaseHas()` — clearer intent
- `expect()->toBe*()` over `$this->assert*()` — Pest's fluent API
- `fn () => ... ->toThrow()` over `$this->expectException()` — cleaner inline assertions
- `->toBeTrue()` / `->toBeFalse()` — Pest fluent style
- `->toBeInstanceOf()` — verify return types
- `->toHaveCount()` — collection count
- `->toHaveKeys()` — array key presence
- `->toContain()` — string containment
- `->not->toBe()` / `->not->toContain()` — negative assertions
- `->toMatchArray()` — partial array matching

---

## 8. What NOT to Test

- **Anything with no spec requirement** — the definitive test of noise: a test that cannot be traced
  to an `FR-*` / `NFR-*` / `UC-*` ID or §6 contract must not exist
- **Eloquent relationships directly** — test through the requirement's Action
- **Simple getters/setters** — trivial passthrough code, unless a requirement names them
- **Configuration loading** — framework behavior
- **Framework-provided functionality** — UUID generation, pagination
- **Simple model scopes in isolation** — test through the Action that uses them
- **Trivial views** — only needed when the requirement's underlying Action is untested
- **Exhaustive edge-case matrices** — boundary/null matrices beyond what the requirement names
- **Mock-orchestration of child Actions** — only when the spec mandates rollback semantics
- **Line-coverage padding** — tests written only to push percentages; use coverage as a diagnostic
  for gaps, never as a target for padding

---

_This document is auto-synchronized with the codebase. When testing practices evolve, update the
relevant sections in `docs/infrastructure/testing.md`, `docs/conventions.md` §12, or the skill
files, then reflect changes here. See `docs/index.md` for the complete documentation catalog._
