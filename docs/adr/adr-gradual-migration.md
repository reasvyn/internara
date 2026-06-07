# Gradual Migration / Optional Complexity

> Last updated: 2026-05-27 Changes: docs: comprehensive infrastructure, architecture, and
> conventions overhaul

## Status

Accepted

## Context

The Internara codebase aspires to several architectural ideals — typed DTOs for all Action inputs,
module events for every significant state change, event-driven cache invalidation, shared validation
rules in Entities, and architecture tests enforcing boundaries.

However, imposing all ideals from day one creates friction that slows development:

- **Typed DTOs** require defining a class (with constructor, properties, `fromArray()`) for every
  Action input — significant boilerplate before any business logic is written.
- **Module events** require defining an event class, a listener class, registering the listener, and
  deciding whether to queue it — overhead that discourages creating events for genuinely important
  state changes.
- **Event-driven cache invalidation** requires a dedicated listener class for every cache key —
  before cache invalidation is even needed.
- **Architecture tests** that fail on every build slow down iteration when boundaries are still
  being explored.

The alternative — never adopting these patterns — leads to the problems the ideals solve: untyped
Action inputs that are hard to reason about, side effects scattered across Actions instead of in
listeners, and cache stale bugs.

The solution is not "all ideals now" or "no ideals ever." The solution is a **gradual migration
path** where each pattern can be adopted incrementally as the codebase matures and as specific pain
points emerge.

## Decision

The following patterns are OPTIONAL during initial development and EARLY stages of a feature. They
become RECOMMENDED as the feature stabilizes, and eventually REQUIRED for all new code.

### Pattern: Typed DTOs for Action Inputs

| Phase         | Convention             | When                                                    |
| ------------- | ---------------------- | ------------------------------------------------------- | ------------------------------------------------ |
| **Start**     | `execute(array $data)` | First iteration while the input shape is still changing |
| **Stabilize** | `execute(Data          | array $data)`                                           | Action accepts both DTO and array via union type |
| **Final**     | `execute(Data $data)`  | Input shape is settled, DTO is the only contract        |

The `Data` base class supports `Data::fromArray()` so that consumers passing arrays continue to work
during migration:

```php
// Phase 2 — both work
public function execute(CreateInternshipData|array $data): Internship
{
    if (is_array($data)) {
        $data = CreateInternshipData::from($data);
    }
    // ... use $data->name, $data->startDate instead of $data['name']
}
```

**When to migrate:** When an Action's input grows beyond 3 parameters, or when the Action is called
from multiple places and the input contract needs to be enforced.

### Pattern: Module Events for Side Effects

| Phase         | Convention                                               | When                                                                                                   |
| ------------- | -------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| **Start**     | Side effects inline in the Action                        | First implementation — notification, log, cache flush all in one method                                |
| **Stabilize** | Event dispatched, listener created                       | When a second side effect needs to be added, or when another listener needs to react to the same event |
| **Final**     | All side effects in listeners, Action only mutates state | When the Action's test needs to verify state changes without triggering side effects                   |

```php
// Phase 1 — side effects inline
public function execute(array $data): Internship
{
    return $this->transaction(function () use ($data) {
        $internship = Internship::create($data);
        $this->log('internship_created', $internship);
        Notification::send($admins, ...); // inline side effect
        return $internship;
    });
}

// Phase 3 — Action dispatches event, listeners handle side effects
public function execute(CreateInternshipData $data): Internship
{
    return $this->transaction(function () use ($data) {
        $internship = Internship::create($data->toArray());
        $this->log('internship_created', $internship);
        event(new InternshipCreated($internship));
        return $internship;
    });
}
```

**When to migrate:** When the Action grows beyond one side effect, or when a second listener needs
to react to the same event.

### Pattern: Event-Driven Cache Invalidation

| Phase         | Convention                                                      | When                                                                                            |
| ------------- | --------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| **Start**     | `Cache::forget()` inline in the Action                          | Quick — "just make it work"                                                                     |
| **Stabilize** | Event dispatched, `CacheInvalidationListener` flushes keys      | When multiple events affect the same cache key, or when cache keys become shared across modules |
| **Final**     | Cache keys registered in `CacheKeys`, invalidated via listeners | Full event-driven invalidation across all modules                                               |

### Pattern: Shared Validation Rules in Entities

| Phase         | Convention                                                                       | When                                                    |
| ------------- | -------------------------------------------------------------------------------- | ------------------------------------------------------- |
| **Start**     | Validation rules in the Form Object only                                         | Quick — rules are co-located with the UI                |
| **Stabilize** | `Entity::rules()` static method, referenced by both Form Object and Form Request | When the same entity is edited from two different forms |
| **Final**     | All module validation rules centralized in Entities                              | Full DRY validation across all UI layers                |

### Pattern: Architecture Tests

| Phase         | Convention                                                             | When                                     |
| ------------- | ---------------------------------------------------------------------- | ---------------------------------------- |
| **Start**     | No architecture tests — boundaries enforced by code review             | During rapid exploration and prototyping |
| **Stabilize** | Critical boundary tests restored (module boundaries, layer separation) | When the module structure stabilizes     |
| **Final**     | Full architecture test suite (naming, conventions, dependency rules)   | When the codebase reaches v1.0           |

Note: Architecture tests were previously implemented via `pest-plugin-arch` but removed due to a
compatibility bug. Restoration is planned when the plugin stabilizes.

## Governing Principle

**Good enough today is better than perfect next week.** Every pattern has a clear migration path. No
developer should hesitate to write an Action because they need to define a DTO first. Write the
array-based version, ship the feature, and migrate when the input stabilizes.

## Consequences

- **Positive**: Development velocity is not blocked by architectural ceremony. A developer can write
  `execute(array $data)`, ship, and migrate to a DTO later.
- **Positive**: Each pattern has a clear, documented migration path — no ambiguity about when or how
  to adopt it.
- **Positive**: Early-stage code is simple and pragmatic. Architectural patterns surface only when
  they provide tangible value (duplicated validation, multiple listeners, etc.).
- **Positive**: The migration paths are backward-compatible — Phase 2 code (union types, dual paths)
  works without breaking existing callers.
- **Negative**: Codebase will have a mix of "phases" during migration — some Actions use DTOs, some
  use arrays. This is expected and temporary.
- **Negative**: Developers must know which phase a given area of the codebase is in before extending
  it. Inconsistent patterns can be confusing without clear conventions.
- **Negative**: Without strict enforcement, some areas may never migrate past Phase 1. Periodic
  architecture reviews are needed to identify stagnated patterns.

## References

- `app/Core/Data/BaseData.php` — DTO base class with `fromArray()` support
- `docs/architecture.md` — Migration Paths section
- `docs/architecture.md` — Action Triad section
- `docs/architecture.md` — Validation Strategy section
- `docs/architecture.md` — Caching Strategy section
