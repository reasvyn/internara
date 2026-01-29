<?php

declare(strict_types=1);

namespace Modules\Student\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @template TModel of \Modules\Student\Models\Student
 *
 * @extends EloquentQuery<TModel>
 */
interface StudentService extends EloquentQuery
{
    /**
     * Create a student record with default values.
     */
    public function createWithDefault(array $data = []): \Modules\Student\Models\Student;
}
