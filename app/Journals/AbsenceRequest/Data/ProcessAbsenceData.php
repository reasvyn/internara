<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Data;

use App\Core\Data\BaseData;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;

final readonly class ProcessAbsenceData extends BaseData
{
    public function __construct(
        public string $absenceId,
        public string $processorId,
        public AbsenceRequestStatus $status,
        public ?string $notes = null,
    ) {}
}
