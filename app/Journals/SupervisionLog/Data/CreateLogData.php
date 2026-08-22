<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Data;

use App\Core\Data\BaseData;

final readonly class CreateLogData extends BaseData
{
    public function __construct(
        public string $studentId,
        public string $registrationId,
        /** @var array{supervisor_id: string, date?: string, topic?: string, notes?: string} */
        public array $data,
    ) {}
}
