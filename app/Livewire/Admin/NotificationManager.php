<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Notification\DeleteNotificationAction;
use App\Actions\Notification\GetNotificationsAction;
use App\Actions\Notification\MarkAllAsReadAction;
use App\Actions\Notification\MarkAsReadAction;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Admin UI for managing notifications.
 *
 * S2 - Sustain: Real-time notification management.
 */
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
            'echo:private-user.{Auth::id()},.Illuminate\Notifications\Events\BroadcastNotificationCreated' => 'updateUnreadCount',
        ];
    }
}
