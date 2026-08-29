<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Notifications\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\Notifications\Models\Notification;

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
