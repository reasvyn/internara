# Testing Separation

## What It Enforces

Model tests require a database (RefreshDatabase). Entity tests do not. These are kept in separate
directories: `tests/Unit/{Module}/Entities/` for Entity tests (pure PHP, no DB), and
`tests/Feature/{Module}/` or `tests/Unit/{Module}/Models/` for tests that need a database.

## Why It Matters

Entity tests that don't need a database are faster, simpler, and more reliable. Instantiating
`new AcademicYearState(isActive: true, hasRelatedRecords: false)` and calling `canBeDeleted()` is a
pure function call — no database setup, no migrations, no mocking. A test that runs in milliseconds
encourages more tests and faster feedback.

The separation also clarifies what each test covers. An Entity test tests business rules in
isolation. A Model test tests data access. An Action test tests orchestration. The distinction makes
it obvious where to add a test for a given failure.

## When It Applies

Always. When testing business rules, create Entity tests without RefreshDatabase. When testing data
access (relationships, scopes, casts), create Model tests with RefreshDatabase.

The test locations and their database requirements:

- `tests/Unit/{Module}/Entities/{Name}Test.php`: Business rules, state transitions, capability
  checks — NO DB needed
- `tests/Unit/{Module}/Models/{Name}Test.php`: Relationships, scopes, casts, accessors — DB needed
- `tests/Feature/{Module}/{Action}Test.php`: Action orchestration, validation, side effects — DB
  needed
- `tests/Feature/{Module}/{Component}Test.php`: Full Livewire component integration — DB needed

Exceptions: If an Entity method somehow depends on a service that requires the container (which
should not happen with pure Entities), you may need to reconsider the Entity's design rather than
add a database dependency to the test.
