# Entity Purity

## What It Enforces

Entities are `final readonly` classes with zero framework dependencies. No Eloquent, no Facades, no
Service Container references. The only allowed framework import is
`Illuminate\Database\Eloquent\Model` — and only in the `fromModel()` factory method. All state is
passed in through the constructor as typed properties.

## Why It Matters

Framework dependencies make classes harder to test (they require mocking), harder to reason about
(hidden side effects via Facades), and harder to reuse across contexts. A pure PHP Entity can be
instantiated in a unit test with no setup, no database, and no service container. Its behavior is
fully determined by its constructor arguments.

The `final readonly` constraint enforces immutability. An Entity's state is set once at construction
and never changes. This eliminates an entire class of bugs (accidental mutation) and makes the
business rules predictable: given the same state, an Entity method always returns the same answer.

## When It Applies

Always when creating Entity classes. The factory method `fromModel(Model): static` is the only
bridge between the framework world and the pure module world. It extracts only the values the Entity
needs from the Model — never passes the whole Model to the Entity.

Entity methods return business answers, not raw data: `canLogin()`, `isTerminal()`,
`requiresAction()`, `canTransitionTo()`, `canBeDeleted()`. Simple getters for Entity-owned state
(like `status(): AccountStatus`) are acceptable.

Exceptions: The `fromModel()` method may import `Illuminate\Database\Eloquent\Model`. That is the
only exception.
