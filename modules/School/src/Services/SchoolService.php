<?php

declare(strict_types=1);

namespace Modules\School\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Modules\Exception\AppException;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService as SchoolServiceContract;
use Modules\Shared\Services\EloquentQuery;

class SchoolService extends EloquentQuery implements SchoolServiceContract
{
    /**
     * SchoolService constructor.
     */
    public function __construct(School $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'email']);
    }

    /**
     * Retrieve schools based on conditions.
     * Returns a single School model if configured as single record, or a Collection otherwise.
     *
     * @param array<string, mixed> $where Conditions to filter the query.
     * @param array<int, string> $columns Columns to retrieve.
     *
     * @return School|\Illuminate\Support\Collection The found school or a collection of schools.
     */
    public function get(array $filters = [], array $columns = ['*']): \Illuminate\Support\Collection
    {
        return parent::get($filters, $columns);
    }

    public function getSchool(array $columns = ['*']): ?School
    {
        return $this->first([], $columns);
    }

    /**
     * List schools with optional filtering and pagination.
     *
     * @param array<string, mixed> $filters Filter criteria (e.g., 'search', 'sort').
     * @param int $perPage Number of records per page.
     * @param array<int, string> $columns Columns to retrieve.
     *
     * @return LengthAwarePaginator Paginated list of schools.
     */
    public function list(
        array $filters = [],
        int $perPage = 10,
        array $columns = ['*'],
    ): LengthAwarePaginator {
        return $this->model
            ->query()
            ->select($columns)
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere(
                        'email',
                        'like',
                        "%{$search}%",
                    );
                });
            })
            ->when(
                $filters['sort'] ?? null,
                function (Builder $query, string $sort) {
                    $query->orderBy($sort, $filters['direction'] ?? 'asc');
                },
                function (Builder $query) {
                    $query->latest();
                },
            )
            ->paginate($perPage);
    }

    /**
     * Create a new school, respecting the single-record configuration.
     *
     * @param array<string, mixed> $data The data for creating the school.
     *
     * @throws AppException If a school already exists and the system is in single-record mode.
     *
     * @return School The newly created school.
     */
    public function create(array $data): School
    {
        try {
            if (config('school.single_record') && $this->model->exists()) {
                throw new AppException(
                    userMessage: 'school::exceptions.single_record_exists',
                    code: 409, // Conflict
                );
            }

            $school = parent::create($data); // Updated call
            $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

            return $school;
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                // Duplicate entry
                throw new AppException(
                    userMessage: 'records::exceptions.unique_violation',
                    replace: ['record' => $this->recordName],
                    logMessage: 'Attempted to create school with duplicate unique field.',
                    code: 409,
                    previous: $e,
                );
            }
            throw new AppException(
                userMessage: 'records::exceptions.creation_failed',
                replace: ['record' => $this->recordName],
                logMessage: 'School creation failed: '.$e->getMessage(),
                code: 500,
                previous: $e,
            );
        }
    }

    public function update(mixed $id, array $data, array $columns = ['*']): School
    {
        /** @var School $school */
        $school = parent::update($id, $data, $columns); // Updated call
        $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

        return $school;
    }

    public function save(array $attributes, array $values = []): School
    {
        $school = parent::save($attributes, $values);
        $allData = array_merge($attributes, $values);
        $this->handleSchoolLogo($school, $allData['file_logo'] ?? null);

        return $school;
    }

    /**
     * Retrieve the first school record.
     *
     * @param array<int, string> $columns
     */
    public function first(array $filters = [], array $columns = ['*']): ?School
    {
        return parent::first($filters, $columns);
    }

    protected function handleSchoolLogo(
        School &$school,
        UploadedFile|string|null $logo = null,
    ): bool {
        return isset($logo) ? $school->setLogo($logo) : false;
    }
}
