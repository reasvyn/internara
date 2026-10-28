<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Data;

use App\Modules\Core\Data\BaseData;

final readonly class GenerateMagicLinkData extends BaseData
{
    public function __construct(
        public string $registrationId,
        public ?string $creatorId = null,
        public int $validityDays = 30,
    ) {}
}
