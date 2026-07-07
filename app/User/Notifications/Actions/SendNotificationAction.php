<?php

declare(strict_types=1);

namespace App\User\Notifications\Actions;

use App\Core\Actions\BaseCommandAction;
use App\Core\Contracts\SendsNotifications;
use App\User\Models\User;
use App\User\Notifications\Data\NotificationData;
use App\User\Notifications\Events\NotificationSent;
use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

/**
 * Stateless Action to send in-app notification.
 *
 * S1 - Secure: Validates user exists.
 * S2 - Sustain: Single-purpose action.
 */
final class SendNotificationAction extends BaseCommandAction implements SendsNotifications
{
    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): Notification {
        $notificationData = NotificationData::from([
            'userId' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'link' => $link,
        ]);

        Validator::make(
            [
                'userId' => $notificationData->userId,
                'type' => $notificationData->type,
                'title' => $notificationData->title,
            ],
            [
                'userId' => 'required|string',
                'type' => 'required|string|max:50',
                'title' => 'required|string|max:255',
            ],
        )->validate();

        $user = User::findOrFail($notificationData->userId);

        return $this->transaction(function () use ($user, $notificationData) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $notificationData->type,
                'title' => $notificationData->title,
                'message' => $notificationData->message,
                'data' => $notificationData->data,
                'link' => $notificationData->link,
                'is_read' => false,
            ]);

            $this->log('notification_sent', $notification, [
                'user_id' => $user->id,
                'type' => $notificationData->type,
            ]);

            event(new NotificationSent($notification));

            return $notification;
        });
    }
}
