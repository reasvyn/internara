<?php

declare(strict_types=1);

namespace App\Auth\Account\Data;

use App\Core\Data\BaseData;

final readonly class ActivateAccountData extends BaseData
{
    public function __construct(
        public int $userId,
        public string $code,
        public string $password,
    ) {}
}
