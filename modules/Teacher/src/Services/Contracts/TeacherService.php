<?php

declare(strict_types=1);

namespace Modules\Teacher\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Teacher\Models\Teacher
 *
 * @extends EloquentQuery<TModel>
 */
interface TeacherService extends EloquentQuery
{
    /**
     * Create a teacher record with default values.
     */
    public function createWithDefault(array $data = []): \Modules\Teacher\Models\Teacher;
}
