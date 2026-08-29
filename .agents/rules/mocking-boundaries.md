# Mocking Boundaries — Fake at the Edge, Never in the Core

## Intent

Faking is reserved for the boundaries where a test must not touch the real world: HTTP, the file
system, the queue, notifications, events, and the cache. Everything inside the application — Eloquent
models, the database, the framework services under test — runs real. This rule fixes exactly where
fakes are allowed and what a `shouldReceive()` on an internal collaborator means.

## Rationale

The purpose of a fake is to isolate the code under test from *side effects that are slow, external,
or non-deterministic* — not to avoid running the code you are actually testing.

- **Mocking Eloquent proves nothing.** A `Mockery::mock(Model::class)` replaces the exact code whose
  query, scope, cast, or constraint the test is meant to verify. Green tests for mocked models pass
  even when the real model is broken.
- **Real database is the cheapest truth.** With `LazilyRefreshDatabase`, factories, and
  `assertModelExists()`, a feature test against the real database is fast *and* verifies the actual
  persistence layer.
- **`fake()` is the blessed primitive.** Laravel's `*::fake()` swaps the driver with an in-memory
  implementation that records calls and supports assertions. It is deterministic, zero-network, and
  far less code than hand-rolled mocks.
- **`shouldReceive()` is a smell.** It usually appears where someone is stubbing an internal
  collaborator (a child Action, a repository, a model) to "unit test" wiring. That test verifies the
  mock contract, not behavior — and it is brittle: any refactor inside the collaborator breaks the
  expectation.

## How to Apply — The Boundary Table

| Boundary      | Approach                         | Never |
|---------------|----------------------------------|-------|
| Eloquent / DB | Factories + real database        | `Mockery::mock(Model::class)` |
| External HTTP | `Http::fake()`                   | Real network calls |
| File system   | `Storage::fake()`                | `File::shouldReceive()` |
| Queue         | `Queue::fake()`                  | A real queue worker in tests |
| Notifications | `Notification::fake()`           | Real mail delivery |
| Events        | `Event::fake([Specific::class])` | `Mockery::spy()` on listeners |
| Cache         | `Cache::fake()`                  | Real cache server |
| Auth          | `actingAs($user)`                | Stubbing the auth guard |

Two placement rules:

1. **Fake after arranging.** Set up factories *first*, then fake events — faking before factory
   setup silences real model events that listeners depend on.
2. **Fake narrowly.** `Event::fake([OrderCreated::class])` fakes one event, not all. Broad fakes
   hide the real dispatch behavior you may be asserting against.

## Examples

```php
test("{SpecID}-{ReqID}: CreateAssignment dispatches AssignmentCreated", function () {
    $assignment = Assignment::factory()->create();          // arrange first
    Event::fake([AssignmentCreated::class]);                // then fake narrowly

    app(CreateAssignmentAction::class)->execute(...);

    Event::assertDispatched(AssignmentCreated::class);
});

test("{SpecID}-{ReqID}: SubmitAssignment uploads via media library", function () {
    Storage::fake('public');

    $result = app(SubmitAssignmentAction::class)->execute(...);

    Storage::disk('public')->assertExists("assignments/{$result->data->file_path}");
});
```

## Anti-Patterns & Pitfalls

- **`shouldReceive()` on any internal class** — models, child Actions, repositories, services. If
  you are stubbing code you wrote, the test is testing the wiring, not the requirement.
- **`Mockery::mock(Model::class)`** — replaces the persistence under test.
- **`Event::fake()` positioned before factory setup** — real created-events are swallowed, and
  listeners depending on them never register.
- **Faking everything "just in case"** — a test with a fake for every collaborator verifies nothing
  real (see `rules/layer-test-patterns.md`).
- **Real side effects leaking** — a test that actually hits HTTP, writes to disk, or enqueues real
  jobs is slow, flaky, and environment-dependent.

## Verification & Detection

- Grep test files for `shouldReceive(` — each hit is a review flag.
- Grep for `Mockery::mock(` targeting a Model — each is a violation.
- Confirm every external boundary uses its `*::fake()` counterpart, and that `Event::fake([...])`
  appears *after* factory setup in each test.

```bash
rg -n "shouldReceive|Mockery::mock" tests/ || echo "clean"
```
