# ADR-012: Gradual Migration / Optional Complexity

> **Status:** Accepted
> **Last updated:** 2026-06-10

## Context

The codebase aspires to several architectural ideals — typed DTOs for all Action inputs, module events for state changes, event-driven cache invalidation, shared validation rules in Entities, and architecture tests enforcing boundaries.

However, imposing all ideals from day one creates friction:

- **Typed DTOs** require a class with constructor, properties, and `fromArray()` for every Action input — significant boilerplate before any business logic.
- **Module events** require an event class, listener class, registration, and queuing decision — overhead that discourages creating events.
- **Event-driven cache invalidation** requires a listener class for every cache key before invalidation is even needed.
- **Architecture tests** that fail on every build slow iteration during boundary exploration.

The solution is not "all ideals now" or "no ideals ever." It is a **gradual migration path** where each pattern can be adopted incrementally.

## Decision

Each pattern follows a three-phase migration: Start, Stabilize, Final. Every developer should ship features first and migrate patterns later.

### DTOs for Action Inputs

| Phase | Convention | When |
|---|---|---|
| Start | `execute(array $data)` | First iteration — input shape still changing |
| Stabilize | `execute(Data\|array $data)` | Action accepts both via union type |
| Final | `execute(Data $data)` | Input shape settled, DTO is the only contract |

BaseData supports `fromArray()` so consumers passing arrays continue to work during migration.

### Module Events for Side Effects

| Phase | Convention | When |
|---|---|---|
| Start | Side effects inline in Action | First implementation |
| Stabilize | Event dispatched, listener created | Second side effect or second listener needed |
| Final | All side effects in listeners | Action test needs to verify state without triggering side effects |

### Event-Driven Cache Invalidation

| Phase | Convention | When |
|---|---|---|
| Start | `Cache::forget()` inline in Action | Quick — "just make it work" |
| Stabilize | Event dispatched, listener flushes keys | Multiple events affect same key |
| Final | `config/cache-keys.php` registry, listener-driven | Full cross-module invalidation |

### Shared Validation Rules in Entities

| Phase | Convention | When |
|---|---|---|
| Start | Rules in Form Object only | Quick — co-located with UI |
| Stabilize | Entity::rules() referenced by both | Same entity edited from two forms |
| Final | All module rules centralized in Entities | Full DRY across all UI layers |

### Architecture Tests

| Phase | Convention | When |
|---|---|---|
| Start | No tests — code review enforcement | Rapid exploration |
| Stabilize | Critical boundary tests restored | Module structure stabilizes |
| Final | Full test suite (naming, conventions, deps) | v1.0 release |

Note: Architecture tests were removed due to a `pest-plugin-arch` compatibility bug. Restoration planned when the plugin stabilizes.

### Governing Principle

**Good enough today is better than perfect next week.** Every pattern has a clear migration path. No developer should hesitate to write an Action because they need to define a DTO first. Write the array-based version, ship the feature, and migrate when the input stabilizes.

## Consequences

- **Positive**: Development velocity is not blocked by architectural ceremony. Ship first, migrate later.
- **Positive**: Each pattern has a clear, documented migration path — no ambiguity about when or how to adopt it.
- **Positive**: Early-stage code is simple and pragmatic. Patterns surface only when they provide tangible value.
- **Positive**: Migration paths are backward-compatible — Phase 2 code (union types) works without breaking existing callers.
- **Negative**: Codebase has a mix of phases during migration — some Actions use DTOs, some use arrays. Expected and temporary.
- **Negative**: Without strict enforcement, some areas may never migrate past Phase 1. Periodic architecture reviews are needed.

## References

- `app/Core/Data/BaseData.php` — DTO base class with fromArray() support
- `docs/architecture.md` — Migration Paths, Action Triad, Validation, Caching sections
