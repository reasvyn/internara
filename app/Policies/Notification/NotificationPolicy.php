<?php

declare(strict_types=1);

namespace App\Policies\Notification;

use App\Models\Notification;
use App\Models\User;
use App\Policies\Shared\BasePolicy;

/**
 * Policy for Notification model.
 *
 * S1 - Secure: Users can only manage their own notifications.
 */
class NotificationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }
}
