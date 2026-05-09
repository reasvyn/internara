# Notifications

## Dual System

| Mechanism | Use case | Lifespan |
|---|---|---|
| `flash()` (PHPFlasher) | Action feedback (save, delete, error) | Single request |
| In-app notification | Important alerts requiring acknowledgment | Until read |
| Email notification | External communication (approvals, welcomes) | Persistent |

## In-App Notifications

`App\Models\Notification` (extends `BaseModel`, UUID PK) stores notifications in a custom `notifications` table with fields: `user_id`, `type`, `title`, `message`, `data` (json), `link`, `is_read`, `read_at`.

Entity integration: `$notification->asNotificationStatus()` returns a `NotificationStatus` domain entity.

### Sending

```php
// Direct action call
app(SendNotificationAction::class)->execute(
    userId: $user->id,
    type: 'internship_approved',
    title: 'Internship Approved',
    message: 'Your internship has been approved.',
    link: route('internships.show', $internship->id),
);
```

`SendNotificationAction` validates the user exists and creates a record with `is_read = false`.

### Laravel Notification Channel

`App\Channels\CustomDatabaseChannel` bridges Laravel notifications to the custom `notifications` table. Each notification class implements `toCustomDatabase($notifiable)` returning an array with keys: `type`, `title`, `message`, `data`, `link`.

All domain notifications route through three channels: `mail`, `broadcast`, and `CustomDatabaseChannel::class`.

## Domain Notifications

| Domain | Notification |
|---|---|
| Auth | `WelcomeNotification`, `AdminRecoveredNotification` |
| Internship | `InternshipRegistrationNotification` |
| Assignment | `AssignmentNotification`, `SubmissionFeedbackNotification` |
| Document | `ReportGeneratedNotification`, `JobFailedNotification` |
| User | `AccountStatusNotification`, `TestMailNotification` |

Notification types are defined in `App\Enums\Notification\NotificationType`.

## Flash Messages

PHPFlasher (Emerald theme, 5s timeout, bottom-right, dark mode enabled):

```php
flash()->success(__('internship.save_success'));
flash()->error(__('setup.wizard.requirements_not_met'));
```