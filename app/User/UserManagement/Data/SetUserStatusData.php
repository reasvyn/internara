<?php

declare(strict_types=1);

namespace App\User\UserManagement\Data;

use App\Core\Data\BaseData;
use App\User\Enums\AccountStatus;

final readonly class SetUserStatusData extends BaseData
{
    public function __construct(
        public string $userId,
        public AccountStatus $newStatus,
        public ?string $reason = null,
        public bool $skipAuthCheck = false,
    ) {}
}
