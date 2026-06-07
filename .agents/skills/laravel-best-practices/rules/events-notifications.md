# Events & Notifications

## What It Enforces

Events are auto-discovered (no manual EventServiceProvider registration). Queued listeners use
`ShouldQueue`. Notifications implement `ShouldQueue` by default. Events use
`ShouldDispatchAfterCommit` inside transactions. On-demand notifications replace dummy models.

## Why It Matters

Event discovery reduces boilerplate — Laravel scans listener `handle()` type-hints to wire events
automatically. `ShouldDispatchAfterCommit` prevents queued listeners from processing before the
triggering transaction commits, avoiding reads of non-existent data. On-demand notifications
eliminate the need to create fake notifiable models just to send a notification.

## When It Applies

- Events: auto-discovered, dispatched from Actions, implement `ShouldDispatchAfterCommit` when
  inside transactions
- Listeners: implement `ShouldQueue` for side effects, auto-discovered by `handle()` type-hint
- Notifications: implement `ShouldQueue` + `Queueable`, queue via `viaQueues()` for channel-specific
  queues
- On-demand: `Notification::route('mail', 'email')->route('slack', '#channel')->notify(...)`
- Caching: run `php artisan event:cache` in production

Multi-channel notifications route through mail, broadcast, and database channels. Use
`afterCommit()` on individual notifications when the notification is sent inside a transaction.

Exceptions: Synchronous listeners are acceptable for operations that must complete before the
response is sent (e.g., updating a cached value).
