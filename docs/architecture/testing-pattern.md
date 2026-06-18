# Testing Pattern Reference

> **Last updated:** 2026-06-10
>
> Pattern reference for testing conventions across the codebase. Describes **what** to test and
> **how to approach** each layer — not exact implementation.

---

## 1. Testing Philosophy

### 1.1 Every Change Must Have Tests
Every code change — feature, refactor, bug fix — must be accompanied by tests that verify the
change works correctly and do not break existing behavior. A change is not complete until its
tests pass.

### 1.2 Red-Green-Refactor (TDD)
Write the test first, watch it fail, then write the minimum implementation to make it pass, then
refactor. The test stays green throughout refactoring.

### 1.3 Layer-by-Layer TDD Entry Points
TDD order follows bottom-up dependency: enums and entities have no dependencies and are tested
first (unit), then Actions (feature), then Livewire components that consume them (feature).

| Layer | TDD Entry Point | What You Test First |
|-------|----------------|---------------------|
| **Enum** | Unit test | Labels, transition rules, terminal states |
| **Entity** | Unit test | Construct, assert business rule methods |
| **Command Action** | Feature test | Execute → assert database state changed |
| **Read Action** | Feature test | Set up data → assert returned structure |
| **Process Action** | Feature test | Full workflow + partial failure scenarios |
| **Livewire** | Feature test | Render → interact → assert component state |
| **Policy** | Unit test | Mock user/model → assert boolean gate methods |
| **Console Command** | Feature test | Call command → assert exit code / output |

---

## 2. Test Structure & File Organization

### 2.1 Module-First Structure
Tests mirror the source structure exactly. Every file in `app/` has a corresponding test file in
`tests/`:

```
tests/
├── Feature/{Module}/{SubModule}/{Name}Test.php   → Integration tests (Actions, Livewire)
├── Unit/{Module}/{SubModule}/{Name}Test.php        → Isolated tests (Entities, Enums)
└── {Feature,Unit}/{Component}/{Name}Test.php       → Shared component tests
```

### 2.2 Three Test Tiers

| Tier | Directory | Database | Speed | Tests What |
|------|-----------|----------|-------|-----------|
| **Unit** | `tests/Unit/{Module}/` | Never | Fast (ms) | Entities, Enums, DTOs, Policies, Support, Contracts |
| **Feature** | `tests/Feature/{Module}/` | Always | Medium (s) | Actions, Livewire, Console Commands, Middleware, Events |
| **Arch** | `tests/Arch/` | Never | Fast (ms) | Structural rules via `arch()` expectations |

### 2.3 Value Object Tests
Value objects, flat enums, and small validation rules that belong to a module but are too small
for their own submodule go under `tests/Unit/{Module}/Types/`.

---

## 3. Scope Isolation

### 3.1 One File, One Scope — CRITICAL
Do **not** combine multiple distinct testing scopes into a single test file. Each dedicated test
file must thoroughly cover its target from multiple angles: happy path, validation constraints,
edge cases, and error handling.

### 3.2 Dedicated Files for Each Component
- Every **Action** has its own test file
- Every **Console Command** has its own test file
- Every **Livewire component** has its own test file
- Every **Policy** has its own test file

---

## 4. Test Naming Conventions

### 4.1 File Naming
Files use PascalCase with `Test.php` suffix.

### 4.2 Test Descriptions
Use `it()` for descriptive sentences that read as specifications. The description should complete
the sentence: "it **creates a resource with valid input**".

### 4.3 Grouping with `describe()`
Use `describe()` to group related tests by subject (e.g., by Action name, by method name).

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
Constructor and property assertions. Test fromArray hydration, toArray serialization, key
conversion (snake_case to camelCase). Test only/except filtering, merge immutability, and
polymorphic from() sources.

### 6.4 Command Action Tests (Feature)
Resolve from container, execute with data, assert database changes via `assertModelExists()`.
Assert result is a model instance, use `fresh()` to verify DB state. Assert validation exceptions
and domain rule exceptions.

### 6.5 Read Action Tests (Feature)
Set up data, call the reader, assert return structure and types. No database mutation expected.

### 6.6 Process Action Tests (Feature)
Mock child Actions to verify orchestration. Test full workflow and partial failure / rollback
scenarios.

### 6.7 Livewire Tests (Feature)
Use `Livewire::test()` entry point. Assert properties with `assertSet()`, views with
`assertViewIs()`, dispatched events with `assertDispatched()`. Test invalid input graceful
fallback.

### 6.8 Policy Tests (Unit)
Direct gate method assertions. Use anonymous classes as simple mocks. No database needed — pure
boolean gate logic.

### 6.9 Console Command Tests (Feature)
Use `$this->artisan()` with `assertExitCode()`, `expectsOutputToContain()`, and
`expectsConfirmation()`. Use `partialMock()` for services.

### 6.10 Middleware Tests (Feature)
Register test routes in `beforeEach()`, then `$this->get()` and assert headers.

### 6.11 Event / Listener Tests (Feature)
Fake specific events after factory setup. Direct listener instantiation when testing handlers
in isolation. Assert cache or side-effect changes.

### 6.12 Support / Utility Tests (Unit)
Group by method name with `describe()`. Use anonymous classes with trait usage. Fake
`MessageLogged` for logger assertions.

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

- **Eloquent relationships directly** — test through Actions
- **Simple getters/setters** — trivial passthrough code
- **Configuration loading** — framework behavior
- **Framework-provided functionality** — UUID generation, pagination
- **Simple model scopes in isolation** — test through Actions that use them
- **Trivial views** — only needed when underlying Actions are untested

---

*This document is auto-synchronized with the codebase. When testing practices evolve, update the
relevant sections in `docs/infrastructure/testing.md`, `docs/conventions.md` §12, or the skill
files, then reflect changes here. See `docs/doc-index.md` for the complete documentation catalog.*
