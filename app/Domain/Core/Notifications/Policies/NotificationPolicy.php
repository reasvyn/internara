<?php

declare(strict_types=1);

namespace App\Domain\Policies;

use App\Domain\Core\Notifications\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy for Notification model.
 *
 * S1 - Secure: Users can only manage their own notifications.
 */
class NotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view their notifications
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
