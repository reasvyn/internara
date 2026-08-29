<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Profile\Events;

use App\Modules\Core\Events\BaseEvent;
use App\Modules\User\Domain\Profile\Models\Profile;

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
