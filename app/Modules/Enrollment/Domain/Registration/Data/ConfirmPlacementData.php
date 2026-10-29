<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Data;

use App\Modules\Core\Data\BaseData;

final readonly class ConfirmPlacementData extends BaseData
{
    /**
     * @param array<int, string> $mentorIds
     */
    public function __construct(
        public string $registrationId,
        public ?string $placementId = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public array $mentorIds = [],
        public ?string $dispatchLetterRef = null,
    ) {}
}
