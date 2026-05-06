<?php

declare(strict_types=1);

namespace App\Domain\Setup\Data;

final readonly class RecoverAdminData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $isReset = false,
        public string $role = 'super_admin',
    ) {}
}
