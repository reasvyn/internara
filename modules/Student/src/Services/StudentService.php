<?php

declare(strict_types=1);

namespace Modules\Student\Services;

use Modules\Shared\Services\EloquentQuery;
use Modules\Student\Models\Student;
use Modules\Student\Services\Contracts\StudentService as Contract;

class StudentService extends EloquentQuery implements Contract
{
    public function __construct(Student $model)
    {
        $this->setModel($model);
        $this->setSearchable(['nisn']);
    }

    /**
     * {@inheritdoc}
     */
    public function createWithDefault(array $data = []): Student
    {
        if (empty($data['nisn'])) {
            $data['nisn'] = 'PENDING-'.(string) \Illuminate\Support\Str::uuid();
        }

        return $this->create($data);
    }
}
