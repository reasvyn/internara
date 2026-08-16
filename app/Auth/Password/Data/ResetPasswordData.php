<?php

declare(strict_types=1);

namespace App\Auth\Password\Data;

use App\Core\Data\BaseData;

final readonly class ResetPasswordData extends BaseData
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
        public string $passwordConfirmation,
    ) {}
}
