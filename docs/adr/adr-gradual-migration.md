# Gradual Migration

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Architecture Overview](../architecture.md) § Migration Paths |

## Context and Problem Statement

The codebase aspires to several ideals — typed DTOs for all Action inputs, module events for
state changes, event-driven cache invalidation, shared validation in Entities, and architecture
tests enforcing boundaries. Imposing all ideals from day one creates friction: DTOs demand a
class before any business logic, events require an event/listener pair and queuing decision, and
failing arch tests slow iteration during boundary exploration. The answer is neither "all ideals
now" nor "no ideals ever" but a path where each pattern can be adopted incrementally.

**Decision Drivers:**

* Ship features first; migrate patterns when they provide tangible value
* Backward-compatible intermediate steps that do not break existing callers
* Explicit deferral — document when a pattern is needed, not before

## Considered Options

* **Enforce all ideals from day one** — *Pros:* consistency. *Cons:* ceremony blocks velocity,
  discourages event creation and typing.*
* **No ideals, pragmatism only** — *Pros:* fastest start. *Cons:* drift accumulates silently; no
  documented path toward the intended architecture.*
* **Gradual three-phase migration (chosen)** — Start → Stabilize → Final per pattern, with a
  governing principle of good-enough-today. *Pros:* velocity and direction co-exist.*

## Decision Outcome

**Chosen option: Gradual three-phase migration** — every developer ships first and migrates
later. Each pattern's trigger is explicit.

**DTOs for Action Inputs:**

| Phase | Convention | When |
|-------|------------|------|
| Start | `execute(array $data)` | Input shape still changing |
| Stabilize | `execute(Data\|array $data)` | Union type; `fromArray()` keeps callers working |
| Final | `execute(Data $data)` | Shape settled; DTO is the only contract |

**Module Events for Side Effects:**

| Phase | Convention | When |
|-------|------------|------|
| Start | Inline in Action | First implementation |
| Stabilize | Event + listener created | Second side effect or second listener needed |
| Final | All side effects in listeners | Action test must verify state without side effects |

**Event-Driven Cache Invalidation:**

| Phase | Convention | When |
|-------|------------|------|
| Start | `Cache::forget()` inline | Quick fix |
| Stabilize | Event dispatches, listener flushes | Multiple events affect same key |
| Final | `config/cache-keys.php` registry, listener-driven | Full cross-module invalidation |

**Shared Validation in Entities:**

| Phase | Convention | When |
|-------|------------|------|
| Start | Rules only in Form Object | Co-located with UI |
| Stabilize | `Entity::rules()` referenced by both | Same entity from two forms |
| Final | All rules centralized in Entities | Full DRY across UI layers |

**Architecture Tests:**

| Phase | Convention | When |
|-------|------------|------|
| Start | Code review only | Rapid exploration |
| Stabilize | Critical boundary tests restored | Module structure stabilizes |
| Final | Full suite (naming, conventions, deps) | v1.0 release |

*Note:* architecture tests were removed due to a `pest-plugin-arch` bug; restoration planned
when the plugin stabilizes.

**Governing Principle:** *Good enough today is better than perfect next week.* No developer
should hesitate to write an Action because a DTO is not yet defined — ship the array version,
migrate when the input stabilizes.

### Positive Consequences

* Velocity not blocked by ceremony; each pattern has a clear, documented migration trigger
* Early code stays simple; patterns surface only when valuable
* Stabilize phase is backward-compatible (union types)

### Negative Consequences

* Mixed phases during migration (some Actions use DTOs, some arrays) — expected and temporary
* Without enforcement, some areas may stall at Phase 1 — requires periodic architecture review

## Links

* [Architecture Overview](../architecture.md) — triad, validation, caching, and migration paths
* [Actions Guide](../guides/arch/action-pattern.md) — triad contracts per phase
* [Modular Pattern](../guides/arch/modular-pattern.md) — module boundaries that tests will enforce
