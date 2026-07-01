# Events & Notifications

## What It Enforces

Events are registered manually in `config/event.php` — NOT auto-discovered. Only create an event
when a listener needs to react asynchronously (cache invalidation, cross-module notification).
Simple CRUD operations that are logged via `$this->log()` do NOT need events.

Queued listeners use `ShouldQueue`. Notifications implement `ShouldQueue` by default. Events use
`ShouldDispatchAfterCommit` inside transactions. On-demand notifications replace dummy models.

## Why It Matters

Over-engineering events for every Action creates dead code — event classes without listeners are
noise. Events should only exist when there's a clear async consumer. `$this->log()` provides
sufficient audit trail for most mutations. Manual registration in `config/event.php` makes the
event→listener mapping explicit and auditable.

## When It Applies

- Events: registered in `config/event.php`, dispatched from Actions only when a listener exists
- Listeners: implement `ShouldQueue` for side effects, registered alongside events in `config/event.php`
- Notifications: implement `ShouldQueue` + `Queueable`, queue via `viaQueues()` for channel-specific
  queues
- On-demand: `Notification::route('mail', 'email')->route('slack', '#channel')->notify(...)`
- Caching: run `php artisan event:cache` in production
- **Rule of thumb:** If there's no listener, there's no event. Skip it.

Multi-channel notifications route through mail, broadcast, and database channels. Use
`afterCommit()` on individual notifications when the notification is sent inside a transaction.

Exceptions: Synchronous listeners are acceptable for operations that must complete before the
response is sent (e.g., updating a cached value).
