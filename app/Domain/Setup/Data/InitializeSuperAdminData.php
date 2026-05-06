<?php

declare(strict_types=1);

namespace App\Domain\Setup\Data;

final readonly class InitializeSuperAdminData
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $name = null,
        public ?string $username = null,
    ) {}
}
