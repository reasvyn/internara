# Mail

## What It Enforces

All mailables implement `ShouldQueue` to make queueing the default. `afterCommit()` prevents queued
mail from processing before the transaction commits. Tests use `Mail::assertQueued()` over
`assertSent()`. Markdown templates for transactional emails. Dedicated queues per notification
channel.

## Why It Matters

Sending email synchronously blocks the response until the SMTP conversation completes — potentially
seconds of latency. Implementing `ShouldQueue` on a mailable makes it queued by default regardless
of how it's dispatched. `afterCommit()` prevents the queued mail from being processed before the DB
transaction commits, avoiding reads of non-existent data.

## When It Applies

Every mailable and notification with a mail channel should implement `ShouldQueue` + `Queueable`.
Use `afterCommit()` when dispatching inside a transaction. Use `Mail::assertQueued()` in tests (not
`assertSent()`) since the mailable is queued.

Generate markdown mailables:
`php artisan make:mail OrderConfirmation --markdown=emails.order-confirmation`. Use `viaQueues()` to
route specific channels to dedicated queue connections.

Content tests instantiate the mailable directly and assert on the rendered output. Sending tests use
`Mail::fake()` and assert the mailable was queued.

Exceptions: Immediate, critical emails (password reset) may send synchronously, but using queues
with a low-latency connection (e.g., `sync` driver in development) is preferred.
