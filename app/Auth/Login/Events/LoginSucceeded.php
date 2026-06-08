<?php

declare(strict_types=1);

namespace App\Auth\Login\Events;

use App\User\Models\User;

final readonly class LoginSucceeded
{
    public function __construct(
        public User $user,
        public string $identifier,
    ) {}
}