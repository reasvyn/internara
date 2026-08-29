<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\AccountStatus\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Models\User;

final class UserAccountUnlocked extends BaseEvent
{
    public function __construct(public User $user) {}

    public function eventName(): string
    {
        return 'user.account_unlocked';
    }
}
