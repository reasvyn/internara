<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\AccountRecovery\Data;

use App\Modules\Core\Data\BaseData;

final readonly class RedeemRecoverySlipData extends BaseData
{
    public function __construct(
        public string $username,
        public string $code,
        public string $newPassword,
    ) {}
}
