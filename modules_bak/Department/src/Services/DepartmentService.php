<?php

declare(strict_types=1);

namespace Modules\Department\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
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
    public function query(array $filters = [], array $columns = ['*'], array $with = []): Builder
    {
        // Enforce N+1 protection for school relationship
        if (empty($with)) {
            $with = ['school'];
        }

        return parent::query($filters, $columns, $with);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?Department
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Department $department, array $data): void
    {
        if (isset($data['school_id'])) {
            $this->validateSchool($data['school_id']);
        }

        if (!$this->skipAuthorization) {
            Gate::authorize('update', $department);
        }

        $this->skipAuthorization = false;

        $filteredData = $this->filterFillable($data);

        try {
            $department->update($filteredData);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'update_failed');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Department $department): bool
    {
        return parent::delete($department);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15): \Illuminate\Pagination\Paginator
    {
        return $this->query()->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getDropdownOptions(): array
    {
        return $this->model->newQuery()->pluck('name', 'id')->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Department
    {
        $data['school_id'] = $this->ensureSchoolId($data['school_id'] ?? null);

        /** @var Department */
        return parent::create($data)->loadMissing(['school']);
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
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        return [
            'total' => $this->count(),
            'with_internships' => $this->query()->whereHas('internships')->count(),
        ];
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
        if (!$this->schoolService->exists(['id' => $schoolId])) {
            throw new RecordNotFoundException(
                uuid: $schoolId,
                module: 'School',
                message: 'school::exceptions.not_found',
            );
        }
    }
}
