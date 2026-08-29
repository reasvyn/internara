<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Data;

use App\Modules\Core\Data\BaseData;

final readonly class RegistrationData extends BaseData
{
    public function __construct(
        public string $internshipId,
        public ?string $placementId = null,
        public ?string $academicYear = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $proposedCompanyName = null,
        public ?string $proposedCompanyAddress = null,
    ) {}
}
