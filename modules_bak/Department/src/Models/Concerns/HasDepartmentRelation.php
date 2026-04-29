<?php

declare(strict_types=1);

namespace Modules\Department\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Department\Services\Contracts\DepartmentService;

trait HasDepartmentRelation
{
    /**
     * Get the department associated with the model.
     */
    public function department(): BelongsTo
    {
        /** @var DepartmentService $departmentService */
        $departmentService = app(DepartmentService::class);

        return $departmentService->defineBelongsTo($this, 'department_id');
    }
}
