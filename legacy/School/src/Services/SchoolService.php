<?php

declare(strict_types=1);

namespace Modules\School\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Exception\AppException;
use Modules\School\Events\SchoolDeleted;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService as SchoolServiceContract;
use Modules\Setup\Services\Contracts\AppSetupService;
use Modules\Shared\Services\EloquentQuery;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements the business logic for managing institutional information.
 */
class SchoolService extends EloquentQuery implements SchoolServiceContract
{
    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?School
    {
        return $this->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function update(School $school, array $data): void
    {
        if (! $this->skipAuthorization) {
            Gate::authorize('update', $school);
        }

        $this->skipAuthorization = false;

        $filteredData = $this->filterFillable($data);

        try {
            $school->update($filteredData);
            $this->handleSchoolLogo($school, $data['logo_file'] ?? null);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'update_failed');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(School $school): bool
    {
        $id = (string) $school->id;
        $deleted = parent::delete($school);

        if ($deleted) {
            SchoolDeleted::dispatch($id);
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function getDropdownOptions(): array
    {
        return $this->model->newQuery()->pluck('name', 'id')->toArray();
    }

    /**
     * Create a new school service instance.
     */
    public function __construct(School $model)
    {
        $this->setModel($model);
        $this->setSearchable(['name', 'email']);
    }

    /**
     * Retrieve schools based on conditions.
     */
    public function get(array $filters = [], array $columns = ['*'], array $with = []): Collection
    {
        return parent::get($filters, $columns, $with);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchool(array $columns = ['*']): ?School
    {
        return $this->first([], $columns);
    }

    /**
     * Create a new school record.
     */
    public function create(array $data): School
    {
        if (
            isset($data['institutional_code']) &&
            strlen((string) $data['institutional_code']) < 3
        ) {
            throw new AppException(
                userMessage: 'school::exceptions.invalid_institutional_code',
                code: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (config('school.single_record') && $this->exists()) {
            throw new AppException(
                userMessage: 'school::exceptions.single_record_exists',
                code: Response::HTTP_CONFLICT,
            );
        }

        /** @var School $school */
        $school = parent::create($data);
        $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

        return $school;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $attributes, array $values = []): School
    {
        return DB::transaction(function () use ($attributes, $values) {
            $isSetupAuthorized = session(AppSetupService::SESSION_SETUP_AUTHORIZED) === true;
            $data = array_merge($attributes, $values);
            $schoolId = $data['id'] ?? $this->model->newQuery()->first(['id'])?->id;
            unset($data['id']);

            $query = $isSetupAuthorized ? $this->withoutAuthorization()->query() : $this->query();
            $school = $query->updateOrCreate(['id' => $schoolId], $data);
            $this->handleSchoolLogo($school, $data['logo_file'] ?? null);

            return $school;
        });
    }

    /**
     * Retrieve the first school record.
     */
    public function first(array $filters = [], array $columns = ['*'], array $with = []): ?School
    {
        return parent::first($filters, $columns, $with);
    }

    /**
     * Handle institutional logo update.
     */
    protected function handleSchoolLogo(
        School &$school,
        UploadedFile|string|null $logo = null,
    ): bool {
        return isset($logo) ? $school->setLogo($logo) : false;
    }
}
