# Testing New Features

## What It Enforces

Tests follow module-first structure: `tests/{Suite}/{Module}/{Name}Test.php`. Entity tests need no
database. Action and Livewire tests use `RefreshDatabase` or `LazilyRefreshDatabase`. All tests use
Pest v4.

## Why It Matters

Testing at the right level reduces feedback time. Entity tests run in milliseconds without a
database. Action tests verify the full orchestration with a database. Livewire tests validate the UI
integration. This layered approach means business rules are tested quickly and thoroughly, while
integration tests confirm everything works together.

## When It Applies

Every new feature must have tests at the appropriate level:

- DTO: test construction, `fromArray()` hydration, `toArray()` serialization, `merge()` immutability
- Entity: test business rules directly — instantiate the Entity, call methods, assert booleans
- Action: test with DTO input, assert `ActionResponse` properties, verify persistence and side effects
- Livewire: test component interactions — build DTO, call Action via method injection, assert state changes

Test recommendations:

- `LazilyRefreshDatabase` over `RefreshDatabase` for speed
- `assertModelExists()` over `assertDatabaseHas()` for clarity
- Factory states over manual model creation
- `Event::fake()` after factory setup (not before)
- `Exceptions::fake()` to assert exception reporting
- `Http::preventStrayRequests()` to catch unexpected HTTP calls
- `Mail::assertQueued()` over `assertSent()`

Exceptions: Trivial views or read-only pages may not need dedicated tests if the underlying Actions
are already tested.
