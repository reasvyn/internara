<?php

declare(strict_types=1);

namespace App\Auth\SuperAdmin\Events;

use App\Core\Events\BaseEvent;
use App\User\Models\User;

final class SuperAdminRecovered extends BaseEvent
{
    public function __construct(
        public readonly User $user,
        public readonly string $email,
    ) {}

    public function eventName(): string
    {
        return 'super_admin.recovered';
    }
}
