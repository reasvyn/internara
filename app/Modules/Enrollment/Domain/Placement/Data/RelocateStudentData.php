<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Placement\Data;

use App\Modules\Core\Data\BaseData;

final readonly class RelocateStudentData extends BaseData
{
    public function __construct(
        public string $registrationId,
        public string $targetPlacementId,
        public string $reason,
        public string $adminId,
    ) {}
}
