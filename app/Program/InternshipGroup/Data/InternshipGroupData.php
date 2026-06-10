<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Data;

use App\Core\Data\BaseData;

final readonly class InternshipGroupData extends BaseData
{
    public function __construct(
        public string $internshipId,
        public string $name,
        public ?string $placementId = null,
        public ?bool $isActive = null,
    ) {}
}
