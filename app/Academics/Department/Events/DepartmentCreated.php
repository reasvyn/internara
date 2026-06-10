<?php

declare(strict_types=1);

namespace App\Academics\Department\Events;

use App\Academics\Department\Models\Department;
use App\Core\Events\BaseEvent;

final class DepartmentCreated extends BaseEvent
{
    public function __construct(public Department $department) {}

    public function eventName(): string
    {
        return 'department.created';
    }
}
