<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\Notification\Livewire;

use App\Domain\Core\Support\CacheKeys;
use App\Domain\User\Aggregates\Notification\Models\Notification;
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
            CacheKeys::NOTIFICATION_UNREAD.$userId,
            60,
            function () use ($userId) {
                return Notification::where('user_id', $userId)
                    ->where('is_read', false)
                    ->count();
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
        return view('user.notification.notification-bell');
    }
}
