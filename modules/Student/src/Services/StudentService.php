<?php

declare(strict_types=1);

namespace Modules\Student\Services;

use Modules\Shared\Services\EloquentQuery;
use Modules\Student\Models\Student;
use Modules\Student\Services\Contracts\StudentService as Contract;

/**
 * Implements the business logic for student specialized data.
 */
class StudentService extends EloquentQuery implements Contract
{
    /**
     * Create a new service instance.
     */
    public function __construct(Student $model)
    {
        $this->setModel($model);
        $this->setSearchable(['national_identifier']);
    }

    /**
     * {@inheritdoc}
     */
    public function createWithDefault(array $data = []): Student
    {
        if (empty($data['national_identifier'])) {
            $data['national_identifier'] = 'PENDING-'.(string) \Illuminate\Support\Str::uuid();
        }

        return $this->create($data);
    }
}
