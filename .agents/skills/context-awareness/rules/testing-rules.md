# Testing Rules — What to Verify

> **Last updated:** 2026-07-03 **Changes:** initial — practical test patterns, not doc replacement

This is NOT a replacement for `docs/architecture/testing-pattern.md` or
`docs/infrastructure/testing.md`. Use this as a quick checklist when writing or reviewing tests.

## Test Structure

```
tests/{Module}/{SubModule}/{Name}Test.php
```

All tests live under `tests/{Module}/` — there is no Unit vs Feature directory split.
Tests that need a database use `LazilyRefreshDatabase`; pure logic tests do not.

## Quick Checklist Per Test

### Action Tests (Feature)

```
[ ] Uses LazilyRefreshDatabase (not RefreshDatabase)
[ ] Uses real factories, no Mockery for Eloquent
[ ] Tests happy path: creates/updates/deletes as expected
[ ] Tests business rule: verify RejectedException on invalid state
[ ] Tests validation: verify ValidationException on invalid input
[ ] Uses assertModelExists() over assertDatabaseHas()
[ ] Event::fake() AFTER factory creation (not before)
```

### Livewire Tests (Feature)

```
[ ] Uses LazilyRefreshDatabase
[ ] Tests render: assertSuccessful(), assertViewIs()
[ ] Tests mutations via Action calls
[ ] Tests validation errors: assertHasErrors()
[ ] Tests authorization: assertForbidden() or actingAs()
[ ] No Eloquent mocking — real factories
```

### Entity/DTO/Enum Tests (Unit)

```
[ ] No LazilyRefreshDatabase (no DB needed)
[ ] Entity: test every business question method
[ ] DTO: test fromArray()/toArray() roundtrip
[ ] Enum: test every case has non-empty label()
[ ] Enum (StatusEnum): test validTransitions() for every case
[ ] Enum (StatusEnum): test isTerminal() for terminal states
```

## Mocking Rules

| Scenario        | Use                              | Never Use                     |
| --------------- | -------------------------------- | ----------------------------- |
| Eloquent models | Factories + real DB              | `Mockery::mock(Model::class)` |
| External HTTP   | `Http::fake()`                   | Real HTTP calls               |
| File system     | `Storage::fake()`                | `File::shouldReceive()`       |
| Queue           | `Queue::fake()`                  | Real queue worker             |
| Notifications   | `Notification::fake()`           | Real mail sending             |
| Events          | `Event::fake([Specific::class])` | `Mockery::spy()`              |
| Cache           | `Cache::fake()`                  | `Cache::shouldReceive()`      |
| Auth            | `actingAs($user)`                | Auth facade mock              |
| Cookies         | `Cookie::fake()`                 | `Cookie::shouldReceive()`     |

**Rule of thumb:** If you're using `shouldReceive()`, you're probably doing it wrong. Prefer
`fake()` methods which are scoped to the test and don't leak between tests.

## Coverage Targets

| Layer               | Target |
| ------------------- | ------ |
| Entities            | 100%   |
| Enums               | 100%   |
| DTOs                | 100%   |
| Command Actions     | >= 90% |
| Read Actions        | >= 80% |
| Livewire components | >= 80% |
| Policies            | 100%   |
