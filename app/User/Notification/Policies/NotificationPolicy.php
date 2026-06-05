<?php

declare(strict_types=1);

namespace App\User\Notification\Policies;

use App\Core\Policies\BasePolicy;
use App\User\Models\User;
use App\User\Notification\Models\Notification;

class NotificationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $this->isAdmin($user);
    }
}
