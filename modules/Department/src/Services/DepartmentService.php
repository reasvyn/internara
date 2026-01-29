<?php

declare(strict_types=1);

namespace Modules\Department\Services;

use Modules\Department\Models\Department;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Shared\Services\EloquentQuery;

class DepartmentService extends EloquentQuery implements Contracts\DepartmentService
{
    public function __construct(Department $model, protected SchoolService $schoolService)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'school_id']);
    }

    public function create(array $data): Department
    {
        $schoolId = $data['school_id'] ?? null;
        unset($data['school_id']);

        /** @var Department $department */
        $department = parent::create($data);
        $department->changeSchoolId($schoolId);

        return $department->fresh(['school']);
    }

    public function update(mixed $id, array $data): Department
    {
        $schoolId = $data['school_id'] ?? null;
        unset($data['school_id']);

        /** @var Department $department */
        $department = parent::update($id, $data);
        $department->changeSchoolId($schoolId);

        return $department->fresh(['school']);
    }

    public function save(array $attributes, array $values = []): Department
    {
        $schoolId = $attributes['school_id'] ?? null;
        unset($attributes['school_id']);

        /** @var Department $department */
        $department = parent::save($attributes, $values);
        $department->changeSchoolId($schoolId);

        return $department->fresh(['school']);
    }
}
