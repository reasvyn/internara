<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Data;

use App\Core\Data\BaseData;

final readonly class ClockInData extends BaseData
{
    public function __construct(
        public string $userId,
        /** @var array{latitude?: float, longitude?: float} */
        public array $data = [],
        public ?string $requestIp = null,
    ) {}
}
