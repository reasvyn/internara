<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\Internship\Data;

use App\Modules\Core\Data\BaseData;

final readonly class InternshipData extends BaseData
{
    public function __construct(
        public string $name,
        public string $academicYearId,
        public string $startDate,
        public string $endDate,
        public ?string $description = null,
        public ?string $status = null,
        public ?string $registrationStartDate = null,
        public ?string $registrationEndDate = null,
    ) {}
}
