<?php

declare(strict_types=1);

namespace App\User\Notifications\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Contracts\SendsNotifications;
use App\Support\CacheKeys;
use App\User\Models\User;
use App\User\Notifications\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * Stateless Action to send in-app notification.
 *
 * S1 - Secure: Validates user exists.
 * S2 - Sustain: Single-purpose action.
 */
final class SendNotificationAction extends BaseAction implements SendsNotifications
{
    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): Notification {
        Validator::make([
            'userId' => $userId,
            'type' => $type,
            'title' => $title,
        ], [
            'userId' => 'required|string',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
        ])->validate();

        $user = User::findOrFail($userId);

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'link' => $link,
            'is_read' => false,
        ]);

        Cache::forget(CacheKeys::NOTIFICATION_UNREAD.$user->id);

        return $notification;
    }
}
