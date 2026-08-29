<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Data;

use App\Modules\Core\Data\BaseData;
use App\Modules\User\Enums\AccountStatus;

final readonly class SetUserStatusData extends BaseData
{
    public function __construct(
        public string $userId,
        public AccountStatus $newStatus,
        public ?string $reason = null,
        public bool $skipAuthCheck = false,
    ) {}
}
