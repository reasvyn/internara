<?php

declare(strict_types=1);

namespace App\Livewire\Notification\Admin;

use App\Domain\Notification\Actions\DeleteNotificationAction;
use App\Domain\Notification\Actions\GetNotificationsAction;
use App\Domain\Notification\Actions\MarkAllAsReadAction;
use App\Domain\Notification\Actions\MarkAsReadAction;
use App\Domain\Notification\Models\Notification;
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

        // Falling back to notification-center if manager view is missing
        $view = view()->exists('livewire.notification.notification-manager')
            ? 'livewire.notification.notification-manager'
            : 'livewire.notification.notification-center';

        return view($view, [
            'notifications' => $action->execute((string) $userId, false, 50),
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
        $action->execute((string) Auth::id());

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
        $userId = Auth::id();

        return [
            "echo-private:App.Domain.User.Models.User.{$userId},.Illuminate\Notifications\Events\BroadcastNotificationCreated" => 'updateUnreadCount',
        ];
    }
}
