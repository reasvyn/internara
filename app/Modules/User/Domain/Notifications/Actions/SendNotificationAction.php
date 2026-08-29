<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Notifications\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\Notifications\Events\NotificationSent;
use App\Modules\User\Domain\Notifications\Models\Notification;
use Illuminate\Support\Facades\Validator;

/**
 * Stateless Action to send in-app notification.
 *
 * S1 - Secure: Validates user exists.
 * S2 - Sustain: Single-purpose action.
 */
final class SendNotificationAction extends BaseCommandAction implements SendsNotifications
{
    public function execute(NotificationData $data): Notification
    {
        Validator::make(
            [
                'userId' => $data->userId,
                'type' => $data->type,
                'title' => $data->title,
            ],
            [
                'userId' => 'required|string',
                'type' => 'required|string|max:50',
                'title' => 'required|string|max:255',
            ],
        )->validate();

        $user = User::findOrFail($data->userId);

        return $this->transaction(function () use ($user, $data) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $data->type,
                'title' => $data->title,
                'message' => $data->message,
                'data' => $data->data,
                'link' => $data->link,
                'is_read' => false,
            ]);

            $this->log('notification_sent', $notification, [
                'user_id' => $user->id,
                'type' => $data->type,
            ]);

            event(new NotificationSent($notification));

            return $notification;
        });
    }
}
