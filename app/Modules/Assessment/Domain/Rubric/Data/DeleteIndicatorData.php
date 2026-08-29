<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Data;

use App\Modules\Core\Data\BaseData;

final readonly class DeleteIndicatorData extends BaseData
{
    public function __construct(
        public string $competencyId,
        public string $indicatorId,
    ) {}
}
