<?php

declare(strict_types=1);

namespace App\Academics\Department\Data;

use App\Core\Data\BaseData;

final readonly class DepartmentData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $id = null,
    ) {}
}
