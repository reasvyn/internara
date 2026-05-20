# Side Effects

## What It Enforces

All side effects — audit logging, domain events, notifications, cache invalidation — belong inside the Action. Livewire components and Controllers must never emit side effects directly. Flash messages are the deliberate exception because they are a UI concern, not a business operation.

## Why It Matters

Side effects are part of the business operation, not the UI. Placing them in the Action keeps them:
- Atomic with the database change (transaction rollback prevents inconsistent state)
- Testable as part of the operation (test the Action, get all effects)
- Consistent across all callers (Livewire, API, Artisan command all behave identically)

## When It Applies

Every Action that performs mutations must handle its side effects:
- Create/Update/Delete: log via BaseAction's `$this->log()`
- State transitions affecting other domains: dispatch domain events
- User notifications: queue (never send synchronously)
- Cache invalidation: clear affected cache keys

Exceptions: Flash messages belong in the component. Read-only operations (reports, exports) have no side effects unless audit logging is required.
