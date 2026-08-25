# Eloquent Observers for Model-Level Side Effects

> **Last updated:** 2026-08-25 **Changes:** rewrite to MADR-lite industry-standard format

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Event Pattern](../guides/arch/event-pattern.md) and [Event System Spec](../specs/NUCY3-event-system.md) |

## Context and Problem Statement

Internara has two mechanisms for reacting to model changes: decoupled Events + Listeners
(cross-module, fire-and-forget, queueable) and Eloquent Observers (single-model, synchronous).
Most side effects use events (49 events, 20 listeners across 13 modules), but three use cases
require tighter coupling: cache invalidation must complete before the response (events are
deferred until after commit), snapshots must capture state at the exact status-change moment
(event payload may be stale), and deletion guards must prevent the delete before it proceeds
(impossible with deferred events).

**Decision Drivers:**

* Synchronous completion before HTTP response for correctness
* Same-transaction semantics — rollback must undo the side effect for mutations
* Single-model scope — no cross-module fan-out

## Considered Options

* **Events + Listeners for everything** — *Pros:* uniform, decoupled. *Cons:* cannot guarantee
  synchronous cache invalidation, snapshot timing, or deletion prevention.*
* **Observers for qualifying single-model side effects, Events otherwise (chosen)** —
  *Pros:* synchronous guarantees where needed; decoupled elsewhere. *Cons:* tighter coupling
  where Observers are used.*

## Decision Outcome

**Chosen option: Observers for qualifying single-model side effects** — use Eloquent Observers
when ALL three criteria hold; otherwise use Events + Listeners.

**Criteria:**

1. **Same-module only** — observer and model in the same module
2. **Synchronous required** — must complete before the response
3. **Single-model scope** — reacts only to this model's lifecycle

**Decision Framework:**

| Criterion | Observer | Event + Listener |
|-----------|----------|------------------|
| Coupling | Same model | Cross-module OK |
| Timing | Synchronous | Deferred (after commit) |
| Queuing | No | Yes (`ShouldQueue`) |
| Rollback | Rolls back with model | Discarded on rollback |
| Use case | Cache invalidation, guards, snapshots | Notifications, cross-cache, logging |

**Current Observers:**

| Observer | Model | Hook | Purpose |
|----------|-------|------|---------|
| `ReportObserver` | `Report` | `saved()` | Snapshot when status = FINALIZED |
| `SettingObserver` | `Setting` | `created/updated/deleted` | Invalidates per-key/group/global cache |
| `UserObserver` | `User` | `deleting()` | Prevents superadmin deletion via RejectedException |

### Positive Consequences

* Synchronous guarantees — cache invalidation completes before next request can see stale data
* Simplicity — no event/listener registration plumbing
* Transaction safety — observer rolls back with the model operation for mutations

### Negative Consequences

* Tight coupling to one model — later multi-model needs require refactor to event listener
* Auto-registered via `booted()` — tests must explicitly disable when side effects unwanted
* No async — long-running logic would block the request; observers must stay fast

## Links

* [Event Pattern](../guides/arch/event-pattern.md) — alternative mechanism and conventions
* [Event System Spec](../specs/NUCY3-event-system.md) — full event catalog
* [Actions Guide](../guides/arch/action-pattern.md) — where events are dispatched from
