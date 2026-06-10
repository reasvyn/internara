<?php

declare(strict_types=1);

namespace App\Auth\Login\Events;

use App\Core\Events\BaseEvent;
use App\User\Models\User;

final class LoginSucceeded extends BaseEvent
{
    public function __construct(
        public User $user,
        public string $identifier,
    ) {}

    public function eventName(): string
    {
        return 'login.succeeded';
    }
}
