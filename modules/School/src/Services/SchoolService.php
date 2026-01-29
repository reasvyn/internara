<?php

declare(strict_types=1);

namespace Modules\School\Services;

use Illuminate\Database\Eloquent\Model;
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
        if (config('school.single_record') && $this->exists()) {
            throw new AppException(
                userMessage: 'school::exceptions.single_record_exists',
                code: 409, // Conflict
            );
        }

        /** @var School $school */
        $school = parent::create($data);
        $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

        return $school;
    }

    public function update(mixed $id, array $data, array $columns = ['*']): School
    {
        /** @var School $school */
        $school = parent::update($id, $data);
        $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

        return $school;
    }

    public function save(array $attributes, array $values = []): School
    {
        $school = parent::save($attributes, $values);
        $allData = array_merge($attributes, $values);
        $this->handleSchoolLogo($school, $allData['logo_file'] ?? null);

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
