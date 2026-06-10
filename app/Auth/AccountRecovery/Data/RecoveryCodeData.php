<?php

declare(strict_types=1);

namespace App\Auth\AccountRecovery\Data;

use App\Core\Data\BaseData;

final readonly class RecoveryCodeData extends BaseData
{
    public function __construct(
        public string $plainText,
        public string $hashedToken,
        public ?string $expiresAt = null,
    ) {}
}
