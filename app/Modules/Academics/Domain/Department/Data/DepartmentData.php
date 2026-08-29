<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\Department\Data;

use App\Modules\Core\Data\BaseData;

final readonly class DepartmentData extends BaseData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $id = null,
    ) {}
}
