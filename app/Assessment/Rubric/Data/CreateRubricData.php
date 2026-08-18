<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Data;

use App\Core\Data\BaseData;

final readonly class CreateRubricData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}
}
