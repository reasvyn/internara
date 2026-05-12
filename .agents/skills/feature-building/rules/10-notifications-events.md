# Notifications & Events

## Flash Messages

Use PHPFlasher for immediate action feedback (save, delete, error):

```php
flash()->success(__('internship.save_success'));
flash()->error(__('setup.wizard.requirements_not_met'));
flash()->warning(__('No records selected.'));
```

Never use maryUI Toast methods (`$this->success()`, `$this->error()`, etc.).

## Notification Classes

All notifications implement `ShouldQueue` and use `Queueable`:

```php
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $password,  // public promotion
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'broadcast', CustomDatabaseChannel::class];
    }
}
```

### Channels

Every domain notification routes through three channels:
1. `mail` — external email
2. `broadcast` — real-time browser notification
3. `CustomDatabaseChannel::class` — in-app notification table

### Constructor

Use `public` promotion for notification constructor parameters (not `private`/`protected`).

## Domain Events

Dispatch events for key state transitions:

```php
event(new InternshipCreated($internship, auth()->user()));
event(new SetupFinalized(schoolId: $id, installedAt: now()));
```

Events are dispatched from Actions, never from Livewire components.

## In-App Notifications

Use `SendNotificationAction` for custom in-app notifications:

```php
app(SendNotificationAction::class)->execute(
    userId: $user->id,
    type: 'internship_approved',
    title: 'Internship Approved',
    message: 'Your internship has been approved.',
    link: route('internships.show', $internship->id),
);
```
