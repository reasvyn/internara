<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Login\Events;

use App\Modules\Core\Events\BaseEvent;

final class LoginFailed extends BaseEvent
{
    public function __construct(
        public string $identifier,
        public string $reason,
    ) {}

    public function eventName(): string
    {
        return 'login.failed';
    }
}
