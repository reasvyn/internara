<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Data;

use App\Core\Data\BaseData;
use App\User\Models\User;

final readonly class ClockOutData extends BaseData
{
    public function __construct(
        public User $user,
        /** @var array{latitude?: float, longitude?: float} */
        public array $data,
        public ?string $requestIp = null,
    ) {}
}
