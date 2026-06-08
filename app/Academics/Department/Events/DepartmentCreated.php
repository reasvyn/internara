<?php

declare(strict_types=1);

namespace App\Academics\Department\Events;

use App\Academics\Department\Models\Department;

final readonly class DepartmentCreated
{
    public function __construct(
        public Department $department,
    ) {}
}