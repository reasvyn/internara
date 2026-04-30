<?php

declare(strict_types=1);

namespace Modules\Assignment\Services\Contracts;

use Modules\Assignment\Models\AssignmentType;
use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<AssignmentType>
 */
interface AssignmentTypeService extends EloquentQuery
{
    /**
     * Find an assignment type by its slug.
     */
    public function findBySlug(string $slug): ?AssignmentType;
}
