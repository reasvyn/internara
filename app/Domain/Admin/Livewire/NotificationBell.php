<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Models\Notification;
use Illuminate\Support\Facades\Auth;
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
        $this->unreadCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
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
        return view('admin.notification-bell');
    }
}
