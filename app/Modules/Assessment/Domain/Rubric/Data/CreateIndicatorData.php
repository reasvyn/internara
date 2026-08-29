<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Data;

use App\Modules\Core\Data\BaseData;

final readonly class CreateIndicatorData extends BaseData
{
    public function __construct(
        public string $competencyId,
        public string $name,
        public ?string $description = null,
        public int $maxScore = 100,
        public int $weight = 0,
        public int $order = 0,
    ) {}
}
