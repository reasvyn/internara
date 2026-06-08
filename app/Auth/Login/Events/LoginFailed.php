<?php

declare(strict_types=1);

namespace App\Auth\Login\Events;

final readonly class LoginFailed
{
    public function __construct(
        public string $identifier,
        public string $reason,
    ) {}
}