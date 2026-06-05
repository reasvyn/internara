# Testing

## What It Enforces

Tests use Pest v4 with module-first structure. `LazilyRefreshDatabase` over `RefreshDatabase` for speed. `assertModelExists()` over `assertDatabaseHas()` for clarity. Factory states and sequences over manual model creation. `Event::fake()` and `Http::fake()` after factory setup.

## Why It Matters

`LazilyRefreshDatabase` skips migration replay if the schema is current, saving significant time in test suites. Model assertions are clearer than raw database assertions — `assertModelExists($model)` says exactly what it checks. Factory states encapsulate common model setup patterns, reducing duplication.

## When It Applies

- Entity tests: direct instantiation, no database, no RefreshDatabase
- Action tests: resolve from container, call execute, assert result or exception
- Livewire tests: simulate interactions with `Livewire::test()`, assert state changes
- Architecture tests: enforce structural rules via `arch()` expectations

Best practices:
- `Event::fake()` after factory creation (UUID events need to fire)
- `Exceptions::fake()` to assert exception reporting
- `Http::preventStrayRequests()` + `Http::fake()` for HTTP client tests
- `Mail::assertQueued()` over `assertSent()` for queued mailables
- `throws(RejectedException::class)` for Action tests that expect rejection
- Datasets for repetitive validation rule testing
- `recycle()` to share relationship instances across factories

Exceptions: `RefreshDatabase` may be needed if `LazilyRefreshDatabase` causes issues with specific test scenarios.
