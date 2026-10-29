<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Data;

use App\Modules\Core\Data\BaseData;

final readonly class PlacementPreferenceData extends BaseData
{
    public function __construct(
        public ?string $placementId = null,
        public ?string $proposedCompanyName = null,
        public ?string $proposedCompanyAddress = null,
    ) {}
}
