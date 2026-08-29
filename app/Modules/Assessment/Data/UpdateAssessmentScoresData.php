<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Data;

use App\Modules\Core\Data\BaseData;

final readonly class UpdateAssessmentScoresData extends BaseData
{
    public function __construct(
        public string $competencyId,
        public string $indicatorId,
        public ?float $score,
    ) {}
}
