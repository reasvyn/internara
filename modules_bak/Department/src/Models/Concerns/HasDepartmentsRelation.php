<?php

declare(strict_types=1);

namespace Modules\Department\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Department\Services\Contracts\DepartmentService;

trait HasDepartmentsRelation
{
    /**
     * Get the departments for the school.
     */
    public function departments(): HasMany
    {
        /** @var DepartmentService $departmentService */
        $departmentService = app(DepartmentService::class);

        return $departmentService->defineHasMany($this);
    }
}
