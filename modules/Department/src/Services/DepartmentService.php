<?php

declare(strict_types=1);

namespace Modules\Department\Services;

use Modules\Department\Models\Department;
use Modules\Exception\RecordNotFoundException;
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
        $this->setSearchable(['name', 'school.name']);
        $this->setSortable(['name', 'created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function query(
        array $filters = [],
        array $columns = ['*'],
        array $with = [],
    ): \Illuminate\Database\Eloquent\Builder {
        // Enforce N+1 protection for school relationship
        if (empty($with)) {
            $with = ['school'];
        }

        return parent::query($filters, $columns, $with);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Department
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Department::class);

        $data['school_id'] = $this->ensureSchoolId($data['school_id'] ?? null);

        /** @var Department */
        return parent::create($data)->loadMissing(['school']);
    }

    /**
     * {@inheritdoc}
     */
    public function update(mixed $id, array $data): Department
    {
        if (isset($data['school_id'])) {
            $this->validateSchool($data['school_id']);
        }

        /** @var Department */
        return parent::update($id, $data)->loadMissing(['school']);
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $attributes, array $values = []): Department
    {
        $schoolId = $attributes['school_id'] ?? ($values['school_id'] ?? null);

        if ($schoolId) {
            $this->validateSchool($schoolId);
        }

        /** @var Department */
        return parent::save($attributes, $values)->loadMissing(['school']);
    }

    /**
     * Ensures a valid school ID is provided, or falls back to the default school.
     */
    protected function ensureSchoolId(?string $schoolId): ?string
    {
        if ($schoolId) {
            $this->validateSchool($schoolId);

            return $schoolId;
        }

        $defaultSchool = $this->schoolService->first(['id']);

        return $defaultSchool?->id;
    }

    /**
     * Validates that the given school ID exists.
     */
    protected function validateSchool(string $schoolId): void
    {
        if (! $this->schoolService->exists(['id' => $schoolId])) {
            throw new RecordNotFoundException(
                uuid: $schoolId,
                module: 'School',
                message: 'school::exceptions.not_found'
            );
        }
    }
}
