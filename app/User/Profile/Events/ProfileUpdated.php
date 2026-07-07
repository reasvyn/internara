<?php

declare(strict_types=1);

namespace App\User\Profile\Events;

use App\Core\Events\BaseEvent;
use App\User\Profile\Models\Profile;

final class ProfileUpdated extends BaseEvent
{
    public function __construct(
        public Profile $profile,
        public ?string $previousEmail = null,
        public ?string $previousUsername = null,
    ) {}

    public function eventName(): string
    {
        return 'profile.updated';
    }
}
