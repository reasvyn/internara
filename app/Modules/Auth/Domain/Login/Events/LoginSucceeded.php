<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Login\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Models\User;

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
