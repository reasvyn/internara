# Test Quality — Spec Traceability & Noise Control

Checklist to ensure tests are spec-traceable and free of noise.

> **Core rule:** a test that cannot be traced to a spec requirement is noise. Coverage is measured
> in spec requirements covered, not lines of code.

## Structure

- [ ] Test file path: `tests/{Module}/{SubModule}/{Name}Test.php`
- [ ] File name: `{Name}Test.php` (PascalCase + Test suffix)
- [ ] Every spec requirement (`FR-*` / `NFR-*` / `UC-*`) has at least one test

## Spec Traceability

- [ ] Each test description prefixes its spec + requirement ID, e.g. `test('SE5Q9-FR-A4: ...')`
- [ ] Group with `describe('{SPECID}')` when useful (spec ID once, `test('{REQ}: ...')` inside)
- [ ] Scenarios tested are exactly the ones the spec names (happy path + named rejections)
- [ ] No test for behavior no requirement mentions

## Feature Tests

- [ ] `LazilyRefreshDatabase` (not `RefreshDatabase`)
- [ ] `assertModelExists()` preferred over `assertDatabaseHas()`
- [ ] No Eloquent mocking — use factories + real database
- [ ] `Event::fake()` positioned AFTER factory setup

## Unit Tests

- [ ] No `LazilyRefreshDatabase`/`RefreshDatabase` (no DB needed)
- [ ] DTO/Entity/Enum tested **only when the spec's §6 contract defines them**
- [ ] Enum: every case the spec lists has a non-empty `label()`
- [ ] Enum (StatusEnum): `validTransitions()` only for transitions the spec defines

## Mocking Rules

| Boundary      | Use                              | Never Use                     |
| ------------- | -------------------------------- | ----------------------------- |
| Eloquent      | Factories + real DB              | `Mockery::mock(Model::class)` |
| External HTTP | `Http::fake()`                   | Real HTTP calls               |
| File system   | `Storage::fake()`                | `File::shouldReceive()`       |
| Queue         | `Queue::fake()`                  | Real queue worker             |
| Notifications | `Notification::fake()`           | Real mail sending             |
| Events        | `Event::fake([Specific::class])` | `Mockery::spy()`              |

## Noise Signals — Delete or Never Write

- ❌ Tests for implementation internals the spec doesn't describe
- ❌ Exhaustive validation/edge-case matrices not named in the spec
- ❌ "Comprehensive multi-angle" padding (CSS classes, DOM structure, HTTP status codes)
- ❌ Framework behavior tests (UUID generation, pagination, configuration loading)
- ❌ Mock-orchestration of child Actions unless the spec requires rollback semantics
- ❌ Tests left behind after their spec requirement was removed (orphan tests)

## Destructive Patterns

- ❌ Tests depending on state from other tests (flaky)
- ❌ `dd()`/`dump()` in test files
- ❌ `assertDatabaseHas()` when `assertModelExists()` is available
