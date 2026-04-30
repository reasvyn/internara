<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Notification\Models\Notification as NotificationModel;
use Modules\Notification\Services\Contracts\NotificationService as Contract;
use Modules\Shared\Services\BaseService;
use Modules\User\Models\User;

class NotificationService extends BaseService implements Contract
{
    /**
     * Send notification to a specific user.
     */
    public function sendToUser(string $userId, string $title, string $message): void
    {
        $user = User::findOrFail($userId);
        $user->notify(new \Modules\Notification\Notifications\GenericNotification($title, $message));
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $notificationId): void
    {
        $notification = NotificationModel::findOrFail($notificationId);
        $notification->markAsRead();
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(string $userId): int
    {
        return NotificationModel::where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get paginated notifications for a user.
     */
    public function getUserNotifications(string $userId, int $perPage = 15): array
    {
        return NotificationModel::where('notifiable_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function send(mixed $recipients, Notification $notification): void
    {
        NotificationFacade::send($recipients, $notification);
    }

    /**
     * {@inheritdoc}
     */
    public function sendNow(mixed $recipients, Notification $notification): void
    {
        NotificationFacade::sendNow($recipients, $notification);
    }
}
