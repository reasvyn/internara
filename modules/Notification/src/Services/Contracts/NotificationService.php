<?php

declare(strict_types=1);

namespace Modules\Notification\Services\Contracts;

interface NotificationService
{
    public function sendToUser(string $userId, string $title, string $message): void;
    public function markAsRead(string $notificationId): void;
    public function getUnreadCount(string $userId): int;
    public function getUserNotifications(string $userId, int $perPage = 15): array;
}
