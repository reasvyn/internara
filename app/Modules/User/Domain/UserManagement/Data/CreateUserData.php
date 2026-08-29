<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Data;

use App\Modules\Core\Data\BaseData;

final readonly class CreateUserData extends BaseData
{
    public function __construct(
        public array $user,
        public array $profile = [],
        public array $roles = [],
        public bool $sendNotification = true,
    ) {}
}
