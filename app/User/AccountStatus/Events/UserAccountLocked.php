<?php

declare(strict_types=1);

namespace App\User\AccountStatus\Events;

use App\Core\Events\BaseEvent;
use App\User\Models\User;

final class UserAccountLocked extends BaseEvent
{
    public function __construct(public User $user) {}

    public function eventName(): string
    {
        return 'user.account_locked';
    }
}
