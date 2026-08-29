<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Models\User;

final class UserDeleted extends BaseEvent
{
    public function __construct(public User $user) {}

    public function eventName(): string
    {
        return 'user.deleted';
    }
}
