<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Data;

use App\Modules\Core\Data\BaseData;

final readonly class CreateLogData extends BaseData
{
    public function __construct(
        public string $studentId,
        public string $registrationId,
        /** @var array{supervisor_id: string, date?: string, topic?: string, notes?: string} */
        public array $data,
    ) {}
}
