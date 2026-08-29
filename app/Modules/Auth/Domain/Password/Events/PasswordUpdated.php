<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Password\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Models\User;

final class PasswordUpdated extends BaseEvent
{
    public function __construct(public User $user) {}

    public function eventName(): string
    {
        return 'password.updated';
    }
}
