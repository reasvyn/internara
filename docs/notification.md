# Notification Documentation: Internara

## 1. Overview

Internara implements a dual notification system:

1. **In-App Notifications**: Custom `notifications` table with read/unread tracking
2. **Email Notifications**: Optional Laravel notifications (via events/listeners)

### Configuration

- **Model**: `app/Models/Notification.php`
- **Migration**: `2026_04_30_022555_create_notifications_table.php`
- **Actions**: `app/Actions/Notification/`
- **UI**: `app/Livewire/Admin/NotificationManager.php`

## 2. Database Structure

### Notifications Table

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('user_id');
    $table->string('type', 50);
    $table->string('title');
    $table->text('message')->nullable();
    $table->json('data')->nullable();
    $table->string('link')->nullable();
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->index(['user_id', 'is_read']);
    $table->index(['user_id', 'created_at']);
});
```

### Model: `App\Models\Notification`

```php
class Notification extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = ['user_id', 'type', 'title', 'message', 'data', 'link', 'is_read'];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Business Rules
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function isUnread(): bool
    {
        return !$this->is_read;
    }
}
```

## 3. Notification Types

### Standard Types (Constants)

```php
// Suggested type constants (not enforced by DB)
INTERNSHIP_CREATED = 'internship_created'
INTERNSHIP_APPROVED = 'internship_approved'
MENTEE_REGISTERED = 'mentee_registered'
DOCUMENT_UPLOADED = 'document_uploaded'
ASSESSMENT_GRADED = 'assessment_graded'
GENERAL_INFO = 'general_info'
SYSTEM_ALERT = 'system_alert'
```

### Type Usage

```php
// Creating notification with type
Notification::create([
    'user_id' => $userId,
    'type' => 'internship_approved',
    'title' => 'Internship Approved',
    'message' => 'Your internship has been approved.',
    'link' => route('internships.show', $internship->id),
    'data' => ['internship_id' => $internship->id],
]);
```

## 4. Core Actions

### SendNotificationAction

```php
namespace App\Actions\Notification;

use App\Models\Notification;
use App\Models\User;

class SendNotificationAction
{
    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): Notification {
        $user = User::findOrFail($userId);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'link' => $link,
            'is_read' => false,
        ]);

        return $notification;
    }
}
```

### GetNotificationsAction

```php
class GetNotificationsAction
{
    public function execute(string $userId, bool $unreadOnly = false, int $limit = 50): Collection
    {
        $query = Notification::where('user_id', $userId);

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }
}
```

### MarkAsReadAction

```php
class MarkAsReadAction
{
    public function execute(Notification $notification): void
    {
        $notification->markAsRead();
    }
}
```

### MarkAllAsReadAction

```php
class MarkAllAsReadAction
{
    public function execute(string $userId): void
    {
        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
```

### DeleteNotificationAction

```php
class DeleteNotificationAction
{
    public function execute(Notification $notification): void
    {
        $notification->delete();
    }
}
```

## 5. Livewire UI: NotificationManager

### Component: `app/Livewire/Admin/NotificationManager.php`

```php
class NotificationManager extends Component
{
    public bool $showAll = false;
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->updateUnreadCount();
    }

    public function render()
    {
        $userId = Auth::id();
        $action = app(GetNotificationsAction::class);

        return view('livewire.admin.notification-manager', [
            'notifications' => $action->execute($userId, false, 50),
            'unreadCount' => $this->unreadCount,
        ]);
    }

    public function markAsRead(string $id, MarkAsReadAction $action): void
    {
        $notification = Notification::findOrFail($id);
        $action->execute($notification);

        $this->updateUnreadCount();
        $this->dispatch('notification-read');
    }

    public function markAllAsRead(MarkAllAsReadAction $action): void
    {
        $action->execute(Auth::id());

        $this->updateUnreadCount();
        $this->dispatch('notifications-read');
    }

    public function delete(string $id, DeleteNotificationAction $action): void
    {
        $notification = Notification::findOrFail($id);
        $action->execute($notification);

        $this->updateUnreadCount();
    }

    public function updateUnreadCount(): void
    {
        $this->unreadCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
    }

    public function getListeners(): array
    {
        return [
            'echo:private-user.{Auth::id()},Illuminate\Notifications\Events\BroadcastNotificationCreated' =>
                'updateUnreadCount',
        ];
    }
}
```

### Blade View (Simplified)

```blade
<div>
    <div class="badge badge-primary">{{ $unreadCount }} unread</div>

    @foreach ($notifications as $notification)
        <div class="card @if(!$notification->is_read) bg-blue-50 @endif">
            <h3>{{ $notification->title }}</h3>
            <p>{{ $notification->message }}</p>
            @if ($notification->link)
                <a href="{{ $notification->link }}">View</a>
            @endif

            @if (! $notification->is_read)
                <button wire:click="markAsRead('{{ $notification->id }}')">Mark as Read</button>
            @endif
        </div>
    @endforeach
</div>
```

## 6. Event-Driven Notifications (Optional)

### Example: Internship Created Event

```php
// Event: app/Events/InternshipCreated.php
class InternshipCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Internship $internship,
        public readonly User $createdBy,
    ) {}
}

// Listener: app/Listeners/SendInternshipCreatedNotifications.php
class SendInternshipCreatedNotifications
{
    public function __construct(private readonly SendNotificationAction $sendNotification) {}

    public function handle(InternshipCreated $event): void
    {
        // Notify the mentee
        $this->sendNotification->execute(
            userId: $event->internship->student_id,
            type: 'internship_created',
            title: 'Internship Created',
            message: 'Your internship "' . $event->internship->name . '" has been created.',
            link: route('internships.show', $event->internship->id),
            data: ['internship_id' => $event->internship->id],
        );
    }
}
```

### Register Listener

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    InternshipCreated::class => [
        SendInternshipCreatedNotifications::class,
    ],
];
```

## 7. Flash Messages (PHPFlasher)

Internara uses **PHPFlasher** for temporary flash messages (success, error, warning, info) that
appear after user actions and automatically disappear.

### Configuration

- **Package**: `php-flasher/flasher-laravel`
- **Config File**: `config/flasher.php`
- **Theme**: Emerald (default)
- **Render Location**: `resources/views/components/layouts/base.blade.php` with `@flasher_render`

### Config Structure (`config/flasher.php`)

```php
return [
    'default' => 'flasher',

    'main_script' => '/vendor/flasher/flasher.min.js',
    'styles' => ['/vendor/flasher/flasher.min.css'],

    'options' => [
        'theme' => 'emerald',
        'timeout' => 5000,
        'position' => 'bottom-right',
        'darkMode' => true, // Supports dark theme
    ],

    'plugins' => [
        'flasher' => [
            'scripts' => [
                '/vendor/flasher/flasher.min.js',
                '/vendor/flasher/themes/emerald/emerald.min.js',
            ],
            'styles' => [
                '/vendor/flasher/flasher.min.css',
                '/vendor/flasher/themes/emerald/emerald.min.css',
            ],
        ],
    ],

    // Laravel session flash message mapping
    'flash_bag' => [
        'success' => ['success'],
        'error' => ['error', 'danger'],
        'warning' => ['warning', 'alarm'],
        'info' => ['info', 'notice', 'alert'],
    ],
];
```

### Usage in PHP (Backend)

```php
// Using helper function (simplest - matches actual codebase usage)
flash()->success('Internship created successfully!');
flash()->error('Failed to save attendance log.');
flash()->warning('Your submission is pending review.');
flash()->info('System maintenance scheduled tonight.');

// In Actions/Controllers (example from codebase)
class CreateInternshipAction
{
    public function execute(CreateInternshipRequest $request): Internship
    {
        $internship = Internship::create($request->validated());

        // Flash success message (actual pattern used in Livewire components)
        flash()->success(__('internship.save_success'));

        return $internship;
    }
}

// With translation strings (as used in the project)
flash()->success(__('auth::ui.login.welcome_back', ['name' => $user->name]));
flash()->error(__('setup.wizard.requirements_not_met'));
flash()->warning(__('placement.update_success'));
```

### Actual Usage in Codebase

Based on `app/Livewire/` components:

```php
// app/Livewire/Auth/Login.php:52
flash()->success(__('auth::ui.login.welcome_back', ['name' => $user->name]));

// app/Livewire/Setup/SetupWizard.php:272
flash()->success(__('setup.wizard.setup_complete'));

// app/Livewire/Admin/Internship/InternshipIndex.php:93
flash()->success(__('internship.update_success'));

// app/Livewire/Admin/Company/CompanyIndex.php:88
flash()->success(__('company.save_success'));
```

### Usage in Blade (Frontend)

The `@flasher_render` directive is placed in the base layout to automatically render all queued
flash messages:

```blade
{{-- In resources/views/components/layouts/base.blade.php --}}
@flasher_render
{{-- Renders all queued messages --}}
```

This directive:

- Automatically includes required JS/CSS assets from config
- Renders all pending flash messages (success, error, warning, info)
- Respects configured theme (Emerald), position, timeout, and dark mode settings

### Flash vs In-App Notifications

| Feature         | PHPFlasher (Flash)                    | In-App Notifications           |
| --------------- | ------------------------------------- | ------------------------------ |
| **Persistence** | Temporary (session-based)             | Permanent (database)           |
| **Lifespan**    | Disappears after 5s                   | Stays until manually read      |
| **Use Case**    | Action feedback (save, delete, error) | Important alerts, approvals    |
| **Storage**     | Session flash data                    | `notifications` table          |
| **Example**     | "Mentee registered!"                 | "Your internship was approved" |

### Testing with PHPFlasher

> **Note**: PHPFlasher provides testing utilities via `Flasher\Prime\Test\FlasherAssert`. However,
> the project currently doesn't have Flasher-specific tests. Below are example patterns:

```php
use Flasher\Prime\Test\FlasherAssert;

test('it shows success message', function () {
    // Perform action that triggers flasher
    $this->post('/internships', $data);

    // Assert flash message was added
    FlasherAssert::assertHasFlash('success', 'Internship created successfully!');
});

test('it shows error on failure', function () {
    // Simulate failure
    $this->post('/internships', []);

    FlasherAssert::assertHasFlash('error');
});
```

### S1 Security Considerations

```php
// ✅ DO: Sanitize user input in messages
flash()->error('Failed to process: ' . e($validatedError));

// ❌ DON'T: Include sensitive data
flash()->error('Database connection failed: ' . $dbPassword); // Never!
```

---

## 8. Email Notifications (Optional)

### Using Laravel's Notification System

```php
use Illuminate\Support\Facades\Notification;
use App\Notifications\InternshipApproved;

// Send email notification
$user->notify(new InternshipApproved($internship));
```

### Creating Notification Class

```bash
php artisan make:notification InternshipApproved
```

```php
// app/Notifications/InternshipApproved.php
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InternshipApproved extends Notification
{
    public function __construct(public Internship $internship) {}

    public function via($notifiable): array
    {
        return ['mail', 'database']; // Both email and in-app
    }

    public function toMail($notifiable): MailMessage
    {
        return new MailMessage()
            ->subject('Internship Approved')
            ->line('Your internship has been approved!')
            ->action('View Internship', route('internships.show', $this->internship->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'internship_approved',
            'title' => 'Internship Approved',
            'message' => 'Your internship has been approved.',
            'link' => route('internships.show', $this->internship->id),
        ];
    }
}
```

## 9. Notification Testing

### Pest PHP Tests

```php
use App\Actions\Notification\SendNotificationAction;
use App\Models\Notification;

test('can send notification', function () {
    $user = User::factory()->create();

    $action = app(SendNotificationAction::class);
    $notification = $action->execute(
        userId: $user->id,
        type: 'test_notification',
        title: 'Test Title',
        message: 'Test Message',
    );

    expect($notification)->toBeInstanceOf(Notification::class);
    expect($notification->user_id)->toBe($user->id);
    expect($notification->is_read)->toBeFalse();
});

test('can mark as read', function () {
    $notification = Notification::factory()->create(['is_read' => false]);

    $notification->markAsRead();

    expect($notification->fresh()->is_read)->toBeTrue();
    expect($notification->read_at)->not->toBeNull();
});

test('can get unread count', function () {
    $user = User::factory()->create();
    Notification::factory(3)->create(['user_id' => $user->id, 'is_read' => false]);
    Notification::factory(2)->create(['user_id' => $user->id, 'is_read' => true]);

    $count = Notification::where('user_id', $user->id)->where('is_read', false)->count();

    expect($count)->toBe(3);
});
```

## 10. Real-Time Notifications (Broadcasting)

### Configuration

```env
BROADCAST_DRIVER=pusher  # or 'reverb', 'redis', 'log'

# Pusher example
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

### Broadcasting Notification

```php
// In your notification class
public function via($notifiable): array
{
    return ['broadcast', 'database'];  // Both broadcast and in-app
}

public function toBroadcast($notifiable)
{
    return [
        'title' => $this->notification->title,
        'message' => $this->notification->message,
    ];
}
```

### Listening in Livewire

```php
public function getListeners(): array
{
    return [
        'echo:private-user.{Auth::id()},NotificationSent' => 'updateUnreadCount',
    ];
}
```

## 11. Performance Considerations

### Database Indexes

```php
// Already added in migration
$table->index(['user_id', 'is_read']); // For unread count queries
$table->index(['user_id', 'created_at']); // For listing with orderBy
```

### Pagination for Large Lists

```php
// In GetNotificationsAction
public function execute(string $userId, bool $unreadOnly = false, int $perPage = 20): LengthAwarePaginator
{
    $query = Notification::where('user_id', $userId);

    if ($unreadOnly) {
        $query->where('is_read', false);
    }

    return $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
}
```

### Archiving Old Notifications

```php
// Scheduled job to archive/readelete old notifications
Schedule::call(function () {
    Notification::where('created_at', '<', now()->subMonths(3))
        ->where('is_read', true)
        ->delete();
})->monthly();
```

## 12. Security (S1)

### S1 - Secure: Authorization

```php
// Always check user owns the notification
public function markAsRead(string $id): void
{
    $notification = Notification::findOrFail($id);

    // Authorize: User can only read their own notifications
    if ($notification->user_id !== Auth::id()) {
        abort(403, 'Unauthorized');
    }

    $notification->markAsRead();
}
```

### S1 - Secure: No Sensitive Data in Notifications

```php
// ❌ DON'T
SendNotificationAction::execute(
    userId: $user->id,
    type: 'security_alert',
    title: 'Password Changed',
    data: ['new_password' => 'plaintext_password'], // ❌ Never!
);

// ✅ DO
SendNotificationAction::execute(
    userId: $user->id,
    type: 'security_alert',
    title: 'Password Changed',
    message: 'Your password was changed successfully.',
    // No sensitive data in data/message
);
```

## 13. Troubleshooting

### Notifications Not Appearing

1. Check if notification was created: `php artisan tinker` → `Notification::all()`
2. Verify user_id is correct
3. Check if Livewire component is listening to events
4. For real-time: Check broadcasting configuration

### Unread Count Incorrect

```php
// Manually recalculate
$user = Auth::user();
$user->unread_count = Notification::where('user_id', $user->id)->where('is_read', false)->count();
```

### Performance Issues

- Add index on `(user_id, is_read)` if missing
- Use pagination for notification lists
- Archive old notifications (older than 3 months)

---

**Last Updated**: April 30, 2026
