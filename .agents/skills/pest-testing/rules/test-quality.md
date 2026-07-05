# Test Quality — Measurable Coverage & Consistency

Checklist to ensure tests are high-quality and measurable.

## Structure

- [ ] Test file path: `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`
- [ ] File name: `{Name}Test.php` (PascalCase + Test suffix)
- [ ] Every Action has its own test file

## Feature Tests

- [ ] `LazilyRefreshDatabase` (not `RefreshDatabase`)
- [ ] `assertModelExists()` preferred over `assertDatabaseHas()`
- [ ] No Eloquent mocking — use factories + real database
- [ ] `Event::fake()` positioned AFTER factory setup
- [ ] Happy path + business rule violation + validation error tested

## Unit Tests

- [ ] No `LazilyRefreshDatabase`/`RefreshDatabase` (no DB needed)
- [ ] Entity: every business question method tested
- [ ] DTO: `fromArray()`/`toArray()` roundtrip tested
- [ ] Enum: every case has non-empty `label()`
- [ ] Enum (StatusEnum): `validTransitions()` for every case
- [ ] Enum (StatusEnum): `isTerminal()` for terminal states

## Mocking Rules

| Boundary      | Use                              | Never Use                     |
| ------------- | -------------------------------- | ----------------------------- |
| Eloquent      | Factories + real DB              | `Mockery::mock(Model::class)` |
| External HTTP | `Http::fake()`                   | Real HTTP calls               |
| File system   | `Storage::fake()`                | `File::shouldReceive()`       |
| Queue         | `Queue::fake()`                  | Real queue worker             |
| Notifications | `Notification::fake()`           | Real mail sending             |
| Events        | `Event::fake([Specific::class])` | `Mockery::spy()`              |

## Coverage Targets

- Entity/Enum/DTO: 100%
- Command Actions: ≥ 90%
- Read Actions: ≥ 80%
- Livewire: ≥ 80%
- Policies: 100%

## Destructive Patterns

- ❌ Tests depending on state from other tests (flaky)
- ❌ `dd()`/`dump()` in test files
- ❌ `assertDatabaseHas()` when `assertModelExists()` is available
