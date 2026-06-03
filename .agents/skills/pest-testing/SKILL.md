# Pest Testing Skill

## When to Activate

Apply this skill whenever writing, editing, or fixing tests for this project. Activate for all testing tasks — feature tests, unit tests, architecture tests, and Livewire component tests. Do not use for factories, seeders, migrations, or non-test PHP code.

## Core Principles

### Domain-First Test Structure

Tests mirror the application's aggregate-based domain structure: `tests/Feature/{Domain}/{Aggregate}/{Name}Test.php` for integration tests and `tests/Unit/{Domain}/{Aggregate}/{Name}Test.php` for unit tests. Small value objects and flat enums go in `tests/Unit/{Domain}/Types/{Name}Test.php`. Tests are created with `php artisan make:test --pest {Name}Test` (without `Feature/` or `Unit/` prefix in the name argument).

### Testing Level Separation

Each layer has a distinct testing strategy:
- Entity tests instantiate pure PHP objects directly — no database needed, no RefreshDatabase trait. They test business rules in isolation.
- Action tests resolve the Action from the container, call execute(), and assert on returned models or exceptions. They use `LazilyRefreshDatabase` and `assertModelExists()`.
- Livewire tests use `Livewire::test()` to simulate component interactions. They assert state changes, validation errors, and dispatched events.

### Performance Preferences

`LazilyRefreshDatabase` over `RefreshDatabase` (skips migration replay if schema is current). `assertModelExists()` over `assertDatabaseHas()` (clearer intent). Factory states and sequences over manual model creation. Fakes after factory setup (not before — UUID generation events must not be silenced).

## Architecture Tests

Structural rules (all Entities are `final readonly` and extend BaseEntity, all Actions have an `execute()` method, all Models extend BaseModel) are enforced through code review and PHPStan analysis.

## Verification Before Finalizing

- Are tests in the correct directory (Feature vs Unit, right Domain, right Aggregate)?
- Do Entity tests avoid RefreshDatabase entirely?
- Are Action tests using LazilyRefreshDatabase?
- Is `assertModelExists()` preferred over `assertDatabaseHas()`?
- Are `Event::fake()` and `Http::fake()` positioned correctly (after factory setup)?
- Are `Mail::assertQueued()` used instead of `assertSent()`?
