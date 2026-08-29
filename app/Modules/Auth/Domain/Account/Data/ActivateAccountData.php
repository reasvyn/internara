<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Account\Data;

use App\Modules\Core\Data\BaseData;

final readonly class ActivateAccountData extends BaseData
{
    public function __construct(
        public int $userId,
        public string $code,
        public string $password,
    ) {}
}
