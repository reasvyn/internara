<?php

declare(strict_types=1);

namespace App\User\UserManagement\Data;

use App\Core\Data\BaseData;

final readonly class UpdateUserData extends BaseData
{
    public function __construct(
        public int $userId,
        public array $user,
        public ?array $profile = null,
        public ?array $roles = null,
    ) {}
}
