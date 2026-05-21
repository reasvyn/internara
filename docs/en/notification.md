# Notification System

## Channel Strategy

The application uses multiple notification channels because different
situations call for different delivery methods. A single notification can be
sent through multiple channels simultaneously — for example, a welcome
message is delivered as an email, an in-app notification, and a real-time
broadcast.

The in-app channel stores notifications in a custom `notifications` table.
This is the primary channel for all notifications. Users see them in a
notification center accessible from the top navigation bar. Notifications are
persistent — they remain in the database until read or cleaned up — and can
be marked as read individually or in bulk. This channel uses a custom
implementation rather than Laravel's built-in database channel because the
custom schema provides more control over notification types, titles,
messages, links, and additional payload data.

The mail channel is used for communications that need to reach the user
outside the application: welcome emails, account status changes, high-severity
incident reports. Mail is always queued for asynchronous delivery so it
never blocks the HTTP response.

The broadcast channel pushes notifications to the browser in real time using
Laravel Reverb (WebSocket). When a notification is sent, the user's browser
receives it immediately without polling. This is used to update the
notification bell badge and prepend new notifications to the list. If Reverb
is not configured, the broadcast channel is silently skipped — the
notification still arrives via the in-app channel on the next page load.

Flash messages provide immediate action feedback — "Profile updated
successfully" or "Settings saved." These are displayed as toast notifications
and disappear after a few seconds. They are not stored in the database and
exist only for the current request.

## When to Use Each Channel

The in-app channel is always used. It is the canonical record of
notifications a user has received. The mail channel is added when the user
needs to be notified even when they are not actively using the application.
The broadcast channel is added when the notification should appear
immediately without waiting for a page refresh. Flash messages are used for
instant feedback on actions the user just performed.

## CustomDatabaseChannel Concept

The standard Laravel database notification channel stores notifications in a
fixed-schema table provided by a package migration. The custom channel
replaces this with a domain-defined table that has additional columns: a
string-based notification type identifier, a title separate from the message,
an optional deep-link URL, and a JSON data field for extra payload. This
makes querying and displaying notifications more flexible without needing to
parse serialized data.

Each notification class must implement a `toCustomDatabase()` method that
returns the structured data array. The channel then creates a record in the
`notifications` table. This approach means the notification center UI can
display title, message, type badge, and link without deserializing anything.

## Queue Integration for Async Delivery

Every notification class implements `ShouldQueue` and uses the `Queueable`
trait. This ensures that sending a notification never blocks the HTTP
response. Mail delivery, database writes, and broadcast pushes all happen in
the background via the queue worker. Multiple recipients are processed
efficiently because each notification job is independent. Failed
notifications are automatically retried according to the queue configuration.

## Where to Find It

Notifications are in `app/Domain/*/Notifications/` organized by domain. The
custom database channel is at
`app/Domain/Core/Channels/CustomDatabaseChannel.php`. Broadcast configuration
is in `config/broadcasting.php` and `config/reverb.php`. Mail configuration
is in `config/mail.php`. Flash message configuration is in
`config/flasher.php` (timeout, position, styling set there).
