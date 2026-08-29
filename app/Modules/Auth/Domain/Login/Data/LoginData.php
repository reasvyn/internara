<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Login\Data;

use App\Modules\Core\Data\BaseData;

final readonly class LoginData extends BaseData
{
    public function __construct(
        public string $identifier,
        public string $password,
        public bool $remember = false,
    ) {}
}
