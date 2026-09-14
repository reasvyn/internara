<?php

declare(strict_types=1);

namespace App\Modules\Academic\Domain\AcademicYear\Data;

use App\Modules\Core\Data\BaseData;

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
