<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Data;

use App\Core\Data\BaseData;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Journals\Attendance\Models\Attendance;
use App\User\Models\User;

final readonly class ProcessAbsenceData extends BaseData
{
    public function __construct(
        public Attendance $absence,
        public User $processor,
        public AbsenceRequestStatus $status,
        public ?string $notes = null,
    ) {}
}
