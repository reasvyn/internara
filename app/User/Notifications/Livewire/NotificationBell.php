<?php

declare(strict_types=1);

namespace App\User\Notifications\Livewire;

use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->updateUnreadCount();
    }

    public function updateUnreadCount(): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            $this->unreadCount = 0;

            return;
        }

        $this->unreadCount = Cache::remember(
            config('cache-keys.notification_unread').$userId,
            60,
            function () use ($userId) {
                return Notification::where('user_id', $userId)->where('is_read', false)->count();
            },
        );
    }

    public function getListeners(): array
    {
        return [
            'notification-read' => 'updateUnreadCount',
            'notifications-read' => 'updateUnreadCount',
        ];
    }

    public function render(): View
    {
        return view('user.notifications.notification-bell');
    }
}
