<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Data;

use App\Modules\Core\Data\BaseData;

final readonly class UpdateUserData extends BaseData
{
    public function __construct(
        public int $userId,
        public array $user,
        public ?array $profile = null,
        public ?array $roles = null,
    ) {}
}
