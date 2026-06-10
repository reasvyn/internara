<?php

declare(strict_types=1);

namespace App\Auth\Login\Events;

use App\Core\Events\BaseEvent;

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
