# Testing

## Testing Philosophy

The test suite is organized by domain and by test type. Every change to the
codebase must be accompanied by tests that verify the change works correctly
and does not break existing behavior. Tests are a specification of what the
code does — reading the tests should tell a developer what the system's
behavioral contract is.

## Feature vs Unit vs Arch Test Distinction

Feature tests test complete workflows from end to end. They hit HTTP routes,
submit forms, interact with Livewire components, and verify database state,
redirects, and response content. Feature tests use the full application boot
and a real (in-memory SQLite) database. They answer the question: "does this
workflow produce the correct outcome when used as a real user would?"

Unit tests test isolated pieces of logic in isolation. They test Actions,
Entities, and pure utility classes without the need for a database, HTTP
request, or full application boot. Unit tests run fast because they have no
dependencies. They answer the question: "does this specific function return
the correct value given these inputs?"

Arch tests enforce structural rules about the codebase. They verify that
classes in certain directories extend the correct base classes, that domains
do not import from other domains directly, that facades are not used in
domain code, and that all files declare strict types. Arch tests are written
once and prevent architectural drift as the codebase grows. They answer the
question: "is the codebase still organized the way we intended?"

## When to Use Each

Use a unit test for a pure business rule: an Entity method that determines
whether a status transition is allowed, an Action that computes a score, a
Support class that formats data. No database needed, no HTTP needed.

Use a feature test for a user-visible workflow: registering a user,
submitting an assignment, approving an internship. The test creates any
necessary records, performs the action, and verifies the outcome in the
database, response, and session.

Use an arch test for structural invariants: "all Entities are final readonly
classes," "all Actions have a single execute() method," "Livewire components
do not use facades."

## What LazilyRefreshDatabase Does

`LazilyRefreshDatabase` is a testing trait that defers database migration
until the first query hits the database, rather than migrating before every
test. This speeds up the test suite dramatically because tests that do not
touch the database — pure logic tests, validation tests, early-return tests
— skip migration entirely. When a test does perform a database operation, the
trait migrates once for that test class, then wraps each test in a
transaction that is rolled back after the test completes.

This is distinct from `RefreshDatabase`, which migrates the database fresh
for every test. LazilyRefreshDatabase achieves the same isolation (each test
starts with a clean database) with less overhead.

## How Entity Testing Works Without a Database

Entities are `final readonly` classes with zero framework dependencies. They
do not extend Eloquent, do not use facades, and do not access the database.
Testing them is a matter of constructing an instance with given values and
asserting that its methods return the expected results. For example, an
AccountStatus enum can be tested by verifying that `SUSPENDED->allowsLogin()`
returns false, that status transitions follow the defined state machine, and
that terminal statuses cannot transition further.

This makes Entity tests the fastest and most reliable tests in the suite.
They have no setup, no teardown, no dependencies. They are pure PHP function
tests.

## Where to Find It

Tests are organized in `tests/Feature/{Domain}/`, `tests/Unit/{Domain}/`,
and `tests/Arch/`. The base `TestCase` is at `tests/TestCase.php`. Pest
global configuration is at `tests/Pest.php`. Composer scripts for running
tests are in `composer.json` under the `scripts` section.
