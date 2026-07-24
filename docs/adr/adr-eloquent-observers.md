# ADR-015: Eloquent Observers for Model-Level Side Effects

> **Last updated:** 2026-07-23 **Changes:** feat — initial ADR for observer pattern

## Description

Eloquent Observers handle model-level side effects that are tightly coupled to a single model's
lifecycle — cache invalidation, snapshot capture, and deletion guards. They run synchronously
within the same transaction as the model operation.

## Context

Internara has two mechanisms for reacting to model changes:

1. **Events + Listeners** — decoupled, cross-module, fire-and-forget, can be queued
2. **Eloquent Observers** — coupled to a single model, same-module, synchronous

Most side effects in the system use events (49 events, 20 listeners across 13 modules). However,
three use cases require tighter coupling than events provide:

- **Cache invalidation** must happen synchronously before the response returns, not after
  transaction commit (events are deferred).
- **Data snapshots** must capture the model state at the exact moment of a status change, not
  after the transaction commits (event payload may be stale).
- **Deletion guards** must prevent the model from being deleted before the operation proceeds,
  which is impossible with deferred events.

## Decision

Use Eloquent Observers for model-level side effects that meet ALL of the following criteria:

1. **Same-module only** — the observer and its model live in the same module
2. **Synchronous required** — the side effect must complete before the HTTP response
3. **Single-model scope** — the side effect reacts only to this model's lifecycle, not
   cross-module data

For all other side effects (cross-module, async, fire-and-forget), use the Event + Listener
pattern.

### Decision Framework

| Criterion | Observer | Event + Listener |
|-----------|----------|-----------------|
| Coupling | Same model | Cross-module OK |
| Timing | Synchronous | Deferred (after commit) |
| Queuing | No | Yes (`ShouldQueue`) |
| Rollback behavior | Rolls back with model | Discarded on rollback |
| Use case | Cache invalidation, guards, snapshots | Notifications, cross-cache, logging |

## Consequences

### Positive

- **Synchronous guarantees:** Cache invalidation completes before the response, preventing stale
  reads on next request.
- **Simplicity:** No config/event.php registration, no listener class, no event class — just
  an observer with hook methods.
- **Transaction safety:** Observer runs inside the same DB transaction. If the model operation
  rolls back, the observer side effect also rolls back (for mutations).

### Negative

- **Tight coupling:** Observer is bound to one model. If the side effect later needs to react
  to other models' changes, the observer must be refactored to an event listener.
- **Test isolation:** Observers auto-register via model `booted()` method. Tests that don't
  want observer side effects must explicitly disable them.
- **No async option:** Long-running observer logic (e.g., external API calls) would block the
  request. Observers must be fast.

## Current Observers

| Observer | Model | Hook | Purpose |
|----------|-------|------|---------|
| `ReportObserver` | `Report` | `saved()` | Captures snapshot when status = FINALIZED |
| `SettingObserver` | `Setting` | `created/updated/deleted` | Invalidates per-key, per-group, and global cache |
| `UserObserver` | `User` | `deleting()` | Prevents superadmin deletion via `RejectedException` |

## References

- `app/Reports/Report/Observers/ReportObserver.php` — Snapshot observer
- `app/Settings/Observers/SettingObserver.php` — Cache invalidation observer
- `app/User/Observers/UserObserver.php` — Deletion guard observer
- `docs/architecture/event-pattern.md` — Event + Listener pattern (alternative)
- `docs/specs/event-system.md` — Event system specification
- `docs/specs/core-foundation.md` — Base classes and contracts
