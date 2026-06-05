# Notifications & Events

## What It Enforces

Flash messages use PHPFlasher (`flash()->success()`) — never maryUI Toast methods. Notifications implement `ShouldQueue` and use `Queueable`. Module events are dispatched from Actions, never from Livewire components.

## Why It Matters

PHPFlasher provides consistent, styled flash messages across the application. Queuing notifications prevents user-facing requests from waiting on email delivery or broadcast. Dispatching events from Actions ensures side effects are atomic with the operation that triggered them.

## When It Applies

Every notification should:
- Implement `ShouldQueue` for async delivery
- Route through channels: mail, broadcast, and database (CustomDatabaseChannel)
- Use public constructor promotion (not private/protected) for notification parameters

Every event should:
- Be dispatched from the Action's transaction, not from the Livewire component
- Use public readonly properties for event data
- Use `ShouldDispatchAfterCommit` when inside a transaction to prevent listeners from reading uncommitted data

Flash messages:
- `flash()->success(__('module.created'))` for success
- `flash()->error(__('module.cannot_delete'))` for errors
- `flash()->warning(__('common.no_records_selected'))` for warnings

Exceptions: None. These are universal conventions.
