<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Data;

use App\Core\Data\BaseData;

final readonly class CreateSupervisionLogData extends BaseData
{
    public function __construct(
        public string $userId,
        public string $registrationId,
        /** @var array{date?: string, topic?: string, notes?: string} */
        public array $data,
    ) {}
}
