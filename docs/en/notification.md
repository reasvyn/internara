# Notifications

## Channels

| Channel | Use case |
|---|---|
| Flash messages (PHPFlasher) | Immediate action feedback (save, delete, error) |
| In-app notifications | Important alerts requiring acknowledgment |
| Email notifications | External communication (approvals, welcomes) |

## In-App Notifications

The custom `notifications` table stores user-specific notifications with read tracking. Each notification has a type, title, message, optional link, and read status.

Sending a notification:

```php
app(SendNotificationAction::class)->execute(
    userId: $user->id,
    type: 'internship_approved',
    title: 'Internship Approved',
    message: 'Your internship has been approved.',
    link: route('internships.show', $internship->id),
);
```

`SendNotificationAction` validates that the user exists and creates an unread notification.

### Laravel Notification Channel

`CustomDatabaseChannel` bridges Laravel's notification system to the custom `notifications` table. Each notification class implements `toCustomDatabase($notifiable)` returning an array with keys: `type`, `title`, `message`, `data`, `link`.

Domain notifications route through three channels: `mail`, `broadcast`, and `CustomDatabaseChannel`.

## Flash Messages

PHPFlasher displays success and error messages with a 5-second timeout, positioned at the bottom-right:

```php
flash()->success(__('internship.save_success'));
flash()->error(__('setup.wizard.requirements_not_met'));
```
