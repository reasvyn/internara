<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\Department\Events;

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Events\BaseEvent;

final class DepartmentCreated extends BaseEvent
{
    public function __construct(public Department $department) {}

    public function eventName(): string
    {
        return 'department.created';
    }
}
