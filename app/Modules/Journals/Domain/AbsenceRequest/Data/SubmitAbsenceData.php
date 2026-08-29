<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Data;

use App\Modules\Core\Data\BaseData;

final readonly class SubmitAbsenceData extends BaseData
{
    public function __construct(
        public string $userId,
        public string $registrationId,
        /** @var array{start_date?: string, reason_type: string, reason_description?: string, attachment_path?: string} */
        public array $data,
    ) {}
}
