<?php

declare(strict_types=1);

namespace App\Livewire\Common;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
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
        $this->unreadCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
    }

    public function getListeners(): array
    {
        $userId = Auth::id();

        return [
            "echo-private:App.Models.User.{$userId},.Illuminate\Notifications\Events\BroadcastNotificationCreated" => 'updateUnreadCount',
            'notification-read' => 'updateUnreadCount',
            'notifications-read' => 'updateUnreadCount',
        ];
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-button icon="o-bell" class="btn-ghost btn-sm relative" link="/notifications">
                @if($unreadCount > 0)
                    <span class="badge badge-error badge-xs absolute top-0 right-0 animate-pulse">{{ $unreadCount }}</span>
                @endif
            </x-mary-button>
        </div>
        HTML;
    }
}
