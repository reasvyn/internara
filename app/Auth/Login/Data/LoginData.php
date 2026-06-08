<?php

declare(strict_types=1);

namespace App\Auth\Login\Data;

use App\Core\Data\BaseData;

final readonly class LoginData extends BaseData
{
    public function __construct(
        public string $identifier,
        public string $password,
        public bool $remember = false,
    ) {}
}