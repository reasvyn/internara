<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Data;

use App\Modules\Core\Data\BaseData;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;

final readonly class ProcessAbsenceData extends BaseData
{
    public function __construct(
        public string $absenceId,
        public string $processorId,
        public AbsenceRequestStatus $status,
        public ?string $notes = null,
    ) {}
}
