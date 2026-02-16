<?php

declare(strict_types=1);

namespace Modules\Department\Services;

use Modules\Department\Models\Department;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Shared\Services\EloquentQuery;

/**
 * @property Department $model
 */
class DepartmentService extends EloquentQuery implements Contracts\DepartmentService
{
    /**
     * Create a new DepartmentService instance.
     */
    public function __construct(Department $model, protected SchoolService $schoolService)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'school_id']);
        $this->setSortable(['name', 'created_at']);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Department
    {
        $schoolId = $data['school_id'] ?? null;

        if ($schoolId && !$this->schoolService->exists(['id' => $schoolId])) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                __('school::exceptions.not_found'),
            );
        }

        return parent::create($data)->fresh(['school']);
    }

    /**
     * {@inheritDoc}
     */
    public function update(mixed $id, array $data): Department
    {
        $schoolId = $data['school_id'] ?? null;

        if ($schoolId && !$this->schoolService->exists(['id' => $schoolId])) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                __('school::exceptions.not_found'),
            );
        }

        return parent::update($id, $data)->fresh(['school']);
    }

    /**
     * {@inheritDoc}
     */
    public function save(array $attributes, array $values = []): Department
    {
        $schoolId = $attributes['school_id'] ?? ($values['school_id'] ?? null);

        if ($schoolId && !$this->schoolService->exists(['id' => $schoolId])) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                __('school::exceptions.not_found'),
            );
        }

        return parent::save($attributes, $values)->fresh(['school']);
    }
}
