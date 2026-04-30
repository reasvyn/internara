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
    
    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'data', 'link', 'is_read'
    ];
    
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
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }
    
    public function isUnread(): bool
    {
        return ! $this->is_read;
    }
}
```

## 3. Notification Types

### Standard Types (Constants)
```php
// Suggested type constants (not enforced by DB)
INTERNSHIP_CREATED = 'internship_created'
INTERNSHIP_APPROVED = 'internship_approved'
STUDENT_REGISTERED = 'student_registered'
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
        
        return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
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
            'echo:private-user.{Auth::id()},Illuminate\Notifications\Events\BroadcastNotificationCreated' => 'updateUnreadCount',
        ];
    }
}
```

### Blade View (Simplified)
```blade
<div>
    <div class="badge badge-primary">{{ $unreadCount }} unread</div>
    
    @foreach($notifications as $notification)
        <div class="card @if(!$notification->is_read) bg-blue-50 @endif">
            <h3>{{ $notification->title }}</h3>
            <p>{{ $notification->message }}</p>
            @if($notification->link)
                <a href="{{ $notification->link }}">View</a>
            @endif
            
            @if(!$notification->is_read)
                <button wire:click="markAsRead('{{ $notification->id }}')">
                    Mark as Read
                </button>
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
    public function __construct(
        private readonly SendNotificationAction $sendNotification,
    ) {}
    
    public function handle(InternshipCreated $event): void
    {
        // Notify the student
        $this->sendNotification->execute(
            userId: $event->internship->student_id,
            type: 'internship_created',
            title: 'Internship Created',
            message: 'Your internship "' . $event->internship->name . '" has been created.',
            link: route('internships.show', $event->internship->id),
            data: ['internship_id' => $event->internship->id]
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

## 7. Email Notifications (Optional)

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
        return ['mail', 'database'];  // Both email and in-app
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
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

## 8. Notification Testing

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
        message: 'Test Message'
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
    
    $count = Notification::where('user_id', $user->id)
        ->where('is_read', false)
        ->count();
    
    expect($count)->toBe(3);
});
```

## 9. Real-Time Notifications (Broadcasting)

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

## 10. Performance Considerations

### Database Indexes
```php
// Already added in migration
$table->index(['user_id', 'is_read']);      // For unread count queries
$table->index(['user_id', 'created_at']);  // For listing with orderBy
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

## 11. Security (S1)

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
    data: ['new_password' => 'plaintext_password']  // ❌ Never!
);

// ✅ DO
SendNotificationAction::execute(
    userId: $user->id,
    type: 'security_alert',
    title: 'Password Changed',
    message: 'Your password was changed successfully.'
    // No sensitive data in data/message
);
```

## 12. Troubleshooting

### Notifications Not Appearing
1. Check if notification was created: `php artisan tinker` → `Notification::all()`
2. Verify user_id is correct
3. Check if Livewire component is listening to events
4. For real-time: Check broadcasting configuration

### Unread Count Incorrect
```php
// Manually recalculate
$user = Auth::user();
$user->unread_count = Notification::where('user_id', $user->id)
    ->where('is_read', false)
    ->count();
```

### Performance Issues
- Add index on `(user_id, is_read)` if missing
- Use pagination for notification lists
- Archive old notifications (older than 3 months)

---

**Last Updated**: April 30, 2026  
**Notification Table**: `notifications`  
**Model**: `App\Models\Notification`  
**Actions**: 5 actions in `app/Actions/Notification/`  
**UI**: `app/Livewire/Admin/NotificationManager.php`