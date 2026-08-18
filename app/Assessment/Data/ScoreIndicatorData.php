<?php

declare(strict_types=1);

namespace App\Assessment\Data;

use App\Core\Data\BaseData;

final readonly class ScoreIndicatorData extends BaseData
{
    public function __construct(
        public string $competencyId,
        public string $indicatorId,
        public float $score,
    ) {}
}
