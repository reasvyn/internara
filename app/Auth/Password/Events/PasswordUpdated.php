<?php

declare(strict_types=1);

namespace App\Auth\Password\Events;

use App\Core\Events\BaseEvent;
use App\User\Models\User;

final class PasswordUpdated extends BaseEvent
{
    public function __construct(public User $user) {}

    public function eventName(): string
    {
        return 'password.updated';
    }
}
