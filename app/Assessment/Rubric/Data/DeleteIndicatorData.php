<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Data;

use App\Core\Data\BaseData;

final readonly class DeleteIndicatorData extends BaseData
{
    public function __construct(
        public string $competencyId,
        public string $indicatorId,
    ) {}
}
