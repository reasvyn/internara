# Notifications

## System Overview

Internara uses a dual notification approach:

1. **In-app notifications** — persisted in the `notifications` table, managed via `App\Domain\Notification\Models\Notification`
2. **Laravel notifications** — queued classes that deliver via mail, broadcast, and a custom database channel

## In-App Notifications

### Model

`App\Domain\Notification\Models\Notification` — uses `HasUuid`, fillable via `#[Fillable]` attribute.

| Field | Type | Description |
|---|---|---|
| `user_id` | uuid | Target user |
| `type` | string | Notification category |
| `title` | string | Display title |
| `message` | text (nullable) | Body text |
| `data` | json (nullable) | Structured payload |
| `link` | string (nullable) | Navigation path |
| `is_read` | boolean | Read state |
| `read_at` | datetime (nullable) | Marked-read timestamp |

### Sending Notifications

Use `SendNotificationAction` directly or through `CustomDatabaseChannel`:

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

### Custom Database Channel

`App\Domain\Notification\Channels\CustomDatabaseChannel` bridges Laravel's notification system to the project's in-app notification table. It delegates to `SendNotificationAction` for consistency.

All domain notifications use three channels: `mail`, `broadcast`, and `CustomDatabaseChannel::class`.

## Domain Notifications

Each domain defines its own queued notification classes:

| Domain | Notification | Triggers |
|---|---|---|
| Auth | `WelcomeNotification` | New user created |
| Internship | `RegistrationNotification` | Registration status change |
| Assignment | `AssignmentNotification` | New assignment published |
| Assignment | `SubmissionFeedbackNotification` | Submission reviewed |
| Document | `ReportGeneratedNotification` | PDF report completed |
| User | `AccountStatusNotification` | Account state change |
| Notification | `JobFailedNotification` | Background job failure |

### Notification Structure

Each queued notification implements `toCustomDatabase()` to provide data for in-app storage:

```php
// app/Domain/Internship/Notifications/RegistrationNotification.php
public function toCustomDatabase($notifiable): array
{
    return [
        'type' => 'internship_registration_update',
        'title' => __('notifications.internship_registration.title'),
        'message' => __('notifications.internship_registration.message', [
            'internship' => $this->internshipName,
            'status' => strtoupper($this->status),
        ]),
        'link' => '/student/dashboard',
        'data' => [
            'internship_name' => $this->internshipName,
            'status' => $this->status,
        ],
    ];
}
```

## Flash Messages

PHPFlasher (`php-flasher/flasher-laravel`) provides transient action feedback.

| Setting | Value |
|---|---|
| Theme | Emerald |
| Timeout | 5000ms |
| Position | Bottom-right |
| Dark mode | Enabled |

```php
flash()->success(__('internship.save_success'));
flash()->error(__('setup.wizard.requirements_not_met'));
```

Rendered via `@flasher_render` in `resources/views/components/layouts/base.blade.php`.

## When to Use Each

| Mechanism | Use case | Lifespan |
|---|---|---|---|
| `flash()` | Action feedback (save, delete, error) | Single request |
| In-app notification | Important alerts requiring acknowledgment | Until read |
| Email notification | External communication (approvals, welcomes) | Persistent in inbox |
