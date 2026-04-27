<?php

declare(strict_types=1);

namespace Modules\Department\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Department\Models\Department
 *
 * @extends EloquentQuery<TModel>
 */
interface DepartmentService extends EloquentQuery
{
    /**
     * Get summary metrics for departments.
     *
     * @return array<string, int>
     */
    public function getStats(): array;
}
