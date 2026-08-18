<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Data;

use App\Core\Data\BaseData;

final readonly class CreateCompetencyData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public int $weight = 0,
        public string $evaluatorRole = 'teacher',
        public int $order = 0,
    ) {}
}
