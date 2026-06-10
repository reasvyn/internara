<?php

declare(strict_types=1);

namespace App\Academics\AcademicYear\Data;

use App\Core\Data\BaseData;

final readonly class AcademicYearData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public bool $isActive = false,
        public ?string $id = null,
    ) {}
}
