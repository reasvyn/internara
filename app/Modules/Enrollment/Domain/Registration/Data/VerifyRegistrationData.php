<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Data;

use App\Modules\Core\Data\BaseData;

final readonly class VerifyRegistrationData extends BaseData
{
    public function __construct(
        public string $registrationId,
        public string $placementId,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public array $mentorIds = [],
    ) {}
}
