<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Data;

use App\Modules\Core\Data\BaseData;

final readonly class UpdateRubricData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}
}
